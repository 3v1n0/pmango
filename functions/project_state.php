<?php

define("PROJECTS_STATE_KEY", 'ProjectsState');

if (!isset($project_id))
	$project_id = $_REQUEST['project_id'];
	
function unsetProjectsStates() {
	global $AppUI;
	
	$AppUI->unsetState(PROJECTS_STATE_KEY);
}

function setProjectState($label, $value = null) {
	global $AppUI, $project_id;
	
	if (is_null($value)) return;

	if ($label === null)
		$AppUI->state[PROJECTS_STATE_KEY][$project_id] = $value;
	else
		$AppUI->state[PROJECTS_STATE_KEY][$project_id][$label] = $value;
}

function unsetProjectState($parent, $label) {
	global $AppUI, $project_id;
	
	if($label !== null)
		unset($AppUI->state[PROJECTS_STATE_KEY][$project_id][$label]);
}

function getProjectState($label, $default_value = null ) {	
	global $AppUI, $project_id;
	
	if ($label === null)
		$state = $AppUI->state[PROJECTS_STATE_KEY][$project_id];
	else
		$state = $AppUI->state[PROJECTS_STATE_KEY][$project_id][$label];
	
	if (!is_null($state)) {
		return $state;
	} else if (!is_null($default_value)) {
		setProjectSubState($label, $default_value);
		return $default_value;
	} else  {
		return NULL;
	}
}

function setProjectSubState($parent, $label, $value = null) {
	global $AppUI, $project_id;
	
	if (is_null($value)) return;
	
	$AppUI->state[PROJECTS_STATE_KEY][$project_id][$parent][$label] = $value;
}

function unsetProjectSubState($parent, $label) {
	global $AppUI, $project_id;
	
	if($label !== null)
		unset($AppUI->state[PROJECTS_STATE_KEY][$project_id][$parent][$label]);
}

function getProjectSubState($parent, $label, $default_value = null) {	
	global $AppUI, $project_id;
	
	$state = $AppUI->state[PROJECTS_STATE_KEY][$project_id][$parent][$label];
	
	if (!is_null($state)) {
		return $state;
	} else if (!is_null($default_value)) {
		setProjectSubState($parent, $label, $default_value);
		return $default_value;
	} else  {
		return NULL;
	}
}

?>