<?php declare(strict_types = 1);

namespace Modules\HostConfig\Actions;

use CControllerResponseData;
use CWebUser;
use API;

class GetGroupROView extends \CController
{
    public function init(): void
    {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool
    {
        return true;
    }

    protected function checkPermissions(): bool
    {
        $permit_user_types = [USER_TYPE_ZABBIX_USER, USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN];
        return in_array($this->getUserType(), $permit_user_types, true);
    }

    private function formatInventory(array $inv): array
    {
        $labels = [
            'type'      => 'Environment',
            'type_full' => 'Product',
            'name'      => 'Host Name',
            'os'        => 'OS',
            'contact'   => 'Customer'
        ];

        $order = ['name', 'contact', 'type_full', 'type', 'os'];

        $out = [];
        foreach ($order as $key) {
            if (isset($inv[$key])) {
                $out[$labels[$key]] = $inv[$key];
            }
        }
        return $out;
    }

    protected function doAction(): void
    {
        $export = $_REQUEST['export'] ?? null;
        $groupids_raw = $_REQUEST['groupids'] ?? $_REQUEST['groupid'] ?? null;
        $hostid = $_REQUEST['hostid'] ?? null;

        if ($export !== null && $groupids_raw !== null) {
            // Parse groupids (can be single or comma-separated)
            $groupids = [];
            if (is_array($groupids_raw)) {
                $groupids = array_map('intval', $groupids_raw);
            } else {
                $groupids = array_map('intval', explode(',', $groupids_raw));
            }
            $groupids = array_filter(array_unique($groupids));

            if (empty($groupids)) {
                header('HTTP/1.1 404 Not Found');
                echo 'No hostgroups selected';
                exit;
            }

            // Determine which hosts to export
            if ($hostid !== null && !empty($hostid)) {
                // Single host export
                $hostsToExport = [$hostid];
            } else {
                // All hosts in selected hostgroups (with deduplication)
                $hosts = API::Host()->get([
                    'groupids' => $groupids,
                    'output' => ['hostid'],
                ]);

                // Deduplicate hosts
                $uniqueHostIds = [];
                foreach($hosts as $h) {
                    $uniqueHostIds[$h['hostid']] = true;
                }
                $hostsToExport = array_keys($uniqueHostIds);
            }

            if (empty($hostsToExport)) {
                header('HTTP/1.1 404 Not Found');
                echo 'No hosts found in the selected hostgroup(s)';
                exit;
            }

            // Get severity colors
            $severities = API::Settings()->get([
                'output' => ['severity_color_0','severity_color_1','severity_color_2','severity_color_3','severity_color_4','severity_color_5']
            ]);

            // Fetch data for all hosts
            $allHostsData = [];

            foreach ($hostsToExport as $currentHostId) {
                $hostInfo = API::Host()->get([
                    'hostids' => $currentHostId,
                    'output' => ['hostid', 'host', 'name', 'status', 'description', 'maintenance_status'],
                    'selectInventory' => ['type_full', 'name', 'os', 'os_short', 'contact', 'type'],
                    'selectHostGroups' => ['groupid', 'name'],
                    'selectInterfaces' => ['interfaceid', 'type', 'ip', 'dns', 'port'],
                    'selectTags' => ['tag', 'value', 'automatic'],
                    'selectParentTemplates' => ['templateid', 'name'],
                    'selectMacros' => ['hostmacroid','macro','value','description']
                ]);

                if (empty($hostInfo)) continue;

                $itemsInfo = API::Item()->get([
                    'hostids' => $currentHostId,
                    'webitems' => 1,
                    'templated' => null,
                    'preservekeys' => 0,
                    'output' => ['itemid', 'name', 'key_', 'delay', 'history', 'trends', 'status', 'state', 'description'],
                    'sortfield' => 'name'
                ]);

                $triggers = API::Trigger()->get([
                    'output' => ['triggerid', 'description', 'expression', 'priority', 'status', 'state'],
                    'filter' => ['hostid' => $currentHostId],
                    'expandExpression' => true,
                    'expandDescription' => true,
                    'sortfield' => 'description'
                ]);

                $allHostsData[] = [
                    'hostInfo'  => $hostInfo[0],
                    'items'     => $itemsInfo,
                    'triggers'  => $triggers
                ];
            }

            switch ($export) {
                case 'csv':
                    $this->exportToCSV($allHostsData, $severities);
                    return;
                case 'html':
                    $this->exportToHTML($allHostsData, $severities);
                    return;
                case 'json':
                    $this->exportToJSON($allHostsData);
                    return;
            }
        }

        $data = [];
        $response = new CControllerResponseData($data);
        $this->setResponse($response);
    }

    /**
     * CSV EXPORT - 3 Sections
     */
    private function exportToCSV(array $allHostsData, array $severities): void
    {
        if (empty($allHostsData)) {
            header('HTTP/1.1 404 Not Found');
            echo 'No hosts found';
            exit;
        }

        $filename = 'hostgroup_export_' . date('Y-m-d_Hi') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $fp = fopen('php://output', 'w');

        // SHEET 1: GENERAL INFO, TAGS, MACROS, INVENTORY
        fputcsv($fp, ['SHEET 1: GENERAL INFORMATION']);
        fputcsv($fp, []);
        fputcsv($fp, ['Export Date', date('Y-m-d H:i:s')]);
        fputcsv($fp, ['Total Hosts', count($allHostsData)]);
        fputcsv($fp, []);

        fputcsv($fp, ['GENERAL INFORMATION - ALL HOSTS']);
        fputcsv($fp, ['Host Name', 'Display Name', 'Status', 'Maintenance Status', 'Description', 'Interfaces', 'Groups', 'Templates']);

        foreach ($allHostsData as $hostData) {
            $host = $hostData['hostInfo'];

            $interfaces = [];
            foreach($host['interfaces'] as $iface){
                $if_text = '';
                if($iface['ip']) $if_text .= $iface['ip'];
                if($iface['ip'] && $iface['dns']) $if_text .= ' / ';
                if($iface['dns']) $if_text .= $iface['dns'];
                if($if_text) $interfaces[] = $if_text;
            }

            $groups = array_map(function($g){ return $g['name']; }, $host['hostgroups']);
            $templates = array_map(function($t){ return $t['name']; }, $host['parentTemplates']);

            fputcsv($fp, [
                $host['host'],
                $host['name'],
                $host['status'] == 0 ? 'Monitored' : 'Not Monitored',
                $host['maintenance_status'] == 0 ? 'Not Under Maintenance' : 'Under Maintenance',
                $host['description'] ?? '',
                implode(', ', $interfaces),
                implode(', ', $groups),
                implode(', ', $templates)
            ]);
        }

        fputcsv($fp, []);
        fputcsv($fp, []);

        // Tags
        fputcsv($fp, ['TAGS']);
        fputcsv($fp, ['Host Name', 'Tag', 'Value']);
        foreach ($allHostsData as $hostData) {
            $host = $hostData['hostInfo'];
            if(!empty($host['tags'])){
                foreach($host['tags'] as $tag){
                    fputcsv($fp, [$host['host'], $tag['tag'], $tag['value']]);
                }
            } else {
                fputcsv($fp, [$host['host'], '(no tags)', '']);
            }
        }

        fputcsv($fp, []);
        fputcsv($fp, []);

        // Macros
        fputcsv($fp, ['MACROS']);
        fputcsv($fp, ['Host Name', 'Macro', 'Value', 'Description']);
        foreach ($allHostsData as $hostData) {
            $host = $hostData['hostInfo'];
            if(!empty($host['macros'])){
                foreach($host['macros'] as $macro){
                    fputcsv($fp, [
                        $host['host'], 
                        $macro['macro'], 
                        $macro['value'] ?? '***', 
                        $macro['description'] ?? ''
                    ]);
                }
            } else {
                fputcsv($fp, [$host['host'], '(no macros)', '', '']);
            }
        }

        fputcsv($fp, []);
        fputcsv($fp, []);

        // Inventory
        fputcsv($fp, ['INVENTORY']);
        fputcsv($fp, ['Host Name', 'Host Name (Inv)', 'Customer', 'Product', 'Environment', 'OS']);
        foreach ($allHostsData as $hostData) {
            $host = $hostData['hostInfo'];
            $inv = $host['inventory'] ?? [];
            $invFormatted = $this->formatInventory($inv);

            fputcsv($fp, [
                $host['host'],
                $invFormatted['Host Name'] ?? '',
                $invFormatted['Customer'] ?? '',
                $invFormatted['Product'] ?? '',
                $invFormatted['Environment'] ?? '',
                $invFormatted['OS'] ?? ''
            ]);
        }

        fputcsv($fp, []);
        fputcsv($fp, []);
        fputcsv($fp, []);

        // SHEET 2: ITEMS
        fputcsv($fp, ['SHEET 2: ITEMS']);
        fputcsv($fp, []);
        fputcsv($fp, ['Host Name', 'Name', 'Key', 'Interval', 'History', 'Trends', 'Description', 'Status', 'State']);

        foreach ($allHostsData as $hostData) {
            $host = $hostData['hostInfo'];
            $items = $hostData['items'];

            foreach ($items as $item) {
                fputcsv($fp, [
                    $host['host'],
                    $item['name'],
                    $item['key_'],
                    $item['delay'],
                    $item['history'],
                    $item['trends'],
                    $item['description'] ?? '',
                    $item['status'] == 0 ? 'Enabled' : 'Disabled',
                    $item['state'] == 0 ? 'Normal' : 'Not supported'
                ]);
            }
        }

        fputcsv($fp, []);
        fputcsv($fp, []);
        fputcsv($fp, []);

        // SHEET 3: TRIGGERS
        fputcsv($fp, ['SHEET 3: TRIGGERS']);
        fputcsv($fp, []);
        fputcsv($fp, ['Host Name', 'Trigger Name', 'Severity', 'Expression', 'Status', 'State']);

        foreach ($allHostsData as $hostData) {
            $host = $hostData['hostInfo'];
            $triggers = $hostData['triggers'];

            foreach ($triggers as $tr) {
                fputcsv($fp, [
                    $host['host'],
                    $tr['description'],
                    $this->getPriorityName((int)$tr['priority']),
                    $tr['expression'],
                    $tr['status'] == 0 ? 'Enabled' : 'Disabled',
                    $tr['state'] == 0 ? 'Normal' : 'Not supported'
                ]);
            }
        }

        fclose($fp);
        exit;
    }

    /**
     * HTML EXPORT
     */
    private function exportToHTML(array $allHostsData, array $severities): void
    {
        if (empty($allHostsData)) {
            header('HTTP/1.1 404 Not Found');
            echo 'No hosts found';
            exit;
        }

        $filename = 'hostgroup_export_' . date('Y-m-d_Hi') . '.html';
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HostGroup Configuration Export</title>
    <style>
        body {
            font-family: 'Trebuchet MS', Tahoma, Arial, sans-serif;
            font-size: 13px;
            background: #f5f5f5;
            margin: 0;
        }
        .page {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 20px 25px;
        }
        .header {
            background: #007bff;
            color: #fff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 22px;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section h2 {
            margin: 0 0 10px 0;
            font-size: 16px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 6px 8px;
            border-bottom: 1px solid #e4e4e4;
            text-align: left;
        }
        th {
            background: #f0f0f0;
        }
        tr:nth-child(even) td {
            background: #fafafa;
        }
        .kv-table td:first-child {
            width: 160px;
            font-weight: 600;
            background: #f9f9f9;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            color: #fff;
        }
        .prio-0 { background: #999; }
        .prio-1 { background: #5bc0de; }
        .prio-2 { background: #5cb85c; }
        .prio-3 { background: #f0ad4e; }
        .prio-4 { background: #d9534f; }
        .prio-5 { background: #b52b2b; }
        .status-enabled {
            color: #28a745;
            font-weight: 600;
        }
        .status-disabled {
            color: #dc3545;
            font-weight: 600;
        }
        code {
            font-size: 11px;
        }
        @media print {
            body { background: #fff; }
            .page { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>HostGroup Configuration Export</h1>
            <div>Exported: <?php echo date('Y-m-d H:i:s'); ?> | Total Hosts: <?php echo count($allHostsData); ?></div>
        </div>

<?php foreach ($allHostsData as $index => $hostData): 
    $host = $hostData['hostInfo'];
    $items = $hostData['items'];
    $triggers = $hostData['triggers'];
    $inv = $host['inventory'] ?? [];
    $invFormatted = $this->formatInventory($inv);
    $statusText = $host['status'] == 0 ? 'Monitored' : 'Not Monitored';
    $maintText = $host['maintenance_status'] == 0 ? 'Not Under Maintenance' : 'Under Maintenance';
?>
        <div class="section">
            <h2>Host #<?php echo ($index + 1); ?>: <?php echo htmlspecialchars($host['host']); ?></h2>
            <table class="kv-table">
                <tr><td>Hostname</td><td><?php echo htmlspecialchars($host['host']); ?></td></tr>
                <tr><td>Display name</td><td><?php echo htmlspecialchars($host['name']); ?></td></tr>
                <tr><td>Status</td><td><?php echo htmlspecialchars($statusText); ?></td></tr>
                <tr><td>Maintenance status</td><td><?php echo htmlspecialchars($maintText); ?></td></tr>
                <tr><td>Description</td><td><?php echo htmlspecialchars($host['description'] ?? ''); ?></td></tr>
            </table>
        </div>

        <div class="section">
            <h2>Inventory</h2>
            <table class="kv-table">
                <tr><td>Host Name</td><td><?php echo htmlspecialchars($inv['name'] ?? ''); ?></td></tr>
                <tr><td>Customer</td><td><?php echo htmlspecialchars($inv['contact'] ?? ''); ?></td></tr>
                <tr><td>Product</td><td><?php echo htmlspecialchars($inv['type_full'] ?? ''); ?></td></tr>
                <tr><td>Environment</td><td><?php echo htmlspecialchars($inv['type'] ?? ''); ?></td></tr>
                <tr><td>OS</td><td><?php echo htmlspecialchars($inv['os'] ?? ''); ?></td></tr>
            </table>
        </div>

        <div class="section">
            <h2>Host Tags</h2>
            <table class="kv-table">
                <tr><th>Tag</th><th>Value</th></tr>
                <?php foreach ($host['tags'] ?? [] as $tag): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tag['tag']); ?></td>
                    <td><?php echo htmlspecialchars($tag['value']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="section">
            <h2>Macros</h2>
            <table class="kv-table">
                <tr><th>Name</th><th>Value</th><th>Description</th></tr>
                <?php foreach ($host['macros'] ?? [] as $macro): ?>
                <tr>
                    <td><?php echo htmlspecialchars($macro['macro']); ?></td>
                    <td><?php echo isset($macro['value']) ? htmlspecialchars($macro['value']) : '***'; ?></td>
                    <td><?php echo htmlspecialchars($macro['description'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="section">
            <h2>Items (<?php echo count($items); ?>)</h2>
            <table>
                <thead>
                    <tr><th>Name</th><th>Key</th><th>Interval</th><th>History</th>
                        <th>Trends</th><th>Description</th><th>Status</th><th>State</th></tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><code><?php echo htmlspecialchars($item['key_']); ?></code></td>
                    <td><?php echo htmlspecialchars($item['delay']); ?></td>
                    <td><?php echo htmlspecialchars($item['history']); ?></td>
                    <td><?php echo htmlspecialchars($item['trends']); ?></td>
                    <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                    <td class="<?php echo $item['status'] == 0 ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $item['status'] == 0 ? 'Enabled' : 'Disabled'; ?>
                    </td>
                    <td><?php echo $item['state'] == 0 ? 'Normal' : 'Not supported'; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Triggers (<?php echo count($triggers); ?>)</h2>
            <table>
                <thead>
                    <tr><th>Trigger Name</th><th>Severity</th><th>Expression</th><th>Status</th><th>State</th></tr>
                </thead>
                <tbody>
                <?php foreach ($triggers as $tr): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tr['description']); ?></td>
                    <td>
                        <span class="badge prio-<?php echo (int)$tr['priority']; ?>">
                            <?php echo htmlspecialchars($this->getPriorityName((int)$tr['priority'])); ?>
                        </span>
                    </td>
                    <td><code><?php echo htmlspecialchars($tr['expression']); ?></code></td>
                    <td class="<?php echo $tr['status'] == 0 ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $tr['status'] == 0 ? 'Enabled' : 'Disabled'; ?>
                    </td>
                    <td><?php echo $tr['state'] == 0 ? 'Normal' : 'Not supported'; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <hr style="margin: 30px 0;">

<?php endforeach; ?>

    </div>
</body>
</html>
        <?php
        $html = ob_get_clean();
        echo $html;
        exit;
    }

    /**
     * JSON EXPORT
     */
    private function exportToJSON(array $allHostsData): void
    {
        if (empty($allHostsData)) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'No hosts found']);
            exit;
        }

        $filename = 'hostgroup_export_' . date('Y-m-d_Hi') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $exportData = [
            'export_info' => [
                'exported_at' => date('Y-m-d H:i:s'),
                'total_hosts' => count($allHostsData)
            ],
            'hosts' => []
        ];

        foreach ($allHostsData as $hostData) {
            $host = $hostData['hostInfo'];
            $inv = $host['inventory'] ?? [];
            $invFormatted = $this->formatInventory($inv);

            $exportData['hosts'][] = [
                'general_information' => [
                    'hostname'           => $host['host'],
                    'display_name'       => $host['name'],
                    'status'             => $host['status'] == 0 ? 'Monitored' : 'Not Monitored',
                    'maintenance_status' => $host['maintenance_status'] == 0 ? 'Not Under Maintenance' : 'Under Maintenance',
                    'description'        => $host['description'] ?? ''
                ],
                'inventory' => $invFormatted,
                'tags'      => $host['tags'] ?? [],
                'macros'    => $host['macros'] ?? [],
                'items'     => array_map(function($item) {
                    return [
                        'name'        => $item['name'],
                        'key'         => $item['key_'],
                        'interval'    => $item['delay'],
                        'history'     => $item['history'],
                        'trends'      => $item['trends'],
                        'description' => $item['description'] ?? '',
                        'status'      => $item['status'] == 0 ? 'Enabled' : 'Disabled',
                        'state'       => $item['state'] == 0 ? 'Normal' : 'Not supported'
                    ];
                }, $hostData['items']),
                'triggers' => array_map(function($tr) {
                    return [
                        'name'       => $tr['description'],
                        'severity'   => $this->getPriorityName((int)$tr['priority']),
                        'expression' => $tr['expression'],
                        'status'     => $tr['status'] == 0 ? 'Enabled' : 'Disabled',
                        'state'      => $tr['state'] == 0 ? 'Normal' : 'Not supported'
                    ];
                }, $hostData['triggers'])
            ];
        }

        echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function getPriorityName(int $priority): string
    {
        $map = [
            0 => 'Not classified',
            1 => 'Information',
            2 => 'Low',
            3 => 'Medium',
            4 => 'High',
            5 => 'Critical'
        ];
        return $map[$priority] ?? 'Unknown';
    }
}
