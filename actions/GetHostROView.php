<?php declare(strict_types = 1);

namespace Modules\HostConfig\Actions;

use CController as CAction;
use CControllerResponseData;
use API;

/**
 * Read‑only host config view.
 */
class GetHostROView extends CAction {

    public function init(): void {
        // Read‑only, so CSRF not needed.
        $this->disableCsrfValidation();
    }

    /**
     * Basic input check – allow all for now.
     */
    protected function checkInput(): bool {
        return true;
    }

    /**
     * Allow all regular Zabbix users (adjust if needed).
     */
    protected function checkPermissions(): bool {
        $permit_user_types = [USER_TYPE_ZABBIX_USER, USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN];

        return in_array($this->getUserType(), $permit_user_types, true);
    }

    /**
     * Main controller logic.
         ****** Re-map & reorder inventory for all export formats
     */
    private function formatInventory(array $inv): array {
        $labels = [
            'type'       => 'Environment',
            'type_full'  => 'Product',
            'name'       => 'Host Name',
            'os'         => 'OS',
            'contact'    => 'Customer'
        ];

        $order = [
            'name',
            'contact',
            'type_full',
            'type',
            'os',
        ];

        $out = [];

        foreach ($order as $key) {
            if (isset($inv[$key])) {
                $out[$labels[$key]] = $inv[$key];
            }
        }

        return $out;
    }

    protected function doAction(): void {
        // Read raw request parameters.
        $export = $_REQUEST['export'] ?? null;
        $hostid = $_REQUEST['hostid'] ?? null;

        if ($export !== null && $hostid !== null) {
            
            // -------------------------------
            // HOST INFO
            // -------------------------------	
            $hostInfo = API::Host()->get([
                'hostids'           => $hostid,
                'output'            => ['hostid', 'host', 'name', 'status', 'description', 'maintenance_status'],
                'selectInventory'   => ['type_full', 'name', 'os', 'os_short', 'contact'],
                'selectHostGroups'  => ['groupid', 'name'],
                'selectInterfaces'  => ['interfaceid', 'type', 'ip', 'dns', 'port'],
                'selectTags'        => ['tag', 'value', 'automatic'],
                'selectParentTemplates' => ['templateid', 'name']
            ]);

            // -------------------------------
            // ITEMS
            // -------------------------------
            $itemsInfo = API::Item()->get([
                'hostids'   => $hostid,
                'webitems'  => 1,   // include web scenario items like in your viewhost.php
                'templated' => null,
                'preservekeys' => 0,
                'output'    => ['itemid', 'name', 'key_', 'delay', 'history', 'trends', 'status', 'state', 'description'],
                'sortfield' => 'name'
            ]);

            // -------------------------------
            // TRIGGERS
            // -------------------------------
            $triggers = API::Trigger()->get([
                'output'            => ['triggerid', 'description', 'expression', 'priority', 'status', 'state'],
                'filter'            => ['hostid' => $hostid],
                'expandExpression'  => true,
                'expandDescription' => true,
                'sortfield'         => 'description'
            ]);

            switch ($export) {
                case 'csv':
                    $this->exportToCSV($hostInfo, $itemsInfo, $triggers);
                    return;

                case 'html':
                    $this->exportToHTML($hostInfo, $itemsInfo, $triggers);
                    return;

                case 'json':
                    $this->exportToJSON($hostInfo, $itemsInfo, $triggers);
                    return;
            }
            // Unknown format → fall through.
        }

        // Normal view.
        $data = [];
        $response = new CControllerResponseData($data);
        $this->setResponse($response);
    }
   
