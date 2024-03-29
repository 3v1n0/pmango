<?php
/**
---------------------------------------------------------------------------

 PMango Project

 Title:      view TaskNetwork.

 File:       viewtasknetwork.php
 Location:   pmango/modules/tasks
 Started:    2010.01.27
 Author:     Marco Trevisan
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2010.01.27
   0.5 use jquery and redesign the interface 
   0.3 first version

---------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2009-2010 Marco Trevisan (Treviño) <mail@3v1n0.net>
 All rights reserved.

 PMango reuses part of the code of dotProject 2.0.1: dotProject code is
 released under GNU GPL, further information at: http://www.dotproject.net
 Copyright (C) 2003-2005 The dotProject Development Team.

 Other libraries used by PMango are redistributed under their own license.
 See ReadMe.txt in the root folder for details.

 PMango is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation Inc., 51 Franklin St, 5th Floor, Boston, MA 02110-1301 USA.

---------------------------------------------------------------------------
*/

include "TaskNetwork.class.php";

global $m, $a, $tab;

$project_id = dPgetParam($_REQUEST, 'project_id', 0);
$tab = dPgetParam($_REQUEST, 'tab', 0);

if (!empty($_POST)) {
	$task_level = $AppUI->getState('Tasks', 'Explode');
	$new_task_level = dPgetParam($_POST, 'explode_tasks', '1');

	if ($task_level != $new_task_level) {
		$task_level = $new_task_level;
		setProjectSubState('Tasks', 'Explode', $task_level);
		setProjectSubState('Tasks', 'opened', array());
		setProjectSubState('Tasks', 'closed', array());
	}
}

$show_names     = dPgetBoolParam($_POST, 'show_names');
$show_progress  = dPgetBoolParam($_POST, 'show_progress');
$show_alerts    = dPgetBoolParam($_POST, 'show_alerts');
$show_p_data    = dPgetBoolParam($_POST, 'show_p_data');
$show_a_data    = dPgetBoolParam($_POST, 'show_a_data');
$show_p_res     = dPgetBoolParam($_POST, 'show_p_res');
$show_a_res     = dPgetBoolParam($_POST, 'show_a_res');
$show_p_time    = dPgetBoolParam($_POST, 'show_p_time');
$show_a_time    = dPgetBoolParam($_POST, 'show_a_time');
$show_vertical  = dPgetBoolParam($_POST, 'show_vertical');
$show_def_dep   = dPgetBoolParam($_POST, 'show_def_dep', true);
$show_dep       = dPgetBoolParam($_POST, 'show_dep', true);
$show_all_arrow = dPgetBoolParam($_POST, 'show_all_arrow');
$show_time_gaps = dPgetBoolParam($_POST, 'show_time_gaps');
$cr_path_index  = dPgetParam($_POST, 'cr_path_index', 0);

$graph_img_src = "?m=tasks&suppressHeaders=1&a=tasknetwork&project_id=$project_id".
                 "&names=".($show_names ? "true" : "false").
                 "&progress=".($show_progress ? "true" : "false").
                 "&alerts=".($show_alerts ? "true" : "false").
                 "&p_data=".($show_p_data ? "true" : "false").
                 "&a_data=".($show_a_data ? "true" : "false").
                 "&p_res=".($show_p_res ? "true" : "false").
                 "&a_res=".($show_a_res ? "true" : "false").
                 "&p_time=".($show_p_time ? "true" : "false").
                 "&a_time=".($show_a_time ? "true" : "false").
                 "&vertical=".($show_vertical ? "true" : "false").
                 "&def_dep=".($show_def_dep ? "true" : "false").
                 "&dep=".($show_dep ? "true" : "false").
                 "&all_arrow=".($show_all_arrow ? "true" : "false").
                 "&time_gaps=".($show_time_gaps ? "true" : "false").
                 "&cr_path=$cr_path_index";

?>

<script type="text/javascript" src="./modules/tasks/resizable_graph_viewer.js"></script>
<script type="text/javascript">

var projectID = <?php  echo $project_id ?>;
var loader = './style/default/images/loader.gif';
var graph_error = './style/default/images/graph_loading_error.png';

function resourceSelectSwap(actual) {
	if (actual) {
		document.tn_options.show_p_res.checked = false;
	} else {
		document.tn_options.show_a_res.checked = false;
	}
}

