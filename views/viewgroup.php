<?php
/**
 * View file for Host Group configuration viewer
 * With autocomplete multi-select for hostgroups (Zabbix style)
 */

// Mapping arrays for display
static $map_status = array(
    0 => '<span style="color: green;">Monitored</span>',
    1 => '<span style="color: red;">Not Monitored</span>'
);

static $map_maintenance_status = array(
    0 => '<span style="color: green;">Not Under Maintenance</span>',
    1 => '<span style="color: red;"><span class="icon-maintenance"></span> Under Maintenance</span>'
);

static $map_item_status = array(
    0 => '<span style="color: green;">Enabled</span>',
    1 => '<span style="color: red;">Disabled</span>'
);

static $map_item_state = array(
    0 => '<span style="color: green;">Normal</span>',
    1 => '<span style="color: red;">Not Supported</span>'
);

static $map_trigger_status = array(
    0 => '<span style="color: green;">Enabled</span>',
    1 => '<span style="color: red;">Disabled</span>'
);

static $map_trigger_state = array(
    0 => '<span style="color: green;">Normal</span>',
    1 => '<span style="color: red;">Not Supported</span>'
);

static $map_trigger_priority = array(
    0 => 'Not classified',
    1 => 'Information',
    2 => 'Low',
    3 => 'Medium',
    4 => 'High',
    5 => 'Critical'
);

// Get all hostgroups for autocomplete
$all_groups = API::HostGroup()->get([
    'output' => ['groupid', 'name'],
    'sortfield' => 'name',
    'sortorder' => 'ASC'
]);

$groups_data_json = [];
foreach($all_groups as $g) {
    $groups_data_json[] = [
        'id' => $g['groupid'],
        'name' => $g['name']
    ];
}
?>

