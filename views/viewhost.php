<?php

require_once './include/page_header.php';

foreach (glob("./include/classes/html/*.php") as $filename)
{
	require_once $filename;
}

static $map_status = array(
	0 => '<span class="green">Monitored</span>',
	1 => '<span class="red">Not Monitored</span>'
);
static $map_maintenance_status  = array(
        0 => '<span class="green">Not Under Maintenance</span>',
        1 => '<span class="red"><span class="icon-maintenance"></span> Under Maintenance</span>'
);
static $map_item_status  = array(
        0 => '<span class="green">Enabled</span>',
        1 => '<span class="red">Disabled</span>'
);
static $map_item_state  = array(
        0 => '<span class="green">Normal</span>',
        1 => '<span class="red">Not Supported</span>'
);
static $map_trigger_status  = array(
        0 => '<span class="green">Enabled</span>',
        1 => '<span class="red">Disabled</span>'
);
static $map_trigger_state  = array(
        0 => '<span class="green">Normal</span>',
        1 => '<span class="red">Not Supported</span>'
);
static $map_trigger_priority  = array(
        0 => 'Not classified',
        1 => 'Information',
        2 => 'Low',
        3 => 'Medium',
        4 => 'High',
        5 => 'Critical'
);

function build_table($array){
	$html="";
	foreach($array as $key => $value ){
	    $html .= '<tr>';
	    $html .= '<th>' . htmlspecialchars($key) . '</th>';
	    $html .= '<td>' . htmlspecialchars($value) . '</td>';
	    $html .= '</tr>';
        }

    return $html;
}
?>

<style>
/* General Table Styling */
table {
  border-collapse: collapse;
  width: 100%;
  font-family: "Trebuchet MS", Tahoma, Arial, sans-serif;
}

th, td {
  text-align: left;
  padding: 8px;
}

/* Dotted Borders Between Cells */
table td + td,
table th + td {
  border-left: solid 1px;
  border-style: dotted;
}

/* Table Header Bottom Border */
thead {
  border-bottom: solid 1px;
  border-style: dotted;
}

/* Alternating Row Colors */
tr:nth-child(even) {
  background-color: #f4f7fb;
}

/* Summary/Section Headers */
summary h2 {
  display: inline-block;
  font-family: "Trebuchet MS", Tahoma, Arial, sans-serif;
}

/* Tooltip Styling */
.tooltip {
  text-decoration: none;
  position: relative;
}

.tooltip span {
  display: none;
}

.tooltip:hover span {
  display: block;
  position: absolute;
  top: 0;
  left: -75%;
  z-index: 1000;
  width: auto;
}

/* Page Title */
#page-title-general {
  text-align: center;
  margin: -15px;
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  font-family: "Trebuchet MS", Tahoma, Arial, sans-serif;
}

/* Host Search Wrapper Form Layout */
.host-search-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  font-family: "Trebuchet MS", Tahoma, Arial, sans-serif;
}

.host-search-wrapper label,
.host-search-wrapper input,
.host-search-wrapper select,
.host-search-wrapper button {
  font-family: "Trebuchet MS", Tahoma, Arial, sans-serif;
}

/* Maintenance Icon */
.icon-maintenance::before {
  content: "\26A0";
  margin-right: 6px;
  font-weight: bold;
  color: #d4af37;
  font-size: 18px;
  vertical-align: middle;
}

/* Table Headers For Items & Triggers */
#items thead th,
#triggers thead th,
#macros thead th {
  background-color: #e9eff6;
  color: #1a1a1a;
  font-weight: 600;
  font-size: 14px;
  border-bottom: 2px solid #d0d7e1;
  letter-spacing: 0.3px;
  font-family: "Trebuchet MS", Tahoma, Arial, sans-serif;
}

/* Table Cells Font */
table th,
table td {
  font-family: "Trebuchet MS", Tahoma, Arial, sans-serif;
}

