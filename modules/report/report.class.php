<?php
/**
---------------------------------------------------------------------------

 PMango Project

 Title:      report functions.

 File:       report.class.php
 Location:   PMango\modules\report
 Started:    2005.09.30
 Author:     Riccardo Nicolini
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://penelope.di.unipi.it

 Version history.
 - 2010.03.25 Marco Trevisan
   Support for thumbnails in graph reports, and some code cleanup
 - 2010.02.15 Marco Trevisan
   Redisegned version, it is used both for saving and retriving the reports
 - 2007.05.08 Riccardo
   First version, created to manage PDF reports. 
   
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

define('GRAPH_REPORTS_PATH', './modules/report/img/');
define('GRAPH_REPORTS_EXT', 'png');

require_once $AppUI->getSystemClass('dp');
require_once $AppUI->getModuleClass('tasks');
$AppUI->savePlace();
/**
 * The Reoprt Class
 */
class CReport extends CDpObject {


	var $report_id = NULL;

	// the constructor
	function CReport() {
		$this->CDpObject( 'report', 'report_id' );
	}

	function delete() {
		$sql = "DELETE FROM reports WHERE report_id = $this->report_id";
		if (!db_exec( $sql )) {
			return db_error();
		} else {
			return NULL;
		}
	}
	
	static function initUserReport($project_id) {
		global $AppUI;

		$sql = "SELECT COUNT(*) FROM reports WHERE project_id = $project_id AND user_id = ".$AppUI->user_id;
		$exist = db_loadResult($sql);
	
		if (!$exist) {
			$sql = "INSERT INTO reports (report_id, project_id, user_id, ".
			       "p_is_incomplete, p_show_mine, p_report_level, p_report_roles, ".
			       "p_report_sdate, p_report_edate, p_report_opened, p_report_closed, ".
			       "a_is_incomplete, a_show_mine, a_report_level, a_report_roles, ".
			       "a_report_sdate, a_report_edate, a_report_opened, a_report_closed, ".
			       "l_hide_inactive, l_hide_complete, l_user_id, l_report_sdate, l_report_edate, ".
			       "properties, prop_summary, gantt, wbs, task_network) ".
			       "VALUES (NULL, $project_id, ".$AppUI->user_id.", NULL, NULL, NULL, NULL, NULL, ".
			                "NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, ".
			                "NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);";

			db_exec($sql); db_error();
			
			unsetProjectSubState('PDFReports', PMPDF_REPORT);
		}
		
	}
	
	static function deleteUserReports($user) {
		$sql = "SELECT gantt, wbs, task_network FROM reports WHERE user_id = $user";
		
	    $list = db_loadList($sql);
	    foreach (@$list as $item) {
	    	$reports = array_merge(explode(';', $item['gantt']), explode(';', $item['wbs']),
	    	                       explode(';', $item['task_network']));

	    	foreach($reports as $report) {
	    		if (!$report) continue;
	    		
	    		$file = GRAPH_REPORTS_PATH.'/'.$report.".".GRAPH_REPORTS_EXT;
	    		if (file_exists($file) && is_file($file)) @unlink($file);
	    		
	    		$file_tb = GRAPH_REPORTS_PATH.'/'.$report."_tb.".GRAPH_REPORTS_EXT;
	    		if (file_exists($file_tb) && is_file($file_tb)) @unlink($file_tb);
	    	}
   		}
    
	    $sql="DELETE FROM reports WHERE user_id = $user";
	    db_exec($sql); db_error();
	}
	
	static function addTaskPlannedReport($project_id, $showIncomplete, $showMine, $explodeTasks,
	                                     $roles, $sd, $ed, $r_task_opened, $r_task_closed) {
		global $AppUI;
		
		$sql = "UPDATE reports SET
				  	p_is_incomplete = '$showIncomplete',
				  	p_show_mine = '$showMine',
					p_report_level = $explodeTasks,
					p_report_roles = '$roles',
					p_report_sdate = '$sd', 
					p_report_edate = '$ed', 
					p_report_opened = '$r_task_opened', 
					p_report_closed = '$r_task_closed' 
			    WHERE
			  		project_id = $project_id
			    AND
			  		reports.user_id = ".$AppUI->user_id;
		
		db_exec($sql);
		db_error();
	}
	
