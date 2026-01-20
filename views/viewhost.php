<?php
/**
 * View file for multi-host configuration viewer
 * With colored severity badges (like HTML export)
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

// Get severity colors from Zabbix settings
$severities = API::Settings()->get([
    'output' => ['severity_color_0','severity_color_1','severity_color_2','severity_color_3','severity_color_4','severity_color_5']
]);

// Get all hosts for autocomplete
$all_hosts = API::Host()->get([
    'output' => ['hostid', 'host', 'name'],
    'sortfield' => 'name',
    'sortorder' => 'ASC'
]);

$hosts_data_json = [];
foreach($all_hosts as $h) {
    $display = $h['host'];
    if(!empty($h['name'])) {
        $display .= ' (' . $h['name'] . ')';
    }
    $hosts_data_json[] = [
        'id' => $h['hostid'],
        'name' => $h['host'],
        'display' => $display
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'Trebuchet MS', Tahoma, Arial, sans-serif;
            margin: 1px;
        }

        /* Table styles */
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
            font-size: 13px;
            font-weight: 600;
            color: #1f2c33;
            text-align: center;
            min-width: 80px;
        }

        .severity-0 { background: #999999; }      /* Not classified - Gray */
        .severity-1 { background: #7499FF; }      /* Information - Blue */
        .severity-2 { background: #FFC859; }      /* Low - Yellow */
        .severity-3 { background: #FFA059; }      /* Medium - Orange */
        .severity-4 { background: #E97659; }      /* High - Red */
        .severity-5 { background: #E45959; }      /* Critical - Dark Red */

        /* Inline search wrapper */
        .inline-search-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 4px 550px;
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

        .selected-host-tag {
            background: #e8f4f8;
            border: 1px solid #0074cc;
            border-radius: 3px;
            padding: 3px 8px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
        }

        .selected-host-tag .remove {
            cursor: pointer;
            color: #0074cc;
            font-weight: bold;
            font-size: 14px;
        }

        .selected-host-tag .remove:hover {
            color: #d00;
        }

        #hostSearchInput {
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
            padding: 1px 4px;
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
            background: #fafafa;
            padding: 2px;
            margin: 2px 0;
            border-radius: 5px;
            text-align: center;
        }

        .export-section button {
            margin: 5px;
        }

        .section-header {
            background: #f0f0f0;
            padding: 10px;
            margin: 20px 0 10px 10px;
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
		
		/* ==========================
		HEADER GRADIENT ENHANCEMENT
		========================== */
		.header-title {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: #ffffff;
			padding: 8px 16px;
			position: relative;
			box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
		}
		
		/* Title text */
		#page-title-general {
			color: #ffffff;
			font-size: 30px;
			font-weight: 600;
			margin: 0;
			position: relative;   /* override absolute */
			left: auto;
			transform: none;
			text-align: center;
		}
		
		/* Sidebar toggle button */
		.header-title .button-toggle {
			background: rgba(255, 255, 255, 0.15);
			border: 1px solid rgba(255, 255, 255, 0.3);
			color: #ffffff;
			padding: 6px 12px;
			border-radius: 4px;
			font-size: 13px;
		}
		
		.header-title .button-toggle:hover {
			background: rgba(255, 255, 255, 0.25);
			cursor: pointer;
		}
		
		/* Align nav and title nicely */
		.header-title {
			display: flex;
			align-items: center;
		}
		
		.header-title nav {
			flex: 0 0 auto;
		}
		
		.header-title > div {
			flex: 1;
			text-align: center;
		}
		
		/* Fix Zabbix core header clipping for custom gradient header */
		.header-title h1 {
			line-height: 35px;   /* or 38px if you increase font-size */
			overflow: visible;  /* allow full text */
			white-space: normal; /* allow wrap if needed */
		}

    </style>
</head>
<body>

<header class="header-title">
	<nav class="sidebar-nav-toggle" role="navigation" aria-label="Sidebar control">
		<button type="button" id="sidebar-button-toggle" class="button-toggle" title="Show sidebar">Show sidebar</button>
	</nav>
	<div>
		<h1 id="page-title-general" >View Monitoring Configurations for Host/s</h1>
	</div>
</header>

<form method="post" id="hostForm">
    <div class="inline-search-wrapper">
        <label for="hostSearchInput">Host:</label>
        <div class="multiselect-container">
            <div class="multiselect-input-wrapper" onclick="document.getElementById('hostSearchInput').focus()">
                <div id="selectedHostsTags"></div>
                <input type="text" 
                       id="hostSearchInput" 
                       placeholder="🔍 Search hosts..." 
                       autocomplete="off">
            </div>
            <div id="autocompleteDropdown" class="autocomplete-dropdown"></div>
        </div>
        <input type="hidden" name="hostids_submitted" id="hostidsSubmitted" value="">
        <button type="submit">Search Hosts</button>
    </div>
</form>

<?php
// Handle multi-host selection
$selected_hostids = [];
if(isset($_POST['hostids_submitted']) && !empty($_POST['hostids_submitted'])){
    $selected_hostids = array_map('intval', explode(',', $_POST['hostids_submitted']));
    $selected_hostids = array_filter($selected_hostids);
}

if(!empty($selected_hostids)){
    // Fetch all host data first
    $all_hosts_info = [];

    foreach($selected_hostids as $single_hostid){
        $hostInfo = API::Host()->get([
            'hostids' => $single_hostid,
            'output' => ['hostid','host','name','status','description','maintenance_status'],
            'selectHostGroups' => ['groupid','name'],
            'selectInterfaces' => ['interfaceid','type','main','available','error','ip','dns','port'],
            'selectInventory' => ['type','type_full','name','os','contact'],
            'selectMacros' => ['hostmacroid','macro','value','description','type','automatic'],
            'selectParentTemplates' => ['templateid','name'],
            'selectTags' => ['tag','value']
        ]);

        if(empty($hostInfo)) continue;

        $itemsInfo = API::Item()->get([
            'hostids' => [$single_hostid],
            'webitems' => 1,
            'preservekeys' => 1,
            'templated' => NULL,
            'output' => ['itemid','type','name','key_','delay','history','trends','status','state','description'],
            'selectTriggers' => ['description'],
            'sortfield' => 'name'
        ]);

        $triggers = API::Trigger()->get([
            'output' => ['triggerid','description','expression','priority','status','state'],
            'hostids' => [$single_hostid],
            'expandExpression' => true,
            'expandDescription' => true,
            'sortfield' => 'description'
        ]);

        $all_hosts_info[] = [
            'info' => $hostInfo[0],
            'items' => $itemsInfo,
            'triggers' => $triggers
        ];
    }

    // Export buttons
    ?>
    <div class="export-section">
        <strong>Export <?php echo count($all_hosts_info); ?> host(s):</strong><br><br>
        <button type="button" onclick="downloadHostConfig('csv')">📄 Download CSV</button>
        <button type="button" onclick="downloadHostConfig('html')">🌐 Download HTML</button>
        <button type="button" onclick="downloadHostConfig('json')">{ } Download JSON</button>
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
                <th>Proxy</th>
                <th>Proxy Group</th>
                <th>Interfaces</th>
                <th>Groups</th>
                <th>Templates</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($all_hosts_info as $host_data): 
            $host = $host_data['info'];
        ?>
            <tr>
                <td class="host-name-header"><?php echo htmlspecialchars($host['host']); ?></td>
                <td><?php echo htmlspecialchars($host['name']); ?></td>
                <td><?php echo $map_status[$host['status']]; ?></td>
                <td><?php echo $map_maintenance_status[$host['maintenance_status']]; ?></td>
                <td>N/A</td>
                <td>None</td>
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
    <?php foreach($all_hosts_info as $index => $host_data): 
        $host = $host_data['info'];
        $items = $host_data['items'];
        $triggers = $host_data['triggers'];
    ?>

        <div class="host-details-section">
            <h2 class="host-title">
                <?php echo htmlspecialchars($host['host']); ?> 
                <span style="color: #666; font-size: 14px;">(<?php echo htmlspecialchars($host['name']); ?>)</span>
            </h2>

            <!-- HORIZONTAL LAYOUT: MACROS | INVENTORY | TAGS -->
            <div class="horizontal-sections">

                <!-- MACROS (COLLAPSED by default) -->
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

                <!-- INVENTORY (COLLAPSED by default) -->
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

                <!-- TAGS (COLLAPSED by default) -->
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

            <!-- ITEMS -->
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

            <!-- TRIGGERS with COLORED SEVERITY BADGES -->
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
} // end if hosts selected
?>

<script>
// All hosts data for autocomplete
const hostsData = <?php echo json_encode($hosts_data_json); ?>;
let selectedHosts = new Set();

<?php
// Restore previously selected hosts
if(!empty($selected_hostids)){
    echo "selectedHosts = new Set(" . json_encode(array_map('strval', $selected_hostids)) . ");";
}
?>

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelectedHostsTags();
    updateHiddenInput();
});

const searchInput = document.getElementById('hostSearchInput');
const dropdown = document.getElementById('autocompleteDropdown');

// Show dropdown on focus
searchInput.addEventListener('focus', function() {
    showDropdown();
});

// Filter on input			  
searchInput.addEventListener('input', function() {
    showDropdown();
});

// Hide dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.multiselect-container')) {
        dropdown.classList.remove('show');
    }
});

