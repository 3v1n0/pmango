<?php 
/**
---------------------------------------------------------------------------

 PMango Project

 Title:      project properties control 

 File:       do_properties.php
 Location:   pmango\modules\projects
 Started:    2006.02.01
 Author:     Lorenzo Ballini
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2006.07.30 Lorenzo
   First version, created to verify properties in a project.

---------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2006 Giovanni A. Cignoni, Lorenzo Ballini, Marco Bonacchi
 All rights reserved.

 PMango reuses part of the code of dotProject 2.0.1: dotProject code is 
 released under GNU GPL, further information at: http://www.dotproject.net
 Copyright (c) 2003-2005 The dotProject Development Team.

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

include $AppUI->getModuleFile('report', 'generatePDF');

$obj = new CProject();
if (!$obj->load(dPgetParam($_POST, 'project_id', 0))) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

$wf = dPgetBoolParam($_POST, 'wf', false);
$ce = dPgetBoolParam($_POST, 'ce', false);
$ee = dPgetBoolParam($_POST, 'ee', false);
$te = dPgetBoolParam($_POST, 'te', false);

$pre_options = getProjectState('PropertiesOptions');

setProjectSubState('PropertiesOptions', 'well_formed', $wf);
setProjectSubState('PropertiesOptions', 'cost_effective', $ce);
setProjectSubState('PropertiesOptions', 'effort_effective', $ee);
setProjectSubState('PropertiesOptions', 'time_effective', $te);

if ($pre_options !== getProjectState('PropertiesOptions'))
	deletePDF($project_id, PMPDF_PROPERTIES);

$r='';
$okMsg='Project is ';
$koMsg='Project isn\'t ';

$r2 = $obj->isDefined();
if ($wf || $ce || $te) {
	if ($r2 == '')
			$AppUI->setProperties('Project is Defined', UI_MSG_PROP_OK);
		else {
			$AppUI->setProperties('Project isn\'t Defined', UI_MSG_PROP_KO);
			if (strlen($r2)>0) {
				$r2{strlen($r2)-1}=' ';
				$AppUI->setProperties($r2, UI_MSG_PROP_KO);
			}
	}
}

if ($wf) {
	$r = $obj->isWellFormed();
	if ($r == '' && $r2 == '') {
		$AppUI->setProperties('Project is Well Formed', UI_MSG_PROP_OK);
		$okMsg.='Well Formed, ';
	}
	else {
		$AppUI->setProperties('Project isn\'t Well Formed', UI_MSG_PROP_KO);
		if (strlen($r)>0) {
			$r{strlen($r)-1}=' ';
			$AppUI->setProperties($r, UI_MSG_PROP_KO);
		}
		$koMsg.='Well Formed, ';
	}
}

if ($ce) {
	$r = $obj->isCostEffective();
	if ($r == '' && $r2 == '') {
		$AppUI->setProperties('Project is Cost Effective', UI_MSG_PROP_OK);
		$okMsg.='Cost Effective, ';
	}
	else {
		$AppUI->setProperties('Project isn\'t Cost Effective', UI_MSG_PROP_KO);
		if (strlen($r)>0) { 
			$r{strlen($r)-1}=' ';
			$AppUI->setProperties($r, UI_MSG_PROP_KO);
		}
		$koMsg.='Cost Effective, ';
	}
}

if ($ee) {
	$r = $obj->isEffortEffective();
	if ($r == '' && $r2 == '') {
		$AppUI->setProperties('Project is Effort Effective', UI_MSG_PROP_OK);
		$okMsg.='Effort Effective, ';
	}
	else {
		$AppUI->setProperties('Project isn\'t Effort Effective', UI_MSG_PROP_KO);
		if (strlen($r)>0) {
			$r{strlen($r)-1}=' ';
			$AppUI->setProperties($r, UI_MSG_PROP_KO);
		}
		$koMsg.='Effort Effective, ';
	}
}

if ($te) {
	$r = $obj->isTimeEffective();
	if ($r == '' && $r2 == '') {
		$AppUI->setProperties('Project is Time Effective', UI_MSG_PROP_OK);
		$okMsg.='Time Effective';
	}
	else {
		$AppUI->setProperties('Project isn\'t Time Effective', UI_MSG_PROP_KO);
		if (strlen($r)>0) {
			$r{strlen($r)-1}=' ';
			$AppUI->setProperties($r, UI_MSG_PROP_KO);
		}
		$koMsg.='Time Effective';
	}
}

if ($okMsg{strlen($okMsg)-2}==",") $okMsg{strlen($okMsg)-2}=".";
if ($koMsg{strlen($koMsg)-2}==",") $koMsg{strlen($koMsg)-2}=".";

if (strlen($koMsg) == 14 && strlen($okMsg) > 11 )
	$AppUI->setMsg( $okMsg , UI_MSG_PROP_OK);
elseif (strlen($koMsg) > 14) 
	$AppUI->setMsg( $koMsg , UI_MSG_PROP_KO);
	
setProjectState('PropertiesComputed', true);
	
$AppUI->redirect();

?>