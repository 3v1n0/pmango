<?php
/**
 -------------------------------------------------------------------------------------------

 PMango Project

 Title:      reports page.

 File:       view.php
 Location:   PMango/modules/report
 Started:    2007.05.08
 Author:     Riccardo Nicolini
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://penelope.di.unipi.it

 Version history.
 - 2010.02.23 Marco Trevisan
   Multiple graph views support
 - 2010.02.08 Marco Trevisan
   AJAX forms, code cleanup.
 - 2007.10.20 Marco
   Now the report's pages it's opened in a new windows.
 - 2007.05.08 Riccardo
   First version, created to manage .pdf files generation.

 -------------------------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2006 Giovanni A. Cignoni, Lorenzo Ballini, Marco Bonacchi, Riccardo Nicolini
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
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation Inc., 51 Franklin St, 5th Floor, Boston, MA 02110-1301 USA.

 -------------------------------------------------------------------------------------------
 */
include $AppUI->getModuleFile('report', 'makePDF');

$project_id = intval( dPgetParam( $_GET, "project_id", 0 ) );
$projects = $AppUI->getState('Projects');

$q  = new DBQuery;
$q->addTable('projects');
$q->addQuery('projects.project_id, project_color_identifier, project_name, project_description,
	project_start_date, project_finish_date');
$q->addWhere("projects.project_id = '$project_id'");

$project = $q->loadList();
$q  = new DBQuery;
$q->addTable('groups');
$q->addTable('projects');
$q->addQuery('groups.group_name');
$q->addWhere("projects.project_group = groups.group_id and projects.project_id = '$project_id'");

$group = $q->loadList();

foreach ($project as $p){
	$p_color=$p['project_color_identifier'];
	$name=$p['project_name'];
}

foreach ($group as $g){
	$group_name=$g['group_name'];
}

$canRead = !getDenyRead( $m );
$canEdit = !getDenyEdit( $m );

if (!$canRead) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

$AppUI->savePlace();

if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'ReportIdxTab', $_GET['tab'] );
}

$AppUI->setState( 'report_page', dPgetParam($_POST, 'page', 'P') );
$AppUI->setState( 'report_color', dPgetParam($_POST, 'color', 1) );
$AppUI->setState( 'report_border', dPgetParam($_POST, 'border', 1) );

$page = $AppUI->getState( 'report_page');
$color = $AppUI->getState( 'report_color' );
$border = $AppUI->getState( 'report_border' );

$tab = $AppUI->getState( 'ReportIdxTab' ) !== NULL ? $AppUI->getState( 'ReportIdxTab' ) : 0;
$active = intval( !$AppUI->getState( 'ReportIdxTab' ) );

$titleBlock = new CTitleBlock( 'Project Reports', 'applet-report.png', $m, "$m.$a" );
$titleBlock->addCrumb( "?m=projects&a=view&project_id=$project_id", "View project" );
$titleBlock->addCell();

if (!empty($_POST) && dPgetBoolParam($_POST, 'make_report_pdf')) {
	setProjectSubState('ReportOptions', 'add_properties', dPgetBoolParam($_POST, 'add_properties'));
	setProjectSubState('ReportOptions', 'add_planned', dPgetBoolParam($_POST, 'add_planned'));
	setProjectSubState('ReportOptions', 'add_actual', dPgetBoolParam($_POST, 'add_actual'));
	setProjectSubState('ReportOptions', 'add_log', dPgetBoolParam($_POST, 'add_log'));
	setProjectSubState('ReportOptions', 'add_gantt', dPgetBoolParam($_POST, 'add_gantt'));
	setProjectSubState('ReportOptions', 'add_wbs', dPgetBoolParam($_POST, 'add_wbs'));
	setProjectSubState('ReportOptions', 'add_tasknetwork', dPgetBoolParam($_POST, 'add_tasknetwork'));
}

$add_properties = getProjectSubState('ReportOptions', 'add_properties', false);
$add_planned = getProjectSubState('ReportOptions', 'add_planned', false);
$add_actual = getProjectSubState('ReportOptions', 'add_actual', false);
$add_log = getProjectSubState('ReportOptions', 'add_log', false);

$add_gantt = getProjectSubState('ReportOptions', 'add_gantt', false);
$add_wbs = getProjectSubState('ReportOptions', 'add_wbs', false);
$add_tasknetwork = getProjectSubState('ReportOptions', 'add_tasknetwork', false);