	static function addTaskActualReport($project_id, $showIncomplete, $showMine, $explodeTasks,
	                                    $roles, $sd, $ed, $r_task_opened, $r_task_closed) {                  	
		global $AppUI;
		
		$sql = "UPDATE reports SET
				  	a_is_incomplete = '$showIncomplete',
				  	a_show_mine = '$showMine',
					a_report_level = $explodeTasks,
					a_report_roles = '$roles', 
					a_report_sdate = '$sd', 
					a_report_edate = '$ed', 
					a_report_opened = '$r_task_opened', 
					a_report_closed = '$r_task_closed' 
			  	WHERE 
			  		reports.project_id = $project_id 
			  	AND
			  		reports.user_id = ".$AppUI->user_id;
		
		db_exec($sql);
		db_error();
	}
	
	static function addProjectReport($project_id, $properties, $summary) {
		global $AppUI;
		
		$sql = "UPDATE reports SET properties = '$properties', ".
	           "prop_summary = '$summary' WHERE project_id = $project_id ".
	           "AND user_id = ".$AppUI->user_id;
		
		db_exec($sql);
		db_error();
	}
	
	static function addLogReport($project_id, $hide_complete, $hide_inactive, $user_id, $sd, $ed) {
		global $AppUI;
		
		$sql = "UPDATE reports SET l_hide_complete = '$hide_complete', ".
	           "l_hide_inactive = '$hide_inactive', l_user_id = '$user_id', ".
	           "l_report_sdate = '$sd', l_report_edate = '$ed' ".
	           "WHERE project_id = $project_id AND user_id = ".$AppUI->user_id;
		
		db_exec($sql);
		db_error();
	}
	
	private static function getGraphReportName($project_id, $graph_type) {
		global $AppUI;
		
		return $AppUI->user_id."-".$project_id."_".$graph_type.time();
	}
	
	static function addGraphReport($project_id, PMGraph $graph) {
		global $AppUI;
		
		switch ($graph->getType()) {
			case "TaskNetwork":
				$graphtype = "task_network";
				break;
			case "WBS":
				$graphtype = "wbs";
				break;
			case "GANTT":
				$graphtype = "gantt";
				break;
			default:
				return null;
		}
		
		$filename = CReport::getGraphReportName($project_id, $graphtype);
		$imgfile = GRAPH_REPORTS_PATH.'/'.$filename.".".GRAPH_REPORTS_EXT;
		
		$graph->draw(GRAPH_REPORTS_EXT, $imgfile);
							
		if (file_exists($imgfile)) {
			$thumb = makeThumbnail(imagecreatefrompng($imgfile), 100);
			$thumbfile = GRAPH_REPORTS_PATH.'/'.$filename."_tb.".GRAPH_REPORTS_EXT;
			
			if (GRAPH_REPORTS_EXT == 'png')
				imagepng($thumb, $thumbfile);
			else if (GRAPH_REPORTS_EXT == 'jpg' || GRAPH_REPORTS_EXT == 'jpeg')
				imagejpeg($thumb, $thumbfile);
			else if (GRAPH_REPORTS_EXT == 'gif') 
				imagegif($thumb, $thumbfile);
			
			$report = CReport::getReportValue($project_id, $graphtype);
			if (!empty($report)) $report .= ';';
			$report .= $filename;
			
			$sql = "UPDATE reports SET $graphtype = '$report' ".
			       "WHERE reports.project_id = $project_id  AND reports.user_id = ".$AppUI->user_id;
			db_exec($sql);
			db_error();
			
			return $imgfile;
		}
		
		return null;
	}
	