function showDropdown() {
    const searchTerm = searchInput.value.toLowerCase();
    const filtered = hostsData.filter(host => 
        host.display.toLowerCase().includes(searchTerm)
    );

    if (filtered.length === 0) {
        dropdown.innerHTML = '<div class="autocomplete-item" style="color: #999;">No hosts found</div>';
    } else {
        dropdown.innerHTML = filtered.slice(0, 50).map(host => {
            const isSelected = selectedHosts.has(host.id.toString());
            const className = isSelected ? 'autocomplete-item selected' : 'autocomplete-item';
            const text = isSelected ? host.display + ' ✓' : host.display;
            return `<div class="${className}" data-hostid="${host.id}" data-hostname="${escapeHtml(host.name)}" data-display="${escapeHtml(host.display)}">${escapeHtml(text)}</div>`;
        }).join('');
    }

    dropdown.classList.add('show');

    // Add click handlers
    dropdown.querySelectorAll('.autocomplete-item').forEach(item => {
        item.addEventListener('click', function() {
            const hostid = this.getAttribute('data-hostid');

            if (!selectedHosts.has(hostid)) {
                selectedHosts.add(hostid);
                updateSelectedHostsTags();
                updateHiddenInput();
            }

            searchInput.value = '';
            searchInput.focus();
            showDropdown();
        });
    });
}

function updateSelectedHostsTags() {
    const tagsContainer = document.getElementById('selectedHostsTags');

    if (selectedHosts.size === 0) {
        tagsContainer.innerHTML = '';
        return;
    }

    const tags = Array.from(selectedHosts).map(hostid => {
        const host = hostsData.find(h => h.id.toString() === hostid);
        if (!host) return '';

        return `<span class="selected-host-tag">
            ${escapeHtml(host.name)}
            <span class="remove" onclick="removeHost('${hostid}')" title="Remove">×</span>
        </span>`;
    }).join('');

    tagsContainer.innerHTML = tags;
}

function removeHost(hostid) {
    selectedHosts.delete(hostid.toString());
    updateSelectedHostsTags();
    updateHiddenInput();
    showDropdown();
}

function updateHiddenInput() {
    document.getElementById('hostidsSubmitted').value = Array.from(selectedHosts).join(',');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Export function
function downloadHostConfig(format) {
    if (selectedHosts.size === 0) {
        alert('Please select at least one host');
        return;
    }

    const hostids = Array.from(selectedHosts).join(',');
    window.location.href = 'zabbix.php?action=gethostro.view&export=' + encodeURIComponent(format) + '&hostids=' + hostids;
}
</script>

</body>
</html>