function buildGraphUrl() {
	var show_names = $("#show_names:checked").val();
	var show_alerts = $("#show_alerts:checked").val();
	var show_progress = $("#show_progress:checked").val();
	var show_p_data = $("#show_p_data:checked").val();
	var show_a_data = $("#show_a_data:checked").val();
	var show_p_res = $("#show_p_res:checked").val();
	var show_a_res = $("#show_a_res:checked").val();
	var show_p_time = $("#show_p_time:checked").val();
	var show_a_time = $("#show_a_time:checked").val();
	var show_vertical = $("#show_vertical").val();
	var show_def_dep = $("#show_def_dep:checked").val();
	var show_dep = $("#show_dep:checked").val();
	var show_all_arrow = $("#show_all_arrow:checked").val();
	var show_time_gaps = $("#show_time_gaps:checked").val();
	var cr_path_index = $("#cr_path_index").val();
	var explosion = $("#explode_tasks").val();
	
	var url = "?m=tasks&suppressHeaders=1&a=tasknetwork&project_id="+projectID+
		      "&names="+(show_names ? "true" : "false")+
		      "&progress="+(show_progress ? "true" : "false")+
		      "&alerts="+(show_alerts ? "true" : "false")+
		      "&p_data="+(show_p_data ? "true" : "false")+
		      "&a_data="+(show_a_data ? "true" : "false")+
		      "&p_res="+(show_p_res ? "true" : "false")+
		      "&a_res="+(show_a_res ? "true" : "false")+
		      "&p_time="+(show_p_time ? "true" : "false")+
		      "&a_time="+(show_a_time ? "true" : "false")+
		      "&vertical="+(show_vertical == 1 ? "true" : "false")+
	          "&def_dep="+(show_def_dep ? "true" : "false")+
	          "&dep="+(show_dep ? "true" : "false")+
	          "&all_arrow="+(show_all_arrow ? "true" : "false")+
	          "&time_gaps="+(show_time_gaps ? "true" : "false")+
	          "&cr_path="+cr_path_index+
	          "&explode_tasks="+explosion;
	
    return url;
}

function makeTNPDF() {
	document.tn_options.make_graph_pdf.value = "true";
	document.tn_options.add_graph_report.value = "false";
	generatePDF('tn_options', 'tn_pdf_span');
	document.tn_options.make_graph_pdf.value = "false";
}

function addTNReport() {
	document.tn_options.make_graph_pdf.value = "false"; 
	document.tn_options.add_graph_report.value = "true";
	addReport('tn_options', 'tasknetwork_report_btn');
	document.tn_options.add_graph_report.value = "false";
}

function doSubmit() {
//	document.tn_options.submit(); //TODO enable on old browsers 
	viewerLoadPlaceHolder("#task_network_graph", loader);
	viewerLoadGraph("#task_network_graph", buildGraphUrl(), graph_error);
	$("#tasknetwork_report_btn").fadeIn();
}

resizeItemToVisible("#task_network_graph", 0.95);
createResizableViewer("#resizable_task_network", "#task_network_graph");
viewerLoadPlaceHolder("#task_network_graph", loader);
viewerLoadGraph("#task_network_graph", '<?php  echo $graph_img_src; ?>', graph_error);
displayItemSwitchPreCallback = optionsSwitchCallBack;
displayItemSwitchPreCallbackARGS = {container: "#resizable_task_network",
								    graph: "#task_network_graph"}; 
</script>

<form id="tn_options" name="tn_options" method="post" action="?<?php echo "m=$m&a=$a&project_id=$project_id";?>">
	<div id='tab_settings_content' style="display: none;">
		<table border='0' cellpadding='1' cellspacing='3' align="center">
			<tr>
				<td class="tab_setting_group">
					<table border="0" cellspacing="0">
						<tr>
							<td class="tab_setting_title" rowspan="4"><?php echo $AppUI->_('Show');?>:</td>
							<td class="tab_setting_item">
								<input type='checkbox' id="show_names" name='show_names' <? echo $show_names ? 'checked="checked" ' : '' ?>/>
								<label for="show_names"><?php echo $AppUI->_('Task Names'); ?></label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='checkbox' id="show_alerts" name='show_alerts' <? echo $show_alerts ? 'checked="checked" ' : '' ?>/>
								<label for="show_alerts"><?php echo $AppUI->_('Alerts'); ?></label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='checkbox' id="show_progress" name='show_progress' <? echo $show_progress ? 'checked="checked" ' : '' ?>/>
								<label for="show_progress"><?php echo $AppUI->_('Progress'); ?></label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='checkbox' id="show_time_gaps" name='show_time_gaps' <? echo $show_time_gaps ? 'checked="checked" ' : '' ?>/>
								<label for="show_time_gaps"><?php echo $AppUI->_('Time Gaps'); ?></label>
							</td>
						</tr>
					</table>
				</td>
				<td class="tab_setting_group">
					<table border="0" cellspacing="0">
						<tr>
							<td class="tab_setting_item">
								<input type='checkbox' id="show_all_arrow" name='show_all_arrow' <? echo $show_all_arrow ? 'checked="checked" ' : '' ?>/>
								<label for="show_all_arrow"><?php echo $AppUI->_('All the Arrows'); ?></label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='checkbox' id="show_def_dep" name='show_def_dep' <? echo $show_def_dep ? 'checked="checked" ' : '' ?>/>
								<label for="show_def_dep"><?php echo $AppUI->_('Default Dependencies'); ?></label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='checkbox' id="show_dep" name='show_dep' <? echo $show_dep ? 'checked="checked" ' : '' ?>/>
								<label for="show_dep"><?php echo $AppUI->_('Dependencies'); ?></label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<select id=cr_path_index name="cr_path_index" class="text">
									<option value="0"<? echo $cr_path_index == 0 ? ' selected="selected"' : '' ?>>
										<? echo $AppUI->_('No Critical Path')?>
									</option>