	private static function getReportValue($project_id, $value) {
		GLOBAL $AppUI;
		$user_id = $AppUI->user_id;
		
		$sql="SELECT $value FROM reports WHERE project_id = $project_id AND user_id = $user_id";
		$report = db_loadResult($sql);

		return $report;
	}
	
	private static function unsetReportValue($project_id, $value) {
		GLOBAL $AppUI;
		$user_id = $AppUI->user_id;
		
		$sql="UPDATE reports SET $value = NULL WHERE project_id = $project_id AND user_id = $user_id";
		db_exec($sql);
		db_error();
	}

	static function getTaskReport($project_id, $report_type = PMPDF_PLANNED) {
		
		GLOBAL $AppUI;
		$user_id = $AppUI->user_id;
		

		if ($report_type == PMPDF_PLANNED) {
			$sql = "SELECT p_is_incomplete as is_incomplete, p_show_mine as show_mine, ".
		                  "p_report_level as report_level, p_report_roles as report_roles, ".
		                  "p_report_sdate as report_sdate, p_report_edate as report_edate, ".
		                  "p_report_opened as report_opened, p_report_closed as report_closed ".
		            "FROM reports WHERE reports.project_id=".$project_id." AND user_id=".$user_id;
		} else if ($report_type == PMPDF_ACTUAL) {
			$sql = "SELECT a_is_incomplete as is_incomplete, a_show_mine as show_mine, ".
			              "a_report_level as report_level, a_report_roles as report_roles, ".
			              "a_report_sdate as report_sdate, a_report_edate as report_edate, ".
			              "a_report_opened as report_opened, a_report_closed as report_closed ".
			       "FROM reports WHERE reports.project_id=".$project_id." AND user_id=".$user_id;	
		}
		
		$param = db_loadList($sql);
		
		$lev = $param[0]['report_level'];
		$role = $param[0]['report_roles'];
		
		if($role != null){
			switch($role){
				case "N": $showrole="Person Number";
				break;
				case "P": $showrole="Person Name";
				break;
				case "R": $showrole="Person Role";
				break;
				case "A": $showrole="Person Name and Role";
				break;
		}
			
		$tasks_opened = !empty($param[0]['report_opened']) ? explode('/', $param[0]['report_opened']) : array();
		$tasks_closed = !empty($param[0]['report_closed']) ? explode('/', $param[0]['report_closed']) : array();
		
		$opened_info = array();
		$closed_info = array();

		if (!empty($tasks_opened))
		  	$sql="SELECT task_name, task_id FROM tasks WHERE task_id in (".implode(",", $tasks_opened).")";
			foreach (@db_loadList($sql) as $task) {
				if (!empty($task['task_name'])) {
					$opened_info[] = array('wbs' => CTask::getWBS($task['task_id']),
					                       'name' => $task['task_name']);
				}
			}
		}

		if (!empty($tasks_closed)) {
		  	$sql="SELECT task_name, task_id FROM tasks WHERE task_id in (".implode(",", $tasks_closed).")";
			foreach (@db_loadList($sql) as $task) {
				if (!empty($task['task_name'])) {
		  			$closed_info[] = array('wbs' => CTask::getWBS($task['task_id']),
				    	                   'name' => $task['task_name']);
				}
			}
		}

		$sdate = new CDate($param[0]['report_sdate']);
		$edate = new CDate($param[0]['report_edate']);	

		$s = "<table border='0' cellpadding='1' cellspacing='2'>
				<tr>
					<td nowrap='nowrap'>Show Incomplete</td>
					<td nowrap='nowrap'>
						<img border='0' src='./images/icons/".($param[0]['is_incomplete'] ? 'stock_ok-16.png' : 'stock_cancel-16.png')."' />
					</td>
				</tr>
				<tr>
					<td nowrap='nowrap'>Show Mine</td>
					<td nowrap='nowrap'>
						<img border='0' src='./images/icons/".($param[0]['show_mine'] ? 'stock_ok-16.png' : 'stock_cancel-16.png')."' />
					</td>
				</tr>
				<tr>
					<td nowrap='nowrap'>Explosion Level</td>
					<td nowrap='nowrap'>Level ".$lev."</td>
				</tr>
				<tr>
					<td nowrap='nowrap'>Show Roles</td>
					<td nowrap='nowrap'>".$showrole."</td>
				</tr>
				<tr>
					<td nowrap='nowrap'>Date Period</td>
					<td nowrap='nowrap'>".$sdate->format( FMT_REGULAR_DATE ).' - '.$edate->format( FMT_REGULAR_DATE )."</td>
				</tr>
				<tr>
					<td valign='top'>Exploded Tasks</td>
					<td>";
			if (count($opened_info)) {
				$s .= "
						<a href='#' onclick='$(\"#rep_exploded_tasks\").slideToggle()'>
							".count($opened_info)."
							<img src='./modules/report/images/details.gif' alt='Show Details' title='Show Details' border='0'>
						</a>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
						<div id='rep_exploded_tasks' style='display: none;'>
		 					<table>";
			foreach ($opened_info as $task){
			 	$s .="
				 				<tr>
				 					<td nowrap='nowrap'>".$task['wbs']."
				 					</td>
				 					<td nowrap='nowrap'>- ".$task['name']."</td>
				 				</tr>";
				}
				
			$s .="
							</table>
						</div>";
		} else {
			$s .= "<img border='0' src='./images/icons/stock_cancel-16.png'>";
		}
			 
		$s .= "
					</td>
				</tr>
				<tr>
					<td valign='top'>Closed Tasks</td>
					<td>";

			if (count($closed_info)) {
				$s .= "
						<a href='#' onclick='$(\"#rep_closed_tasks\").slideToggle()'>
							".count($closed_info)."
							<img src='./modules/report/images/details.gif' alt='Show Details' title='Show Details' border='0'>
						</a>
					</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>
		 			<div id='rep_closed_tasks' style='display: none;'>
		 				<table>";
			foreach ($closed_info as $task) {
			 	$s .="
			 				<tr>
			 					<td nowrap='nowrap'>".$task['wbs']."
			 					</td>
			 					<td nowrap='nowrap'>- ".$task['name']."</td>
			 				</tr>";
			}
				
			$s .="
						</table>
					</div>";
		} else {
			$s .= "<img border='0' src='./images/icons/stock_cancel-16.png'>";
		}
			 
		$s .="
					</td>
				</tr>
				<tr>
					<td nowrap='nowrap' colspan='4'>&nbsp;</td>
				</tr>
			</table>";
		
		if (!$lev) {
	 		if ($report_type == PMPDF_PLANNED) $s="<br>No Task Planned Report defined";
			else if ($report_type == PMPDF_ACTUAL) $s="<br>No Task Actual Report defined";
		}
		
		echo $s;
		
		if($role!=null) {
			$is_incomplete=$param[0]['is_incomplete'];
			$show_mine = $param[0]['show_mine'];
			
			$values = array('opened' => $tasks_opened,
			                'closed' => $tasks_closed,
			                'start_date' => $sdate,
			                'end_date' => $edate,
			                'roles' => $role,
			                'level' => $lev,
			                'show_incomplete' => $is_incomplete,
			                'show_mine' => $show_mine);
			
			return $values;
		}
		else return null;
	}