$titleBlock->show();

GLOBAL $AppUI, $canRead, $canEdit, $m;

if (!$canRead) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

$user_id = $AppUI->user_id;

CReport::initUserReport($project_id);

if (dPgetParam($_GET, 'reset', false)) {
	$reset = true;

	switch($_GET['reset']) {
		case 'actual':
			CReport::usetTaskActual($project_id);
			break;
		case 'planned':
			CReport::usetTaskPlanned($project_id);
			break;
		case 'properties':
			CReport::unsetProperties($project_id);
			break;
		case 'log':
			CReport::unsetLog($project_id);
			break;
		case 'gantt':
			CReport::unsetGantt($project_id);
			break;
		case 'wbs':
			CReport::unsetWbs($project_id);
			break;
		case 'task_network':
			CReport::unsetTaskNetwork($project_id);
			break;
		default:
			$reset = false;
	}

	if ($reset)
	unsetProjectSubState('PDFReports', PMPDF_REPORT);

	$AppUI->redirect("m=$m&a=$a&project_id=$project_id");
}


$sql="SELECT p_report_sdate, a_report_sdate, l_report_sdate, properties, gantt, wbs, task_network FROM reports WHERE reports.project_id=".$project_id." AND user_id=".$user_id;
$disable_report = db_loadList($sql);

$report_items = 7;
$item = 0;
?>

<script
	type="text/javascript" src="./modules/projects/view.js"></script>
<script language="javascript">
var state = 'hidden';

function showhide(layer_ref) {
	if (state == '') {
		state = 'none';
	}
	else {
		state = '';
	}
	if (document.all) { //IS IE 4 or 5 (or 6 beta)
		eval( "document.all." + layer_ref + ".style.display = state");
	}
	if (document.layers) { //IS NETSCAPE 4 or below
		document.layers[layer_ref].display = state;
	}
	if (document.getElementById && !document.all) {
		maxwell_smart = document.getElementById(layer_ref);
		maxwell_smart.style.display = state;
	}
}

var selectors_values = [];

function selectorSwitch(me) {
	var selectors = $("select.report_page_selector");
	var equal_sel;
	var my_id;
	
	selectors.each(function(i) {
		if (this == me)
			my_id = i;
		
		if ($(this).val() == $(me).val() && this != me) {
			equal_sel = $(this);
		}
	});

	equal_sel.val(selectors_values[my_id]);
	updateSelectorsState();
}

function makeReportPDF() {
	if ($("form[name=make_pdf_form] input[id^=add_]:checked").length == 0) {
		alert("Please, select a Report.");
		return;
	}

	document.make_pdf_form.make_report_pdf.value = "true";
	document.make_pdf_form.load_image.value = "false";
	document.make_pdf_form.delete_image.value = "false";

	generatePDF('make_pdf_form', 'report_pdf_span');

	document.make_pdf_form.make_report_pdf.value = "false";
}

function loadGroupImage() {
	document.make_pdf_form.load_image.value = "true";
	document.make_pdf_form.make_report_pdf.value = "false";
	document.make_pdf_form.delete_image.value = "false";

	document.make_pdf_form.submit();

	document.make_pdf_form.load_image.value = "false";
}

function deleteGroupImage() {
	document.make_pdf_form.delete_image.value = "true";
	document.make_pdf_form.load_image.value = "false";
	document.make_pdf_form.make_report_pdf.value = "false";

	document.make_pdf_form.submit();

	document.make_pdf_form.delete_image.value = "false";
}

function updateSelectorsState() {
	$(function() {
		$("select.report_page_selector").each(function(i) {
			selectors_values[i] = $(this).val();
		});
	});
}

updateSelectorsState();

</script>

<form id="make_pdf_form" name='make_pdf_form' method='POST'
	action=<? echo '?m=report&a=view&project_id='.$project_id;?>
	enctype="multipart/form-data">
