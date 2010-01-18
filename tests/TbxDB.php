<?php
$baseDir = "../";
include "$baseDir/includes/config.php";
include "$baseDir/includes/db_adodb.php";
include "$baseDir/includes/db_connect.php";
include "$baseDir/includes/main_functions.php";

include "$baseDir/classes/ui.class.php";
if (!isset( $_SESSION['AppUI'] ) || isset($_GET['logout'])) {
    if (isset($_GET['logout']) && isset($_SESSION['AppUI']->user_id))
    {
        $AppUI =& $_SESSION['AppUI'];
		$user_id = $AppUI->user_id;
        addHistory('login', $AppUI->user_id, 'logout', $AppUI->user_first_name . ' ' . $AppUI->user_last_name);
    }

	$_SESSION['AppUI'] = new CAppUI;
}
$AppUI =& $_SESSION['AppUI'];
include "$baseDir/modules/tasks/tasks.class.php";

class taskBoxDB {
	private $pTaskID;
	private $pCTask;
	private $pChild;

	private $pPlannedData;
	private $pActualData;
	private $pPlannedTimeframe;
	private $pActualTimeFrame;
	private $pPlannedResources;
	private $pActualResources;

	private $pUseCache;

	public function taskBoxDB($id) {
		$this->pTaskID = $id;
		$this->pCTask = new CTask($id);

		if (!$this->pCTask->isLeaf()) {
			$this->pChild = $this->pCTask->getChild(null, $this->pCTask->getProjectID());
		} else {
			$this->pChild = null;
		}

		$this->pUseCache = true;
	}

	public function useCache($use) {
		$this->pUseCache = $use ? true : false;
	}

	public function getWBS() {
		return $this->pCTask->getWBS();
	}

	public function getTaskName() {
		return $this->pCTask->getName();
	}

	public function getProgress() {
		return intval($this->pCTask->getProgress());
	}

	public function getPlannedData() {
		if (!$this->pPlannedData || !$this->pUseCache)
			$this->computePlannedData();

		$duration = $this->pPlannedData['duration'];
		$effort = $this->pPlannedData['effort'];
		$cost = $this->pPlannedData['cost'];

		return array("duration" => !is_null($duration) ? $duration : "NA",
		             "effort"   => !is_null($effort) ? $effort." ph" : "NA",
		             "cost"     => !is_null($cost) ? $cost." ".$dPconfig['currency_symbol'] : "NA");
	}

	private function computePlannedData() {
		$start = $this->pCTask->getStartDateFromTask(null, $this->pChild);
		$end = $this->pCTask->getFinishDateFromTask(null, $this->pChild);

		$duration = $this->getTimediff($start['task_start_date'], $end['task_finish_date']);
		$effort = $this->pCTask->getEffort();
		$cost = $this->pCTask->getBudget();

		$this->pPlannedData['duration'] = $duration;
		$this->pPlannedData['effort'] = $effort;
		$this->pPlannedData['cost'] = $cost;
	}

	public function getActualData() {
		if (!$this->pActualData || !$this->pUseCache)
			$this->computePlannedData();

		$duration = $this->pActualData['duration'];
		$effort = $this->pActualData['effort'];
		$cost = $this->pActualData['cost'];

		// TODO check for progress < 100;
		return array("duration" => !is_null($duration) ? $duration : "NA",
		             "effort"   => !is_null($effort) ? $effort." ph" : "NA",
		             "cost"     => !is_null($cost) ? $cost.$dPconfig['currency_symbol'] : "NA");
	}

	private function computeActualData() {
		$start = $this->pCTask->getActualStartDate(null, $this->pChild);
		$end = $this->pCTask->getActualFinishDate(null, $this->pChild);

		$duration = $this->getTimediff($start['task_log_start_date'], $end['task_log_finish_date']);
		$effort = $this->pCTask->getActualEffort(null, $this->pChild);
		$cost = $this->pCTask->getActualCost(null, $this->pChild);

		$this->pActualData['duration'] = $duration;
		$this->pActualData['effort'] = $effort;
		$this->pActualData['cost'] = $cost;
	}