    /**
     * -------------------------------------------------
     * CSV EXPORT
     * -------------------------------------------------
     */
    private function exportToCSV(array $hostInfo, array $itemsInfo, array $triggers): void {
        if (empty($hostInfo)) {
            header('HTTP/1.1 404 Not Found');
            echo 'Host not found';
            exit;
        }

        $host = $hostInfo[0];
                $inv = $host['inventory'] ?? [];
        $invFormatted = $this->formatInventory($inv);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=host_' . $host['host'] . '_' . date('Y-m-d_Hi') . '.csv');

        $fp = fopen('php://output', 'w');

        // “Sheet” 1: Host info.
        fputcsv($fp, ['HOST CONFIG EXPORT']);
        fputcsv($fp, ['Exported at', date('Y-m-d H:i:s')]);
        fputcsv($fp, []);

        fputcsv($fp, ['GENERAL INFORMATION']);
        fputcsv($fp, ['Hostname', $host['host']]);
        fputcsv($fp, ['Display name', $host['name']]);
        fputcsv($fp, ['Status', $host['status'] == 0 ? 'Monitored' : 'Not Monitored']);
        fputcsv($fp, ['Maintenance status', $host['maintenance_status'] == 0 ? 'Not Under Maintenance' : 'Under Maintenance']);
        fputcsv($fp, ['Description', $host['description'] ?? '']);
        fputcsv($fp, []);

        // Tags
        fputcsv($fp, []);
        fputcsv($fp, ['TAGS']);
        fputcsv($fp, ['Tag', 'Value']);
              if (!empty($host['tags'])) {
                   foreach ($host['tags'] as $t) {
         fputcsv($fp, [$t['tag'], $t['value']]);
                    }
            } else {
         fputcsv($fp, ['(none)', '']);
                }
         fputcsv($fp, []);
        
        // Inventory
        $inv = $host['inventory'] ?? [];
        fputcsv($fp, ['INVENTORY']);
        foreach ($invFormatted as $label => $value) {
            fputcsv($fp, [$label, $value]);
        }
        fputcsv($fp, []);
        fputcsv($fp, []); // spacer between sheets.

        // “Sheet” 2: Items – Name|Key|Interval|History|Trends|Description|Status|State
        fputcsv($fp, ['ITEMS']);
        fputcsv($fp, ['Name', 'Key', 'Interval', 'History', 'Trends', 'Description', 'Status', 'State']);

        foreach ($itemsInfo as $item) {
            fputcsv($fp, [
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
        fputcsv($fp, []);
        fputcsv($fp, []);

        // “Sheet” 3: Triggers – Trigger Name|Severity|Trigger with expand expression|Status|State
        fputcsv($fp, ['TRIGGERS']);
        fputcsv($fp, ['Trigger name', 'Severity', 'Trigger with expand expression', 'Status', 'State']);

        foreach ($triggers as $tr) {
            fputcsv($fp, [
                $tr['description'],
                $this->getPriorityName((int)$tr['priority']),
                $tr['expression'],                                  // expanded
                $tr['status'] == 0 ? 'Enabled' : 'Disabled',
                $tr['state'] == 0 ? 'Normal' : 'Not supported'
            ]);
        }

        fclose($fp);
        exit;
    }

    /**
     * -------------------------------------------------
     * HTML EXPORT
     * -------------------------------------------------
     */
    private function exportToHTML(array $hostInfo, array $itemsInfo, array $triggers): void {
        if (empty($hostInfo)) {
            header('HTTP/1.1 404 Not Found');
            echo 'Host not found';
            exit;
        }

        $host = $hostInfo[0];
        $inv  = $host['inventory'] ?? [];

        $statusText = $host['status'] == 0 ? 'Monitored' : 'Not Monitored';
        $maintText  = $host['maintenance_status'] == 0 ? 'Not Under Maintenance' : 'Under Maintenance';

        ob_start();
        ?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Host config: <?php echo htmlspecialchars($host['host']); ?></title>
    <style>
        body { font-family: Trebuchet MS, sans-serif; font-size: 13px; background: #f5f5f5; margin: 0; }
        .page { max-width: 1200px; margin: 0 auto; background: #fff; padding: 20px 25px; }
        .header { background: #007bff; color: #fff; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .header h1 { margin: 0 0 5px 0; font-size: 22px; }
        .section { margin-bottom: 25px; page-break-inside: avoid; }
        .section h2 { margin: 0 0 10px 0; font-size: 16px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e4e4e4; text-align: left; }
        th { background: #f0f0f0; }
        tr:nth-child(even) td { background: #fafafa; }
        .kv-table td:first-child { width: 160px; font-weight: 600; background: #f9f9f9; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 11px; color: #fff; }
        .prio-0 { background: #999; }
        .prio-1 { background: #5bc0de; }
        .prio-2 { background: #5cb85c; }
        .prio-3 { background: #f0ad4e; }
        .prio-4 { background: #d9534f; }
        .prio-5 { background: #b52b2b; }
        .status-enabled { color: #28a745; font-weight: 600; }
        .status-disabled { color: #dc3545; font-weight: 600; }
        code { font-size: 11px; }
        @media print {
            body { background: #fff; }
            .page { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <h1><?php echo htmlspecialchars($host['name']); ?></h1>
        <div>Hostname: <?php echo htmlspecialchars($host['host']); ?></div>
        <div>Status: <?php echo htmlspecialchars($statusText); ?> | Generated: <?php echo date('Y-m-d H:i:s'); ?></div>
    </div>

    <div class="section">
        <h2>General information</h2>
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
        <h2>Items (<?php echo count($itemsInfo); ?>)</h2>
        <table>
            <thead>
            <tr>
                <th>Name</th><th>Key</th><th>Interval</th><th>History</th>
                <th>Trends</th><th>Description</th><th>Status</th><th>State</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($itemsInfo as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo htmlspecialchars($item['key_']); ?></code></td>
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
            <tr>
                <th>Trigger name</th><th>Severity</th><th>Trigger with expand expression</th><th>Status</th><th>State</th>
            </tr>
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
                    <td><?php echo htmlspecialchars($tr['expression']); ?></code></td>
                    <td class="<?php echo $tr['status'] == 0 ? 'status-enabled' : 'status-disabled'; ?>">
                        <?php echo $tr['status'] == 0 ? 'Enabled' : 'Disabled'; ?>
                    </td>
                    <td><?php echo $tr['state'] == 0 ? 'Normal' : 'Not supported'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
        <?php

        $html = ob_get_clean();

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename=host_' . $host['host'] . '_' . date('Y-m-d_Hi') . '.html');
        echo $html;
        exit;
    }

   /**
     * -------------------------------------------------
     * JSON EXPORT
     * -------------------------------------------------
     */
    private function exportToJSON(array $hostInfo, array $itemsInfo, array $triggers): void {
        if (empty($hostInfo)) {
            header('HTTP/1.1 404 Not Found');
            echo json_encode(['error' => 'Host not found']);
            exit;
        }

        $host = $hostInfo[0];

        $data = [
            'export_info' => [
                'format'      => 'json',
                'exported_at' => date('c'),
                'version'     => '1.0'
            ],
            'host' => [
                'hostid'             => $host['hostid'],
                'host'               => $host['host'],
                'name'               => $host['name'],
                'status'             => $host['status'] == 0 ? 'Monitored' : 'Not Monitored',
                'maintenance_status' => $host['maintenance_status'] == 0 ? 'Not Under Maintenance' : 'Under Maintenance',
                'description'        => $host['description'] ?? '',
                'inventory'          => $this->formatInventory($host['inventory'] ?? []),
                'hostgroups'         => $host['hostgroups'] ?? [],
                'interfaces'         => $host['interfaces'] ?? [],
                'tags'               => $host['tags'] ?? [],
                'parentTemplates'    => $host['parentTemplates'] ?? []
            ],
            'items' => array_map(function ($item) {
                return [
                    'itemid'      => $item['itemid'],
                    'name'        => $item['name'],
                    'key'         => $item['key_'],
                    'delay'       => $item['delay'],
                    'history'     => $item['history'],
                    'trends'      => $item['trends'],
                    'description' => $item['description'] ?? '',
                    'status'      => $item['status'] == 0 ? 'Enabled' : 'Disabled',
                    'state'       => $item['state'] == 0 ? 'Normal' : 'Not supported'
                ];
            }, $itemsInfo),
            'triggers' => array_map(function ($tr) {
                return [
                    'triggerid'   => $tr['triggerid'],
                    'description' => $tr['description'],
                    'priority'    => $this->getPriorityName((int)$tr['priority']),
                    'status'      => $tr['status'] == 0 ? 'Enabled' : 'Disabled',
                    'state'       => $tr['state'] == 0 ? 'Normal' : 'Not supported',
                    'expression'  => $tr['expression']   // expanded
                ];
            }, $triggers)
        ];

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=host_' . $host['host'] . '_' . date('Y-m-d_Hi') . '.json');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function getPriorityName(int $priority): string {
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
