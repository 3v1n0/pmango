<?php

include "TaskBox.class.php";
include "TaskBoxDB.class.php";
include "../lib/phptreegraph/GDRenderer.php";

class WBS /*implements PMGraph TODO */ {
	private $pProject;
	private $pTaskLevel;
	private $pOpenedTasks;
	private $pClosedTasks;

	private $pShowNames;
	private $pShowProgress;
	private $pShowPlannedData;
	private $pShowActualData;
	private $pShowPlannedResources;
	private $pShowActualResources;
	private $pShowPlannedTimeframe;
	private $pShowActualTimeframe;
	private $pShowAlerts;

	private $pTasks;
	private $pTasksTable;
	private $pTree;
	private $pNodeID;
	
	public function WBS($project, $level_space = 35, $node_space = 10, $subtree_space = 30) {
		$this->pProject = $project;
		$this->pTaskLevel = 1;
		$this->pOpenedTasks = array();
		$this->pClosedTasks = array();

		$this->pShowNames = false;
		$this->pShowProgress = false;
		$this->pShowPlannedData = false;
		$this->pShowActualData = false;
		$this->pShowPlannedResources = false;
		$this->pShowActualResources = false;
		$this->pShowPlannedTimeframe = false;
		$this->pShowActualTimeframe = false;
		$this->pShowAlerts = false;

		$this->pTasks = array();
		$this->pTasksTable = array();
		$this->pTree = new GDRenderer($level_space, $node_space, $subtree_space);
		$this->pNodeID = 1;
	}

	public function setProject($p) {
		$this->pProject = abs(intval($p));
	}

	public function setTaskLevel($tl) {
		$this->pTaskLevel = intval($tl) > 1 ? intval($tl) : 1;
	}

	public function setOpenedTasks($tsk) {
		$this->pOpenedTasks = is_array($tsk) ? $tsk : array();
	}

	public function setClosedTasks($tsk) {
		$this->pClosedTasks = is_array($tsk) ? $tsk : array();
	}
	
	public function showNames($show) {
		$this->pShowNames = $show ? true : false;
	}
	
	public function showProgress($show) {
		$this->pShowProgress = $show ? true : false;
	}
	
	public function showPlannedData($show) {
		$this->pShowPlannedData = $show ? true : false;
	}

	public function showActualData($show) {
		$this->pShowActualData = $show ? true : false;
	}
	
	public function showPlannedResources($show) {
		$this->pShowPlannedResources = $show ? true : false;
		
		if ($this->pShowPlannedResources)
			$this->pShowActualResources = false;
	}

	public function showActualResources($show) {
		$this->pShowActualResources = $show ? true : false;
		
		if ($this->pShowActualResources)
			$this->pShowPlannedResources = false;
	}
	
	public function showPlannedTimeframe($show) {
		$this->pShowPlannedTimeframe = $show ? true : false;
	}

	public function showActualTimeframe($show) {
		$this->pShowActualTimeframe = $show ? true : false;
	}
	
	public function showAlerts($show) {
		$this->pShowAlerts = $show ? true : false;
	}
	
	public function getType() {
		return "WBS";
	}
	
	public function getWidth() {
		return $this->pTree->getWidth();
	}
	
	public function getHeight() {
		return $this->pTree->getHeight();
	}

	public function getImage() {
		$this->buildWBS();
		return $this->pTree->image();
	}

	public function draw($format = "png", $file = null) {
		switch ($format) {
			case "png":
				if (!$file) header("Content-type: image/png");
				imagepng($this->getImage(), $file);
				break;
			case "jpg":
			case "jpeg":
				if (!$file) header("Content-type: image/jpeg");
				imagejpeg($this->getImage(), $file);
				break;
			case "gif":
				if (!$file) header("Content-type: image/jpeg");
				imagegif($this->getImage(), $file);
				break;
		}
	}
	
	//--
	
	private function buildWBS() {
		$this->initRoot();
		$this->pullTasks();
		$this->initTree();
		$this->populateTree();
	}
	
	private function initRoot() {
		$sql = "SELECT project_name FROM projects WHERE project_id = ".$this->pProject;
		$db = db_exec($sql);
		echo db_error();
		$row = db_fetch_row($db);
		
		// XXX if the child tbx are minimal, use also a minimal root (?). 
		$tbx = new TaskBox(null);
		$tbx->setName($row['project_name']);
		
		$this->pTree->add($this->pNodeID, 0, "", $tbx->getWidth(), $tbx->getHeight(), $tbx->getImage());
		$this->pNodeID++;
	}
	