/* Main Content */
main,
main label,
main input,
main select,
main button,
main h1 {
  font-family: "Trebuchet MS", Tahoma, Arial, sans-serif;
}

/* Buttons Hover Effect */
main button:hover {
  cursor: pointer;
  opacity: 0.9;
}

/* Consistent Spacing */
main input, main select, main button {
  padding: 4px 8px;
  font-size: 14px;
}

main button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 32px;
  padding: 4px 12px;
  font-family: "Trebuchet MS", Tahoma, Arial, sans-serif;
  font-size: 14px;
  cursor: pointer;
  border: none;
  background-color: #0074cc;
  color: white;
  border-radius: 3px;
  box-sizing: border-box;
}

/* Select dropdown styling */
main select {
  height: 32px;
  border: 1px solid #ccc;
  border-radius: 3px;
  background-color: white;
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
</style>

<link href="./modules/get-host-ro/views/includes/css/jquery.dataTables.css" rel="stylesheet"/>
<script src="./modules/get-host-ro/views/includes/js/jquery.dataTables.js"></script>

<script>
// Handle hostgroup selection to populate hosts
function onHostGroupChange() {
    var hostgroupSelect = document.getElementById('hostgroup');
    var hostSelect = document.getElementById('host');
    var selectedGroupId = hostgroupSelect.value;
    
    // Clear current host options
    hostSelect.innerHTML = '<option value="">-- Select Host (Optional) --</option>';
    
    if (!selectedGroupId) {
        return;
    }
    
    // Get hosts for selected hostgroup via AJAX
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'zabbix.php?action=gethostro.view', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var hosts = JSON.parse(xhr.responseText);
                hosts.forEach(function(host) {
                    var option = document.createElement('option');
                    option.value = host.hostid;
                    option.textContent = host.name || host.host;
                    hostSelect.appendChild(option);
                });
            } catch(e) {
                console.error('Error parsing hosts:', e);
            }
        }
    };
    
    xhr.send('get_hostgroup_hosts=1&groupid=' + encodeURIComponent(selectedGroupId));
}

function downloadHostConfig(format) {
    var hostgroupField = document.getElementById('hostgroup');
    var hostField = document.getElementById('host');
    
    if (!hostgroupField.value) {
        alert('Please select a hostgroup first.');
        return;
    }

    var hostgroupid = encodeURIComponent(hostgroupField.value);
    var hostid = hostField.value ? encodeURIComponent(hostField.value) : '';
    
    var base = 'zabbix.php';
    var url = base + '?action=gethostro.view&export=' + encodeURIComponent(format) + '&groupid=' + hostgroupid;
    
    if (hostid) {
        url += '&hostid=' + hostid;
    }
    
    window.location.href = url;
}
</script>

<header class="header-title">
	<nav class="sidebar-nav-toggle" role="navigation" aria-label="Sidebar control">
		<button type="button" id="sidebar-button-toggle" class="button-toggle" title="Show sidebar">Show sidebar</button>
	</nav>
	<div>
		<h1 id="page-title-general">View Host Configurations</h1>
	</div>
</header>
<main>
	<form method="post">
		<div id="tabs" class="table-forms-container ui-tabs ui-widget ui-widget-content ui-corner-all" style="visibility: visible;">
			<div id="maintenanceTab" aria-labelledby="tab_maintenanceTab" class="ui-tabs-panel ui-widget-content ui-corner-bottom" role="tabpanel" aria-expanded="true" aria-hidden="false">
				<ul class="table-forms" id="maintenanceFormList">
					<li>
						<div class="host-search-wrapper">
							<label class="form-label-asterisk" for="hostgroup">HostGroup</label>
							<select id="hostgroup" name="hostgroup" onchange="onHostGroupChange()" required>
								<option value="">-- Select HostGroup --</option>
<?php
// Fetch hostgroups starting with 'CUSTOMER/'
$hostgroups = api::hostgroup()->get(array(
    'output' => array('groupid', 'name'),
    'search' => array('name' => 'CUSTOMER/'),
    'searchByAny' => true,
    'startSearch' => true,
    'sortfield' => 'name'
));

