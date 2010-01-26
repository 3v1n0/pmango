<?php

/**
---------------------------------------------------------------------------

 PMango Project

 Title:      TaskBoxDB.

 File:       TaskBoxDB.class.php
 Location:   pmango/modules/tasks
 Started:    2009.12.18
 Author:     Marco Trevisan
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2010.01.22, Marco Trevisan
   0.8, fixed the actual/planned resources methods
 - 2010.01.19, Matteo Pratesi
   0.6, leaf support.
 - 2010.01.18, Marco Trevisan
   0.5, Code review, moved to a class with caching support
 - 2010.01.15, Marco Trevisan
   0.3.5, WBS support and code cleanup.
 - 2010.01.12, Matteo Pratesi
   0.3, Actual Data, Timeframe, Resources
 - 2010.01.07, Matteo Pratesi
   0.2, Planned Timeframe and Progress queries.
 - 2009.12.18, Marco Trevisan
   0.1, added basic queries.

---------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2006 Giovanni A. Cignoni, Lorenzo Ballini, Marco Bonacchi
 Copyright (C) 2009-2010 Marco Trevisan (Treviño) <mail@3v1n0.net>
 Copyright (C) 2009-2010 Matteo Pratesi <pratesi.matteo@gmail.com>
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
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation Inc., 51 Franklin St, 5th Floor, Boston, MA 02110-1301 USA.

---------------------------------------------------------------------------
*/

class TaskBoxDB {
	private $pTaskID;
	private $pCTask;
	private $pChild;

	private $pName;
	private $pWBS;
	private $pProgress;
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

	public function getId(){
		return $this->pTaskID;
	}
	
	public function getWBS() {
		if (!$this->pWBS || !$this->pUseCache)
			$this->pWBS = $this->pCTask->getWBS().".";

		return $this->pWBS;
	}

	public function getTaskName() {
		if (!$this->pName || !$this->pUseCache)
			$this->pName = $this->pCTask->getName();

		return $this->pName;
	}

	public function getProgress() {
		if (!$this->pProgress || !$this->pUseCache)
			$this->pProgress = intval($this->pCTask->getProgress());

		return $this->pProgress;
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

		$duration = $this->getTimediff($this->pPlannedTimeframe['start'], $this->pPlannedTimeframe['end']);
		$effort = $this->pCTask->getEffort();
		$cost = $this->pCTask->getBudget();

		$this->pPlannedData['duration'] = $duration;
		$this->pPlannedData['effort'] = $effort;
		$this->pPlannedData['cost'] = $cost;
	}

	public function getActualData() {
		global $dPconfig;

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

		$duration = $this->getTimediff($this->pActualTimeframe['start'], $this->pActualTimeframe['end']);
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
		if (!$this->pActualTimeframe || !$this->pUseCache)
			$this->computeActualTimeframe();

		return array("start" => $this->dateFormat($this->pActualTimeframe['start']),
		             "end"   => $this->dateFormat($this->pActualTimeframe['end']));
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
			$this->computePlannedResources();

		return $this->pPlannedResources;
	}

	private function computePlannedResources() {

		$q = new DBQuery();
		$q->clear();
		$q->addQuery('ut.user_id as uid, ut.proles_id as rid, '.
		             'CONCAT_WS(" ", u.user_last_name, u.user_first_name) as name, '.
		             'pr.proles_name as role, ut.effort as planned_effort');
		$q->addTable('user_tasks','ut');
		$q->addJoin('users', 'u', 'u.user_id = ut.user_id');
		$q->addJoin('project_roles', 'pr', 'pr.proles_id = ut.proles_id');
		$q->addWhere('ut.proles_id > 0 and ut.task_id = '.$this->pTaskID);

		$resources = $q->loadList();

		$max_digits = 1;
		foreach ($resources as &$res) {
			$max_digits = max(strlen($res['planned_effort']), $max_digits);
		}

		if ($max_digits > 1)
			foreach ($resources as &$res)
				$res['planned_effort'] = str_pad($res['planned_effort'], $max_digits, "0", STR_PAD_LEFT);

		$this->pPlannedResources = $resources;
	}

	public function getActualResources() {
		if (!$this->pActualResources || !$this->pUseCache)
			$this->computeActualResources();

		return $this->pActualResources;
	}

	private function computeActualResources() {

		$q = new DBQuery();

		if (!$this->pChild) {
			if (!$this->pPlannedResources || !$this->pUseCache)
				$this->computePlannedResources();

			$resources = $this->pPlannedResources;
		} else {
			$q->clear();
			$q->addQuery('ut.user_id as uid, ut.proles_id as rid, '.
			             'CONCAT_WS(" ", u.user_last_name, u.user_first_name) as name, '.
		                 'pr.proles_name as role, sum(ut.effort) as planned_effort');
			$q->addTable('user_tasks','ut');
			$q->addJoin('users', 'u', 'u.user_id = ut.user_id');
			$q->addJoin('project_roles', 'pr', 'pr.proles_id = ut.proles_id');
			$q->addWhere('ut.proles_id > 0 and '.
			             '(SELECT COUNT(*) FROM tasks AS tt WHERE ut.task_id != tt.task_id '.
			                                      '&& tt.task_parent = ut.task_id) < 1 and '.
			             'ut.task_id in ('.$this->pChild.')');
			$q->addGroup('ut.user_id, ut.proles_id');

			$resources = $q->loadList();

			$max_digits = 1;
			foreach ($resources as &$res) {
				$max_digits = max(strlen($res['planned_effort']), $max_digits);
				$this->pTaskPeople[$res['rid']]['role'] = $res['role'];
				$this->pTaskPeople[$res['rid']][$res['uid']] = $res['name'];
			}

			if ($max_digits > 1)
				foreach ($resources as &$res)
					$res['planned_effort'] = str_pad($res['planned_effort'], $max_digits, "0", STR_PAD_LEFT);

		}

		$q->clear();
		$q->addQuery('task_log_creator as uid, task_log_proles_id as rid, '.
		             'sum(task_log_hours) as actual_effort');
		$q->addTable('task_log','tl');
		$q->addWhere('task_log_proles_id > 0 and '.
		             ($this->pChild ? '(SELECT COUNT(*) FROM tasks AS tt WHERE '.
		                                     'tl.task_log_creator != tt.task_id'.
			                    '&& tt.task_parent = tl.task_log_task) < 1 and ' : '').
			         'task_log_task '.($this->pChild ? 'in ('.$this->pChild.')' : '= '.$this->pTaskID));
		$q->addGroup('task_log_creator, task_log_proles_id');

		$a_resources = $q->loadList();

		$max_digits = 1;
		foreach ($resources as &$pres) {
			$found = false;

			foreach($a_resources as $ares) {
				if ($ares['uid'] == $pres['uid'] && $ares['rid'] == $pres['rid']) {
					$pres['actual_effort'] = $ares['actual_effort'];
					$found = true;
					break;
				}
			}

			$max_digits = max(strlen($pres['actual_effort']), $max_digits);

			if ($found == false)
				$pres['actual_effort'] = 0;
		}

		if ($max_digits > 1)
			foreach ($resources as &$res)
				$res['actual_effort'] = str_pad($res['actual_effort'], $max_digits, "0", STR_PAD_LEFT);

		$this->pActualResources = $resources;
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

		if ($pa == null)
			return $alert;

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

	public function isLeaf(){
		return $this->pChild ? false : true;
	}

	//--

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

?>