<?
								for ($i = 1; $i <= 5; $i++) {
?>
									<option value="<? echo $i ?>"<? echo $cr_path_index == $i ? ' selected="selected"' : '' ?>>
										<? echo $AppUI->_('Critical path ')." ".$i ?>
									</option>
<?
								}
?>
								</select>
							</td>
						</tr>
					</table>
				</td>
				<td class="tab_setting_group">
					<table border="0" cellspacing="0">
						<tr>
							<td class="tab_setting_title" rowspan="3"><?php echo $AppUI->_('Planned Info'); ?>:</td>
							<td class="tab_setting_item">
								<input type='checkbox' id="show_p_data" name='show_p_data' <? echo $show_p_data ? 'checked="checked" ' : '' ?>/>
								<label for="show_p_data">
									<abbr title="<?php echo $AppUI->_('Duration, effort and cost'); ?>">
										<?php echo $AppUI->_('Data'); ?>
									</abbr>
								</label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='checkbox' id="show_p_res" name='show_p_res' onclick="resourceSelectSwap(false);" <? echo $show_p_res ? 'checked="checked" ' : '' ?>/>
								<label for="show_p_res">
									<abbr title="<?php echo $AppUI->_('Personal Effort, Person and Role'); ?>">
										<?php echo $AppUI->_('Resources'); ?>
									</abbr>
								</label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='checkbox' id="show_p_time" name='show_p_time' <? echo $show_p_time ? 'checked="checked" ' : '' ?>/>
								<label for="show_p_time">
									<abbr title="<?php echo $AppUI->_('Start and End dates'); ?>">
										<?php echo $AppUI->_('Timeframe'); ?>
									</abbr>
								</label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_title"><?php echo $AppUI->_('View Mode'); ?>:</td>
							<td class="tab_setting_item">
								<select id="show_vertical" name="show_vertical" class="text">
									<option value="0" <?php if(!$show_vertical) echo 'selected="selected"';?>><?php echo $AppUI->_('Horizontal View'); ?></option>
									<option value="1" <?php if($show_vertical) echo 'selected="selected"';?>><?php echo $AppUI->_('Vertical View'); ?></option>
								</select>
							</td>
						</tr>
					</table>
				</td>
				<td class="tab_setting_group">
					<table border="0" cellspacing="0">
						<tr>
							<td class="tab_setting_title" rowspan="3"><?php echo $AppUI->_('Actual Info'); ?>:</td>
							<td class="tab_setting_item">
								<input type='checkbox' id="show_a_data" name='show_a_data' <? echo $show_a_data ? 'checked="checked" ' : '' ?>/>
								<label for="show_a_data">
									<abbr title="<?php echo $AppUI->_('Duration, effort and cost'); ?>">
										<?php echo $AppUI->_('Data'); ?>
									</abbr>
								</label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='checkbox' id="show_a_res" name='show_a_res' onclick="resourceSelectSwap(true);" <? echo $show_a_res ? 'checked="checked" ' : '' ?>/>
								<label for="show_a_res">
									<abbr title="<?php echo $AppUI->_('Personal Effort, Person and Role'); ?>">
										<?php echo $AppUI->_('Resources'); ?>
									</abbr>
								</label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='checkbox' id="show_a_time" name='show_a_time' <? echo $show_a_time ? 'checked="checked" ' : '' ?>/>
								<label for="show_a_time">
									<abbr title="<?php echo $AppUI->_('Start and End dates'); ?>">
										<?php echo $AppUI->_('Timeframe'); ?>
									</abbr>
								</label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_title"><?php echo $AppUI->_('Explode Tasks'); ?>:</td>
							<td>&nbsp; <select id="explode_tasks" name="explode_tasks" class="text">
