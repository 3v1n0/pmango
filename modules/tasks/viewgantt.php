<?php
/**
---------------------------------------------------------------------------

 PMango Project

 Title:      view Gantt.

 File:       viewgantt.php
 Location:   pmango\modules\tasks
 Started:    2005.09.30
 Author:     Lorenzo Ballini
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2010.01.27
   0.5 use jquery and code/interface redesign 
 - 2006.07.30 Lorenzo
   Second version, modified to view PMango Gantt.
 - 2006.07.30 Lorenzo
   First version, unmodified from dotProject 2.0.1.

---------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2006 Giovanni A. Cignoni, Lorenzo Ballini, Marco Bonacchi
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

include "PMgantt.class.php";

GLOBAL $min_view, $m, $a, $tab;

$min_view = defVal(@$min_view, false);
$project_id = defVal($_REQUEST['project_id'], 0);
$tab = dPgetParam($_REQUEST, 'tab', 0);

if (!empty($_POST) && !dPgetBoolParam($_POST, 'make_graph_pdf') && !dPgetBoolParam($_POST, 'add_graph_report')) {
	$task_level = getProjectSubState('Tasks', 'Explode');
	$new_task_level = dPgetParam($_POST, 'explode_tasks', '1');

	if ($task_level != $new_task_level) {
		$task_level = $new_task_level;
		setProjectSubState('Tasks', 'Explode', $task_level);
		setProjectSubState('Tasks', 'opened', array());
		setProjectSubState('Tasks', 'closed', array());
	}
}

$sql = "SELECT project_start_date, project_finish_date FROM projects WHERE project_id = $project_id";
$tmp = db_loadList($sql);
$project_dates = $tmp[0];

//if (!dPgetBoolParam($_POST, 'make_graph_pdf') && !dPgetBoolParam($_POST, 'add_graph_report')
// use session!

// sdate and edate passed as unix time stamps
$sdate = dPgetParam( $_POST, 'sdate', 0 );
$edate = dPgetParam( $_POST, 'edate', 0 );
$showLabels = dPgetParam( $_POST, 'showLabels', '0' );
//if set GantChart includes user labels as captions of every GantBar
if ($showLabels!='0') {
    $showLabels='1';
}

$show_names = dPgetParam($_POST, 'show_names', false);
if (empty($_POST)) $show_names = true;

$show_deps = dPgetBoolParam($_POST, 'show_dependencies');
$show_bw = dPgetBoolParam($_POST, 'show_bw');
$show_res = dPgetBoolParam($_POST, 'show_res');
$make_pdf = dPgetBoolParam($_REQUEST, 'make_gantt_pdf');

// months to scroll
$scroll_date = 1;

$display_option = dPgetParam( $_POST, 'display_option', 'all' );

// format dates
$df = $AppUI->getPref('SHDATEFORMAT');

$project_start = new CDate($project_dates['project_start_date']);
$project_end = new CDate($project_dates['project_finish_date']);

$start_dates['all'] = $project_start;
$end_dates['all'] = $project_end;

$today = new CDate();
$start_dates['custom'] = intval($sdate) ? new CDate($sdate) : clone $today;
$end_dates['custom'] = intval($edate) ? new CDate($edate) : clone $today;

$today = new CDate(); $today->addDays(1);
$start_dates['from_start'] = clone $project_start;
$end_dates['from_start'] = clone $today;

$today = new CDate(); $today->addDays(-1);
$start_dates['to_end'] = $today->after($project_end) ? clone $project_start : clone $today;
$end_dates['to_end'] = clone $project_end;

$today = new CDate(); $today->addMonths(-$scroll_date);
$start_dates['this_month'] = $today;
$today = new CDate(); $today->addMonths($scroll_date);
$end_dates['this_month'] = clone $today;

$start_date = $start_dates[$display_option];
$end_date = $end_dates[$display_option];

// setup the title block
if (!@$min_view) {
	$titleBlock = new CTitleBlock( 'Gantt Chart', 'applet-48.png',$m, "$m.$a" );
	//$titleBlock->addCrumb( "?m=tasks", "tasks list" );
	$titleBlock->addCrumb( "?m=projects&a=view&project_id=$project_id", "View project" );
	$titleBlock->show();
}

$graph_img_src = "?m=tasks&a=gantt&suppressHeaders=1&project_id=$project_id".
                 "&show_names=".($show_names ? "true" : "false").
                 "&draw_deps=".($show_deps ? "true" : "false").
                 "&colors=".($show_bw ? "false" : "true").
                 "&show_res=".($show_res ? "true" : "false").
                    ($display_option == 'all' ? '' :
                      '&start_date='.$start_date->format("%Y-%m-%d").
                      '&finish_date='.$end_date->format("%Y-%m-%d"));
?>

<style type="text/css">
	#graphloader {
		width: 100px;
		height: 100px;
		background: url('./style/default/images/loader.gif') no-repeat center center;
		background-color: #fff;
	}
</style>

<script language="javascript">
var projectID = <?php  echo $project_id ?>;
var graphWidth = $(window).width() * 0.95;
var calendarField = '';
var graph_load_error = './style/default/images/graph_loading_error.png';

function popCalendar( field ){
	calendarField = field;
	idate = eval( 'document.gantt_options.' + field + '.value' );
	window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=250, height=220, scollbars=false' );
}

/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar( idate, fdate ) {
	fld_date = eval( 'document.gantt_options.' + calendarField );
	fld_fdate = eval( 'document.gantt_options.show_' + calendarField );
	fld_date.value = idate;
	fld_fdate.value = fdate;

	var radios = document.gantt_options.time_interval;

	for(var i = 0; i < radios.length; i++) {
		radios[i].checked = false;
		if(radios[i].id == "custom") {
			radios[i].checked = true;
		}
	}

	document.gantt_options.display_option.value = 'custom';
}

function scrollPrev() {
	f = document.gantt_options;
<?php
	$new_start = $start_date;
	$new_end = $end_date;
	$new_start->addMonths( -$scroll_date );
	$new_end->addMonths( -$scroll_date );
	echo "f.sdate.value='".$new_start->format( FMT_TIMESTAMP_DATE )."';";
	echo "f.edate.value='".$new_end->format( FMT_TIMESTAMP_DATE )."';";
?>
	document.gantt_options.display_option.value = 'custom';
	f.submit();
}

function scrollNext() {
	f = document.gantt_options;
<?php
	$new_start = $start_date;
	$new_end = $end_date;
	$new_start->addMonths( $scroll_date+1 );
	$new_end->addMonths( $scroll_date+1 );
?>
	f.sdate.value = '<?php echo $new_start->format( FMT_TIMESTAMP_DATE ) ?>';
	f.edate.value = '<?php echo $new_end->format( FMT_TIMESTAMP_DATE ) ?>';
	f.display_option.value = 'custom';
	f.submit();
}

function showFromStart() {
	document.gantt_options.display_option.value = "from_start";
	document.gantt_options.sdate.value = '<?php echo $start_dates['from_start']->format("%Y%m%d"); ?>';
	document.gantt_options.edate.value = '<?php echo $end_dates['from_start']->format("%Y%m%d"); ?>';
	doSubmit();
//	document.gantt_options.submit();
}

function showToEnd() {
	document.gantt_options.display_option.value = "to_end";
	document.gantt_options.sdate.value = '<?php echo $start_dates['to_end']->format("%Y%m%d"); ?>';
	document.gantt_options.edate.value = '<?php echo $end_dates['to_end']->format("%Y%m%d"); ?>';
	doSubmit();
//	document.gantt_options.submit();
}

function showThisMonth() {
	document.gantt_options.display_option.value = "this_month";
	document.gantt_options.sdate.value = '<?php echo $start_dates['this_month']->format("%Y%m%d"); ?>';
	document.gantt_options.edate.value = '<?php echo $end_dates['this_month']->format("%Y%m%d"); ?>';
	doSubmit();
//	document.gantt_options.submit();
}

function showFullProject() {
	document.gantt_options.display_option.value = "all";
	document.gantt_options.sdate.value = '<?php echo $start_dates['all']->format("%Y%m%d"); ?>';
	document.gantt_options.edate.value = '<?php echo $end_dates['all']->format("%Y%m%d"); ?>';
	doSubmit();
//	document.gantt_options.submit();
}

$(function(){
	var loader = $('#graphloader');
	loader.width(graphWidth);
	loader.height(400);
	loader.css("background-color", "#fff");
});

function loadGraph(src) {
	var graphSrc = src+'&width='+graphWidth;
	
	$(function () {
		var img = new Image();
		var loader = $('#graphloader');
		var graph = $('#graph');

		$(img).load(function () {
			$(this).hide();
			loader.hide();
			graph.append(this);
			graph.show();
	      	$(this).fadeIn();
		})
	    .error(function () {
	    	var errimg = new Image();

	    	$(errimg).load(function () {
				$(this).hide();
				loader.hide();
				graph.append(this);
				graph.show();
		      	$(this).fadeIn();
			})
			.attr('src', graph_load_error);

			graph.wrap("<a href='"+graphSrc+"' target='_blank' />");
	    })
	    .attr('src', graphSrc);
	});
}

function buildGraphUrl() {
	var show_names = $("#show_names:checked").val();
	var show_deps = $("#show_deps:checked").val();
	var show_bw = $("#show_bw:checked").val();
	var show_res = $("#show_res:checked").val();
	var start_date = document.gantt_options.sdate.value;
	var end_date = document.gantt_options.edate.value;
	var explode = document.gantt_options.explode_tasks.value;

	var url = '?m=tasks&a=gantt&suppressHeaders=1&project_id='+projectID+
	          '&show_names='+(show_names ? 'true' : 'false')+
	          '&draw_deps='+(show_deps ? 'true' : 'false')+
			  '&colors='+(show_bw ? 'false' : 'true')+
			  '&show_res='+(show_res ? 'true' : 'false')+
			  '&explode_tasks='+explode;

	if (document.gantt_options.display_option.value != 'all') {
		url += '&start_date='+start_date+'&finish_date='+end_date;
	}

	return url;
}

function doSubmit() {

	if (document.gantt_options.edate.value < document.gantt_options.sdate.value)
		alert("Start date must before end date");
	else {
		//document.gantt_options.submit(); //TODO enable on old browsers
		$('#graph').empty();
		$('#graphloader').fadeIn();
		loadGraph(buildGraphUrl());
		$("#gantt_report_btn").fadeIn();
	}
}

function makeGANTTPDF() {
	document.gantt_options.make_graph_pdf.value = "true";
	document.gantt_options.add_graph_report.value = "false";
	generatePDF('gantt_options', 'gantt_pdf_span');
	document.gantt_options.make_graph_pdf.value = "false";
}

function addGANTTReport() {
	document.gantt_options.make_graph_pdf.value = "false"; 
	document.gantt_options.add_graph_report.value = "true";
	addReport('gantt_options', 'gantt_report_btn');
	document.gantt_options.add_graph_report.value = "false";
}

loadGraph('<?php echo $graph_img_src; ?>');
</script>

<form id="gantt_options" name="gantt_options" method="post" action="?<?php echo "m=$m&a=$a&project_id=$project_id";?>">
	<input type="hidden" name="display_option" value="<?php echo $display_option;?>" />
	
	<div id="tab_settings_content" style="display: none;">
		<table border="0" cellpadding="4" cellspacing="0" align="center">
			<tr>
				<td align="left" valign="top" width="20">
<?php
				$new_start->addMonths( -$scroll_date );
				$new_end->addMonths( -$scroll_date );
?>
				</td>
			
				<td class="tab_setting_group">
					<table border="0" cellspacing="0">
						<tr>
							<td class="tab_setting_title" rowspan="4"><?php echo $AppUI->_('Draw');?>:</td>
							<td class="tab_setting_item">
								<input type='checkbox' id='show_names' name='show_names' <? echo $show_names ? 'checked="checked"' : '' ?> />
								<label for="show_names"><?php echo $AppUI->_('Task Names'); ?></label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='checkbox' id='show_deps' name='show_dependencies' <? echo $show_deps ? 'checked="checked"' : '' ?> />
								<label for="show_deps"><?php echo $AppUI->_('Dependencies'); ?></label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='checkbox' id='show_bw' name='show_bw'" <? echo $show_bw ? 'checked="checked"' : '' ?> />
								<label for="show_bw"><?php echo $AppUI->_('Printable BW colors'); ?></label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='checkbox' id='show_res' name='show_res'" <? echo $show_res ? 'checked="checked"' : '' ?> />
								<label for="show_res"><?php echo $AppUI->_('Resources'); ?></label>
							</td>
						</tr>
					</table>
				</td>
			
				<td class="tab_setting_group">
					<table border="0" cellspacing="0">
						<tr>
							<td class="tab_setting_title" rowspan="4"><?php echo $AppUI->_('Show');?>:</td>
							<td class="tab_setting_item">
								<input type='radio' id='whole_project' name='time_interval' onclick="showFullProject();" <? echo $display_option == "all" ? 'checked="checked"' : '' ?> />
								<label for="whole_project"><?php echo $AppUI->_('Whole Project'); ?></label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='radio' id='from_start' name='time_interval' onclick='showFromStart();' <? echo $display_option == "from_start" ? 'checked="checked"' : '' ?> />
								<label for="from_start"><?php echo $AppUI->_('From Start'); ?></label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='radio' id='to_end' name='time_interval' onclick='showToEnd();' <? echo $display_option == "to_end" ? 'checked="checked"' : '' ?> />
								<label for="to_end"><?php echo $AppUI->_('To End'); ?></label>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_item">
								<input type='radio' id='custom' name='time_interval' disabled="disabled" <? echo $display_option == "custom" ? 'checked="checked"' : '' ?> />
								<label for="custom"><?php echo $AppUI->_('Custom'); ?></label>
							</td>
						</tr>
					</table>
				</td>
			
				<td align='left' valign="top" style="border-right: solid transparent 20px">
					<table border="0" cellspacing="0">
						<tr>
							<td class="tab_setting_title">
								<?php echo $AppUI->_('From');?>:
							</td>
							<td class="tab_setting_item">
								<input type="hidden" name="sdate" value="<?php echo $start_date->format( FMT_TIMESTAMP_DATE );?>" />
								<input type="text" class="text" name="show_sdate" value="<?php echo $start_date->format( $df );?>" size="12" disabled="disabled" />
								<a href="#" onclick="popCalendar('sdate');"><img src="./images/calendar.gif" width="24" height="12" alt="" border="0"></a>
							</td>
						</tr>
						<tr>
							<td class="tab_setting_title">
								<?php echo $AppUI->_( 'To' );?>:
							</td>
							<td class="tab_setting_item">
							    <input type="hidden" name="edate" value="<?php echo $end_date->format( FMT_TIMESTAMP_DATE );?>" />
								<input type="text" class="text" name="show_edate" value="<?php echo $end_date->format( $df );?>" size="12" disabled="disabled" />
								<a href="#" onclick="popCalendar('edate');"><img src="./images/calendar.gif" width="24" height="12" alt="" border="0"></a>
							</td>
						</tr>
						<tr>							<td class="tab_setting_title">
								<?php echo $AppUI->_('Explode Tasks').": ";?>
							</td>
							<td class="tab_setting_item">
								&nbsp; <select id="explode_tasks" name="explode_tasks" class="text">
<?php
								$maxLevel=CTask::getLevel($project_id);
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
<!--						<tr>
							<td class="tab_setting_title">
								<?php echo $AppUI->_('Size').": ";?>
							</td>
							<td class="tab_setting_item">&nbsp;
								<select name="image_size" class="text" onchange="">
									<option value="1" selected="selected">Default</option>
									<option value="2" >Custom</option>
									<option value="3" >Fit in Window</option>
								</select>
							</td>
						</tr> -->
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
		<table id='' width='100%' border='0' cellpadding='1' cellspacing='0'>
			<tr align="right">
				<td align="right">
	
				</td>
			</tr>
			<tr>
				<td align="right">
<?
					if (dPgetBoolParam($_POST, 'make_graph_pdf') ||
					    dPgetBoolParam($_POST, 'add_graph_report'))	{
						
					    ini_set('memory_limit', dPgetParam($dPconfig, 'reset_memory_limit', 8*1024*1024));

						$gantt = new PMGantt($project_id);
						$gantt->setTaskLevel(getProjectSubState('Tasks', 'Explode'));
						$gantt->setOpenedTasks(getProjectSubState('Tasks', 'opened'));
						$gantt->setClosedTasks(getProjectSubState('Tasks', 'closed'));
						$gantt->setWidth(1000);
						$gantt->setStartDate($start_date->format("%Y-%m-%d"));
						$gantt->setEndDate($end_date->format("%Y-%m-%d"));
						$gantt->showNames($show_names);
						$gantt->showDeps($show_deps);
						$gantt->showResources($show_res);
						$gantt->useColors(!$show_bw);
						
						if (dPgetBoolParam($_POST, 'make_graph_pdf')) {
	                    	generateGraphPDF($project_id, $gantt);
						} else if (dPgetBoolParam($_POST, 'add_graph_report')) {
	                    	CReport::addGraphReport($project_id, $gantt);
						}

	                    ini_restore('memory_limit');
				    }
					
                    $pdf = getProjectSubState('PDFReports', PMPDF_GRAPH_GANTT);
?>
					
					<span id="gantt_pdf_span" style="vertical-align: middle">	
<?							
					if ($pdf && file_exists($pdf)) {
?>
						<a id="gantt_pdf_link" href="<?echo $pdf;?>">
							<img id="gantt_pdf_icon" src="./modules/report/images/pdf_report.gif" alt="PDF Report" border="0">
						</a>
<?
					}
?>
					</span>
	
	
					<input type="hidden" name="make_graph_pdf" value="false" />
					<input type="hidden" name="add_graph_report" value="false" />
					
					<input type="button" class="button" value="<?php echo $AppUI->_( 'Generate PDF' );?>" onclick='makeGANTTPDF();'>
					<input type="button" class="button" value="<?php echo $AppUI->_( 'Add to Report' );?>" onclick='addGANTTReport();' id="gantt_report_btn">
					<input type="button" class="button" value="<?php echo $AppUI->_( 'Configure' );?>" onclick='settingsTabToggle();'>
				</td>
			</tr>
		</table>
	</div>
</form>

<br />
<table cellspacing="0" cellpadding="0" border="1" align="center" style="border-top-style: hidden;">
<?php if ($display_option != "all") { ?>
	<tr style="border-style: hidden;">
		<td align="left" style="border-top-style: hidden; border-left-style: hidden; border-right-style: hidden;">
			<a href="#" onclick="scrollPrev();">
	  			<img src="./images/prev.gif" width="16" height="16" alt="<?php echo $AppUI->_( 'previous' );?>" border="0">
	 		</a>
		</td>
		<td align="right" style="border-top-style: hidden; border-left-style: hidden; border-right-style: hidden;">
			<a href="#" onclick="scrollNext();">
	  			<img src="./images/next.gif" width="16" height="16" alt="<?php echo $AppUI->_( 'next' );?>" border="0">
	 		</a>
		</td>
	</tr>
<?php } ?>
	<tr>
		<td <?php if ($display_option != "all") echo "colspan='2'" ?>>
<?php
if (db_loadResult( "SELECT COUNT(*) FROM tasks WHERE task_project=$project_id" )) {
?>
		<div id="graphloader"></div>
		<div id="graph"></div>
<?php
} else {
	echo $AppUI->_( "No tasks to display" );
}
?>
		</td>
	</tr>
</table>
<br />
