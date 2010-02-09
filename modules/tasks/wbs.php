<?php

/**
 ---------------------------------------------------------------------------

 PMango Project

 Title:      WBS generation.

 File:       wbs.php
 Location:   pmango/modules/tasks
 Started:    2010.01.26
 Author:     Marco Trevisan
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2010.01.26
   0.1, first version

 ---------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2006 Giovanni A. Cignoni, Lorenzo Ballini, Marco Bonacchi
 Copyright (C) 2010 Marco Trevisan (Treviño) <mail@3v1n0.net>
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

include "WBS.class.php";

$project_id = defVal(@$_REQUEST['project_id'], 0);
$task_level = dPgetParam($_GET, 'explode_tasks');

if ($task_level != null && $task_level != getProjectSubState('Tasks', 'Explode', 1)) {
	setProjectSubState('Tasks', 'Explode', $task_level);
	setProjectSubState('Tasks', 'opened', array());
	setProjectSubState('Tasks', 'closed', array());
} else {
	$task_level = getProjectSubState('Tasks', 'Explode', 1);
	$tasks_closed = getProjectSubState('Tasks', "closed");
	$tasks_opened = getProjectSubState('Tasks', "opened");
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

$show_names    = dPgetBoolParam($_GET, 'names');
$show_progress = dPgetBoolParam($_GET, 'progress');
$show_alerts   = dPgetBoolParam($_GET, 'alerts');
$show_p_data   = dPgetBoolParam($_GET, 'p_data');
$show_a_data   = dPgetBoolParam($_GET, 'a_data');
$show_p_res    = dPgetBoolParam($_GET, 'p_res');
$show_a_res    = dPgetBoolParam($_GET, 'a_res');
$show_p_time   = dPgetBoolParam($_GET, 'p_time');
$show_a_time   = dPgetBoolParam($_GET, 'a_time');

// re-set the memory limit for gantt chart drawing acc. to the config value of reset_memory_limit
ini_set('memory_limit', dPgetParam($dPconfig, 'reset_memory_limit', 8*1024*1024));

$wbs = new WBS($project_id);
$wbs->setOpenedTasks($tasks_opened);
$wbs->setClosedTasks($tasks_closed);
$wbs->setTaskLevel($task_level);
$wbs->showNames($show_names);
$wbs->showProgress($show_progress);
$wbs->showAlerts($show_alerts);
$wbs->showPlannedData($show_p_data);
$wbs->showActualData($show_a_data);
$wbs->showPlannedResources($show_p_res);
$wbs->showActualResources($show_a_res);
$wbs->showPlannedTimeframe($show_p_time);
$wbs->showActualTimeframe($show_a_time);
$wbs->draw();

// reset the php memory limit to the original php.ini value
ini_restore('memory_limit');

?>