	static function getLogReport($pid){
		
		GLOBAL $AppUI;
		$user_id = $AppUI->user_id;
		$project_id=$pid;

		$sql = "SELECT l_hide_complete, l_hide_inactive, l_user_id, l_report_sdate, ".
		              "l_report_edate FROM reports WHERE reports.project_id=$project_id ".
		                                                "AND user_id=$user_id";
		$log_param = db_loadList($sql);
		
		
		$ruser_id=$log_param[0]['l_user_id'];
		
		if ($log_param[0]['l_user_id'] != null) {
			
			$sql="SELECT concat(user_first_name,' ',user_last_name) FROM users WHERE user_id=$ruser_id";
			$ruser = db_loadList($sql);
			
			if($ruser_id==-2) $ruser[0][0]= "Grouped by User";
			if($ruser_id==-1) $ruser[0][0]= "All Users";
		
			$sdate=new CDate($log_param[0]['l_report_sdate']);
			$edate=new CDate($log_param[0]['l_report_edate']);	
	
			$s = "
				<table border='0' cellpadding='1' cellspacing='2'>
					<tr>
						<td nowrap='nowrap'>Hide Inactive</td>
						<td nowrap='nowrap'>
							<img border='0' src='./images/icons/".($log_param[0]['l_hide_inactive'] ? "stock_ok-16.png" : "stock_cancel-16.png")."'>
						</td>
					</tr>
					<tr>
						<td nowrap='nowrap'>Incomplete Tasks</td>
						<td nowrap='nowrap'>
							<img border='0' src='./images/icons/".($log_param[0]['l_hide_complete'] ? "stock_ok-16.png" : "stock_cancel-16.png")."'>
						</td>
					</tr>
					<tr>
						<td nowrap='nowrap'>User Filter</td>
						<td nowrap='nowrap'>".$ruser[0][0]."</td>
					</tr>
					<tr>
						<td nowrap='nowrap'>Date Period</td>
						<td nowrap='nowrap'>".$sdate->format( FMT_REGULAR_DATE ).' - '.$edate->format( FMT_REGULAR_DATE )."</td>
					</tr>
					<tr>
						<td nowrap='nowrap' colspan='4'>&nbsp;</td>
					</tr>
				</table>";
		} else {
			$s = "<br>No Task Log Report defined";
		}
		
		echo $s;
		
		if($log_param[0]['l_user_id'] != null) {
			$values = array('user' => $ruser_id,
			                'hide_inactive' => $log_param[0]['l_hide_inactive'],
			                'hide_complete' => $log_param[0]['l_hide_complete'],
			                'start_date' => $sdate,
			                'end_date' => $edate);
			return $values;
		} else return null;
	}
	
