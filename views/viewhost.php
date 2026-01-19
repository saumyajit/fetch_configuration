<?php

#require_once './include/config.inc.php';
#require_once './include/hosts.inc.php';
#require_once './include/maintenances.inc.php';
#require_once './include/forms.inc.php';
#require_once './include/users.inc.php';

require_once './include/page_header.php';
#
foreach (glob("./include/classes/html/*.php") as $filename)
{
	require_once $filename;
//	echo $filename;
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
.host-search-wrapper button {
  font-family: "Trebuchet MS", Tahoma, Arial, sans-serif;
}

/* Maintenance Icon */
.icon-maintenance::before {
  content: "\26A0";        /* ⚠ symbol */
  margin-right: 6px;       /* space before text */
  font-weight: bold;
  color: #d4af37;          /* deep yellow / Zabbix-like */
  font-size: 18px;         /* adjust size as needed */
  vertical-align: middle;  /* align with text */
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

/* Specific Table Styling (Optional) */
#macros thead th {
  background-color: #e9eff6;
  color: #1a1a1a;
  font-weight: 600;
  font-size: 14px;
  border-bottom: 2px solid #d0d7e1;
  letter-spacing: 0.3px;
}

/* Main Content (Forms, Inputs, Buttons) */
main,
main label,
main input,
main button,
main h1 {
  font-family: "Trebuchet MS", Tahoma, Arial, sans-serif;
}

/* Buttons Hover Effect (optional) */
main button:hover {
  cursor: pointer;
  opacity: 0.9;
}

/* Even More Consistent Spacing */
main input, main button {
  padding: 4px 8px;
  font-size: 14px;
}
main button {
  display: inline-flex;          /* use flexbox for alignment */
  align-items: center;           /* vertically center content */
  justify-content: center;       /* horizontally center content */
  height: 32px;                  /* optional fixed height */
  padding: 4px 12px;             /* adjust padding for spacing */
  font-family: "Trebuchet MS", Tahoma, Arial, sans-serif;
  font-size: 14px;
  cursor: pointer;
  border: none;
  background-color: #0074cc;    /* example button color */
  color: white;
  border-radius: 3px;
  box-sizing: border-box;
}

</style>
<link href="./modules/get-host-ro/views/includes/css/jquery.dataTables.css" rel="stylesheet"/>
<script src="./modules/get-host-ro/views/includes/js/jquery.dataTables.js"></script>
<script>$(document).ready(function() {$('#macros').dataTable({paging: false})});</script>
<script>$(document).ready(function() {$('#inventory').dataTable({paging: false})});</script>
<script>$(document).ready(function() {$('#items').dataTable({paging: false})});</script>
<script>$(document).ready(function() {$('#triggers').dataTable({paging: false})});</script>

<script>
function downloadHostConfig(format) {
    var hostField = document.getElementById('exportHostId');
    if (!hostField || !hostField.value) {
        alert('Select a host first.');
        return;
    }

    var hostid = encodeURIComponent(hostField.value);

    // Use the same front controller as the page itself
    var base = 'zabbix.php';

    window.location.href =
        base + '?action=gethostro.view'
        + '&export=' + encodeURIComponent(format)
        + '&hostid=' + hostid;
}
</script>

<header class="header-title">
	<nav class="sidebar-nav-toggle" role="navigation" aria-label="Sidebar control">
		<button type="button" id="sidebar-button-toggle" class="button-toggle" title="Show sidebar">Show sidebar</button>
	</nav>
	<div>
		<h1 id="page-title-general" >View Host Configurations</h1>
	</div>
</header>
<main>
	<form method="post">
		<div id="tabs" class="table-forms-container ui-tabs ui-widget ui-widget-content ui-corner-all" style="visibility: visible;">
			<div id="maintenanceTab" aria-labelledby="tab_maintenanceTab" class="ui-tabs-panel ui-widget-content ui-corner-bottom" role="tabpanel" aria-expanded="true" aria-hidden="false">
				<ul class="table-forms" id="maintenanceFormList">
					<li>
						<div class="host-search-wrapper">
							<label class="form-label-asterisk" for="host">Host</label>
					
							<input list="host" type="text" name="host" size="50" autocomplete="off">
					
							<datalist id="host">
					<?php
					$hosts = api::host()->get(array(
						'output' => array('host','name'),
					));
					$arr_hosts = array();
					
					foreach ($hosts as $onehost) {
						$arr_hosts[] = $onehost['host'];
						if (!empty($onehost['name'])) {
							$arr_hosts[] = $onehost['name'];
						}
					}
					$arr_hosts = array_unique($arr_hosts);
					sort($arr_hosts);
					
					foreach ($arr_hosts as $name) {
					?>
								<option value="<?php echo $name; ?>"><?php echo $name; ?></option>
					<?php } ?>
							</datalist>
					
							<button type="submit" value="Search">Search Host</button>
						</div>
					</li>
				</ul>
			</div>
<?php
if (isset($_POST['host'])) {
	$host = api::host()->get(array(
                        'filter' => array('host' => $_POST['host'],'name' => $_POST['host']),
                        'output' => array('hostid'),
			'searchByAny' => 1
	));
	if (count($host) > 1  ){
		?>search with pattern "<?php echo $_REQUEST["host"]; ?>" has serverals results. Please check your search and <a href="zabbix.php?action=expandtriggermacro.view" title="Search again">retry</a>.<?php
	}
	elseif (count($host) == 0){
		?>search with pattern "<?php echo $_REQUEST["host"]; ?>" has no result. Please check your search and <a href="zabbix.php?action=gethostro.view" title="Search again">retry</a>.<?php
	} else
	{
		$hostInfo = api::host()->get(array(
			'filter' => array('hostid' => $host[0]['hostid']),
			//'searchbyany' => 1,
			'output' => array('hostid','host','name','status','description','proxyid','proxy_groupid','monitored_by','tls_connect','tls_accept','tls_issuer','tls_subject','flags','inventory_mode','maintenance_status'),
			'selectDiscoveryRule' => array('itemid','name','parent_hostid'),
			'selectHostGroups' => array('groupid','name'),
			'selectHostDiscovery' => array('parent_hostid','host'),
			'selectInterfaces' => array('interfaceid','type','main','available','error','details','ip','dns','port','useip'),
//			'selectInventory' => array('type','type_full','name','alias','os','os_full','os_short','serialno_a','serialno_b','tag','asset_tag','macaddress_a','macaddress_b','hardware,hardware_full','software,software_full','software_app_a','software_app_b','software_app_c','software_app_d','software_app_e','contact','location','location_lat','location_lon','notes','chassis','model','hw_arch','vendor','contract_number','installer_name','deployment_status','url_a','url_b','url_c','host_networks','host_netmask','host_router','oob_ip','oob_netmask','oob_router','date_hw_purchase','date_hw_install','date_hw_expiry','date_hw_decomm','site_address_a','site_address_b','site_address_c','site_city','site_state','site_country','site_zip','site_rack','site_notes','poc_1_name','poc_1_email','poc_1_phone_a','poc_1_phone_b','poc_1_cell','poc_1_screen','poc_1_notes','poc_2_name','poc_2_email','poc_2_phone_a','poc_2_phone_b','poc_2_cell','poc_2_screen','poc_2_notes'),
			'selectInventory' => array('type','type_full','name','os','contact'),
			'selectMacros' => array('hostmacroid','macro','value','description','type','automatic'),
			'selectParentTemplates' => array('templateid','name','link_type','uuid'),
			'selectTags' => array('tag','value','automatic')
		));
		
		$proxy_names       = [];
		$proxy_group_names = [];
		
		// monitored_by: 0 = Server, 1 = Proxy, 2 = Proxy group [web:37][web:44]
		$monitored_by = isset($hostInfo[0]['monitored_by']) ? (int)$hostInfo[0]['monitored_by'] : 0;
		
		// Case 1: monitored by single proxy
		if ($monitored_by === 1 && !empty($hostInfo[0]['proxyid']) && $hostInfo[0]['proxyid'] != '0') {
			$proxyInfo = api::proxy()->get(array(
				'proxyids' => array($hostInfo[0]['proxyid']),
				'output'   => array('name')   // proxy name field [web:38]
			));
		
			if (is_array($proxyInfo)) {
				foreach ($proxyInfo as $p) {
					$proxy_names[] = $p['name'];
				}
			}
		}
		
		// Case 2: monitored by proxy group
		if ($monitored_by === 2 && !empty($hostInfo[0]['proxy_groupid']) && $hostInfo[0]['proxy_groupid'] != '0') {
			$pgInfo = api::proxygroup()->get(array(
				'proxy_groupids' => array($hostInfo[0]['proxy_groupid']),
				'output'         => array('name')   // proxy group name field [web:41]
			));
		
			if (is_array($pgInfo)) {
				foreach ($pgInfo as $pg) {
					$proxy_group_names[] = $pg['name'];
				}
			}
		}

		// Host found, expose hostid for export buttons.
        $hostid = $hostInfo[0]['hostid'];
                ?>
                <div style="margin:10px 0;">
                    <input type="hidden" id="exportHostId" value="<?php echo htmlspecialchars($hostid); ?>">
                    <button type="button" onclick="downloadHostConfig('csv')">
                        Download CSV
                    </button>
                    <button type="button" onclick="downloadHostConfig('html')">
                        Download HTML
                    </button>
                    <button type="button" onclick="downloadHostConfig('json')">
                        Download JSON
                    </button>
                </div>
<?php
		$itemsInfo= api::item()->get(array(
			'hostids' => array($hostInfo[0]['hostid']),
			'webitems' => 1,
			'preservekeys' => 1,
			'templated' => NULL,
			'output' => array('itemid','type','name','key_','delay','history','trends','status','state','description'),
			'selectTriggers' => array('description'),
			'sortfield' => 'name'
		));
		  $triggers = API::Trigger()->get(array(
        	        'output' => array('triggerid','description','expression','priority','status','state'),
                	'filter' => array('hostid' => $host[0]['hostid']),
	                'searchByAny' => 1,
        	        'expandExpression' => true,
	                'expandDescription' => true,
	                'sortfield' =>  array('triggerid','description'),
	                'sortorder' => "ASC"
		        ));

        	$severities= API::Settings()->get(array(
                	'output' => array('severity_color_0','severity_color_1','severity_color_2','severity_color_3','severity_color_4','severity_color_5')
	        ));

?>
						<br/>
						<p>
						<div>
						<div style="padding: 1em; display: flex;">
							<div style="flex: 1;">
								<!--<details><pre><?php var_export($hostInfo[0]);?></pre></details>-->
								<h2>General Information</h2>
								<table class="source-tableeditor" style="padding: 10px">
									<tbody>
										<tr>
											<th>Host Name</th>
											<td><?php echo $hostInfo[0]['host'];?></td>
										</tr>
										<tr>
											<th>Display Name</th>
											<td><?php echo $hostInfo[0]['name'];?></td>
										</tr>
										<tr>
											<th>Status</th>
											<td><?php echo $map_status[$hostInfo[0]['status']];?></td>
										</tr>
										<tr>
											<th>Maintenance Status</th>
											<td><?php echo $map_maintenance_status[$hostInfo[0]['maintenance_status']];?></td>
										</tr>
										<tr>
											<th>Proxy</th>
											<td><?php echo !empty($proxy_names) ? htmlspecialchars(implode(', ', $proxy_names)) : 'N/A (Monitored by either Proxy Group or Server)'; ?></td>
										</tr>
										<tr>
											<th>Proxy group</th>
											<td><?php echo !empty($proxy_group_names) ? htmlspecialchars(implode(', ', $proxy_group_names)) : 'None'; ?></td>
										</tr>
										<tr>
											<th>Description</th>
											<td><?php echo $hostInfo[0]['description'];?></td>
										</tr>
										<tr>
											<th>Interfaces</th>
											<td>
												<ul>
<?php 
		foreach($hostInfo[0]['interfaces'] as $interface){
												echo "<li>";
												if ($interface['ip']) { echo $interface['ip'];}
							               	        		if (($interface['ip']) && ($interface['dns'])) {echo " || ";}
							                        		if ($interface['dns']) {echo $interface['dns'];}
												if (count($hostInfo[0]['interfaces']) > 1) {echo " ";}
												echo "</li>";
					}	
?>
												</ul>
											</td>
										</tr>
										<tr>
					                                       		<th>Groups</th>
	                                					        <td>
					        	                                        <ul>
<?php
                foreach($hostInfo[0]['hostgroups'] as $hostgroup){
												echo "<li>".$hostgroup['name']."<li>";
		}
?>
	   						        	                        </ul>						
					                               	        	</td>
										</tr>
<tr>
                                                                                        <th>Templates</th>
                                                                                        <td>
                                                                                                <ul>
<?php
                foreach($hostInfo[0]['parentTemplates'] as $template){
                                                                                                echo "<li><span data-hintbox=\"1\" data-hintbox-static=\"1\" data-hintbox-contents=\"".$template['uuid']."\">".$template['name']."</span><li>";
                }
?>
                                                                                                </ul>
                                                                                        </td>
                                                                                </tr>
									</tbody>
								</table>
							</div>
							<div style="flex: 1;padding-left: 1em;">
                                                                <details open="true">
									<summary><h2>Macros</h2></summary>
									<table id="macros" style="padding: 10px" class="display list-table">
										<thead>
											<tr>
      												<th>Name</th>
												<th>Value</th>
												<th>Description</th>
											</tr>
										</thead>
                                                                                <tbody>
<?php foreach($hostInfo[0]['macros'] as $macro){ ?>
                                                                                        <tr>
                                                                                                <td><?php echo $macro['macro'];?></td>
                                                                                                <td><?php echo isset($macro['value']) ? $macro['value'] : "*** secret field ***"; ?></td>
                                                                                                <td><?php echo $macro['description'];?></td>

                                                                                       </tr>
<?php } ?>
                                                                                </tbody>
									</table>
                                                                </details>
                                                        </div>
							<div style="flex: 1; padding-left: 1em;">
								<details open="true">
									<summary><h2>Inventory</h2></summary>
									<table id="inventory" class="source-tableeditor" style="padding: 10px">
										<thead>
											<th></th>
											<th></th>
										</thead>
										<tbody>
<?php

$inventory_labels = [
    'type'       => 'Environment',
    'type_full'  => 'Product',
    'name'       => 'Host Name',
    'os'         => 'OS',
    'contact'    => 'Customer'
];

$inventory_order = [
    'name',
    'contact',
    'type_full',
    'type',
    'os'
];

$inventory_raw = $hostInfo[0]['inventory'];
$inventory_formatted = [];

foreach ($inventory_order as $key) {
    if (isset($inventory_raw[$key])) {
        $label = isset($inventory_labels[$key]) ? $inventory_labels[$key] : $key;
        $inventory_formatted[$label] = $inventory_raw[$key];
    }
}

echo build_table($inventory_formatted);

?>
										</tbody>
									</table>
								</details>
							</div>
							</div>
							<div>
							<div style="flex: 1;padding:1em;" >
                                                                <details>
									<summary><h2>Items</h2></summary>
									<table id="items" class="source-tableeditor" style="padding: 10px">
										<thead>
											<th>Name</th>
											<th>Key</th>
											<th>Interval</th>
											<th>History</th>
											<th>Trends</th>
											<th>Description</th>
											<th>Status</th>
											<th>State</th>
											<th>Triggers Count</th>
										</thead>
										<tbody>
<?php foreach($itemsInfo as $itemInfo){ ?>
										<tr>
											<td>
<?php
												echo "<a target='_blank' href='items.php?context=host&form=update&itemid=" .$itemInfo['itemid']."&hostid=".$hostInfo[0]['hostid']."'> ".$itemInfo['name']."</a>";
?>
											</td>
                                                                                        <td><?php echo $itemInfo['key_'];?></td>
                                                                                        <td><?php echo $itemInfo['delay'];?></td>
                                                                                        <td><?php echo $itemInfo['history'];?></td>
                                                                                        <td><?php echo $itemInfo['trends'];?></td>
                                                                                        <td><?php echo $itemInfo['description'];?></td>
                                                                                        <td><?php echo $map_item_status[$itemInfo['status']];?></td>
											<td><?php echo $map_item_state[$itemInfo['state']];?></td>
											<td>
												<a title="<?php foreach($itemInfo['triggers'] as $trigger){ echo $trigger['description']."\r\n";} ?>">
												<?php echo count($itemInfo['triggers']);?>
												</a>
											</td>
										</tr>
<?php } ?>
                                                                                </tbody>
									</table>
                                                                </details>
                                                        </div>
							<div style="flex: 1;padding: 1em;">
                                                                <details>
                                                                        <summary><h2>Triggers</h2></summary>
									<table id="triggers" class="source-tableeditor" style="padding: 10px">
										<thead>
											<tr>
									                        <th>Trigger name</th>
									                        <th>Severity</th>
									                        <th>Trigger with expand expression</th>
															<th>Status</th>
															<th>State</th>
                									</tr>
										</thead>
										<tbody>
                <?php

                foreach($triggers as $trigger){
                        echo "<tr>";
                        echo "<td><a target='_blank' href='triggers.php?context=host&form=update&triggerid=" .$trigger['triggerid']. "'> ".$trigger['description'] ."</a></td>";
                        echo "<td style='background-color: #".$severities["severity_color_$trigger[priority]"]."80'>". $map_trigger_priority[$trigger['priority']]."</td>";
                        echo "<td style='background-color: #".$severities["severity_color_$trigger[priority]"]."80'>". $trigger['expression']."</td>";
						echo "<td>".$map_trigger_status[$itemInfo['status']]."</td>";
						echo "<td>".$map_trigger_state[$itemInfo['state']]."</td>";
                        echo "</tr>";
                }
?>
        	                                              	                </tbody>
                                                                        </table>
                                                                </details>
							</div>
							</div>
							
						</div>
<?php
        }
}
?>
		</div>
	</form>
</main>
<?php
require_once './include/page_footer.php';