foreach ($hostgroups as $group) {
?>
								<option value="<?php echo $group['groupid']; ?>" <?php echo (isset($_POST['hostgroup']) && $_POST['hostgroup'] == $group['groupid']) ? 'selected' : ''; ?>>
									<?php echo htmlspecialchars($group['name']); ?>
								</option>
<?php } ?>
							</select>

							<label class="form-label" for="host">Host (Optional)</label>
							<select id="host" name="host">
								<option value="">-- Select Host (Optional) --</option>
							</select>
							
							<button type="submit" value="Search">Search</button>
						</div>
					</li>
				</ul>
			</div>
<?php
// Handle AJAX request for getting hosts in a hostgroup
if (isset($_POST['get_hostgroup_hosts']) && isset($_POST['groupid'])) {
    $hosts = api::host()->get(array(
        'groupids' => $_POST['groupid'],
        'output' => array('hostid', 'host', 'name'),
        'sortfield' => 'name'
    ));
    
    header('Content-Type: application/json');
    echo json_encode($hosts);
    exit;
}

// Main search functionality
if (isset($_POST['hostgroup'])) {
    $selectedGroupId = $_POST['hostgroup'];
    $selectedHostId = isset($_POST['host']) && !empty($_POST['host']) ? $_POST['host'] : null;
    
    // Determine which hosts to process
    if ($selectedHostId) {
        // Single host selected
        $hostsToProcess = array($selectedHostId);
    } else {
        // All hosts in the hostgroup
        $hosts = api::host()->get(array(
            'groupids' => $selectedGroupId,
            'output' => array('hostid'),
        ));
        $hostsToProcess = array_column($hosts, 'hostid');
    }
    
    if (empty($hostsToProcess)) {
?>
        <p>No hosts found in the selected hostgroup.</p>
<?php
    } else {
        // Fetch all host information first
        $allHostsData = [];
        
        foreach ($hostsToProcess as $currentHostId) {
            $hostInfo = api::host()->get(array(
                'filter' => array('hostid' => $currentHostId),
                'output' => array('hostid','host','name','status','description','proxyid','proxy_groupid','monitored_by','tls_connect','tls_accept','tls_issuer','tls_subject','flags','inventory_mode','maintenance_status'),
                'selectDiscoveryRule' => array('itemid','name','parent_hostid'),
                'selectHostGroups' => array('groupid','name'),
                'selectHostDiscovery' => array('parent_hostid','host'),
                'selectInterfaces' => array('interfaceid','type','main','available','error','details','ip','dns','port','useip'),
                'selectInventory' => array('type','type_full','name','os','contact'),
                'selectMacros' => array('hostmacroid','macro','value','description','type','automatic'),
                'selectParentTemplates' => array('templateid','name','link_type','uuid'),
                'selectTags' => array('tag','value','automatic')
            ));
            
            if (empty($hostInfo)) {
                continue;
            }
            
            $hostInfo = $hostInfo[0];
            
            // Proxy information
            $proxy_names = [];
            $proxy_group_names = [];
            $monitored_by = isset($hostInfo['monitored_by']) ? (int)$hostInfo['monitored_by'] : 0;
            
            if ($monitored_by === 1 && !empty($hostInfo['proxyid']) && $hostInfo['proxyid'] != '0') {
                $proxyInfo = api::proxy()->get(array(
                    'proxyids' => array($hostInfo['proxyid']),
                    'output' => array('name')
                ));
                if (is_array($proxyInfo)) {
                    foreach ($proxyInfo as $p) {
                        $proxy_names[] = $p['name'];
                    }
                }
            }
            
            if ($monitored_by === 2 && !empty($hostInfo['proxy_groupid']) && $hostInfo['proxy_groupid'] != '0') {
                $pgInfo = api::proxygroup()->get(array(
                    'proxy_groupids' => array($hostInfo['proxy_groupid']),
                    'output' => array('name')
                ));
                if (is_array($pgInfo)) {
                    foreach ($pgInfo as $pg) {
                        $proxy_group_names[] = $pg['name'];
                    }
                }
            }
            
            // Fetch items and triggers for this host
            $itemsInfo = api::item()->get(array(
                'hostids' => array($hostInfo['hostid']),
                'webitems' => 1,
                'preservekeys' => 1,
                'templated' => NULL,
                'output' => array('itemid','type','name','key_','delay','history','trends','status','state','description'),
                'selectTriggers' => array('description'),
                'sortfield' => 'name'
            ));
            
            $triggers = API::Trigger()->get(array(
                'output' => array('triggerid','description','expression','priority','status','state'),
                'filter' => array('hostid' => $hostInfo['hostid']),
                'searchByAny' => 1,
                'expandExpression' => true,
                'expandDescription' => true,
                'sortfield' => array('triggerid','description'),
                'sortorder' => "ASC"
            ));

            $severities = API::Settings()->get(array(
                'output' => array('severity_color_0','severity_color_1','severity_color_2','severity_color_3','severity_color_4','severity_color_5')
            ));
            
            // Store all data for this host
            $allHostsData[] = [
                'info' => $hostInfo,
                'items' => $itemsInfo,
                'triggers' => $triggers,
                'severities' => $severities,
                'proxy_names' => $proxy_names,
                'proxy_group_names' => $proxy_group_names
            ];
        } // End foreach - data collection
        
        // Now display everything in organized sections
?>
        
        <!-- EXPORT BUTTONS - Single set at top -->
        <div style="margin:20px 0; padding: 15px; background: #f0f0f0; border-radius: 4px;">
            <input type="hidden" id="exportGroupId" value="<?php echo htmlspecialchars($selectedGroupId); ?>">
            <input type="hidden" id="exportHostId" value="<?php echo $selectedHostId ? htmlspecialchars($selectedHostId) : ''; ?>">
            <h3 style="margin: 0 0 10px 0;">Export Configuration Data</h3>
            <button type="button" onclick="downloadHostConfig('csv')" style="margin-right: 10px;">Download CSV</button>
            <button type="button" onclick="downloadHostConfig('html')" style="margin-right: 10px;">Download HTML</button>
            <button type="button" onclick="downloadHostConfig('json')">Download JSON</button>
        </div>
        
        <!-- GENERAL INFORMATION - Consolidated table for all hosts -->
        <div style="margin: 20px 0;">
            <h2 style="border-bottom: 2px solid #007bff; padding-bottom: 10px;">General Information - All Hosts</h2>
            <table class="source-tableeditor" id="general-info-table" style="width: 100%; margin-top: 15px;">
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
<?php 
        foreach ($allHostsData as $hostData) { 
            $host = $hostData['info'];
?>
                    <tr>
                        <td><?php echo htmlspecialchars($host['host']); ?></td>
                        <td><?php echo htmlspecialchars($host['name']); ?></td>
                        <td><?php echo $map_status[$host['status']]; ?></td>
                        <td><?php echo $map_maintenance_status[$host['maintenance_status']]; ?></td>
                        <td><?php echo !empty($hostData['proxy_names']) ? htmlspecialchars(implode(', ', $hostData['proxy_names'])) : 'N/A'; ?></td>
                        <td><?php echo !empty($hostData['proxy_group_names']) ? htmlspecialchars(implode(', ', $hostData['proxy_group_names'])) : 'None'; ?></td>
                        <td>
<?php 
            $interfaces = [];
            foreach($host['interfaces'] as $interface) {
                $intStr = '';
                if ($interface['ip']) $intStr .= $interface['ip'];
                if ($interface['ip'] && $interface['dns']) $intStr .= ' || ';
                if ($interface['dns']) $intStr .= $interface['dns'];
                if ($intStr) $interfaces[] = $intStr;
            }
            echo htmlspecialchars(implode(', ', $interfaces));
?>
                        </td>
                        <td>
<?php 
            $groups = array_column($host['hostgroups'], 'name');
            echo htmlspecialchars(implode(', ', $groups));
?>
                        </td>
                        <td>
<?php 
            $templates = array_column($host['parentTemplates'], 'name');
            echo htmlspecialchars(implode(', ', $templates));
?>
                        </td>
                    </tr>
<?php 
        } // End foreach for general info table
?>
                </tbody>
            </table>
        </div>
        
        <script>
        $(document).ready(function() {
            $('#general-info-table').DataTable({
                paging: false,
                searching: true,
                ordering: true,
                info: false
            });
        });
        </script>
        
        <!-- HOST-SPECIFIC SECTIONS - Macros, Inventory, Items, Triggers per host -->
<?php 
        foreach ($allHostsData as $index => $hostData) { 
            $host = $hostData['info'];
            $hostid = $host['hostid'];
            $itemsInfo = $hostData['items'];
            $triggers = $hostData['triggers'];
            $severities = $hostData['severities'];
            
            $inventory_labels = [
                'type'       => 'Environment',
                'type_full'  => 'Product',
                'name'       => 'Host Name',
                'os'         => 'OS',
                'contact'    => 'Customer'
            ];

            $inventory_order = ['name', 'contact', 'type_full', 'type', 'os'];
            $inventory_raw = $host['inventory'];
            $inventory_formatted = [];

            foreach ($inventory_order as $key) {
                if (isset($inventory_raw[$key])) {
                    $label = isset($inventory_labels[$key]) ? $inventory_labels[$key] : $key;
                    $inventory_formatted[$label] = $inventory_raw[$key];
                }
            }
?>
        
        <div style="margin: 40px 0; padding: 20px; background: #f9f9f9; border-left: 4px solid #007bff;">
            <h2 style="margin-top: 0; color: #007bff;">
                <?php echo htmlspecialchars($host['name']); ?> 
                <span style="font-size: 14px; color: #666;">(<?php echo htmlspecialchars($host['host']); ?>)</span>
            </h2>
            
            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <!-- Macros -->
                <div style="flex: 1;">
                    <details open="true">
                        <summary><h3 style="display: inline-block; margin: 0;">Macros</h3></summary>
                        <table id="macros_<?php echo $hostid;?>" class="display list-table" style="width: 100%; margin-top: 10px;">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Value</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
<?php foreach($host['macros'] as $macro) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($macro['macro']); ?></td>
                                    <td><?php echo isset($macro['value']) ? htmlspecialchars($macro['value']) : "*** secret ***"; ?></td>
                                    <td><?php echo htmlspecialchars($macro['description']); ?></td>
                                </tr>
<?php } ?>
                            </tbody>
                        </table>
                    </details>
                </div>
                
                <!-- Inventory -->
                <div style="flex: 1;">
                    <details open="true">
                        <summary><h3 style="display: inline-block; margin: 0;">Inventory</h3></summary>
                        <table class="source-tableeditor" style="width: 100%; margin-top: 10px;">
                            <tbody>
                            <?php echo build_table($inventory_formatted); ?>
                            </tbody>
                        </table>
                    </details>
                </div>
                
                <!-- Tags -->
                <div style="flex: 1;">
                    <details open="true">
                        <summary><h3 style="display: inline-block; margin: 0;">Tags</h3></summary>
                        <table class="source-tableeditor" style="width: 100%; margin-top: 10px;">
                            <thead>
                                <tr>
                                    <th>Tag</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
<?php if (!empty($host['tags'])) { ?>
<?php foreach($host['tags'] as $tag) { ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($tag['tag']); ?></td>
                                        <td><?php echo htmlspecialchars($tag['value']); ?></td>
                                    </tr>
<?php } ?>
<?php } else { ?>
                                <tr><td colspan="2" style="text-align: center; color: #999;">No tags</td></tr>
<?php } ?>
                            </tbody>
                        </table>
                    </details>
                </div>
            </div>
            
            <!-- Items -->
            <div style="margin: 20px 0;">
                <details>
                    <summary><h3 style="display: inline-block; margin: 0;">Items (<?php echo count($itemsInfo); ?>)</h3></summary>
                    <table id="items_<?php echo $hostid;?>" class="display" style="width: 100%; margin-top: 10px;">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Key</th>
                                <th>Interval</th>
                                <th>History</th>
                                <th>Trends</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>State</th>
                                <th>Triggers</th>
                            </tr>
                        </thead>
                        <tbody>
<?php foreach($itemsInfo as $itemInfo) { ?>
                            <tr>
                                <td>
                                    <a target='_blank' href='items.php?context=host&form=update&itemid=<?php echo $itemInfo['itemid'];?>&hostid=<?php echo $hostid;?>'>
                                        <?php echo htmlspecialchars($itemInfo['name']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($itemInfo['key_']); ?></td>
                                <td><?php echo htmlspecialchars($itemInfo['delay']); ?></td>
                                <td><?php echo htmlspecialchars($itemInfo['history']); ?></td>
                                <td><?php echo htmlspecialchars($itemInfo['trends']); ?></td>
                                <td><?php echo htmlspecialchars($itemInfo['description']); ?></td>
                                <td><?php echo $map_item_status[$itemInfo['status']]; ?></td>
                                <td><?php echo $map_item_state[$itemInfo['state']]; ?></td>
                                <td>
                                    <a title="<?php foreach($itemInfo['triggers'] as $trigger){ echo htmlspecialchars($trigger['description'])."\r\n";} ?>">
                                        <?php echo count($itemInfo['triggers']); ?>
                                    </a>
                                </td>
                            </tr>
<?php } ?>
                        </tbody>
                    </table>
                </details>
            </div>
            
            <!-- Triggers -->
            <div style="margin: 20px 0;">
                <details>
                    <summary><h3 style="display: inline-block; margin: 0;">Triggers (<?php echo count($triggers); ?>)</h3></summary>
                    <table id="triggers_<?php echo $hostid;?>" class="display" style="width: 100%; margin-top: 10px;">
                        <thead>
                            <tr>
                                <th>Trigger Name</th>
                                <th>Severity</th>
                                <th>Expression</th>
                                <th>Status</th>
                                <th>State</th>
                            </tr>
                        </thead>
                        <tbody>
<?php foreach($triggers as $trigger) { ?>
                            <tr>
                                <td>
                                    <a target='_blank' href='triggers.php?context=host&form=update&triggerid=<?php echo $trigger['triggerid'];?>'>
                                        <?php echo htmlspecialchars($trigger['description']); ?>
                                    </a>
                                </td>
                                <td style='background-color: #<?php echo $severities["severity_color_".$trigger['priority']]."80";?>'>
                                    <?php echo $map_trigger_priority[$trigger['priority']]; ?>
                                </td>
                                <td style='background-color: #<?php echo $severities["severity_color_".$trigger['priority']]."80";?>'>
                                    <?php echo htmlspecialchars($trigger['expression']); ?>
                                </td>
                                <td><?php echo $map_trigger_status[$trigger['status']]; ?></td>
                                <td><?php echo $map_trigger_state[$trigger['state']]; ?></td>
                            </tr>
<?php } ?>
                        </tbody>
                    </table>
                </details>
            </div>
        </div>
        
<?php 
        } // End foreach host display 
?>
        
        <script>
        // Initialize DataTables for all host-specific tables
        $(document).ready(function() {
            $('table[id^="macros_"], table[id^="items_"], table[id^="triggers_"]').each(function() {
                $(this).DataTable({
                    paging: true,
                    pageLength: 25,
                    searching: true,
                    ordering: true
                });
            });
        });
        </script>
        
<?php
    } // End if hostsToProcess not empty
} // End if isset($_POST['hostgroup'])
?>
		</div>
	</form>
</main>

<?php
require_once './include/page_footer.php';
?>
