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

define('TTF_DIR', "{$dPconfig['root_dir']}/fonts/Droid/");

include ("{$dPconfig['root_dir']}/lib/jpgraph/src/jpgraph.php");
include ("{$dPconfig['root_dir']}/lib/jpgraph/src/jpgraph_gantt.php");

########################################
////////////////////////////////////////
########################################

// TODO: better implementation, split classes and show line to unite bars

class PMGanttBar extends GanttPlotObject {
	public $progress;
	public $leftMark,$rightMark;
	private $iEnd;
	private $iHeightFactor=0.5;
	private $iFillColor="white",$iFrameColor="black";
	private $iShadow=false,$iShadowColor="darkgray",$iShadowWidth=1,$iShadowFrame="black";
	private $iPattern=GANTT_RDIAG,$iPatternColor="blue",$iPatternDensity=95;
	private $iBreakStyle=false, $iBreakLineStyle='dotted',$iBreakLineWeight=1;
	private $iLabel;
	//---------------
	// CONSTRUCTOR
	function __construct($aPos,$aLabel,$aStart,$aEnd,$aCaption="",$aHeightFactor=0.6) {
		parent::__construct();
		$this->iStart = $aStart;
		// Is the end date given as a date or as number of days added to start date?
		if( is_string($aEnd) ) {
			// If end date has been specified without a time we will asssume
			// end date is at the end of that date
			if( strpos($aEnd,':') === false ) {
				$this->iEnd = strtotime($aEnd)+SECPERDAY-1;
			}
			else {
				$this->iEnd = $aEnd;
			}
		}
		elseif(is_int($aEnd) || is_float($aEnd) ) {
			$this->iEnd = strtotime($aStart)+round($aEnd*SECPERDAY);
		}
		$this->iVPos = $aPos;
		$this->iHeightFactor = $aHeightFactor;
		$this->iLabel = $aLabel;
		$this->title->Set($aLabel);
		$this->caption = new TextProperty($aCaption);
		$this->caption->Align("left","center");
		$this->leftMark =new PlotMark();
		$this->leftMark->Hide();
		$this->rightMark=new PlotMark();
		$this->rightMark->Hide();
		$this->progress = new Progress();
	}

	//---------------
	// PUBLIC METHODS
	function SetShadow($aShadow=true,$aColor="gray") {
		$this->iShadow=$aShadow;
		$this->iShadowColor=$aColor;
	}

	function SetBreakStyle($aFlg=true,$aLineStyle='dotted',$aLineWeight=1) {
		$this->iBreakStyle = $aFlg;
		$this->iBreakLineStyle = $aLineStyle;
		$this->iBreakLineWeight = $aLineWeight;
	}

	function GetMaxDate() {
		return $this->iEnd;
	}

	function SetHeight($aHeight) {
		$this->iHeightFactor = $aHeight;
	}

	function SetColor($aColor) {
		$this->iFrameColor = $aColor;
	}

	function SetFillColor($aColor) {
		$this->iFillColor = $aColor;
	}

	function GetAbsHeight($aImg) {
		if( is_int($this->iHeightFactor) || $this->leftMark->show || $this->rightMark->show ) {
			$m=-1;
			if( is_int($this->iHeightFactor) )
			$m = $this->iHeightFactor;
			if( $this->leftMark->show )
			$m = max($m,$this->leftMark->width*2);
			if( $this->rightMark->show )
			$m = max($m,$this->rightMark->width*2);

			return $m;
		}
		else
		return -1;
	}

	function SetPattern($aPattern,$aColor="blue",$aDensity=95) {
		$this->iPattern = $aPattern;
		$this->iPatternColor = $aColor;
		$this->iPatternDensity = $aDensity;
	}