	static function getProjectReport($pid){
		
		GLOBAL $AppUI;
		$user_id = $AppUI->user_id;
		$project_id=$pid;
		
		$sql="SELECT properties, prop_summary FROM reports WHERE reports.project_id=".$project_id." AND user_id=".$user_id;
		$wbs_param = db_loadList($sql);

		if ( $wbs_param[0]['properties'] != null) {
				$string = stripslashes($wbs_param[0]['properties']);
				$summary=explode("|",stripslashes($wbs_param[0]['prop_summary']));
				$string2="Project isn't ";
				for($i=0;$i<count($summary);$i++){
				$string2.=str_replace("Project isn't","",$summary[$i]);
				if($i<count($summary)-2)
					$string2.=" and ";
				}
			if ($wbs_param[0]['prop_summary'] != null) {	
				$s = "
						<table>
							<tr>
								<td nowrap='nowrap'>
									<a href='#' onclick='$(\"#prop_summary\").slideToggle();'>
										<font color='red'><strong>".$string2."</strong></font>&nbsp;&nbsp;
										<img src='./modules/report/images/details.gif' alt='Show Details' title='Show Details' border='0'>
									</a>
									<div id='prop_summary' style='display:none;'>
										$string
									</div>
								</td>
							</tr>
						</table>";
			} else {
				$s = "
						<table>
							<tr>
								<td nowrap='nowrap'>$string</td>
							</tr>
						</table>";	
			}
		}
		else $s="<br>No Properties Computed";
		echo $s;
		
		return $string;	
	
	}
	
