<?php

if (!isset($project_id))
	$project_id = $_REQUEST['project_id'];

function setProjectState($label, $value = null) {
	global $AppUI, $project_id;
	
	if (is_null($value)) return;

	if ($label === null)
		$AppUI->state['ProjectsState'][$project_id] = $value;
	else
		$AppUI->state['ProjectsState'][$project_id][$label] = $value;
}

function unsetProjectState($parent, $label) {
	global $AppUI, $project_id;
	
	if($label !== null)
		unset($AppUI->state['ProjectsState'][$project_id][$label]);
}

function getProjectState($label, $default_value = null ) {	
	global $AppUI, $project_id;
	
	if ($label === null)
		$state = $AppUI->state['ProjectsState'][$project_id];
	else
		$state = $AppUI->state['ProjectsState'][$project_id][$label];
	
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
	
	$AppUI->state['ProjectsState'][$project_id][$parent][$label] = $value;
}

function unsetProjectSubState($parent, $label) {
	global $AppUI, $project_id;
	
	if($label !== null)
		unset($AppUI->state['ProjectsState'][$project_id][$parent][$label]);
}

function getProjectSubState($parent, $label, $default_value = null ) {	
	global $AppUI, $project_id;
	
	$state = $AppUI->state['ProjectsState'][$project_id][$parent][$label];
	
	if (!is_null($state)) {
		return $state;
	} else if (!is_null($default_value)) {
		setProjectSubState($label, $default_value);
		return $default_value;
	} else  {
		return NULL;
	}
}

?>