	function Stroke($aImg,$aScale) {
		$factory = new RectPatternFactory();
		$prect = $factory->Create($this->iPattern,$this->iPatternColor);
		$prect->SetDensity($this->iPatternDensity);

		// If height factor is specified as a float between 0,1 then we take it as meaning
		// percetage of the scale width between horizontal line.
		// If it is an integer > 1 we take it to mean the absolute height in pixels
		if( $this->iHeightFactor > -0.0 && $this->iHeightFactor <= 1.1)
		$vs = $aScale->GetVertSpacing()*$this->iHeightFactor;
		elseif(is_int($this->iHeightFactor) && $this->iHeightFactor>2 && $this->iHeightFactor < 200 )
		$vs = $this->iHeightFactor;
		else {
			JpGraphError::RaiseL(6028,$this->iHeightFactor);
			//	("Specified height (".$this->iHeightFactor.") for gantt bar is out of range.");
		}

		// Clip date to min max dates to show
		$st = $aScale->NormalizeDate($this->iStart);
		$en = $aScale->NormalizeDate($this->iEnd);

		$limst = max($st,$aScale->iStartDate);
		$limen = min($en,$aScale->iEndDate);

		$xt = round($aScale->TranslateDate($limst));
		$xb = round($aScale->TranslateDate($limen));

		static $ypre;
		if (strlen($this->iLabel)) {
			$yt = round($aScale->TranslateVertPos($this->iVPos)-$vs-($aScale->GetVertSpacing()/2-$vs/2));
			$yb = round($aScale->TranslateVertPos($this->iVPos)-($aScale->GetVertSpacing()/2-$vs/2));
			$ypre = $yb;
		} else {
			$yt = $ypre;
			$yb = $ypre+$vs;
		}
		$middle = round($yt+($yb-$yt)/2);
		$this->StrokeActInfo($aImg,$aScale,$middle);

		// CSIM for title
		if( ! empty($this->title->csimtarget) ) {
			$colwidth = $this->title->GetColWidth($aImg);
			$colstarts=array();
			$aScale->actinfo->GetColStart($aImg,$colstarts,true);
			$n = min(count($colwidth),count($this->title->csimtarget));
			for( $i=0; $i < $n; ++$i ) {
				$title_xt = $colstarts[$i];
				$title_xb = $title_xt + $colwidth[$i];
				$coords = "$title_xt,$yt,$title_xb,$yt,$title_xb,$yb,$title_xt,$yb";

				if( ! empty($this->title->csimtarget[$i]) ) {
					$this->csimarea .= "<area shape=\"poly\" coords=\"$coords\" href=\"".$this->title->csimtarget[$i]."\"";

					if( ! empty($this->title->csimwintarget[$i]) ) {
						$this->csimarea .= "target=\"".$this->title->csimwintarget[$i]."\" ";
					}

					if( ! empty($this->title->csimalt[$i]) ) {
						$tmp = $this->title->csimalt[$i];
						$this->csimarea .= " title=\"$tmp\" alt=\"$tmp\" ";
					}
					$this->csimarea .= " />\n";
				}
			}
		}

		// Check if the bar is totally outside the current scale range
		if( $en <  $aScale->iStartDate || $st > $aScale->iEndDate )
		return;


		// Remember the positions for the bar
		$this->SetConstrainPos($xt,$yt,$xb,$yb);



		$prect->ShowFrame(false);
		$prect->SetBackground($this->iFillColor);
		if( $this->iBreakStyle ) {
			$aImg->SetColor($this->iFrameColor);
			$olds = $aImg->SetLineStyle($this->iBreakLineStyle);
			$oldw = $aImg->SetLineWeight($this->iBreakLineWeight);
			$aImg->StyleLine($xt,$yt,$xb,$yt);
			$aImg->StyleLine($xt,$yb,$xb,$yb);
			$aImg->SetLineStyle($olds);
			$aImg->SetLineWeight($oldw);
		}
		else {
			if( $this->iShadow ) {
				$aImg->SetColor($this->iFrameColor);
				$aImg->ShadowRectangle($xt,$yt,$xb,$yb,$this->iFillColor,$this->iShadowWidth,$this->iShadowColor);
				$prect->SetPos(new Rectangle($xt+1,$yt+1,$xb-$xt-$this->iShadowWidth-2,$yb-$yt-$this->iShadowWidth-2));
				$prect->Stroke($aImg);
			}
			else {
				$prect->SetPos(new Rectangle($xt,$yt,$xb-$xt+1,$yb-$yt+1));
				$prect->Stroke($aImg);
				$aImg->SetColor($this->iFrameColor);
				$aImg->Rectangle($xt,$yt,$xb,$yb);
			}
		}
		// CSIM for bar
		if( ! empty($this->csimtarget) ) {

			$coords = "$xt,$yt,$xb,$yt,$xb,$yb,$xt,$yb";
			$this->csimarea .= "<area shape=\"poly\" coords=\"$coords\" href=\"".$this->csimtarget."\"";

			if( !empty($this->csimwintarget) ) {
				$this->csimarea .= " target=\"".$this->csimwintarget."\" ";
			}

			if( $this->csimalt != '' ) {
				$tmp = $this->csimalt;
				$this->csimarea .= " title=\"$tmp\" alt=\"$tmp\" ";
			}
			$this->csimarea .= " />\n";
		}

		// Draw progress bar inside activity bar
		if( $this->progress->iProgress > 0 ) {

			$xtp = $aScale->TranslateDate($st);
			$xbp = $aScale->TranslateDate($en);
			$len = ($xbp-$xtp)*$this->progress->iProgress-1;

			$endpos = $xtp+$len;
			if( $endpos > $xt ) {

				// Take away the length of the progress that is not visible (before the start date)
				$len -= ($xt-$xtp);

				// Is the the progress bar visible after the start date?
				if( $xtp < $xt )
				$xtp = $xt;

				// Make sure that the progess bar doesn't extend over the end date
				if( $xtp+$len-1 > $xb )
				$len = $xb - $xtp ;

				$prog = $factory->Create($this->progress->iPattern,$this->progress->iColor);
				$prog->SetDensity($this->progress->iDensity);
				$prog->SetBackground($this->progress->iFillColor);
				$barheight = ($yb-$yt+1);
				if( $this->iShadow )
				$barheight -= $this->iShadowWidth;
				$progressheight = floor($barheight*$this->progress->iHeight);
				$marg = ceil(($barheight-$progressheight)/2);
				$pos = new Rectangle($xtp+1,$yt + $marg, $len,$barheight-2*$marg);
				$prog->SetPos($pos);
				$prog->Stroke($aImg);
			}
		}

		// We don't plot the end mark if the bar has been capped
		if( $limst == $st ) {
			$y = $middle;
			// We treat the RIGHT and LEFT triangle mark a little bi
			// special so that these marks are placed right under the
			// bar.

			if( $this->leftMark->GetType() == MARK_LEFTTRIANGLE ) {
				$y = $yb ;

				$bmark = new PlotMark();
				$bmark->SetType($this->leftMark->GetType());
				$bmark->SetColor($this->iFrameColor);
				$bmark->SetFillColor($this->iFillColor);
				$size = $this->leftMark->GetWidth();
				$bmark->SetSize($size % 2 == 0 ? $size+1 : $size+2);
				$bmark->Show();
				$bmark->Stroke($aImg, $xt++, $y);
			}

			$this->leftMark->Stroke($aImg,$xt,$y);
		}
		if( $limen == $en ) {
			$y = $middle;
			// We treat the RIGHT and LEFT triangle mark a little bi
			// special so that these marks are placed right under the
			// bar.

			if( $this->rightMark->GetType() == MARK_RIGHTTRIANGLE ) {
				$y = $yb ;

				$bmark = new PlotMark();
				$bmark->SetType($this->rightMark->GetType());
				$bmark->SetColor($this->iFrameColor);
				$bmark->SetFillColor($this->iFillColor);
				$size = $this->rightMark->GetWidth();
				$bmark->SetSize($size % 2 == 0 ? $size+1 : $size+2);
				$bmark->Show();
				$bmark->Stroke($aImg, $xb--, $y);
			}

			$this->rightMark->Stroke($aImg,$xb,$y);

			$margin = $this->iCaptionMargin;
			if( $this->rightMark->show )
			$margin += $this->rightMark->GetWidth();

			$this->caption->Stroke($aImg,$xb+$margin,$middle);
		}
	}
}