<table border="0" cellpadding="1" cellspacing="0" width="100%"
	class="std">
	<tr>
		<td>
		<table border='0' cellpadding='1' cellspacing='0' width='100%'>
			<tr style="border: outset #d1d1cd 1px;background-color:#<?php echo $p_color;?>" >
				<td nowrap='nowrap' colspan='2'><?php
				echo '<font color="' . bestColor( $p_color ) . '"><strong>'. $name .'<strong></font>';
				?></td>
				<td colspan='2'></td>
				<td nowrap='nowrap' align="center"><?echo '<font color="'. bestColor( $p_color ) . '">'.$AppUI->_('Append Order').'</font>';?>&nbsp;
				</td>
				<td nowrap='nowrap' align="center"><?echo '<font color="'. bestColor( $p_color ) . '">'.$AppUI->_('New Page').'</font>';?>
				</td>
			</tr>
			<tr>
				<td colspan='6'><br>
				</td>
			</tr>
			<tr>
				<td nowrap='nowrap' align="left"><input id="add_properties"
					type="checkbox" name="add_properties"
					<?echo ($add_properties)?"checked":"";echo ($disable_report[0]['properties'])?"":"disabled";?>>
				</td>
				<td nowrap="nowrap"><label for="add_properties"> <strong><?php echo $AppUI->_( 'Project Properties' );?></strong>
				</label></td>
				<td width='100%'></td>
				<td nowrap='nowrap' align='left'><?php echo "<a href='./index.php?m=report&a=view&reset=properties&project_id=$project_id'>".$AppUI->_('Reset')."</a>";?>&nbsp;&nbsp;&nbsp;
				<?php echo "<a href='./index.php?m=projects&a=view&tab=0&project_id=$project_id'>".$AppUI->_('Modify')."</a>";?>&nbsp;&nbsp;&nbsp;
				</td>
				<td nowrap="nowrap" align="center"><select
					class="report_page_selector" name="append_order_a" class="text"
					onchange="selectorSwitch(this);">
					<?
					$order = $_POST['append_order_a'];
					if (!$order) $order = ++$item;

					for ($i = 1; $i <= $report_items; $i++) {
						?>
					<option value="<? echo $i ?>"
					<?echo ($i == $order)? "selected":""?>><? echo $i ?></option>
					<?
					}
					?>
				</select></td>
				<td nowrap="nowrap" align="center"><input type="checkbox"
					name="new_page_a" <?echo ($_POST['new_page_a'])?"checked":"";?>></td>
			</tr>
			<tr>
				<td nowrap='nowrap'></td>
				<td nowrap='nowrap'><? $task_properties = CReport::getProjectReport($project_id); ?>
				</td>
				<td nowrap='nowrap' colspan='4' width='100%'></td>
			</tr>
			<tr>
				<td colspan='6'><br>
				</td>
			</tr>
			<tr>
				<td nowrap='nowrap' align="left"
					style="border-top: outset #d1d1cd 1px"><input id="add_planned"
					type="checkbox" name="add_planned"
					<?echo ($add_planned)?"checked":"";echo ($disable_report[0]['p_report_sdate'])?"":"disabled";?>>
				</td>
				<td nowrap="nowrap" style="border-top: outset #d1d1cd 1px"><label
					for="add_planned"> <strong><?echo $AppUI->_( 'Planned Tasks' );?></strong>
				</label></td>
				<td width='100%' style="border-top: outset #d1d1cd 1px">&nbsp;</td>
				<td nowrap='nowrap' align='left'
					style="border-top: outset #d1d1cd 1px"><?php echo "<a href='./index.php?m=report&a=view&reset=planned&project_id=$project_id'>".$AppUI->_('Reset')."</a>";?>&nbsp;&nbsp;&nbsp;
					<?php echo "<a href='./index.php?m=projects&a=view&tab=0&project_id=$project_id'>".$AppUI->_('Modify')."</a>";?>&nbsp;&nbsp;&nbsp;
				</td>
				<td nowrap="nowrap" align="center"
					style="border-top: outset #d1d1cd 1px"><select
					class="report_page_selector" name="append_order_b" class="text"
					onchange="selectorSwitch(this);">
					<?
					$order = $_POST['append_order_b'];
					if (!$order) $order = ++$item;

					for ($i = 1; $i <= $report_items; $i++) {
						?>
					<option value="<? echo $i ?>"
					<?echo ($i == $order)? "selected":""?>><? echo $i ?></option>
					<?
					}
					?>
				</select></td>
				<td nowrap="nowrap" align="center"
					style="border-top: outset #d1d1cd 1px"><input type="checkbox"
					name="new_page_b" <?echo ($_POST['new_page_b'])?"checked":"";?>></td>
			</tr>
			<tr>
				<td nowrap='nowrap'></td>
				<td nowrap='nowrap'><? $task_planned = CReport::getTaskReport($project_id, PMPDF_PLANNED); ?>
				</td>
				<td nowrap='nowrap' colspan="4"></td>
			</tr>
			<tr>
				<td colspan='6'><br>
				</td>
			</tr>
			<tr>
				<td nowrap='nowrap' align="left"
					style="border-top: outset #d1d1cd 1px"><input id="add_actual"
					type="checkbox" name="add_actual"
					<?echo ($add_actual)?"checked":"";echo ($disable_report[0]['a_report_sdate'])?"":"disabled";?>>
				</td>
				<td nowrap="nowrap" style="border-top: outset #d1d1cd 1px"><label
					for="add_actual"> <strong><?echo $AppUI->_( 'Actual Tasks' );?></strong>
				</label></td>
				<td width="100%" style="border-top: outset #d1d1cd 1px">&nbsp;</td>
				<td nowrap="nowrap" align="left"
					style="border-top: outset #d1d1cd 1px"><?php echo "<a href='./index.php?m=report&a=view&reset=actual&project_id=$project_id'>".$AppUI->_('Reset')."</a>";?>&nbsp;&nbsp;&nbsp;
					<?php echo "<a href='./index.php?m=projects&a=view&tab=1&project_id=$project_id'>".$AppUI->_('Modify')."</a>";?>&nbsp;&nbsp;&nbsp;
				</td>
				<td nowrap="nowrap" align="center"
					style="border-top: outset #d1d1cd 1px"><select
					class="report_page_selector" name="append_order_c" class="text"
					onchange="selectorSwitch(this);">
					<?
					$order = $_POST['append_order_b'];
					if (!$order) $order = ++$item;

					for ($i = 1; $i <= $report_items; $i++) {
						?>
					<option value="<? echo $i ?>"
					<?echo ($i == $order)? "selected":""?>><? echo $i ?></option>
					<?
					}
					?>
				</select></td>
				<td nowrap='nowrap' align="center"
					style="border-top: outset #d1d1cd 1px"><input type="checkbox"
					name="new_page_c" <?echo ($_POST['new_page_c'])?"checked":"";?>></td>
			</tr>
			<tr>
				<td nowrap='nowrap'></td>
				<td nowrap='nowrap'><? $task_actual = CReport::getTaskReport($project_id, PMPDF_ACTUAL); ?>
				</td>
				<td nowrap='nowrap' colspan="4"></td>
			</tr>
			<tr>
				<td colspan='6'><br>
				</td>
			</tr>
			<tr>
				<td nowrap='nowrap' align="left"
					style="border-top: outset #d1d1cd 1px"><input id="add_log"
					type="checkbox" name="add_log"
					<?echo ($add_log)?"checked":"";echo ($disable_report[0]['l_report_sdate'])?"":"disabled";?>>
				</td>
				<td nowrap="nowrap" style="border-top: outset #d1d1cd 1px"><label
					for="add_log"> <strong><?php echo $AppUI->_( 'Task Logs' );?></strong>
				</label></td>
				<td width="100%" style="border-top: outset #d1d1cd 1px">&nbsp;</td>
				<td nowrap="nowrap" align="left"
					style="border-top: outset #d1d1cd 1px"><?php echo "<a href='./index.php?m=report&a=view&reset=log&project_id=$project_id'>".$AppUI->_('Reset')."</a>";?>&nbsp;&nbsp;&nbsp;
					<?php echo "<a href='./index.php?m=projects&a=view&tab=5&project_id=$project_id'>".$AppUI->_('Modify')."</a>";?>&nbsp;&nbsp;&nbsp;
				</td>
				<td nowrap="nowrap" align="center"
					style="border-top: outset #d1d1cd 1px"><select
					class="report_page_selector" name="append_order_d" class="text"
					onchange="selectorSwitch(this);">
					<?
					$order = $_POST['append_order_d'];
					if (!$order) $order = ++$item;

					for ($i = 1; $i <= $report_items; $i++) {
						?>
					<option value="<? echo $i ?>"
					<?echo ($i == $order)? "selected":""?>><? echo $i ?></option>
					<?
					}
					?>
				</select></td>
				<td nowrap='nowrap' align="center"
					style="border-top: outset #d1d1cd 1px"><input type="checkbox"
					name="new_page_d" <?echo ($_POST['new_page_d'])?"checked":"";?>></td>
			</tr>
			<tr>
				<td nowrap='nowrap'></td>
				<td nowrap='nowrap'><? $task_log = CReport::getLogReport($project_id); ?>
				</td>
				<td nowrap='nowrap' colspan="4"></td>
			</tr>
			<tr>
				<td colspan='6'><br>
				</td>
			</tr>
			<tr>
				<td nowrap='nowrap' align="left"
					style="border-top: outset #d1d1cd 1px"><input id="add_gantt"
					type="checkbox" name="add_gantt"
					<?echo ($add_log)?"checked":"";echo ($disable_report[0]['gantt'])?"":"disabled";?>>
				</td>
				<td nowrap="nowrap" style="border-top: outset #d1d1cd 1px"><label
					for="add_gantt"> <strong><?php echo $AppUI->_( 'Gantt Chart' );?></strong>
				</label></td>
				<td width="100%" style="border-top: outset #d1d1cd 1px">&nbsp;</td>
				<td nowrap="nowrap" align="left"
					style="border-top: outset #d1d1cd 1px"><?php echo "<a href='./index.php?m=report&a=view&reset=gantt&project_id=$project_id'>".$AppUI->_('Reset')."</a>";?>&nbsp;&nbsp;&nbsp;
					<?php echo "<a href='./index.php?m=projects&a=view&tab=2&project_id=$project_id'>".$AppUI->_('Modify')."</a>";?>&nbsp;&nbsp;&nbsp;
				</td>
				<td nowrap="nowrap" align="center"
					style="border-top: outset #d1d1cd 1px"><select
					class="report_page_selector" name="append_order_e" class="text"
					onchange="selectorSwitch(this);">
					<?
					$order = $_POST['append_order_e'];
					if (!$order) $order = ++$item;

					for ($i = 1; $i <= $report_items; $i++) {
						?>
					<option value="<? echo $i ?>"
					<?echo ($i == $order)? "selected":""?>><? echo $i ?></option>
					<?
					}
					?>
				</select></td>
				<td nowrap='nowrap' align="center"
					style="border-top: outset #d1d1cd 1px"><input type="checkbox"
					name="new_page_e" checked="checked" disabled="disabled"></td>
			</tr>
			<tr>
				<td nowrap='nowrap'></td>
				<td nowrap='nowrap'><?
				$gantt_views = CReport::getGanttReport($project_id);
				foreach (@$gantt_views as $id => $gantt) {
					if (file_exists($gantt)) {
						?> <a href="<? echo $gantt ?>" target="_blank"> <img
					src="<? echo ($gantt_views['tb'][$id] ? $gantt_views['tb'][$id] : $gantt) ?>"
					height="100" width="100" /> </a> <?
					}
				}
				?></td>
				<td nowrap='nowrap' colspan="4"></td>
			</tr>
			<tr>
				<td colspan='6'><br>
				</td>
			</tr>

			<tr>
				<td nowrap='nowrap' align="left"
					style="border-top: outset #d1d1cd 1px"><input id="add_wbs"
					type="checkbox" name="add_wbs"
					<?echo ($add_log)?"checked":"";echo ($disable_report[0]['wbs'])?"":"disabled";?>>
				</td>
				<td nowrap="nowrap" style="border-top: outset #d1d1cd 1px"><label
					for="add_wbs"> <strong><?php echo $AppUI->_( 'WBS Chart' );?></strong>
				</label></td>
				<td width="100%" style="border-top: outset #d1d1cd 1px">&nbsp;</td>
				<td nowrap="nowrap" align="left"
					style="border-top: outset #d1d1cd 1px"><?php echo "<a href='./index.php?m=report&a=view&reset=wbs&project_id=$project_id'>".$AppUI->_('Reset')."</a>";?>&nbsp;&nbsp;&nbsp;
					<?php echo "<a href='./index.php?m=projects&a=view&tab=3&project_id=$project_id'>".$AppUI->_('Modify')."</a>";?>&nbsp;&nbsp;&nbsp;
				</td>
				<td nowrap="nowrap" align="center"
					style="border-top: outset #d1d1cd 1px"><select
					class="report_page_selector" name="append_order_f" class="text"
					onchange="selectorSwitch(this);">
					<?
					$order = $_POST['append_order_f'];
					if (!$order) $order = ++$item;

					for ($i = 1; $i <= $report_items; $i++) {
						?>
					<option value="<? echo $i ?>"
					<?echo ($i == $order)? "selected":""?>><? echo $i ?></option>
					<?
					}
					?>
				</select></td>
				<td nowrap='nowrap' align="center"
					style="border-top: outset #d1d1cd 1px"><input type="checkbox"
					name="new_page_f" checked="checked" disabled="disabled"></td>
			</tr>
			<tr>
				<td nowrap='nowrap'></td>
				<td nowrap='nowrap'><?
				$wbs_views = CReport::getWbsReport($project_id);
				foreach (@$wbs_views as $id => $wbs) {
					if (file_exists($wbs)) {
						?> <a href="<? echo $wbs ?>" target="_blank"> <img
					src="<? echo ($wbs_views['tb'][$id] ? $wbs_views['tb'][$id] : $wbs) ?>"
					height="100" width="100" /> </a> <?
					}
				}
				?></td>
				<td nowrap='nowrap' colspan="4"></td>
			</tr>
			<tr>
				<td colspan='6'><br>
				</td>
			</tr>

			<tr>
				<td nowrap='nowrap' align="left"
					style="border-top: outset #d1d1cd 1px"><input id="add_tasknetwork"
					type="checkbox" name="add_tasknetwork"
					<?echo ($add_log)?"checked":"";echo ($disable_report[0]['task_network'])?"":"disabled";?>>
				</td>
				<td nowrap="nowrap" style="border-top: outset #d1d1cd 1px"><label
					for="add_tasknetwork"> <strong><?php echo $AppUI->_( 'TaskNetwork Chart' );?></strong>
				</label></td>
				<td width="100%" style="border-top: outset #d1d1cd 1px">&nbsp;</td>
				<td nowrap="nowrap" align="left"
					style="border-top: outset #d1d1cd 1px"><?php echo "<a href='./index.php?m=report&a=view&reset=task_network&project_id=$project_id'>".$AppUI->_('Reset')."</a>";?>&nbsp;&nbsp;&nbsp;
					<?php echo "<a href='./index.php?m=projects&a=view&tab=4&project_id=$project_id'>".$AppUI->_('Modify')."</a>";?>&nbsp;&nbsp;&nbsp;
				</td>
				<td nowrap="nowrap" align="center"
					style="border-top: outset #d1d1cd 1px"><select
					class="report_page_selector" name="append_order_g" class="text"
					onchange="selectorSwitch(this);">
					<?
					$order = $_POST['append_order_g'];
					if (!$order) $order = ++$item;

					for ($i = 1; $i <= $report_items; $i++) {
						?>
					<option value="<? echo $i ?>"
					<?echo ($i == $order)? "selected":""?>><? echo $i ?></option>
					<?
					}
					?>
				</select></td>
				<td nowrap='nowrap' align="center"
					style="border-top: outset #d1d1cd 1px"><input type="checkbox"
					name="new_page_g" checked="checked" disabled="disabled" /></td>
			</tr>
			<tr>
				<td nowrap='nowrap'></td>
				<td nowrap='nowrap'><?
				$tasknetwork_views = CReport::getTaskNetworkReport($project_id);
				foreach (@$tasknetwork_views as $id => $tasknetwork) {
					if (file_exists($tasknetwork)) {
						?> <a href="<? echo $tasknetwork ?>" target="_blank"> <img
					src="<? echo ($tasknetwork_views['tb'][$id] ? $tasknetwork_views['tb'][$id] : $tasknetwork) ?>"
					height="100" width="100" /> </a> <?
					}
				}
				?></td>
				<td nowrap='nowrap' colspan="4"></td>
			</tr>
			<tr>
				<td colspan='6'><br>
				</td>
			</tr>
		</table>
		</td>
	</tr>

	<?php
	$image_path='modules/report/logos/';

	if(dPgetBoolParam($_POST, 'delete_image')) {
		if(file_exists($image_path.$project_id.'.gif')) unlink($image_path.$project_id.".gif");
		if(file_exists($image_path.$project_id.'.jpg')) unlink($image_path.$project_id.".jpg");
		if(file_exists($image_path.$project_id.'.png')) unlink($image_path.$project_id.".png");
	}

	do {
		if (is_uploaded_file($_FILES['image']['tmp_name'])) {
			// Ottengo le informazioni sull'immagine
			list($width, $height, $type, $attr) = getimagesize($_FILES['image']['tmp_name']);
			// Controllo che le dimensioni (in pixel)
			if (($width > 45) || ($height > 45)) {
				$mesg = "<p>Dimensioni non corrette!!</p>";
				break;
			}
			// Controllo che il file sia in uno dei formati GIF, JPG o PNG
			if (($type!=1) && ($type!=2) && ($type!=3)) {
				$mesg = "<p>Formato non corretto!!</p>";
				break;
			}
			switch($type){
				case 1: $ext='.gif';
				$img = imagecreatefromgif($_FILES['image']['tmp_name']);
				if(!imagejpeg($img, 'modules/report/logos/'.$project_id.'.jpg'))
				$mesg = "<p>Errore nel caricamento dell'immagine!!</p>";
				break;
				case 2: $ext='.jpg';
				if (!move_uploaded_file($_FILES['image']['tmp_name'], 'modules/report/logos/'.$project_id.$ext))
				$mesg = "<p>Errore nel caricamento dell'immagine!!</p>";
				break;
				case 3: $ext='.png';
				$img = imagecreatefrompng($_FILES['image']['tmp_name']);
				if(!imagejpeg($img, 'modules/report/logos/'.$project_id.'.jpg'))
				$mesg = "<p>Errore nel caricamento dell'immagine!!</p>";
				break;
			}

		}
	} while (false);
	echo $mesg;

	if(file_exists($image_path.$project_id.'.jpg')) $image_file=$image_path.$project_id.'.jpg';
	else $image_file=$image_path.'nologo.gif';


	?>
	<tr>

		<td align="right" nowrap="nowrap"
			style="border-top: outset #d1d1cd 1px">

		<table border="0" cellpadding="3" cellspacing="1">
			<tr>
				<td align="left" rowspan="2" nowrap="nowrap"><span
					id="group_logo_span"> <img id="group_logo"
					src="<? echo $image_file;?>"> </span></td>
				<td align="left" nowrap="nowrap" colspan="2"><input name="image"
					type="file" size="18" /><br>
				</td>
				<td align="left" width="100%" nowrap="nowrap"></td>
				<td align="left" nowrap="nowrap"><input type="radio" name="page" id="page_p"
					value="P" <?echo ($page=="P")? "checked":""?>>
					<label for="page_p"><?php echo $AppUI->_('Portrait'); ?></label></td>
				<td align="left" nowrap="nowrap"></td>
				<td nowrap="nowrap"><input type="hidden" name="make_report_pdf"
					value="false"> <input type="button" class="button"
					value="<?php echo $AppUI->_( 'Make PDF' );?>"
					onclick='makeReportPDF();'></td>
			</tr>
			<tr>
				<td align="left" nowrap="nowrap"><input type="hidden"
					name="load_image" value="false"> <input class="button"
					name="upload" type="button" value="Load Image"
					onclick='loadGroupImage();' /></td>
				<td align="right" nowrap="nowrap"><input type="hidden"
					name="delete_image" value="false"> <input class="button"
					name="upload" type="button" value="Delete Image"
					onclick='deleteGroupImage();' /></td>
				<td align="left" width="100%" nowrap="nowrap"></td>
				<td align="left" nowrap="nowrap"><input type="radio" name="page"
					value="L" <?echo ($page=="L")? "checked":""?> id="page_l">
					<label for="page_l"><?php echo $AppUI->_('Landscape'); ?></label></td>
				<td align="left" nowrap="nowrap"></td>
				<td align='center' nowrap="nowrap"><?

				if (dPgetBoolParam($_POST, 'make_report_pdf') && !dPgetBoolParam($_POST, 'load_image')) {
					if($image_file==$image_path.'nologo.gif') $image_file='';
					$pdf = PM_headerPdf($name,$page,$border,$group_name,$image_file);
					$populated = false;

					for($k=1;$k<=$report_items;$k++) {

						if ($add_properties && ($_POST['append_order_a'] == $k)) {
							if ($task_properties){
								$populated = true;
								if (isset($_POST['new_page_a']))
									$pdf->AddPage($page);
									
								PM_makePropPdf($pdf, $project_id, $task_properties, $page);
								$pdf->Ln(8);
							} else $msg.="No Tasks Properties computed!  -  ";
						}

						if ($add_planned && ($_POST['append_order_b'] == $k)) {
							if ($task_planned) {
								$populated = true;
								if (isset($_POST['new_page_b']))
									$pdf->AddPage($page);

								$t = $task_planned;
								PM_makeTaskPdf($pdf, $project_id, PMPDF_PLANNED, $t['level'],
								$t['closed'], $t['opened'], $t['roles'],
								$t['start_date'], $t['end_date'],
								$t['show_incomplete'], $t['show_mine']);
								$pdf->Ln(8);
							} else $msg.="No Planned Tasks Report defined!  -  ";
						}

						if ($add_actual && ($_POST['append_order_c'] == $k)) {
							if ($task_actual) {
								$populated = true;
								if (isset($_POST['new_page_c']))
									$pdf->AddPage($page);
									
								$t = $task_actual;
								PM_makeTaskPdf($pdf, $project_id, PMPDF_ACTUAL, $t['level'],
								$t['closed'], $t['opened'], $t['roles'],
								$t['start_date'], $t['end_date'],
								$t['show_incomplete'], $t['show_mine']);
									
								$pdf->Ln(8);
							} else $msg.="No Actual Tasks Report defined!  -  ";
						}

						if ($add_log && ($_POST['append_order_d'] == $k)) {
							if ($task_log!=0) {
								$populated = true;
								if (isset($_POST['new_page_d']))
									$pdf->AddPage($page);

								PM_makeLogPdf($pdf, $project_id, $task_log['user'], $task_log['hide_inactive'],
								$task_log['hide_complete'], $task_log['start_date'], $task_log['end_date']);
								$pdf->Ln(8);
							} else $msg.="No Tasks Log Report defined!";
						}

						if ($add_gantt && ($_POST['append_order_e'] == $k)) {
							foreach ($gantt_views as $gantt) {
								if (file_exists($gantt)) {
									
									if ($populated)
										$pdf->AddPage($page);
									
									$populated = true;
									PM_makeImgPDF($pdf, $gantt, false, true, false);

								} else $msg.="No GANTT Report defined!";
							}
						}

						if ($add_wbs && ($_POST['append_order_f'] == $k)) {
							foreach ($wbs_views as $wbs) {
								if (file_exists($wbs)) {
									
									if ($populated)
										$pdf->AddPage($page);
										
									$populated = true;
									PM_makeImgPDF($pdf, $wbs, false, true, true);

								} else $msg.="No WBS Report defined!";
							}
						}

						if ($add_tasknetwork && ($_POST['append_order_g'] == $k)) {
							foreach ($tasknetwork_views as $tasknetwork) {
								if (file_exists($tasknetwork)) {
									
									if ($populated)
										$pdf->AddPage($page); //XXX use P for vertical view?
									
									$populated = true;
									PM_makeImgPDF($pdf, $tasknetwork, false, true, true);

								} else $msg.="No TaskNetwork Report defined!";
							}
						}
					}

					if ($populated == true) {
						$filename = PM_footerPdf($pdf, $name, PMPDF_REPORT);
						setProjectSubState('PDFReports', PMPDF_REPORT, $filename);
					} else {
						unset($pdf);
						unsetProjectSubState('PDFReports', PMPDF_REPORT);
					}

					if($msg!=null) $AppUI->setMsg($msg,UI_MSG_PROP_KO);
				}

				$pdf_file = getProjectSubState('PDFReports', PMPDF_REPORT);

				if (!file_exists($pdf_file))
				$pdf_file = setProjectSubState('PDFReports', PMPDF_REPORT, null);
				?> <span id="report_pdf_span" style="vertical-align: middle"> <?
				if ($pdf_file) {
					?> <a id="report_pdf_link" href="<?echo $pdf_file;?>"
					target="_blank"> <img id="report_pdf_icon"
					src="./modules/report/images/pdf_report.gif" alt="PDF Report"
					border="0"> </a> <?
}

?> </span></td>
			</tr>
		</table>
		</td>
	</tr>
</table>
</form>
