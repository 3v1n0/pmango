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
	private $pActualTimeframe;
	private $pPlannedResources;
	private $pActualResources;

	private $pUseCache;

	const ALERT_NONE = 0;
	const ALERT_WARNING = 1;
	const ALERT_ERROR = 2;

	public function TaskBoxDB($id) {
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
		global $dPconfig;

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
		if (!$this->pPlannedTimeframe || !$this->pUseCache)
			$this->computePlannedTimeframe();

		$tf = $this->pPlannedTimeframe;
		$duration = $this->getTimediff($tf['start'], $tf['end']);
		$effort = $this->pCTask->getEffort();
		$cost = $this->pCTask->getBudget();

		$this->pPlannedData['duration'] = $duration;
		$this->pPlannedData['effort'] = $effort;
		$this->pPlannedData['cost'] = $cost;
	}

	public function getActualData() {
		if (!$this->pActualData || !$this->pUseCache)
			$this->computeActualData();

		$duration = $this->pActualData['duration'];
		$effort = $this->pActualData['effort'];
		$cost = $this->pActualData['cost'];

		// TODO check for progress < 100;
		return array("duration" => !is_null($duration) ? $duration : "NA",
		             "effort"   => !is_null($effort) ? $effort." ph" : "NA",
		             "cost"     => !is_null($cost) ? $cost.$dPconfig['currency_symbol'] : "NA");
	}

	private function computeActualData() {
		if (!$this->pActualTimeframe || !$this->pUseCache)
			$this->computeActualTimeframe();

		$tf = $this->pActualTimeframe;
		$duration = $this->getTimediff($tf['start'], $tf['end']);
		$effort = $this->pCTask->getActualEffort(null, $this->pChild);
		$cost = $this->pCTask->getActualCost(null, $this->pChild);

		$this->pActualData['duration'] = $duration;
		$this->pActualData['effort'] = $effort;
		$this->pActualData['cost'] = $cost;
	}

	public function getPlannedTimeframe() {
		if (!$this->pPlannedTimeframe || !$this->pUseCache)
			$this->computePlannedTimeframe();

		$tf = $this->pPlannedTimeframe;

		return array("start" => $this->dateFormat($tf['start']),
		             "end"   => $this->dateFormat($tf['end']));
	}

	private function computePlannedTimeframe() {
		$start = $this->pCTask->getStartDateFromTask(null, $this->pChild);
		$end = $this->pCTask->getFinishDateFromTask(null, $this->pChild);

		$this->pPlannedTimeframe['start'] = $start['task_start_date'];
		$this->pPlannedTimeframe['end'] = $end['task_finish_date'];
	}

	public function getActualTimeframe(){
		if (!$this->pActualTimeframe || !$this->pUseCache)
			$this->computeActualTimeframe();

		$tf = $this->pActualTimeframe;

		return array("start" => $this->dateFormat($tf['start']),
		             "end"   => $this->dateFormat($tf['end']));
	}

	private function computeActualTimeframe() {
		$start = $this->pCTask->getActualStartDate(null, $this->pChild);
		$end = $this->pCTask->getActualFinishDate(null, $this->pChild);

		if ($this->getProgress() < 100)
			$end = null;

		$this->pActualTimeframe['start'] = $start['task_log_start_date'];
		$this->pActualTimeframe['end'] = $end['task_log_finish_date'];
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

	public function isAlerted() {
		$alert = TaskBoxDB::ALERT_NONE;

		//TODO check for unfinished tasks!

		if (!$this->pPlannedTimeframe || !$this->pUseCache)
			$this->computePlannedTimeframe();

		if (!$this->pActualTimeframe || !$this->pUseCache)
			$this->computeActualTimeframe();

		$p = $this->pPlannedTimeframe;
		$a = $this->pActualTimeframe;

		if ($p['start'] != $a['start'] || $p['end'] != $a['end']) {
			$alert = TaskBoxDB::ALERT_WARNING;

			if (strtotime($p['start']) < strtotime($a['start']) ||
			    strtotime($p['end']) < strtotime($a['end'])) {
				  $alert = TaskBoxDB::ALERT_ERROR;
				  return $alert;
			    }
		}

		if (!$this->pPlannedData || !$this->pUseCache)
			$this->computePlannedData();

		if (!$this->pActualData || !$this->pUseCache)
			$this->computeActualData();

		$p = $this->pPlannedData;
		$a = $this->pActualData;

		if ($p['effort'] != $a['effort'] || $p['cost'] != $a['cost']) {
			$alert = TaskBoxDB::ALERT_WARNING;

			if ($p['effort'] < $a['effort'] || $p['cost'] < $a['cost']) {
				$alert = TaskBoxDB::ALERT_ERROR;
				return $alert;
			}
		}

		$pa = $this->getActualResources();
		foreach($pa as $r) {
			if ($r['planned_effort'] != $r['actual_effort']) {
				$alert = TaskBoxDB::ALERT_WARNING;

				if ($r['planned_effort'] < $r['actual_effort']) {
					$alert = TaskBoxDB::ALERT_ERROR;
					break;
				}
			}
		}

		return $alert;
	}

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

		$max_p = -1;
		$max_a = -1;

		foreach ($resources as $res) {
			$max_p = max(strlen($res['planned_effort']), $max_p);
			$max_a = max(strlen($res['actual_effort']), $max_a);
		}

		foreach ($resources as &$res) {
			$res['planned_effort'] = str_pad($res['planned_effort'], $max_p, "0", STR_PAD_LEFT);

			if (isset($res['actual_effort']))
				$res['actual_effort'] = str_pad($res['actual_effort'], $max_a, "0", STR_PAD_LEFT);
		}

		return !empty($resources) ? $resources : null;
	}

	private function getTimediff($start, $end) {
		if (is_null($start))
			$start = time();
		else
			$start = strtotime($start);

		if (is_null($end))
			$end = time();
		else
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

$tdb = new TaskBoxDB(86);
echo $tdb->getWBS()."\n";
echo $tdb->getTaskName()."\n";
print_r($tdb->getPlannedData());
print_r($tdb->getActualData());
print_r($tdb->getPlannedTimeframe());
print_r($tdb->getActualTimeframe());
print_r($tdb->getPlannedResources());
print_r($tdb->getActualResources());
echo $tdb->getProgress()."\n";
echo $tdb->isAlerted();
?>
