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

require_once($AppUI->getSystemClass('dp'));
require_once($AppUI->getModuleClass('tasks'));
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

	function getTaskReport($project_id, $report_type = PMPDF_PLANNED) {
		
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

	function getLogReport($pid){
		
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
	
	function getProjectReport($pid){
		
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
	
	function getGanttReport($pid) {
		GLOBAL $AppUI;
		$user_id = $AppUI->user_id;
		$project_id=$pid;
		
		$sql="SELECT gantt FROM reports WHERE reports.project_id=".$project_id." AND user_id=".$user_id;
		$gantt = db_loadResult($sql);

		return $gantt;	
	}
	
	function getWbsReport($pid) {
		GLOBAL $AppUI;
		$user_id = $AppUI->user_id;
		$project_id=$pid;
		
		$sql="SELECT wbs FROM reports WHERE reports.project_id=".$project_id." AND user_id=".$user_id;
		$gantt = db_loadResult($sql);

		return $gantt;	
	}
	
	function getTaskNetworkReport($pid) {
		GLOBAL $AppUI;
		$user_id = $AppUI->user_id;
		$project_id=$pid;
		
		$sql="SELECT task_network FROM reports WHERE reports.project_id=".$project_id." AND user_id=".$user_id;
		$gantt = db_loadResult($sql);

		return $gantt;	
	}
}
?>