<style>
    body {
        font-family: 'Trebuchet MS', Tahoma, Arial, sans-serif;
    }

    table {
        border-collapse: collapse;
        width: 100%;
        font-family: 'Trebuchet MS', Tahoma, Arial, sans-serif;
    }

    th, td {
        text-align: left;
        padding: 8px;
        border: 1px solid #ddd;
    }

    thead th {
        background-color: #e9eff6;
        color: #1a1a1a;
        font-weight: 600;
        font-size: 14px;
        border-bottom: 2px solid #d0d7e1;
    }

    tr:nth-child(even) {
        background-color: #f4f7fb;
    }

    /* Severity Badge Styles */
    .severity-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 600;
        color: #fff;
        text-align: center;
        min-width: 80px;
    }

    .severity-0 { background: #999999; }
    .severity-1 { background: #7499FF; }
    .severity-2 { background: #83E8A0; }
    .severity-3 { background: #FFC859; }
    .severity-4 { background: #FFA059; }
    .severity-5 { background: #E97659; }

    /* Inline search wrapper */
    .inline-search-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 20px 0;
    }

    .inline-search-wrapper label {
        font-weight: bold;
        white-space: nowrap;
    }

    /* Autocomplete multi-select */
    .multiselect-container {
        position: relative;
        flex: 1;
        max-width: 600px;
    }

    .multiselect-input-wrapper {
        border: 1px solid #ccc;
        border-radius: 3px;
        padding: 5px;
        min-height: 34px;
        background: white;
        cursor: text;
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        align-items: center;
    }

    .multiselect-input-wrapper:focus-within {
        border-color: #0074cc;
        box-shadow: 0 0 3px rgba(0, 116, 204, 0.3);
    }

    .selected-group-tag {
        background: #e8f4f8;
        border: 1px solid #0074cc;
        border-radius: 3px;
        padding: 3px 8px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
    }

    .selected-group-tag .remove {
        cursor: pointer;
        color: #0074cc;
        font-weight: bold;
        font-size: 14px;
    }

    .selected-group-tag .remove:hover {
        color: #d00;
    }

    #groupSearchInput {
        border: none;
        outline: none;
        flex: 1;
        min-width: 150px;
        padding: 5px;
        font-family: 'Trebuchet MS', Tahoma, Arial, sans-serif;
        font-size: 14px;
    }

    .autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ccc;
        border-top: none;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .autocomplete-dropdown.show {
        display: block;
    }

    .autocomplete-item {
        padding: 8px 12px;
        cursor: pointer;
        font-size: 13px;
    }

    .autocomplete-item:hover {
        background: #e8f4f8;
    }

    .autocomplete-item.selected {
        background: #f0f0f0;
        color: #999;
    }

    button {
        padding: 8px 16px;
        font-size: 14px;
        cursor: pointer;
        border: none;
        background-color: #0074cc;
        color: white;
        border-radius: 3px;
        font-family: 'Trebuchet MS', Tahoma, Arial, sans-serif;
    }

    button:hover {
        opacity: 0.9;
    }

    .export-section {
        background: #e8f4f8;
        padding: 15px;
        margin: 20px 0;
        border-radius: 5px;
        text-align: center;
    }

    .export-section button {
        margin: 5px;
    }

    .section-header {
        background: #f0f0f0;
        padding: 10px;
        margin: 20px 0 10px 0;
        font-weight: bold;
        border-left: 4px solid #0074cc;
        font-size: 16px;
    }

    .host-name-header {
        color: #0074cc;
        font-weight: bold;
        font-size: 15px;
    }

    /* Horizontal layout for Macros/Inventory/Tags */
    .horizontal-sections {
        display: flex;
        gap: 20px;
        margin: 20px 0;
    }

    .horizontal-sections > div {
        flex: 1;
        min-width: 0;
    }

    details {
        margin: 10px 0;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 10px;
    }

    summary {
        cursor: pointer;
        font-weight: bold;
        font-size: 15px;
        padding: 5px;
    }

    summary:hover {
        background: #f0f0f0;
    }

    .icon-maintenance::before {
        content: "⚠";
        margin-right: 6px;
        font-weight: bold;
        color: #d4af37;
        font-size: 18px;
        vertical-align: middle;
    }

    .host-details-section {
        margin: 30px 0;
        padding: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: #fafafa;
    }

    .host-title {
        color: #0074cc;
        border-bottom: 2px solid #0074cc;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
</style>

<h1 style="text-align: center;">View HostGroup Configurations</h1>

<form method="post" id="groupForm">
    <div class="inline-search-wrapper">
        <label for="groupSearchInput">HostGroup:</label>
        <div class="multiselect-container">
            <div class="multiselect-input-wrapper" onclick="document.getElementById('groupSearchInput').focus()">
                <div id="selectedGroupsTags"></div>
                <input type="text" 
                       id="groupSearchInput" 
                       placeholder="🔍 Search hostgroups..." 
                       autocomplete="off">
            </div>
            <div id="autocompleteDropdown" class="autocomplete-dropdown"></div>
        </div>
        <input type="hidden" name="groupids_submitted" id="groupidsSubmitted" value="">
        <button type="submit">Load HostGroups</button>
    </div>
</form>

<?php
// Handle multi-hostgroup selection
$selected_groupids = [];
if(isset($_POST['groupids_submitted']) && !empty($_POST['groupids_submitted'])){
    $selected_groupids = array_map('intval', explode(',', $_POST['groupids_submitted']));
    $selected_groupids = array_filter($selected_groupids);
}

if(!empty($selected_groupids)){
    // Collect all hosts from all selected groups
    $all_hosts_data = [];
    $total_hosts = 0;
    $selected_group_names = [];

    foreach($selected_groupids as $single_groupid){
        // Get group info
        $group_info = API::HostGroup()->get([
            'groupids' => $single_groupid,
            'output' => ['groupid', 'name']
        ]);

        if(empty($group_info)) continue;

        $selected_group_names[] = $group_info[0]['name'];

        // Get all hosts in this hostgroup
        $hosts_in_group = API::Host()->get([
            'groupids' => $single_groupid,
            'output' => ['hostid', 'host', 'name', 'status', 'description', 'maintenance_status'],
            'selectHostGroups' => ['groupid','name'],
            'selectInterfaces' => ['interfaceid','type','main','available','error','ip','dns','port'],
            'selectInventory' => ['type','type_full','name','os','contact'],
            'selectMacros' => ['hostmacroid','macro','value','description','type','automatic'],
            'selectParentTemplates' => ['templateid','name'],
            'selectTags' => ['tag','value']
        ]);

        foreach($hosts_in_group as $host){
            // Skip duplicates if host is in multiple selected groups
            $host_exists = false;
            foreach($all_hosts_data as $existing){
                if($existing['info']['hostid'] == $host['hostid']){
                    $host_exists = true;
                    break;
                }
            }

            if($host_exists) continue;

            $itemsInfo = API::Item()->get([
                'hostids' => [$host['hostid']],
                'webitems' => 1,
                'preservekeys' => 1,
                'templated' => NULL,
                'output' => ['itemid','type','name','key_','delay','history','trends','status','state','description'],
                'selectTriggers' => ['description'],
                'sortfield' => 'name'
            ]);

            $triggers = API::Trigger()->get([
                'output' => ['triggerid','description','expression','priority','status','state'],
                'hostids' => [$host['hostid']],
                'expandExpression' => true,
                'expandDescription' => true,
                'sortfield' => 'description'
            ]);

            $all_hosts_data[] = [
                'info' => $host,
                'items' => $itemsInfo,
                'triggers' => $triggers
            ];
        }
    }

    $total_hosts = count($all_hosts_data);

    if($total_hosts > 0){
        ?>

        <div class="export-section">
            <strong>Selected HostGroups: <?php echo htmlspecialchars(implode(', ', $selected_group_names)); ?></strong>
            (<?php echo $total_hosts; ?> unique host<?php echo $total_hosts > 1 ? 's' : ''; ?>)<br><br>
            <button type="button" onclick="downloadGroupConfig('csv')">📄 Download CSV</button>
            <button type="button" onclick="downloadGroupConfig('html')">📋 Download HTML</button>
            <button type="button" onclick="downloadGroupConfig('json')">📦 Download JSON</button>
        </div>

        <!-- GENERAL INFORMATION - ALL HOSTS (TABULAR) -->
        <div class="section-header">General Information - All Hosts</div>
        <table>
            <thead>
                <tr>
                    <th>Host Name</th>
                    <th>Display Name</th>
                    <th>Status</th>
                    <th>Maintenance</th>
                    <th>Interfaces</th>
                    <th>Groups</th>
                    <th>Templates</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($all_hosts_data as $host_data): 
                $host = $host_data['info'];
            ?>
                <tr>
                    <td class="host-name-header"><?php echo htmlspecialchars($host['host']); ?></td>
                    <td><?php echo htmlspecialchars($host['name']); ?></td>
                    <td><?php echo $map_status[$host['status']]; ?></td>
                    <td><?php echo $map_maintenance_status[$host['maintenance_status']]; ?></td>
                    <td>
                        <?php
                        $interfaces = [];
                        foreach($host['interfaces'] as $iface){
                            $if_text = '';
                            if($iface['ip']) $if_text .= $iface['ip'];
                            if($iface['ip'] && $iface['dns']) $if_text .= ', ';
                            if($iface['dns']) $if_text .= $iface['dns'];
                            if($if_text) $interfaces[] = $if_text;
                        }
                        echo htmlspecialchars(implode(', ', $interfaces));
                        ?>
                    </td>
                    <td>
                        <?php
                        $groups = array_map(function($g){ return $g['name']; }, $host['hostgroups']);
                        echo htmlspecialchars(implode(', ', $groups));
                        ?>
                    </td>
                    <td>
                        <?php
                        $templates = array_map(function($t){ return $t['name']; }, $host['parentTemplates']);
                        echo htmlspecialchars(implode(', ', $templates));
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- INDIVIDUAL HOST DETAILS -->
        <?php foreach($all_hosts_data as $index => $host_data): 
            $host = $host_data['info'];
            $items = $host_data['items'];
            $triggers = $host_data['triggers'];
        ?>

            <div class="host-details-section">
                <h2 class="host-title">
                    <?php echo htmlspecialchars($host['host']); ?> 
                    <span style="color: #666; font-size: 14px;">(<?php echo htmlspecialchars($host['name']); ?>)</span>
                </h2>

                <!-- HORIZONTAL LAYOUT: MACROS | INVENTORY | TAGS (COLLAPSED BY DEFAULT) -->
                <div class="horizontal-sections">

                    <!-- MACROS (COLLAPSED) -->
                    <div>
                        <details>
                            <summary>MACROS (<?php echo count($host['macros']); ?>)</summary>
                            <table style="margin-top: 10px; font-size: 13px;">
                                <thead>
                                    <tr><th>Name</th><th>Value</th><th>Description</th></tr>
                                </thead>
                                <tbody>
                                <?php 
                                if(!empty($host['macros'])){
                                    foreach($host['macros'] as $macro){ 
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($macro['macro']); ?></td>
                                        <td><?php echo isset($macro['value']) ? htmlspecialchars($macro['value']) : '***'; ?></td>
                                        <td><?php echo htmlspecialchars($macro['description'] ?? ''); ?></td>
                                    </tr>
                                <?php 
                                    }
                                } else {
                                    echo '<tr><td colspan="3"><em>No macros</em></td></tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </details>
                    </div>

                    <!-- INVENTORY (COLLAPSED) -->
                    <div>
                        <details>
                            <summary>INVENTORY</summary>
                            <table style="margin-top: 10px; font-size: 13px;">
                                <tbody>
                                <?php
                                $inventory_labels = [
                                    'name' => 'Host Name',
                                    'contact' => 'Customer',
                                    'type_full' => 'Product',
                                    'type' => 'Environment',
                                    'os' => 'OS'
                                ];

                                $inventory_raw = $host['inventory'] ?? [];
                                $has_inventory = false;

                                foreach($inventory_labels as $key => $label){
                                    if(isset($inventory_raw[$key]) && $inventory_raw[$key] !== ''){
                                        echo '<tr><th style="width: 120px;">' . $label . '</th><td>' . htmlspecialchars($inventory_raw[$key]) . '</td></tr>';
                                        $has_inventory = true;
                                    }
                                }

                                if(!$has_inventory){
                                    echo '<tr><td><em>No inventory data</em></td></tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </details>
                    </div>

                    <!-- TAGS (COLLAPSED) -->
                    <div>
                        <details>
                            <summary>TAGS (<?php echo count($host['tags'] ?? []); ?>)</summary>
                            <table style="margin-top: 10px; font-size: 13px;">
                                <thead>
                                    <tr><th>Tag</th><th>Value</th></tr>
                                </thead>
                                <tbody>
                                <?php 
                                if(!empty($host['tags'])){
                                    foreach($host['tags'] as $tag){ 
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($tag['tag']); ?></td>
                                        <td><?php echo htmlspecialchars($tag['value']); ?></td>
                                    </tr>
                                <?php 
                                    }
                                } else {
                                    echo '<tr><td colspan="2"><em>No tags</em></td></tr>';
                                }
                                ?>
                                </tbody>
                            </table>
                        </details>
                    </div>

                </div>
                <!-- END HORIZONTAL LAYOUT -->

                <!-- ITEMS (COLLAPSED) -->
                <details style="margin-top: 20px;">
                    <summary>ITEMS (<?php echo count($items); ?>)</summary>
                    <table style="margin-top: 10px; font-size: 13px;">
                        <thead>
                            <tr>
                                <th>Name</th><th>Key</th><th>Interval</th><th>History</th><th>Trends</th>
                                <th>Description</th><th>Status</th><th>State</th><th>Triggers</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        if(!empty($items)){
                            foreach($items as $item){ 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><code><?php echo htmlspecialchars($item['key_']); ?></code></td>
                                <td><?php echo htmlspecialchars($item['delay']); ?></td>
                                <td><?php echo htmlspecialchars($item['history']); ?></td>
                                <td><?php echo htmlspecialchars($item['trends']); ?></td>
                                <td><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                                <td><?php echo $map_item_status[$item['status']]; ?></td>
                                <td><?php echo $map_item_state[$item['state']]; ?></td>
                                <td><?php echo count($item['triggers']); ?></td>
                            </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="9"><em>No items</em></td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </details>

                <!-- TRIGGERS (COLLAPSED) with COLORED SEVERITY BADGES -->
                <details style="margin-top: 20px;">
                    <summary>TRIGGERS (<?php echo count($triggers); ?>)</summary>
                    <table style="margin-top: 10px; font-size: 13px;">
                        <thead>
                            <tr>
                                <th>Trigger Name</th><th>Severity</th><th>Expression</th>
                                <th>Status</th><th>State</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        if(!empty($triggers)){
                            foreach($triggers as $trigger){ 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($trigger['description']); ?></td>
                                <td>
                                    <span class="severity-badge severity-<?php echo $trigger['priority']; ?>">
                                        <?php echo $map_trigger_priority[$trigger['priority']]; ?>
                                    </span>
                                </td>
                                <td><code style="font-size: 11px;"><?php echo htmlspecialchars($trigger['expression']); ?></code></td>
                                <td><?php echo $map_trigger_status[$trigger['status']]; ?></td>
                                <td><?php echo $map_trigger_state[$trigger['state']]; ?></td>
                            </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="5"><em>No triggers</em></td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </details>

            </div>

        <?php endforeach; ?>

    <?php
    } else {
        echo '<p style="color: red; text-align: center; margin: 20px;">No hosts found in the selected hostgroup(s).</p>';
    }
}
?>

<script>
// All hostgroups data for autocomplete
const groupsData = <?php echo json_encode($groups_data_json); ?>;
let selectedGroups = new Set();

<?php
// Restore previously selected groups
if(!empty($selected_groupids)){
    echo "selectedGroups = new Set(" . json_encode(array_map('strval', $selected_groupids)) . ");";
}
?>

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedGroupsTags();
    updateHiddenInput();
});

const searchInput = document.getElementById('groupSearchInput');
const dropdown = document.getElementById('autocompleteDropdown');

searchInput.addEventListener('focus', function() {
    showDropdown();
});

searchInput.addEventListener('input', function() {
    showDropdown();
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.multiselect-container')) {
        dropdown.classList.remove('show');
    }
});

function showDropdown() {
    const searchTerm = searchInput.value.toLowerCase();
    const filtered = groupsData.filter(group => 
        group.name.toLowerCase().includes(searchTerm)
    );

    if (filtered.length === 0) {
        dropdown.innerHTML = '<div class="autocomplete-item" style="color: #999;">No hostgroups found</div>';
    } else {
        dropdown.innerHTML = filtered.slice(0, 50).map(group => {
            const isSelected = selectedGroups.has(group.id.toString());
            const className = isSelected ? 'autocomplete-item selected' : 'autocomplete-item';
            const text = isSelected ? group.name + ' ✓' : group.name;
            return `<div class="${className}" data-groupid="${group.id}" data-groupname="${escapeHtml(group.name)}">${escapeHtml(text)}</div>`;
        }).join('');
    }

    dropdown.classList.add('show');

    dropdown.querySelectorAll('.autocomplete-item').forEach(item => {
        item.addEventListener('click', function() {
            const groupid = this.getAttribute('data-groupid');

            if (!selectedGroups.has(groupid)) {
                selectedGroups.add(groupid);
                updateSelectedGroupsTags();
                updateHiddenInput();
            }

            searchInput.value = '';
            searchInput.focus();
            showDropdown();
        });
    });
}

function updateSelectedGroupsTags() {
    const tagsContainer = document.getElementById('selectedGroupsTags');

    if (selectedGroups.size === 0) {
        tagsContainer.innerHTML = '';
        return;
    }

    const tags = Array.from(selectedGroups).map(groupid => {
        const group = groupsData.find(g => g.id.toString() === groupid);
        if (!group) return '';

        return `<span class="selected-group-tag">
            ${escapeHtml(group.name)}
            <span class="remove" onclick="removeGroup('${groupid}')" title="Remove">×</span>
        </span>`;
    }).join('');

    tagsContainer.innerHTML = tags;
}

function removeGroup(groupid) {
    selectedGroups.delete(groupid.toString());
    updateSelectedGroupsTags();
    updateHiddenInput();
    showDropdown();
}

function updateHiddenInput() {
    document.getElementById('groupidsSubmitted').value = Array.from(selectedGroups).join(',');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Export function
function downloadGroupConfig(format) {
    if (selectedGroups.size === 0) {
        alert('Please select at least one hostgroup');
        return;
    }

    const groupids = Array.from(selectedGroups).join(',');
    window.location.href = 'zabbix.php?action=getgroupro.view&export=' + encodeURIComponent(format) + '&groupids=' + groupids;
}
</script>