<?php
									$maxLevel = CTask::getLevel($project_id);
									$explodeTasks = getProjectSubState('Tasks', 'Explode', 1);
				
							 		for($i=1; $i <=$maxLevel;$i++){
											$arr2[$i-1] = "Level ".$i;
								    		$arr[$i-1] = $i;}
				
									for($i = 0; $i < count($arr); $i++){
								    	$selected = ($arr[$i] == $explodeTasks) ? 'selected="selected"' : '';
								     	echo "<option value=\"{$arr[$i]}\" {$selected}>{$arr2[$i]}</option>\n";
								 	}
?>
								</select>
							</td>
						</tr>
					</table>
				</td>
					
				<td align='left' valign="bottom" class="tab_setting_group">
					<table border="0" cellspacing="0">
						<tr>
							<td align="left">
								&nbsp; <input type="button" class="button" value="<?php echo $AppUI->_( 'Draw' );?>"  onclick='doSubmit();'>
							</td>
						</tr>
						<tr>
							<td align="left">
								&nbsp; <input type="button" class="button" value="<?php echo $AppUI->_( 'Done' );?>"  onclick='settingsTabToggle();'>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>

	<div id='tab_content'>
		<table id='' width='100%' border='0' cellpadding='1' cellspacing='0' style="border-top: solid transparent 2px;">
			<tr>
				<td align="right">
<?
					if (dPgetBoolParam($_POST, 'make_graph_pdf') ||
					    dPgetBoolParam($_POST, 'add_graph_report'))	{

					    ini_set('memory_limit', dPgetParam($dPconfig, 'reset_memory_limit', 8*1024*1024));

						$TN = new TaskNetwork($project_id);
						$TN->setOpenedTasks(getProjectSubState('Tasks', 'opened'));
						$TN->setClosedTasks(getProjectSubState('Tasks', 'closed'));
						$TN->setTaskLevel(getProjectSubState('Tasks', 'Explode'));
						$TN->showNames($show_names);
						$TN->showProgress($show_progress);
						$TN->showAlerts($show_alerts);
						$TN->showPlannedData($show_p_data);
						$TN->showActualData($show_a_data);
						$TN->showPlannedResources($show_p_res);
						$TN->showActualResources($show_a_res);
						$TN->showPlannedTimeframe($show_p_time);
						$TN->showActualTimeframe($show_a_time);
						$TN->showVertical($show_vertical);
						$TN->showDefaultDependencies($show_def_dep);
						$TN->showDependencies($show_dep);
						$TN->showAllArrow($show_all_arrow);
						$TN->showTimeGaps($show_time_gaps);
						$TN->setCriticalPathIndex($cr_path_index);
						
						if (dPgetBoolParam($_POST, 'make_graph_pdf')) {
	                    	generateGraphPDF($project_id, $TN);
						} else if (dPgetBoolParam($_POST, 'add_graph_report')) {
	                    	CReport::addGraphReport($project_id, $TN);
						}

	                    ini_restore('memory_limit');
				    }
					
                    $pdf = getProjectSubState('PDFReports', PMPDF_GRAPH_TN);
?>
					
					<span id="tn_pdf_span" style="vertical-align: middle">	
<?							
					if ($pdf && file_exists($pdf)) {
?>
						<a id="tn_pdf_link" href="<?echo $pdf;?>">
							<img id="tn_pdf_icon" src="./modules/report/images/pdf_report.gif" alt="PDF Report" border="0">
						</a>
<?
					}
?>
					</span>
	
	
					<input type="hidden" name="make_graph_pdf" value="false" />
					<input type="hidden" name="add_graph_report" value="false" />
					
					<input type="button" class="button" value="<?php echo $AppUI->_( 'Generate PDF' );?>" onclick='makeTNPDF();'>
					<input type="button" class="button" value="<?php echo $AppUI->_( 'Add to Report' );?>" onclick='addTNReport();' id="tasknetwork_report_btn">
					<input type="button" class="button" value="<?php echo $AppUI->_( 'Configure' );?>" onclick='settingsTabToggle();'>
				</td>
			</tr>
		</table>
	</div>
</form>

<br />
<table cellspacing="0" cellpadding="0" border="1" align="center" style="border-top-style: hidden;">
	<tr>
		<td>
<?php
if (db_loadResult( "SELECT COUNT(*) FROM tasks WHERE task_project=$project_id" )) {
?>
		<div id="resizable_task_network" class="resizable">
			<div id="task_network_graph" class="graph"></div>
		</div>
<?php
} else {
	echo $AppUI->_( "No tasks to display" );
}
?>
		</td>
	</tr>
</table>
<br />
