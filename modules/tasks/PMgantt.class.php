<?php
/**
 ---------------------------------------------------------------------------

 PMango Project

 Title:      PMango Graph GANTT class.

 File:       PMgantt.class.php
 Location:   pmango/modules/tasks
 Started:    2010.01.15
 Author:     Marco Trevisan (Treviño) <mail@3v1n0.net>
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2010.02.09 Marco Trevisan
 0.4, added PMProgressBar and improvements in PMGanttBar
 - 2010.01.27 Marco Trevisan
 0.3, bars joiners
 0.2, captions work
 - 2010.01.15 Marco Trevisan
 0.1, created the class based on the work by Lorenzo Ballini (2005.09.30)
 and on my previous patched non-classed version (2009.11.04)

 ---------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2006 Giovanni A. Cignoni, Lorenzo Ballini, Marco Bonacchi
 Copyrigth (C) 2010 Marco Trevisan (Treviño) <mail@3v1n0.net>
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

define('TTF_DIR', "./fonts/Droid/");

include_once "./classes/PMGraph.interface.php";
include "./lib/jpgraph/src/jpgraph.php";
include "./lib/jpgraph/src/jpgraph_gantt.php";

########################################
////////////////////////////////////////
########################################

class PMProgressBar {
	public $iProgress=-1;
	public $iPattern=GANTT_SOLID;
	public $iColor="black", $iFillColor='black', $iFrameColor="black", $iBackgroundFillColor="white";
	public $iBackgroundPattern=GANTT_RDIAG,$iBackgroundColor="red",$iBackGroundDensity=98;
	public $iDensity=98, $iHeight=0.15;
	public $iStart = null, $iEnd = null;
	public $leftMark,$rightMark;
	public $iPadding=0,$iProgressPadding=0;

	function setProgress($aProg) {
		if( $aProg < 0.0 || $aProg > 1.0 ) {
			JpGraphError::RaiseL(6027);
			//("Progress value must in range [0, 1]");
		}
		$this->iProgress = $aProg;
	}

	function setPattern($aPattern,$aColor="blue",$aDensity=98) {
		$this->iPattern = $aPattern;
		$this->iColor = $aColor;
		$this->iDensity = $aDensity;
	}
	
	function setBackgroundPattern($aPattern,$aColor="red",$aDensity=98) {
		$this->iBackgroundPattern = $aPattern;
		$this->iBackgroundColor = $aColor;
		$this->iBackGroundDensity = $aDensity;
	}

	function setFillColor($aColor) {
		$this->iFillColor = $aColor;
	}
	
	function setColor($aColor) {
		$this->iFrameColor = $aColor;
	}
	
	function setBackgroundFillColor($aColor) {
		$this->iBackgroundFillColor = $aColor;
	}
	
	function setPadding($aPadding) {
		$this->iPadding = $aPadding;
	}
	
	function setProgressPadding($aPadding) {
		$this->iProgressPadding = $aPadding;
	}

	function setHeight($aHeight) {
		$this->iHeight = $aHeight;
	}
	
	function setStartEnd($aStart, $aEnd) {
		if (is_null($aStart)) {
			$this->iEnd = null;
			return;
		} else if (is_null($aEnd)) {
			$this->iStart = null;
			$this->iEnd = null;
			return;
		}

		$this->iStart = strtotime($aStart);
		if ( strpos($aEnd,':') === false )
			$this->iEnd = strtotime($aEnd)+SECPERDAY-1;
		else
			$this->iEnd = strtotime($aEnd);
	}
}

class PMGanttBar extends GanttPlotObject {
	public $progressbar;
	public $leftMark,$rightMark;
	private $iEnd;
	private $iHeightFactor=0.5;
	private $iFillColor="white",$iFrameColor="black";
	private $iShadow=false,$iShadowColor="darkgray",$iShadowWidth=1,$iShadowFrame="black";
	private $iPattern=GANTT_RDIAG,$iPatternColor="blue",$iPatternDensity=95;
	private $iBreakStyle=false, $iBreakLineStyle='dotted',$iBreakLineWeight=1;
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
		$this->title->set($aLabel);
		$this->caption = new TextProperty($aCaption);
		$this->caption->Align("left","center");
		$this->leftMark = new PlotMark();
		$this->leftMark->Hide();
		$this->rightMark= new PlotMark();
		$this->rightMark->Hide();
		$this->progressbar = new PMProgressBar();
		$this->progressbar->leftMark = new PlotMark();
		$this->progressbar->leftMark->Hide();
		$this->progressbar->rightMark= new PlotMark();
		$this->progressbar->rightMark->Hide();
	}

	//---------------
	// PUBLIC METHODS
	function setShadow($aShadow=true,$aColor="gray") {
		$this->iShadow=$aShadow;
		$this->iShadowColor=$aColor;
	}

	function setBreakStyle($aFlg=true,$aLineStyle='dotted',$aLineWeight=1) {
		$this->iBreakStyle = $aFlg;
		$this->iBreakLineStyle = $aLineStyle;
		$this->iBreakLineWeight = $aLineWeight;
	}

	function getMaxDate() {
		return $this->iEnd;
	}

	function setHeight($aHeight) {
		$this->iHeightFactor = $aHeight;
	}

	function setColor($aColor) {
		$this->iFrameColor = $aColor;
	}

	function setFillColor($aColor) {
		$this->iFillColor = $aColor;
	}

	function GetAbsHeight(Image $aImg) {
		$m=-1;
		
		if( is_int($this->iHeightFactor) || $this->leftMark->show || $this->rightMark->show ) {
			if( is_int($this->iHeightFactor) )
				$m = $this->iHeightFactor;
			if( $this->leftMark->show )
				$m = max($m,$this->leftMark->width*2);
			if( $this->rightMark->show )
				$m = max($m,$this->rightMark->width*2);
		}
		
		if( $this->progressbar->iStart && $this->progressbar->iProgress > 0 &&
		      (is_int($this->progressbar->iHeight) || $this->progressbar->leftMark->show ||
		      $this->progressbar->rightMark->show)) {
			if ($m == -1) $m = 0;

			$m += $this->progressbar->iHeight + $this->progressbar->iPadding;

			if( $this->progressbar->leftMark->show )
				$m = max($m,$this->progressbar->leftMark->width*2);
			if( $this->progressbar->rightMark->show )
				$m = max($m,$this->progressbar->rightMark->width*2);
		}
		
		return $m;
	}

	function setPattern($aPattern,$aColor="blue",$aDensity=95) {
		$this->iPattern = $aPattern;
		$this->iPatternColor = $aColor;
		$this->iPatternDensity = $aDensity;
	}

	function Stroke(Image $aImg, GanttScale $aScale) { 
		$factory = new RectPatternFactory();
		$bar_drawn = true;

		if (!is_int($this->iHeightFactor) && $this->progressbar->iStart && 
		      $this->iHeightFactor+$this->progressbar->iHeight > 0.9) {
		      	$barH = $this->iHeightFactor+$this->progressbar->iHeight;
		      	$this->iHeightFactor *= 0.9/$barH;
		      	$this->progressbar->iHeight *= 0.9/$barH;
		 }

//		 If height factor is specified as a float between 0,1 then we take it as meaning
//		 percetage of the scale width between horizontal line.
//		 If it is an integer > 1 we take it to mean the absolute height in pixels
		if( $this->iHeightFactor > -0.0 && $this->iHeightFactor <= 1.1) {
			$vs = $aScale->GetVertSpacing()*$this->iHeightFactor;
		} elseif(is_int($this->iHeightFactor) && $this->iHeightFactor>2 && $this->iHeightFactor < 200 ) {
			$vs = $this->iHeightFactor;
		} else {
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
		
		$yt = round($aScale->TranslateVertPos($this->iVPos)-$vs-($aScale->GetVertSpacing()/2-$vs/2));
		$yb = round($aScale->TranslateVertPos($this->iVPos)-($aScale->GetVertSpacing()/2-$vs/2));
		
		$middle = round($yt+($yb-$yt)/2);
		$this->StrokeActInfo($aImg,$aScale,$middle);

		// Check if the bar is totally outside the current scale range
		if( $en <  $aScale->iStartDate || $st > $aScale->iEndDate )
			$bar_drawn = false;

		if ($bar_drawn) {
			$prect = $factory->Create($this->iPattern,$this->iPatternColor);
			$prect->setDensity($this->iPatternDensity);

			// Remember the positions for the bar
			$this->setConstrainPos($xt,$yt,$xb,$yb);
	
			$prect->ShowFrame(false);
			$prect->setBackground($this->iFillColor);
			if( $this->iBreakStyle ) {
				$aImg->setColor($this->iFrameColor);
				$olds = $aImg->setLineStyle($this->iBreakLineStyle);
				$oldw = $aImg->setLineWeight($this->iBreakLineWeight);
				$aImg->StyleLine($xt,$yt,$xb,$yt);
				$aImg->StyleLine($xt,$yb,$xb,$yb);
				$aImg->setLineStyle($olds);
				$aImg->setLineWeight($oldw);
			}
			else {
				if( $this->iShadow ) {
					$aImg->setColor($this->iFrameColor);
					$aImg->ShadowRectangle($xt,$yt,$xb,$yb,$this->iFillColor,$this->iShadowWidth,$this->iShadowColor);
					$prect->setPos(new Rectangle($xt+1,$yt+1,$xb-$xt-$this->iShadowWidth-2,$yb-$yt-$this->iShadowWidth-2));
					$prect->Stroke($aImg);
				}
				else {
					$prect->setPos(new Rectangle($xt,$yt,$xb-$xt+1,$yb-$yt+1));
					$prect->Stroke($aImg);
					$aImg->setColor($this->iFrameColor);
					$aImg->Rectangle($xt,$yt,$xb,$yb);
				}
			}
		}

		// Draw progress bar inside activity bar
		if($bar_drawn && $this->progressbar->iProgress > 0 &&
		   !$this->progressbar->iStart && !$this->progressbar->iEnd) {

			$xtp = $aScale->TranslateDate($st);
			$xbp = $aScale->TranslateDate($en);
			$len = ($xbp-$xtp)*$this->progressbar->iProgress-1;

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

				$prog = $factory->Create($this->progressbar->iPattern,$this->progressbar->iColor);
				$prog->setDensity($this->progressbar->iDensity);
				$prog->setBackground($this->progressbar->iFillColor);
				$barheight = ($yb-$yt+1);
				if( $this->iShadow )
					$barheight -= $this->iShadowWidth;
				$progressheight = floor($barheight*$this->progressbar->iHeight);
				$marg = ceil(($barheight-$progressheight)/2);
				$pos = new Rectangle($xtp+1,$yt + $marg, $len,$barheight-2*$marg);
				$prog->setPos($pos);
				$prog->Stroke($aImg);
			}
		} else if ($this->progressbar->iProgress > 0) {
			if (!$this->progressbar->iStart)
				$this->progressbar->setStartEnd($this->iStart, $this->iEnd);
			
			$progress_bar_drawn = true;

			$prst = $aScale->NormalizeDate($this->progressbar->iStart);
			$pren = $aScale->NormalizeDate($this->progressbar->iEnd);
			
			if (!$this->progressbar->iHeight)
				$this->progressbar->iHeight = $this->iHeight;
			
			if( $this->progressbar->iHeight > -0.0 && $this->progressbar->iHeight <= 1.1)
				$prvs = $aScale->GetVertSpacing()*$this->progressbar->iHeight;
			elseif(is_int($this->progressbar->iHeight) && $this->progressbar->iHeight>2 && $this->progressbar->iHeight < 200 )
				$prvs = $this->progressbar->iHeight;
			else {
				JpGraphError::RaiseL(6028,$this->iHeightFactor);
				//	("Specified height (".$this->iHeightFactor.") for gantt bar is out of range.");
			}
			
			$limprst = max($prst,$aScale->iStartDate);
			$limpren = min($pren,$aScale->iEndDate);
			
			$prxt = round($aScale->TranslateDate($limprst));
			$prxb = round($aScale->TranslateDate($limpren));
			$pryt = $yt+$vs+$this->progressbar->iPadding;
			$pryb = $pryt+$prvs;
			$prmiddle = round($pryt+($pryb-$pryt)/2);
			
			if($pren <  $aScale->iStartDate || $prst > $aScale->iEndDate)
				$progress_bar_drawn = false;
			
			if ($progress_bar_drawn) {
				
				$prxtp = $aScale->TranslateDate($prst);
				$prxbp = $aScale->TranslateDate($pren);
				$len = ($prxbp-$prxtp)*$this->progressbar->iProgress-1;
				
				if( $prxtp+$len > $prxt ) {
					$len -= ($prxt-$prxtp);
					
					if( $prxtp < $prxt )
						$prxtp = $prxt;
						
					if( $prxtp+$len-1 > $prxb )
						$len = $prxb - $prxtp ;
					
					$draw_progress = true;
				}
			
				$aImg->setColor($this->progressbar->iFrameColor);
				$aImg->Rectangle($prxt,$pryt,$prxb,$pryb);

				if ($prxb-$prxt > 1) {
					$prog = $factory->Create(GANTT_SOLID, $this->progressbar->iBackgroundFillColor);
					$prog->setPos(new Rectangle($prxt+1,$pryt+1,$prxb-$prxt-1,$pryb-$pryt-1));
					$prog->Stroke($aImg);
				
					$prog = $factory->Create($this->progressbar->iBackgroundPattern, $this->progressbar->iBackgroundColor);
					$prog->setPos(new Rectangle($prxt+1,$pryt+1,$prxb-$prxt-1,$pryb-$pryt-1));
					$prog->setDensity($this->progressbar->iBackGroundDensity);
					$prog->ShowFrame(false);
					$prog->Stroke($aImg);
				
					if ($draw_progress) {
						$prog = $factory->Create(GANTT_SOLID, $this->progressbar->iFillColor);
						$margin = ($this->progressbar->iProgressPadding > $pryb-$pryt1+1) ? 0 : $this->progressbar->iProgressPadding;
						$prog->setPos(new Rectangle($prxt+1,$pryt+1+$margin,$len,$pryb-$pryt-1-$margin*2));
						$prog->setDensity($this->progressbar->iDensity);
						$prog->ShowFrame(false);
						$prog->Stroke($aImg);
					}
				}
			}
			
				
			if ($prxb > $xb && $prxt >= $xb) {
				$join_len = $prxt - $xb;
				$join_x = $xb;
			} else if ($prxt < $xt && $prxb <= $xt) {
				$join_len = $prxb - $xt;
				$join_x = $xt;
			}
			
			$join_y = $pryt;
						
			if ($join_len != 0) {
				$x_min = $aScale->TranslateDate($aScale->iStartDate);
				$x_max = $aScale->TranslateDate($aScale->iEndDate);

				if ($join_len > 0) {				
					if ($join_x < $x_min) {
						$join_len -= $x_min - $join_x;
						$join_x = $x_min;
					}
					
					if ($join_x+$join_len > $x_max) {
						$join_len = $x_max - $join_x;
					}
				} else {
					if ($join_x+$join_len < $x_min) {
						$join_len = $x_min - $join_x;
					}
					
					if ($join_x > $x_max) {
						$join_len += $join_x - $x_max;
						$join_x = $x_max;
					}
				}		
				
				$aImg->SetColor($this->iFrameColor);
				$aImg->Polygon(array($join_x, $yb,
                                     $join_x, $join_y,
                                     $join_x+$join_len, $join_y));
			}
		}
		
		if (!$bar_drawn && !$progress_bar_drawn)
			return;

		// We don't plot the end mark if the bar has been capped
		if( $limst == $st && $bar_drawn) {
			$y = $middle;
			// We treat the RIGHT and LEFT triangle mark a little bi
			// special so that these marks are placed right under the
			// bar.

			if( $this->leftMark->GetType() == MARK_LEFTTRIANGLE && $this->leftMark->show) {
				$y = $yb ;

				$bmark = new PlotMark();
				$bmark->setType($this->leftMark->GetType());
				$bmark->setColor($this->iFrameColor);
				$bmark->setFillColor($this->iFillColor);
				$size = $this->leftMark->GetWidth();
				$bmark->setSize($size % 2 == 0 ? $size+1 : $size+2);
				$bmark->Show();
				$bmark->Stroke($aImg, $xt++, $y);
			}

			$this->leftMark->Stroke($aImg,$xt,$y);
		}
		if( $limen == $en && $bar_drawn) {
			$y = $middle;
			// We treat the RIGHT and LEFT triangle mark a little bi
			// special so that these marks are placed right under the
			// bar.

			if( $this->rightMark->GetType() == MARK_RIGHTTRIANGLE && $this->rightMark->show) {
				$y = $yb ;

				$bmark = new PlotMark();
				$bmark->setType($this->rightMark->GetType());
				$bmark->setColor($this->iFrameColor);
				$bmark->setFillColor($this->iFillColor);
				$size = $this->rightMark->GetWidth();
				$bmark->setSize($size % 2 == 0 ? $size+1 : $size+2);
				$bmark->Show();
				$bmark->Stroke($aImg, $xb--, $y);
			}

			$this->rightMark->Stroke($aImg,$xb,$y);

			$margin = $this->iCaptionMargin;
			if( $this->rightMark->show )
			$margin += $this->rightMark->GetWidth();

			$this->caption->Stroke($aImg,$xb+$margin,$middle);
		}
		
		if( $limprst == $prst && $progress_bar_drawn) {
			$y = $prmiddle;

			if( $this->progressbar->leftMark->GetType() == MARK_LEFTTRIANGLE && $this->progressbar->leftMark->show) {
				$y = $pryb ;

				$bmark = new PlotMark();
				$bmark->setType($this->progressbar->leftMark->GetType());
				$bmark->setColor($this->progressbar->iFrameColor);
				$bmark->setFillColor($this->progressbar->iFillColor);
				$size = $this->progressbar->leftMark->GetWidth();
				$bmark->setSize($size % 2 == 0 ? $size+1 : $size+2);
				$bmark->Show();
				$bmark->Stroke($aImg, $prxt++, $y);
			}

			$this->progressbar->leftMark->Stroke($aImg,$prxt,$y);
		}
		if( $limpren == $pren && $progress_bar_drawn) {
			$y = $prmiddle;

			if( $this->progressbar->rightMark->GetType() == MARK_RIGHTTRIANGLE && $this->progressbar->rightMark->show) {
				$y = $pryb ;

				$bmark = new PlotMark();
				$bmark->setType($this->progressbar->rightMark->GetType());
				$bmark->setColor($this->progressbar->iFrameColor);
				$bmark->setFillColor($this->progressbar->iFillColor);
				$size = $this->progressbar->rightMark->GetWidth();
				$bmark->setSize($size % 2 == 0 ? $size+1 : $size+2);
				$bmark->Show();
				$bmark->Stroke($aImg, $prxb--, $y);
			}

			$this->progressbar->rightMark->Stroke($aImg,$prxb,$y);
		}
	}
}

########################################
////////////////////////////////////////
########################################

class PMGantt implements PMGraph {
	private $pProjectID;
	private $pTaskLevel;
	private $pOpenedTasks;
	private $pClosedTasks;

	private $pStartDate;
	private $pEndDate;
	private $pToday;

	private $pShowNames;
	private $pShowDependencies;
	private $pShowResources;
	private $pShowTaskGroups;
	private $pUseColors;

	private $pWidth;
	private $pHeight;

	private $pProject;
	private $pTasks;
	private $pGraph;

	private $pChanged;

	public function PMGantt($project) {
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
		$this->pGraph = null;
		$this->pWidth = 0;
		$this->pHeight = 0;
		$this->pChanged = true;
	}

	public function setProject($p) {
		$this->pProject = abs(intval($p));
		$this->pChanged = true;
	}

	public function setTaskLevel($tl) {
		$this->pTaskLevel = intval($tl) > 1 ? intval($tl) : 1;
		$this->pChanged = true;
	}

	public function setOpenedTasks($tsk) {
		$this->pOpenedTasks = is_array($tsk) ? $tsk : array();
		$this->pChanged = true;
	}

	public function setClosedTasks($tsk) {
		$this->pClosedTasks = is_array($tsk) ? $tsk : array();
		$this->pChanged = true;
	}

	public function setWidth($w) {
		$this->pWidth = intval($w) > 1 ? intval($w) : 0;
		$this->pChanged = true;
	}

	public function setHeight($h) {
		$this->pHeight = intval($h) > 1 ? intval($h) : 0;
		$this->pChanged = true;
	}

	public function setStartDate($sd) {
		$this->pStartDate = $sd;
		$this->pChanged = true;
	}

	public function setEndDate($ed) {
		$this->pEndDate = $ed;
		$this->pChanged = true;
	}

	public function showNames($sn) {
		$this->pShowNames = $sn ? true : false;
		$this->pChanged = true;
	}

	public function showDeps($sd) {
		$this->pShowDeps = $sd ? true : false;
		$this->pChanged = true;
	}

	public function showResources($sr) {
		$this->pShowResources = $sr ? true : false;
		$this->pChanged = true;
	}

	public function showTaskGroups($stg) {
		$this->pShowTaskGroups = $stg ? true : false;
		$this->pChanged = true;
	}

	public function useColors($uc) {
		$this->pUseColors = $uc ? true : false;
		$this->pChanged = true;
	}

	public function getWidth() {
		$this->buildGANTT();
			
		if ($this->pGraph->img->width == 0)
		$this->pGraph->AutoSize();

		return $this->pGraph->img->width;
	}

	public function getHeight() {
		$this->buildGANTT();

		if ($this->pGraph->img->height == 0)
		$this->pGraph->AutoSize();

		return $this->pGraph->img->height;
	}

	public function getType() {
		return "GANTT";
	}

	public function getImage() {
		$this->buildGANTT();
		return $this->pGraph->Stroke(_IMG_HANDLER);
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
				if (!$file) header("Content-type: image/gif");
				imagegif($this->getImage(), $file);
				break;
		}
	}

	//--
	private function buildGANTT() {
		if ($this->pGraph != null && !$this->pChanged)
		return;

		$this->pullProjectData();
		$this->pullTasks();
		$this->parseTasks();
		$this->initDates();
		$this->initGraph();
		$this->populateGraph();
		$this->pChanged = false;
	}

	private function pullProjectData() {
		$psql = "SELECT project_id, project_color_identifier, project_name, project_start_date, project_finish_date, ".
		        "project_today, project_active FROM projects WHERE project_id = ".$this->pProjectID;
		$prc = db_exec($psql);
		echo db_error();
		$this->pProject = db_fetch_row($prc);
	}

	private function pullTasks() {
		if (isset($this->pProject['tasks']))
		unset($this->pProject['tasks']);

		$select = "tasks.task_id, task_parent, task_name, task_start_date, task_finish_date, ".
		          "task_priority, task_order, task_project, task_milestone, project_name";
		$join = "LEFT JOIN projects ON project_id = task_project";
		$where = "task_project = $this->pProjectID";

		$tsql = "SELECT $select FROM tasks $join WHERE $where ORDER BY project_id, CAST(task_wbs_index as UNSIGNED)";

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

		unset($this->pProject['tasks']);

		if(!$this->pShowTaskGroups) {
			for($i = 0; $i < count($this->pTasks); $i++) {
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

		if ($this->pProject['project_active'])
		$this->pToday = date("Y-m-d")." 12:00:00";
		else
		$this->pToday = date("Y-m-d", strtotime($this->pProject['project_today']))." 12:00:00";
	}

	private function getTaskResources($tid) {

		$q = new DBQuery();
		$q->clear();
		$q->addQuery('ut.user_id as uid, ut.proles_id as rid, '.
		             'CONCAT_WS(" ", u.user_last_name, SUBSTRING(u.user_first_name,1,1)) as name, '.
		             'pr.proles_name as role, ut.effort as planned_effort');
		$q->addTable('user_tasks','ut');
		$q->addJoin('users', 'u', 'u.user_id = ut.user_id');
		$q->addJoin('project_roles', 'pr', 'pr.proles_id = ut.proles_id');
		$q->addWhere('ut.proles_id > 0 and ut.task_id = '.$tid);

		$resources = $q->loadList();

		$q->clear();
		$q->addQuery('task_log_creator as uid, task_log_proles_id as rid, '.
		             'sum(task_log_hours) as actual_effort');
		$q->addTable('task_log','tl');
		$q->addWhere('task_log_proles_id > 0 and '.
		(!CTask::isLeafSt($tid) ? '(SELECT COUNT(*) FROM tasks AS tt WHERE '.
		                                     'tl.task_log_creator != tt.task_id'.
			                    '&& tt.task_parent = tl.task_log_task) < 1 and ' : '').
			         'task_log_task '.(!CTask::isLeafSt($tid) ? 'in ('.CTask::getChild($tid, $this->pProjectID).')' : '= '.$tid));
		$q->addGroup('task_log_creator, task_log_proles_id');

		$a_resources = $q->loadList();

		foreach ($resources as &$pres) {
			$found = false;

			foreach($a_resources as $ares) {
				if ($ares['uid'] == $pres['uid'] && $ares['rid'] == $pres['rid']) {
					$pres['actual_effort'] = $ares['actual_effort'];
					$found = true;
					break;
				}
			}

			if ($found == false)
			$pres['actual_effort'] = 0;
		}

		return $resources;
	}

	private function initGraph() {
		global $AppUI;

		$this->pGraph = new GanttGraph($this->pWidth, $this->pHeight);

		$this->pGraph->setUserFont1('DroidSans.ttf', 'DroidSans-Bold.ttf');
		$this->pGraph->setUserFont2('DroidSerif-Regular.ttf', 'DroidSerif-Bold.ttf',
		                            'DroidSerif-Italic.ttf', 'DroidSerif-BoldItalic.ttf');
		$this->pGraph->setUserFont3('DroidSansMono.ttf');

		$this->pGraph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH | GANTT_HDAY | GANTT_HWEEK);

		$this->pGraph->setFrame(false);
		$this->pGraph->setBox(true, "#000000", 2);
		$this->pGraph->scale->week->setStyle(WEEKSTYLE_FIRSTDAY);
		//$graph->scale->day->setStyle(DAYSTYLE_SHORTDATE2);

		// This configuration variable may be obsolete
		$jpLocale = dPgetConfig('jpLocale');
		if ($jpLocale)
		$this->pGraph->scale->setDateLocale($jpLocale);

		if ($this->pStartDate && $this->pEndDate)
		$this->pGraph->setDateRange($this->pStartDate, $this->pEndDate);

		$this->pGraph->scale->actinfo->vgrid->setColor('gray');
		$this->pGraph->scale->actinfo->setColor('darkgray');
		$titles_size = ($this->pShowNames && $this->pWidth != 0) ? array($this->pWidth/6) : null;
		$this->pGraph->scale->actinfo->setColTitles(array($AppUI->_('Task', UI_OUTPUT_RAW)), $titles_size);
		$this->pGraph->scale->actinfo->setFont(FF_USERFONT);

		$this->pGraph->scale->tableTitle->set($this->pProject["project_name"]);
		$this->pGraph->scale->tableTitle->setFont(FF_USERFONT1, FS_BOLD, 12);

		if ($this->pUseColors) {
			$this->pGraph->scale->setTableTitleBackground("#".$this->pProject["project_color_identifier"]);
			$this->pGraph->scale->tableTitle->setColor(bestColor($this->pProject["project_color_identifier"]));
		}

		$this->pGraph->scale->tableTitle->Show(true);

		$tinterval = $this->getTimeInterval();

		// show only week or month
		if ($tinterval > 300){
			$this->pGraph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH);
		} else if ($tinterval > 120){
			$this->pGraph->ShowHeaders(GANTT_HYEAR | GANTT_HMONTH | GANTT_HWEEK );
			$this->pGraph->scale->week->setStyle(WEEKSTYLE_WNBR);
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
		global $AppUI;

		//		$now = "2009-12-05 12:00:00";//date("y-m-d");
		$now = $this->pToday;

		for($i = 0, $row = 0; $i < count(@$this->pTasks); $i++) {

			$a     = $this->pTasks[$i];
			if ($a["task_id"] < 1) continue;

			$level = CTask::getTaskLevel($a["task_id"]);
			$task_leaf = $this->pTasks[$i]['is_leaf'];
			$task_leaf_real = CTask::isLeafSt($a["task_id"]);

			if (!$this->pShowTaskGroups) $level = 0;

			$name = Ctask::getWBS($a["task_id"]).".".($this->pShowNames ? " ".$a["task_name"] : "");

			// TODO locale
			//			if ($locale_char_set=='utf-8' && function_exists("utf8_decode") ) {
			//				$name = utf8_decode($name);
			//			}

			if ($task_leaf && !$task_leaf_real)
				$name = "+".@str_repeat(" ", $level-2).$name;
			else
				$name = str_repeat(" ", $level).$name;

			//using new jpGraph determines using Date object instead of string
			$start = $a["task_start_date"];
			$end = $a["task_finish_date"];

			$end_date = new CDate($end);
			//	$start_date->addDays(0);
			$end_date->getDate();

			$start_date = new CDate($start);
			//	$start_date->addDays(0);
			$start = $start_date->getDate();

			$progress = intval(CTask::getPr($a["task_id"]));
			if ($progress > 100) $progress = 100;
			else if ($progress < 0) $progress = 0;

			$title_size = 8;
			$bar = new PMGanttBar($row++, array($name), $start, $end, null,
			                      $task_leaf ? intval($title_size*1.6) : intval($title_size*1.2));

			$bar->title->setFont(FF_USERFONT2, FS_NORMAL, $title_size);

			if (!$this->pUseColors) {
				$bar->setColor('black');
				$bar->setFillColor('white');
				$bar->setPattern(BAND_SOLID,'white');
			}

			if ($this->pShowNames) {
				$tst = new Image();
				$tst->ttf->setUserFont2('DroidSerif-Regular.ttf');

				$cut = strlen($name) - (($this->pWidth/6) * strlen($name) / $bar->title->GetWidth($tst));

				while ($bar->title->GetWidth($tst) >= $this->pWidth/6 && $cut < strlen($name)) {
					$n = trim(substr($name, 0, strlen($name)-(1+$cut)))."...";
					$bar->title->set($n);
					$cut++;
				}
			}

			$child = CTask::getChild($a["task_id"], $this->pProjectID);
			$tstart = CTask::getActualStartDate($a["task_id"], $child);

			$rMarkshow = true;
			$lMarkshow = true;
			$prMarkshow = false;
			$plMarkshow = false;

			if (!empty($tstart['task_log_start_date'])) {

				$lstart = $tstart['task_log_start_date'];

				if (strtotime($lstart) <= strtotime($start)+43200)
					$plMarkshow = true;

				$lMarkshow = (!$plMarkshow);

				$tend = CTask::getActualFinishDate($a["task_id"], $child);

				if (!empty($tend['task_log_finish_date'])) {

					$lend = $tend['task_log_finish_date'];

					if ($progress < 100 && strtotime($lend) < strtotime($now) ||
					strtotime($end) > strtotime($now) && strtotime($lstart) < strtotime($now)) {
						$lend = substr($now, 0, 10);
					}
				} else {
					$lend = substr($now, 0, 10);
				}

				if (strtotime($lend) >= strtotime($end))
					$prMarkshow = true;

				if (strtotime(substr($lend, 0, 10)) == strtotime(substr($now, 0, 10)))
					$prMarkshow = false;

				$rMarkshow = (!$prMarkshow);

				if ($rMarkshow && strtotime($end) == strtotime(substr($now, 0, 10)) ||
				     ($progress < 100 && strtotime($end) < strtotime($now))) {
					$rMarkshow = false;
				}

				$bar->progressbar->setStartEnd($lstart, $lend);
				$bar->progressbar->setHeight($task_leaf ? $title_size : intval($title_size/2)+1);
				$bar->progressbar->setColor('black');
				$bar->progressbar->setBackgroundFillColor('white');

				if ($task_leaf) {
					$bar->progressbar->setBackgroundPattern(GANTT_RDIAG, $this->pUseColors ? 'red' : 'black', 95);
					$bar->progressbar->setProgressPadding(1);
				} else {				
					$bar->progressbar->setBackgroundPattern(BAND_SOLID, $this->pUseColors ? 'gray3' : 'white');
					
					$bar->progressbar->setFillColor($this->pUseColors ? 'green' : 'gray6');
					$bar->progressbar->setPattern(BAND_SOLID, $this->pUseColors ? 'green' : 'gray6', 98);

					if ($plMarkshow) {
						$bar->progressbar->leftMark->Show();
						$bar->progressbar->leftMark->setType(MARK_LEFTTRIANGLE);
						$bar->progressbar->leftMark->setWidth(2);

						if ($progress == 0) {
							$bar->progressbar->leftMark->setColor('black');
							$bar->progressbar->leftMark->setFillColor('black');
						} else {
							$bar->progressbar->leftMark->setColor($this->pUseColors ? 'green' : 'gray6');
							$bar->progressbar->leftMark->setFillColor($this->pUseColors ? 'green' : 'gray6');
						}
					}

					if ($prMarkshow) {
						$bar->progressbar->rightMark->Show();
						$bar->progressbar->rightMark->setType(MARK_RIGHTTRIANGLE);
						$bar->progressbar->rightMark->setWidth(2);

						if ($progress != 100) {
							$bar->progressbar->rightMark->setColor($this->pUseColors ? 'gray3' : 'white');
							$bar->progressbar->rightMark->setFillColor($this->pUseColors ? 'gray3' : 'white');
						} else {
							$bar->progressbar->rightMark->setColor($this->pUseColors ? 'green' : 'gray6');
							$bar->progressbar->rightMark->setFillColor($this->pUseColors ? 'green' : 'gray6');
						}
					}
				}

				$bar->progressbar->setProgress($progress/100);
			}


			if (!$task_leaf) {

				$bar->setColor('black');
				$bar->setFillColor('black');
				$bar->setPattern(BAND_SOLID,'black');

				if ($lMarkshow) {
					$bar->leftMark->Show();
					$bar->leftMark->setType(MARK_LEFTTRIANGLE);
					$bar->leftMark->setWidth(1);
					$bar->leftMark->setColor('black');
					$bar->leftMark->setFillColor('black');
				}

				if ($rMarkshow) {
					$bar->rightMark->Show();
					$bar->rightMark->setType(MARK_RIGHTTRIANGLE);
					$bar->rightMark->setWidth(1);
					$bar->rightMark->setColor('black');
					$bar->rightMark->setFillColor('black');
				}
			}

			if ($this->pShowResources) {
				if (!$task_leaf_real) {
					$caption = CTask::getActualEffort($a['task_id'], $child)."/".CTask::getEffort($a['task_id'])." ph";
				} else {

					$res = $this->getTaskResources($a['task_id']);

					$tst = new Image();
					$tst->ttf->setUserFont3('DroidSansMono.ttf');

					$caption = '';
					$max_res_width = $this->pWidth/(4*count($res));
					
					foreach($res as $r) {
						$fixed = $r['actual_effort'].'/'.$r['planned_effort']."ph,";
						$cap = trim($r['name'].",".$r['role']).";";

						$bar->caption = new TextProperty($cap);
						$bar->caption->setFont(FF_USERFONT3, FS_NORMAL, 7);

						$bar->caption->set($fixed);
						$fixed_size = $bar->caption->GetWidth($tst);

						$bar->caption->set($cap);
						$cut = ($max_res_width * strlen($cap) / ($bar->caption->GetWidth($tst) + $fixed_size));
						
						while ($bar->caption->GetWidth($tst) + $fixed_size >= $max_res_width &&
						       $cut < strlen($cap)-1 && strlen($cap) > 1 && $cut > 1) {
							$cap = trim(substr($cap, 0, $cut))."...";
							$bar->caption->set($cap);
							$cut -= 1;
						}

						$caption .= $fixed.$cap;
					}
				}

				//adding captions
				$bar->caption = new TextProperty($caption);
				$bar->caption->setFont(FF_USERFONT3, FS_NORMAL, 7);
				$bar->caption->Align("left", ($task_leaf || strtotime($end) > strtotime($lend) ? "center" : "bottom"));
			}

			// show tasks which are both finished and past in (dark)gray
			if ($progress >= 100 && $end_date->isPast() && get_class($bar) == "PMGanttBar_DISABLED") {
				//$bar->caption->setColor('darkgray');
				$bar->title->setColor('darkgray');
				$bar->setColor('darkgray');
				$bar->setFillColor('darkgray');
				$bar->setPattern(BAND_SOLID,'gray');

				if ($bar->progressbar->iStart != null && get_class($bar->progressbar) == "PMProgressBar_DISABLED") {
					$bar->progressbar->setColor('gray5');
					$bar->progressbar->setBackgroundFillColor('gray5');
					$bar->progressbar->setBackgroundPattern(BAND_SOLID,'gray3');
					$bar->progressbar->setFillColor('gray5');
					$bar->progressbar->setPattern(BAND_SOLID,'gray3',98);
				}
			}

			if ($a["task_milestone"])
				$bar->title->setColor("#CC0000");

			$this->pTasks[$i]['bar'] = $bar;
			$this->pGraph->Add($bar);
		}

		if ($this->pShowDeps) {
			foreach ($this->pTasks as $task) {
				$sql = "SELECT dependencies_task_id FROM task_dependencies WHERE dependencies_req_task_id = " . $task["task_id"];
				$deps = db_exec($sql);

				while($dep = db_fetch_assoc($deps)) {
					for($d = 0; $d < count($this->pTasks); $d++ ) {
						if($this->pTasks[$d]["task_id"] == $dep[0]) {
							$task['bar']->setConstrain($this->pTasks[$d]['bar']->GetLineNbr(), CONSTRAIN_ENDSTARTMIDDLE, $this->pUseColors ? 'brown' : 'gray4');
							break;
						}
					}
				}
			}
		}

		//$today = date("y-m-d");
		$vline = new GanttVLine(/*$today*/$now, $AppUI->_('Today', UI_OUTPUT_RAW), ($this->pUseColors ? 'darkred' : 'gray3'));
		$vline->title->setFont(FF_USERFONT3, FS_NORMAL, 9);
		$this->pGraph->Add($vline);
	}
}

?>
