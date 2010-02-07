<?php
/**
 ---------------------------------------------------------------------------

 PMango Project

 Title:      Gantt generation.

 File:       gantt.php
 Location:   pmango\modules\tasks
 Started:    2005.09.30
 Author:     Lorenzo Ballini
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2006.07.30 Lorenzo
 Second version, modified to manage PMango Gantt.
 - 2006.07.30 Lorenzo
 First version, unmodified from dotProject 2.0.1 (J. Christopher Pereira).

 ---------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2006 Giovanni A. Cignoni, Lorenzo Ballini, Marco Bonacchi
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

include "PMgantt.class.php";

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

$width      = dPgetParam($_GET, 'width', 600);
$start_date = dPgetParam($_GET, 'start_date', null);
$end_date   = dPgetParam($_GET, 'finish_date', null);

$show_names = dPgetBoolParam($_GET, 'show_names');
$show_res   = dPgetBoolParam($_GET, 'show_res');
$draw_deps  = dPgetBoolParam($_GET, 'draw_deps');
$colors     = dPgetBoolParam($_GET, 'colors');

// re-set the memory limit for gantt chart drawing acc. to the config value of reset_memory_limit
ini_set('memory_limit', dPgetParam($dPconfig, 'reset_memory_limit', 8*1024*1024));

$gantt = new PMGantt($project_id);
$gantt->setTaskLevel($task_level);
$gantt->setOpenedTasks($tasks_opened);
$gantt->setClosedTasks($tasks_closed);
$gantt->setWidth($width);
$gantt->setStartDate($start_date);
$gantt->setEndDate($end_date);
$gantt->showNames($show_names);
$gantt->showDeps($draw_deps);
$gantt->showResources($show_res);
$gantt->useColors($colors);
$gantt->draw();

// reset the php memory limit to the original php.ini value
ini_restore('memory_limit');

?>