	private static function getGraphViews($project_id, $graph_type) {
		$value = CReport::getReportValue($project_id, $graph_type);
		if (!$value)
			return array();
			
		$graphs = explode(';', $value);
		
		foreach ($graphs as $id => $graph) {
			$report[$id]['graph'] = GRAPH_REPORTS_PATH.'/'.$graph.'.'.GRAPH_REPORTS_EXT;
			$tb = GRAPH_REPORTS_PATH.'/'.$graph.'_tb.'.GRAPH_REPORTS_EXT;
			
			if (file_exists($tb))
				$report[$id]['tb'] = $tb;
		}
		
		return $report;
	}
	
	static function getGanttReport($project_id) { 		
		return CReport::getGraphViews($project_id, 'gantt');	
	}
	
	static function getWbsReport($project_id) {
		return CReport::getGraphViews($project_id, 'wbs');	
	}
	
	static function getTaskNetworkReport($project_id) {
		return CReport::getGraphViews($project_id, 'task_network');
	}
	
	static function getGraphReport($graph_view) {
		if (count($graph_view)) {
			?>
			<table border="0" cellspacing="3">
				<tr>
			<?
				foreach (@$graph_view as $graph) {
					?>
					<td style="border: thin solid black; background-color: white; vertical-align: middle; text-align: center;">
					<?
						if (file_exists($graph['graph'])) {
						?>
							<a href="<? echo $graph['graph'] ?>" target="_blank">
								<img src="<? echo ($graph['tb'] ? $graph['tb'] : $graph['graph']) ?>"/>
							</a>
						<?
						}
					?>
					</td>
					<?
				}
				?>
				</tr>
			</table>
			<?
		}
	}
	
	static function usetTaskPlanned($project_id) {
		global $AppUI;
		
		$sql = "UPDATE reports SET p_is_incomplete = NULL, p_show_mine = NULL, ".
		                          "p_report_level = NULL, p_report_roles = NULL, ".
		                          "p_report_sdate = NULL, p_report_edate = NULL, ".
		                          "p_report_opened = NULL, p_report_closed = NULL ".
		       "WHERE project_id = $project_id AND user_id = ".$AppUI->user_id;
		
		db_exec($sql); db_error();
	}
	
	static function usetTaskActual($project_id) {
		global $AppUI;
		
		$sql = "UPDATE reports SET a_is_incomplete = NULL, a_show_mine = NULL, ".
		                          "a_report_level = NULL, a_report_roles = NULL, ".
		                          "a_report_sdate = NULL, a_report_edate = NULL, ".
		                          "a_report_opened = NULL, a_report_closed = NULL ".
		       "WHERE project_id = $project_id AND user_id = ".$AppUI->user_id;
		
		db_exec($sql); db_error();
	}
	
	static function unsetProperties($project_id) {
		global $AppUI;
		
		$sql = "UPDATE reports SET properties = NULL, prop_summary = NULL ".
		       "WHERE project_id = $project_id AND user_id = ".$AppUI->user_id;
		
		db_exec($sql); db_error();
	}
	
	static function unsetLog($project_id) {
		global $AppUI;
		
		$sql = "UPDATE reports SET l_hide_inactive = NULL, l_hide_complete = NULL, ".
		                           "l_user_id = NULL, l_report_sdate = NULL, l_report_edate = NULL ".
		       "WHERE project_id = $project_id AND user_id = ".$AppUI->user_id;
		
		db_exec($sql); db_error();
	}
	
	private static function unsetGraph($project_id, $type) {
		$views = CReport::getGraphViews($project_id, $type);
		
		foreach($views as $view) {
			if (file_exists($view['graph'])) @unlink($view['graph']);
			if (file_exists($view['tb'])) @unlink($view['tb']);
		}
		
		CReport::unsetReportValue($project_id, $type);
	}
	
	static function unsetGantt($project_id) {
		CReport::unsetGraph($project_id, "gantt");
	}
	
	static function unsetWbs($project_id) {
		CReport::unsetGraph($project_id, "wbs");
	}
	
	static function unsetTaskNetwork($project_id) {
		CReport::unsetGraph($project_id, "task_network");
	}
}
?>