	private function pullTasks() {
		$query = "SELECT task_id, task_parent FROM tasks t ".
		         "WHERE t.task_project = ".$this->pProject." ".
		         "ORDER BY task_id, task_parent, task_wbs_index";
		
		$result = db_exec($query);
		$error = db_error();
		if ($error) {
			echo $error;
			exit;
		}
		
		if (!empty($this->pTasks))
			$this->pTasks = array();
		
		for ($i = 0; $i < db_num_rows($result); $i++) {
			$task = db_fetch_assoc($result);
			
			$add = false;
		    
			if ($task["task_id"] == $task["task_parent"])
				$add = true;
		
			if (CTask::getTaskLevel($task["task_id"]) <= $this->pTaskLevel &&
				  !in_array($task["task_id"], $this->pClosedTasks) ||
			      in_array($task["task_id"], $this->pClosedTasks) &&
			      !in_array($task["task_parent"], $this->pClosedTasks) &&
			      !CTask::isLeafSt($task["task_id"])) {
				$add = true;
			}
		
			if (in_array($task["task_id"], $this->pOpenedTasks) ||
				  in_array($task["task_parent"], $this->pOpenedTasks))
				$add = true;
				
			if ($add) {
				$tbxdb = new taskBoxDB($task['task_id']);
				
				$wbs = $tbxdb->getWBS();
				$this->pTasks[$wbs]['task_id'] = $task['task_id'];
				$this->pTasks[$wbs]['task_parent'] = $task['task_parent'];
				$this->pTasks[$wbs]['tbxdb'] = $tbxdb;
				$this->pTasks[$wbs]['id'] = $this->pNodeID;
				$this->pTasksTable[$task['task_id']] = $this->pNodeID;
		
				$this->pNodeID++;
			}
		}
		
		ksort($this->pTasks);
	}
	
	private function initTree() {
		$this->pTree->setBGColor(array(255, 255, 255));
		$this->pTree->setLinkColor(array(0, 0, 0));
	}
	
	private function populateTree() {

		foreach ($this->pTasks as $task) {
				
			$tbdb = $task['tbxdb'];
		
			$tbx = new TaskBox($tbdb->getWBS());
			
			if ($this->pShowNames)
				$tbx->setName($tbdb->getTaskName());
			
			if ($this->pShowProgress)
				$tbx->setProgress($tbdb->getProgress());
			
			if ($this->pShowPlannedData)
				$tbx->setPlannedDataArray($tbdb->getPlannedData());
			
			if ($this->pShowActualData)
				$tbx->setActualDataArray($tbdb->getActualData());
			
			if ($this->pShowPlannedTimeframe)
				$tbx->setPlannedTimeframeArray($tbdb->getPlannedTimeframe());
			
			if ($this->pShowActualTimeframe)
				$tbx->setActualTimeframeArray($tbdb->getActualTimeframe());
			
			if ($this->pShowPlannedResources)
				$tbx->setResourcesArray($tbdb->getPlannedResources());
			else if ($this->pShowActualResources)
				$tbx->setResourcesArray($tbdb->getActualResources());

			if ($this->pShowAlerts)
				$tbx->setAlerts($tbdb->isAlerted());
		
			$tbx->showExpand((!$tbdb->isLeaf() && !$this->findTaskChild($task)));
			
			$parent = 1;
			if ($task['task_parent'] != $task['task_id'] && $task['task_parent'] > 1)
				$parent = $this->pTasksTable[$task['task_parent']];
				
			$alert_padding = intval($tbx->getAlertSize()/2);
			
			$this->pTree->add($task['id'], $parent, null, $tbx->getWidth(), $tbx->getHeight(),
			                    $tbx->getImage(), 1-$alert_padding, $alert_padding);
		}
	}
	
	private function findTaskChild($target) {
	
		foreach ($this->pTasks as $task) {
			if ($task['task_parent'] == $target['task_id'] &&
			      $task['task_id'] != $target['task_id'])
				return true;
		}
		
		return false;
	}
} 

?>