	public function getPlannedTimeframe() {
		if (!$this->pPlannedTimeframe || !$this->pUseCache)
			$this->computePlannedTimeframe();

		return array("start" => $this->dateFormat($this->pPlannedTimeframe['start']),
		             "end"   => $this->dateFormat($this->pPlannedTimeframe['end']));
	}

	private function computePlannedTimeframe() {
		$start = $this->pCTask->getStartDateFromTask(null, $this->pChild);
		$end = $this->pCTask->getFinishDateFromTask(null, $this->pChild);

		$this->pPlannedTimeframe['start'] = $start['task_start_date'];
		$this->pPlannedTimeframe['end'] = $end['task_finish_date'];
	}

	public function getActualTimeframe(){
		if (!$this->pActualTimeFrame || !$this->pUseCache)
			$this->computeActualTimeframe();

		return array("start" => $this->dateFormat($this->pActualTimeFrame['start']),
		             "end"   => $this->dateFormat($this->pActualTimeFrame['end']));
	}

	private function computeActualTimeframe() {
		$start = $this->pCTask->getActualStartDate(null, $this->pChild);
		$end = $this->pCTask->getActualFinishDate(null, $this->pChild);

		if ($this->getProgress() < 100)
			$end = null;

		$this->pActualTimeFrame['start'] = $start['task_start_date'];
		$this->pActualTimeFrame['end'] = $end['task_finish_date'];
	}

	public function getPlannedResources() {
		if (!$this->pPlannedResources || !$this->pUseCache)
			$this->pPlannedResources = $this->getPeopleEffort(false);

		return $this->pPlannedResources;
	}

	public function getActualResources() {
		if (!$this->pActualResources || !$this->pUseCache)
			$this->pActualResources = $this->getPeopleEffort(true);

		return $this->pActualResources;
	}

	public function isAlerted() {}

	//--

	private function getPeopleEffort($get_actual = true) {
		$q = new DBQuery();
		$q->clear();
		$q->addTable('user_tasks','ut');
		$q->addQuery('CONCAT_WS(" ", u.user_last_name, u.user_first_name) as name, '.
		             'pr.proles_name as role, ut.effort as planned_effort'.
		             ($get_actual ? ', round(ut.perc_effort*ut.effort/100) as actual_effort' : ''));
		$q->addJoin('users','u','u.user_id=ut.user_id');
		$q->addJoin('project_roles','pr','pr.proles_id = ut.proles_id');
		$q->addWhere('ut.proles_id > 0 && ut.task_id = '.$this->pTaskID);
		$resources = $q->loadList();

		return !empty($resources) ? $resources : null;
	}

	private function getTimediff($start, $end) {
		$start = strtotime($start);
		$end = strtotime($end);

		$diff = $end - $start;

		if ($diff == 0)
			return null;
		else if ($diff < 60)
			$unit = "s";
		else if ($diff < 3600) {
			$diff = intval($diff/60 + 0.5);
			$unit = "m";
		} else if ($diff < 86400) {
			$diff = intval($diff/3600 + 0.5);
			$unit = "h";
		} else /*if ($diff < 2592000)*/ {
			$diff = intval($diff/86400 + 0.5);
			$unit = "d";
		} /* else {
			$diff = intval($diff/2592000 + 0.5);
			$unit = "M";
		}*/

		return $diff." ".$unit;
	}

	private function dateFormat($date) {
		if (!empty($date) && $date != null) {
			return date("Y.m.d", strtotime($date));
		} else {
			return "NA";
		}
	}
}

//////////////////// Test classe ////////////////////

$tdb = new taskBoxDB(86);
echo $tdb->getWBS()."\n";
echo $tdb->getTaskName()."\n";
print_r($tdb->getPlannedData());
print_r($tdb->getActualData());
print_r($tdb->getPlannedTimeframe());
print_r($tdb->getActualTimeframe());
print_r($tdb->getPlannedResources());
print_r($tdb->getActualResources());
echo $tdb ->getProgress()."\n";
?>