########################################
////////////////////////////////////////
########################################

class PMGantt /*implements PMGraph TODO */ {
	private $pProjectID;
	private $pTaskLevel;
	private $pOpenedTasks;
	private $pClosedTasks;

	private $pStartDate;
	private $pEndDate;

	private $pShowNames;
	private $pShowDependencies;
	private $pShowResources;
	private $pShowTaskGroups;
	private $pUseColors;
	//private $pShowLabels;
	//private $pShowWork;
	//private $pCharSet;

	private $pProject;
	private $pTasks;
	private $pGraph;

	public function PMGantt($project, $width = 600) {
		$this->pProjectID = $project;
		$this->pTaskLevel = 1;
		$this->pOpenedTasks = array();
		$this->pClosedTasks = array();
		$this->pStartDate = null;
		$this->pEndDate = null;
		$this->pShowNames = true;
		$this->pShowDependencies = true;
		$this->pShowResources = false;
		$this->pShowTaskGroups = true;
		$this->pUseColors = true;
		$this->pProject = array();
		$this->pTasks = array();
		$this->pGraph = new GanttGraph($width);
	}

	public function setProject($p) {
		$this->pProject = $p;
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

	public function setWidth($w) {
		$this->pGraph->img->width = intval($w) > 1 ? intval($w) : 0;
	}

	public function setStartDate($sd) {
		$this->pStartDate = $sd;
	}

	public function setEndDate($ed) {
		$this->pEndDate = $ed;
	}

	public function showNames($sn) {
		$this->pShowNames = $sn ? true : false;
	}

	public function showDeps($sd) {
		$this->pShowDeps = $sd ? true : false;
	}

	public function showResources($sr) {
		$this->pShowResources = $sr ? true : false;
	}

	public function showTaskGroups($stg) {
		$this->pShowTaskGroups = $stg ? true : false;
	}

	public function useColors($uc) {
		$this->pUseColors = $uc ? true : false;
	}

	public function getType() {
		return "GANTT";
	}

	public function getImage() {
		$this->buildGANTT();
		return $this->pGraph->Stroke(_IMG_HANDLER);
	}

	public function draw() {
		$this->buildGANTT();
		$this->pGraph->Stroke();
	}

	//--
	private function buildGANTT() {
		$this->pullProjectData();
		$this->pullTasks();
		$this->parseTasks();
		$this->initDates();
		$this->initGraph();
		$this->populateGraph();
	}

	private function pullProjectData() {
		$psql = "SELECT project_id, project_color_identifier, project_name, project_start_date, ".
		        "project_finish_date FROM projects WHERE project_id = ".$this->pProjectID;
		$prc = db_exec($psql);
		echo db_error();
		$pnums = db_num_rows($prc);
		$this->pProject = db_fetch_row($prc);
	}

	private function pullTasks() {
		if (isset($this->pProject['tasks']))
			unset($this->pProject['tasks']);

		$select = "tasks.task_id, task_parent, task_name, task_start_date, task_finish_date, ".
		          "task_priority, task_order, task_project, task_milestone, project_name";
		$join = "LEFT JOIN projects ON project_id = task_project";
		$where = "task_project = $this->pProjectID";

		$tsql = "SELECT $select FROM tasks $join WHERE $where ORDER BY project_id, task_wbs_index";

		$ptrc = db_exec($tsql);
		$nums = db_num_rows($ptrc);
		echo db_error();

		//pull the tasks into an array
		for ($x=0; $x < $nums; $x++) {
			$row = db_fetch_assoc( $ptrc );
			$add = false;

			if($row["task_start_date"] == "0000-00-00 00:00:00"){
				$row["task_start_date"] = date("Y-m-d H:i:s");
			}

			// calculate or set blank task_finish_date if unset
			if($row["task_finish_date"] == "0000-00-00 00:00:00") {
				$row["task_finish_date"] = "";
			}

			if($row["task_finish_date"] == "0000-00-00 00:00:00") {
				$row["task_finish_date"] = "";
			}

			if ($row["task_id"] == $row["task_parent"]) {
				$add = true;
			}


			if (CTask::getTaskLevel($row["task_id"]) <= $this->pTaskLevel &&
				!in_array($row["task_id"], $this->pClosedTasks) ||
			    in_array($row["task_id"], $this->pClosedTasks) &&
			    !in_array($row["task_parent"], $this->pClosedTasks) &&
			    !CTask::isLeafSt($row["task_id"])) {
				   $add = true;
			}

			if (in_array($row["task_id"], $this->pOpenedTasks) ||
				in_array($row["task_parent"], $this->pOpenedTasks))
				$add = true;

			if ($add)
				$this->pProject['tasks'][] = $row;
		}
	}

	private function parseTasks() {
		reset($this->pProject);

		foreach ($this->pProject['tasks'] as $task) {
			if ($task["task_parent"] == $task["task_id"]) {
				$this->populateTasks($task);
				$this->findTaskChild($this->pProject['tasks'], $task["task_id"]);
			}
		}

		if(!$this->pShowTaskGroups) {
			for($i = 0; $i < count($this->pTasks); $i ++ ) {
				// remove task groups
				if($i != count($this->pTasks)-1 && $this->pTasks[$i + 1]['level'] > $this->pTasks[$i]['level']) {
					// it's not a leaf => remove
					array_splice($this->pTasks, $i, 1);
					continue;
				}
			}
		}

		foreach ($this->pTasks as &$task) {
			$found = false;

			foreach ($this->pTasks as $gitem) {
				if ($task['task_id'] == $gitem['task_parent'] &&
					$task['task_id'] != $gitem['task_id']) {
					$found = true;
					break;
				}
			}

			$task['is_leaf'] = !$found;
		}
	}

	private function findTaskChild(&$tarr, $parent, $level = 0){
		$level = $level+1;

		foreach ($tarr as &$t) {
			if($t["task_parent"] == $parent && $t["task_parent"] != $t["task_id"]){
				$this->populateTasks($t, $level);
				$this->findTaskChild($tarr, $t["task_id"], $level);
			}
		}
	}

	private function populateTasks(&$a, $level=0) {
			$a['level'] = $level;
			$this->pTasks[] = $a;
	}

	private function initDates() {
		if (!$this->pStartDate)
			$this->pStartDate = $this->pProject['project_start_date'];

		if ($this->pProject["project_finish_date"] == "0000-00-00 00:00:00" ||
		    empty($this->pProject["project_finish_date"])) {
			 $this->pProject["project_finish_date"] = $this->pStartDate;
		}
	}

	private function initGraph() {
		$this->pGraph->SetUserFont1('DroidSans.ttf', 'DroidSans-Bold.ttf');
		$this->pGraph->SetUserFont2('DroidSerif-Regular.ttf', 'DroidSerif-Bold.ttf',
		                     'DroidSerif-Italic.ttf', 'DroidSerif-BoldItalic.ttf');
		$this->pGraph->SetUserFont3('DroidSansMono.ttf');

		$this->pGraph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH | GANTT_HDAY | GANTT_HWEEK);

		$this->pGraph->SetFrame(false);
		$this->pGraph->SetBox(true, "#000000", 2);
		$this->pGraph->scale->week->SetStyle(WEEKSTYLE_FIRSTDAY);
		//$graph->scale->day->SetStyle(DAYSTYLE_SHORTDATE2);

		// This configuration variable may be obsolete
		$jpLocale = dPgetConfig('jpLocale');
		if ($jpLocale)
			$this->pGraph->scale->SetDateLocale($jpLocale);

		if ($this->pStartDate && $this->pEndDate)
			$this->pGraph->SetDateRange($this->pStartDate, $this->pEndDate);

		$this->pGraph->scale->actinfo->vgrid->SetColor('gray');
		$this->pGraph->scale->actinfo->SetColor('darkgray');
		$titles_size = ($this->pShowNames && $this->pGraph->img->width != 0) ? array($width/6) : null;
		$this->pGraph->scale->actinfo->SetColTitles(array('Task'), $titles_size);
		$this->pGraph->scale->actinfo->SetFont(FF_USERFONT);

		$this->pGraph->scale->tableTitle->Set($this->pProject["project_name"]);
		$this->pGraph->scale->tableTitle->SetFont(FF_USERFONT1, FS_BOLD, 12);

		if ($this->pUseColors)
			$this->pGraph->scale->SetTableTitleBackground("#".$this->pProject["project_color_identifier"]);

		$this->pGraph->scale->tableTitle->Show(true);

		$tinterval = $this->getTimeInterval();

		// show only week or month
		if ($tinterval > 300){
			$this->pGraph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH);
		} else if ($tinterval > 120){
			$this->pGraph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH | GANTT_HWEEK );
			$this->pGraph->scale->week->SetStyle(WEEKSTYLE_WNBR);
		}
	}

	private function getTimeInterval() {

		if ($this->pStartDate && $this->pEndDate){
			$min_d_start = new CDate($this->pStartDate);
			$max_d_end = new CDate($this->pEndDate);
		} else {
			// find out DateRange from gant_arr
			$d_start = new CDate();
			$d_end = new CDate();
			for($i = 0; $i < count(@$this->pTasks); $i++ ){
				$a = $this->pTasks[$i];
				$start = substr($a["task_start_date"], 0, 10);
				$end = substr($a["task_finish_date"], 0, 10);

				$d_start->Date($start);
				$d_end->Date($end);

				if ($i == 0){
					$min_d_start = $d_start;
					$max_d_end = $d_end;
				} else {
					if (Date::compare($min_d_start,$d_start)>0){
						$min_d_start = $d_start;
					}
					if (Date::compare($max_d_end,$d_end)<0){
						$max_d_end = $d_end;
					}
				}
			}
		}

		return $min_d_start->dateDiff($max_d_end);
	}



	private function populateGraph() {
		$now = "2009-12-05 12:00:00";//date("y-m-d");

		for($i = 0, $row = 0; $i < count(@$this->pTasks); $i++) {

			$a     = $this->pTasks[$i];
			if ($a["task_id"] < 1) continue;

			$level = CTask::getTaskLevel($a["task_id"]);
			$task_leaf = $this->pTasks[$i]['is_leaf'];

			if (!$this->pShowTaskGroups) $level = 0;

			$name = Ctask::getWBS($a["task_id"]).".".($this->pShowNames ? " ".$a["task_name"] : "");

			// TODO locale
//			if ($locale_char_set=='utf-8' && function_exists("utf8_decode") ) {
//				$name = utf8_decode($name);
//			}

			$name = str_repeat(" ", $level).$name;

			//using new jpGraph determines using Date object instead of string
			$start = $a["task_start_date"];
			$end_date = $a["task_finish_date"];

			$end_date = new CDate($end_date);
			//	$end->addDays(0);
			$end = $end_date->getDate();

			$start = new CDate($start);
			//	$start->addDays(0);
			$start = $start->getDate();

			$progress = intval($a["task_id"]) > 0 ? CTask::getPr($a["task_id"]) : 0;
			//$ac = );
			//$progress = 0;//$progress > 0 ? intval($progress) : 0;

			$cap = "";
			if(!$start || $start == "0000-00-00"){
				$start = !$end ? date("Y-m-d") : $end;
				$cap .= "(no start date)";
			}

			if(!$end) {
				$end = $start;
				$cap .= " (no finish date)";
			} else {
				$cap = "";
			}

			$caption = "";
			/*if ($showLabels=='1') {
				$sql = "select ut.task_id, u.user_username, ut.perc_effort from user_tasks ut, users u where u.user_id = ut.user_id and ut.task_id = ".$a["task_id"];
				$res = db_exec( $sql );
				while ($rw = db_fetch_row( $res )) {
				switch ($rw[2]) {
				case 100:
				$caption = $caption."".$rw[1].";";
				break;
				default:
				$caption = $caption."".$rw[1]."[".$rw[2]."%];";
				break;
				}
				}
				$caption = substr($caption, 0, strlen($caption)-1);
				}*/

			$bar = new PMGanttBar($row++, array($name), $start, $end, $cap, $task_leaf ? 0.5 : 0.10);//se padre sarebbe meglio 1
			$bar->title->SetFont(FF_USERFONT2, FS_NORMAL, 8);

			if (!$this->pUseColors) {
				$bar->SetColor('black');
				$bar->SetFillColor('white');
				$bar->SetPattern(BAND_SOLID,'white');
			}

			if ($this->pShowNames) {
				$tst = new Image();
				$tst->ttf->SetUserFont2('DroidSerif-Regular.ttf');

				$cut = 2;
				while ($bar->title->GetWidth($tst) >= $this->pGraph->img->width/6 && $cut < strlen($name)) {
					$n = substr($name, 0, strlen($name)-(1+$cut))."...";
					$bar->title->Set($n);
					$cut++;
				}
			}

			$bar2 = null;

		//	if ($task_leaf) {
				//if ($now > )
				$child = CTask::getChild($a["task_id"], $this->pProjectID);
				$tstart = CTask::getActualStartDate($a["task_id"], $child);

				$rMarkshow = true;
				$lMarkshow = true;
				$prMarkshow = false;
				$plMarkshow = false;

				if (!empty($tstart['task_log_start_date'])) {

					if (strtotime($tstart['task_log_start_date']) <= strtotime($start))
						$plMarkshow = true;

					$lMarkshow = (!$plMarkshow);

					$start = $tstart['task_log_start_date'];

					$tend = CTask::getActualFinishDate($a["task_id"], $child);

					if (!empty($tend['task_log_finish_date'])) {

						if (strtotime($tend['task_log_finish_date']) >= strtotime($end))
							$prMarkshow = true;

						$rMarkshow = (!$prMarkshow);

						if ($progress < 100 && strtotime($tend['task_log_finish_date']) < strtotime($now) ||
						       strtotime($end) > strtotime($now) && strtotime($start) < strtotime($now)) {

						    if (strtotime($end) <= strtotime($now))
						    	$prMarkshow = $rMarkshow = false;

							$end = substr($now, 0, 10);
						} else {
							$end = $tend['task_log_finish_date'];
						}
					}

					$bar2 = new PMGanttBar($row++, '', $start, $end, '', $task_leaf ? 0.3 : 0.15);

					if ($task_leaf) {
						$bar2->SetPattern(GANTT_RDIAG, $this->pUseColors ? 'red' : 'black', 95);
					} else {
						$bar2->SetColor('black');
						$bar2->SetFillColor('black');
						$bar2->SetPattern(BAND_SOLID, $this->pUseColors ? 'gray3' : 'white');

						$bar2->progress->SetFillColor($this->pUseColors ? 'green' : 'gray6');
						$bar2->progress->SetPattern(BAND_SOLID, $this->pUseColors ? 'green' : 'gray6', 98);

						if ($plMarkshow) {
							$bar2->leftMark->Show();
							$bar2->leftMark->SetType(MARK_LEFTTRIANGLE);
							$bar2->leftMark->SetWidth(2);

							if ($progress == 0) {
								$bar2->leftMark->SetColor('black');
								$bar2->leftMark->SetFillColor('black');
							} else {
								$bar2->leftMark->SetColor($this->pUseColors ? 'green' : 'gray6');
								$bar2->leftMark->SetFillColor($this->pUseColors ? 'green' : 'gray6');
							}
						}

						if ($prMarkshow) {
							$bar2->rightMark->Show();
							$bar2->rightMark->SetType(MARK_RIGHTTRIANGLE);
							$bar2->rightMark->SetWidth(2);

							if ($progress != 100) {
								$bar2->rightMark->SetColor($this->pUseColors ? 'gray3' : 'white');
								$bar2->rightMark->SetFillColor($this->pUseColors ? 'gray3' : 'white');
							} else {
								$bar2->rightMark->SetColor($this->pUseColors ? 'green' : 'gray6');
								$bar2->rightMark->SetFillColor($this->pUseColors ? 'green' : 'gray6');
							}
						}
					}


					$bar2->progress->Set($progress/100);
				}
		//	}

			if (!$task_leaf) {

				$bar->SetColor('black');
				$bar->SetFillColor('black');
				$bar->SetPattern(BAND_SOLID,'black');

				if ($lMarkshow) {
					$bar->leftMark->Show();
					$bar->leftMark->SetType(MARK_LEFTTRIANGLE);
					$bar->leftMark->SetWidth(1);
					$bar->leftMark->SetColor('black');
					$bar->leftMark->SetFillColor('black');
				}

				if ($rMarkshow) {
					$bar->rightMark->Show();
					$bar->rightMark->SetType(MARK_RIGHTTRIANGLE);
					$bar->rightMark->SetWidth(1);
					$bar->rightMark->SetColor('black');
					$bar->rightMark->SetFillColor('black');
				}
			}

			//adding captions
			$bar->caption = new TextProperty($caption);
			$bar->caption->Align("left","center");

			// show tasks which are both finished and past in (dark)gray
			if ($progress >= 100 && $end_date->isPast() && get_class($bar) == "PMGanttBar_DISABLED") {
				$bar->caption->SetColor('darkgray');
				$bar->title->SetColor('darkgray');
				$bar->setColor('darkgray');
				$bar->SetFillColor('darkgray');
				$bar->SetPattern(BAND_SOLID,'gray');

				if ($bar2 != null && get_class($bar2) == "PMGanttBar_DISABLED") {
					$bar2->caption->SetColor('gray5');
					$bar2->setColor('gray5');
					$bar2->SetFillColor('gray5');
					$bar2->SetPattern(BAND_SOLID,'gray3');

					$bar2->progress->SetFillColor('gray5');
					$bar2->progress->SetPattern(BAND_SOLID,'gray3',98);
				}
			}

			if ($draw_deps) {
				$sql = "SELECT dependencies_task_id FROM task_dependencies WHERE dependencies_req_task_id=" . $a["task_id"];
				$query = db_exec($sql);

				while($dep = db_fetch_assoc($query)) {
					// find row num of dependencies
					for($d = 0; $d < count($this->pTasks); $d++ ) {
						if($this->pTasks[$d]["task_id"] == $dep["dependencies_task_id"] && $d != $bar->GetLineNbr()) {
							$bar->SetConstrain($d, CONSTRAIN_ENDSTART, $this->pUseColors ? 'brown' : 'gray4');
						}
					}
				}
			}

			if ($a["task_milestone"])
				$bar->title->SetColor("#CC0000");

			$this->pGraph->Add($bar);

			if ($bar2 != null)
				$this->pGraph->Add($bar2);
		}
		//$today = date("y-m-d");
		$vline = new GanttVLine(/*$today*/$now, 'Today', ($this->pUseColors ? 'darkred' : 'gray3'));
		$vline->title->SetFont(FF_USERFONT3, FS_NORMAL, 9);
		$this->pGraph->Add($vline);
	}
}

?>