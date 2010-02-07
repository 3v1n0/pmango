<?php

/**
 ---------------------------------------------------------------------------

 PMango Project

 Title:      Task Network generation.

 File:       tasknetwork.php
 Location:   pmango/modules/tasks
 Started:    2010.01.27
 Author:     Matteo Pratesi
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2010.01.27
   0.1, first version

 ---------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2006 Giovanni A. Cignoni, Lorenzo Ballini, Marco Bonacchi
 Copyright (C) 2010 Matteo Pratesi <matteo.pratesi@libero.it>
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

 ---------------------------------------------------------------------------
 */

include "TaskNetwork.class.php";

$project_id = defVal(@$_REQUEST['project_id'], 0);
$task_level = dPgetParam($_GET, 'explode_tasks');

if ($task_level != null) {
	$AppUI->setSubState('Tasks', 'Explode', $task_level);
	$AppUI->setSubState('Tasks', 'opened', array());
	$AppUI->setSubState('Tasks', 'closed', array());
} else {
	$task_level = $AppUI->getSubState('Tasks', 'Explode', 1);
	$tasks_closed = $AppUI->getSubState('Tasks', "closed");
	$tasks_opened = $AppUI->getSubState('Tasks', "opened");
}

$project_id = ($project_id > 0) ? $project_id : 0;
$psql = "SELECT project_group FROM projects WHERE ".$project_id."= project_id";
$prc = db_exec( $psql );
echo db_error();
$pnums = db_num_rows($prc);

$prc = db_exec($psql);
$project = db_fetch_row($prc);

if (!$perms->checkModule('projects', 'view', '', intval($project['project_group']), 1))
	$AppUI->redirect("m=public&a=access_denied");

$show_names     = dPgetBoolParam($_GET, 'names');
$show_progress  = dPgetBoolParam($_GET, 'progress');
$show_alerts    = dPgetBoolParam($_GET, 'alerts');
$show_p_data    = dPgetBoolParam($_GET, 'p_data');
$show_a_data    = dPgetBoolParam($_GET, 'a_data');
$show_p_res     = dPgetBoolParam($_GET, 'p_res');
$show_a_res     = dPgetBoolParam($_GET, 'a_res');
$show_p_time    = dPgetBoolParam($_GET, 'p_time');
$show_a_time    = dPgetBoolParam($_GET, 'a_time');
$show_vertical  = dPgetBoolParam($_GET, 'vertical');
$show_def_dep   = dPgetBoolParam($_GET, 'def_dep');
$show_dep       = dPgetBoolParam($_GET, 'dep');
$show_all_arrow = dPgetBoolParam($_GET, 'all_arrow');
$show_time_gaps = dPgetBoolParam($_GET, 'time_gaps');
$show_cr_path   = dPgetBoolParam($_GET, 'cr_path');

// re-set the memory limit for gantt chart drawing acc. to the config value of reset_memory_limit
ini_set('memory_limit', dPgetParam($dPconfig, 'reset_memory_limit', 8*1024*1024));

$TN = new TaskNetwork($project_id);
$TN->setOpenedTasks($tasks_opened);
$TN->setClosedTasks($tasks_closed);
$TN->setTaskLevel($task_level);
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
$TN->showCriticalPath($show_cr_path);
$TN->draw();

// reset the php memory limit to the original php.ini value
ini_restore('memory_limit');

?>