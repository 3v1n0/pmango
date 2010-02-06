<?php

/**
---------------------------------------------------------------------------

 PMango Project

 Title:      TaskBox.

 File:       TaskBox.class.php
 Location:   pmango/modules/tasks
 Started:    2009.12.23
 Author:     Marco Trevisan
 Type:       PHP

 This file is part of the PMango project
 Further information at: http://pmango.sourceforge.net

 Version history.
 - 2010.02.05
   0.7, support for minimal width setting
 - 2010.01.27
   0.6, resource wrapping support.
 - 2010.01.14
   0.5, alert support.
 - 2010.01.12
   0.3, caching support.
 - 2010.01.04
   0.2, progress bar bugfixing.
 - 2009.12.23
   0.1, basic taskbox support.

---------------------------------------------------------------------------

 PMango - A web application for project planning and control.

 Copyright (C) 2006 Giovanni A. Cignoni, Lorenzo Ballini, Marco Bonacchi
 Copyright (C) 2009-2010 Marco Trevisan (Treviño) <mail@3v1n0.net>
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

include "{$dPconfig['root_dir']}/lib/imgblock/ImgBlock.class.php";

class TaskBox {
	private $pID;
	private $pName;
	private $pPlannedData;         // duration, efforts, costs
	private $pActualData;
	private $pPlannedTimeframe;    // start/end dates
	private $pActualTimeframe;
	private $pResources;    	   // effort, person, role
	private $pShowAlerts;
	private $pShowExpand;          // show expand sign (+)
	private $pProgress;
	private $pImgBlock;
	private $pGDImage;
	private $pUpdate;

	//--
	private $pFont;
	private $pFontBold;
	private $pFontSize;
	private $pFontPath;
	private $pAlertSize;
	private $pMinWidth;
	private $pMaxWidth;
	private $pMaxLineHeight;
	private $pMinTitleLen;
	
	//--
	private $cMinTitleLen = 3;
	private $cMaxWidthMultiplier = 3;

	const ALERT_NONE = 0;
	const ALERT_WARNING = 1;
	const ALERT_ERROR = 2;

	public function TaskBox($id) {
		$this->pID = $id;
		$this->pName = null;
		$this->pPlannedData = null;
		$this->pActualData = null;
		$this->pPlannedTimeframe = null;
		$this->pActualTimeframe = null;
		$this->pResources = null;
		$this->pShowAlerts = TaskBox::ALERT_NONE;
		$this->pProgress = null;
		$this->pShowExpand = false;

		$this->pImgBlock = null;
		$this->pGDImage = null;
		$this->pChanged = true;
		$this->pMinWidth = 0;
		$this->pAlertSize = 0;
		$this->pMinTitleLen = 0;
	}

	public function setName($n) {
		if (strlen($n) > 0) {
			$this->pName = $n;
			$this->pChanged = true;
		}
	}

	public function setPlannedData($duration, $effort, $cost) {
		$this->pPlannedData['duration'] = $duration;
		$this->pPlannedData['effort'] = $effort;
		$this->pPlannedData['cost'] = $cost;
		$this->pChanged = true;
	}

	public function setPlannedDataArray($pdata) {
		if (isset($pdata['duration']) && isset($pdata['effort']) && isset($pdata['cost'])) {
			$this->pPlannedData = $pdata;
		} else {
			$this->pPlannedData = null;
		}
		$this->pChanged = true;
	}

	public function setActualData($duration, $effort, $cost) {
		$this->pActualData['duration'] = $duration;
		$this->pActualData['effort'] = $effort;
		$this->pActualData['cost'] = $cost;
		$this->pChanged = true;
	}

	public function setActualDataArray($adata) {
		if (isset($adata['duration']) && isset($adata['effort']) && isset($adata['cost'])) {
			$this->pActualData = $adata;
		} else {
			$this->pActualData = null;
		}
		$this->pChanged = true;
	}

	public function setPlannedTimeframe($start, $end) {
		if ($start === null & $end === null) {
			$this->pPlannedTimeframe = null;
		} else {
			$this->pPlannedTimeframe['start'] = ($start == null || empty($start)) ? "NA" : $start;
			$this->pPlannedTimeframe['end'] = ($end == null || empty($end)) ? "NA" : $end;
		}
		$this->pChanged = true;
	}

	public function setPlannedTimeframeArray($ptime) {
		if (isset($ptime['start']) && isset($ptime['end'])) {
			$this->pPlannedTimeframe = $ptime;
		} else {
			$this->pPlannedTimeframe = null;
		}
		$this->pChanged = true;
	}

	public function setActualTimeframe($start, $end) {
		if ($start === null & $end === null) {
			$this->pActualTimeframe = null;
		} else {
			$this->pActualTimeframe['start'] = ($start == null || empty($start)) ? "NA" : $start;
			$this->pActualTimeframe['end'] = ($end == null || empty($end)) ? "NA" : $end;
		}
		$this->pChanged = true;
	}

	public function setActualTimeframeArray($atime) {
		if (isset($atime['start']) && isset($atime['end'])) {
			$this->pActualTimeframe = $atime;
		} else {
			$this->pActualTimeframe = null;
		}
		$this->pChanged = true;
	}

	public function addResources($name, $role, $p_effort, $a_effort = null) {
		if ($name != null && $role != null && $p_effort != null) {
			$arr['name'] = $name;
			$arr['role'] = $role;
			$arr['planned_effort'] = $p_effort;
			if ($a_effort != null) $arr['actual_effort'] = $a_effort;
			$this->pResources[] = $arr;
		} else {
			$this->pResources = null;
		}
		$this->pChanged = true;
	}

	public function setResourcesArray($res) {
		if (isset($res[0]['name']) && isset($res[0]['role']) && isset($res[0]['planned_effort'])) {
			$this->pResources = $res;
		} else if (isset($res['name']) && isset($res['role']) && isset($res['planned_effort'])) {
			$this->pResources[] = $res;
		} else {
			$this->pResources = null;
		}
		$this->pChanged = true;
	}

	public function setProgress($p) {

		if ($p === null) {
			$this->pProgress = $p;
		} else {
			$this->pProgress = intval($p);

			if ($this->pProgress > 100)
				$this->pProgress = 100;

			if ($this->pProgress < 1)
				$this->pProgress = 0;
		}

		$this->pChanged = true;
	}

	public function setAlerts($a) {
		$this->pShowAlerts = $a;
		$this->pChanged = true;
	}

	public function showExpand($e) {
		$this->pShowExpand = $e;
		$this->pChanged = true;
	}

	public function setFontPath($p) {
		$this->pFontPath = $p;
	}

	public function setBorderSize($b) {
		$this->pBorderSize = intval($b) > 0 ? $b : 1;
	}

	public function setFont($f) {
		$this->pFont = $f;
		$this->pChanged = true;
	}

	public function setFontBold($f) {
		$this->pFontBold = $f;
		$this->pChanged = true;
	}

	public function setFontSize($s) {
		$this->pFontSize = intval($b) > 0 ? $b : 10;
		$this->pChanged = true;
	}
	
	public function setMinWidth($w) {
		$this->pMinWidth = intval($w) > 0 ? $w : 0;
	}
	
	public function setMinTitleLen($l) {
		$this->pMinTitleLen = intval($l) > 0 ? $l : 0;
	}

	private function computeFontSize() { //TODO
		//Depends on setSize() ...
	}

	private function init() {
		global $dPconfig;
		
		if (!$this->pFontPath) $this->pFontPath = $dPconfig['root_dir'].'/fonts/Droid/';
		if (!$this->pFont) $this->pFont = "DroidSans.ttf";
		if (!$this->pFontBold) $this->pFontBold = "DroidSans-Bold.ttf";

		$this->pFont = $this->pFontPath.'/'.$this->pFont;
		$this->pFontBold = $this->pFontPath.'/'.$this->pFontBold;
		$this->pFontSize = 10;

		if (!file_exists($this->pFont) || !file_exists($this->pFontBold))
			exit("You must provide valid font files and path!\n");

		$this->pBorderSize = 1;
	}
	
	private function computeMinSize() {
		$widthTester =  str_repeat("3", $this->cMinTitleLen);
			
		if ($this->pMinWidth == 0 && $this->pMinTitleLen > $this->cMinTitleLen) {
			$widthTester = str_repeat("3.", $this->pMinTitleLen);
		}

		$tmp = new TextBlock("+ ".$widthTester, $this->pFontBold, $this->pFontSize);
		$tmp = new BorderedBlock($tmp, 0, $this->pFontSize);
		
		if ($this->pMinWidth == 0) {
			$this->pMinWidth = $tmp->getWidth();
			$this->pMaxWidth = $this->pMinWidth * $this->cMaxWidthMultiplier;
		}
		
		$this->pMinHeight = $tmp->getHeight() + intval(($tmp->getHeight()/100) * 50);
	}

	private function isMinimal() {
		if ($this->pName == null &&
		    $this->pPlannedData == null &&
		    $this->pActualData == null &&
		    $this->pPlannedTimeframe == null &&
		    $this->pActualTimeframe == null &&
		    $this->pResources == null) {
			return true;
		} else {
			return false;
		}
	}

	private function buildTaskBox() {

		if ($this->pImgBlock != null && !$this->pChanged)
			return;

		$this->init();
		$this->computeMinSize();

		$mainVBox = new VerticalBoxBlock(0);
		$mainVBox->setSpace(-1);

		/* Header block */
		if ($this->pShowExpand) $txt = "+ ";
		$txt .= $this->pID.($this->pName != null ? " ".$this->pName : "");
		$header = new TextBlock($txt, $this->pFontBold, $this->pFontSize);

		$hbox = new HorizontalBoxBlock($this->pBorderSize);
		$hbox->setMerge(true);
		$hbox->setSpace($this->isMinimal() ? $this->pFontSize/2 : $this->pFontSize);
		$hbox->addBlock($header);
		$hbox->setMinHeight($this->pMinHeight);

		if ($this->isMinimal()) {
			$hbox->setWidth($this->pMinWidth);
		} else {
			$hbox->setWidth($this->pMaxWidth);
		}

		$mainVBox->addBlock($hbox);

		/* Planned data */
		if ($this->pPlannedData != null) {
			$hbox = new HorizontalBoxBlock($this->pBorderSize);
			$hbox->setMerge(true);
			$hbox->setSpace(1);
			$hbox->setHomogeneous(true);

			$hbox->addBlock(new TextBlock($this->pPlannedData['duration'], $this->pFont, $this->pFontSize));
			$hbox->addBlock(new TextBlock($this->pPlannedData['effort'], $this->pFont, $this->pFontSize));
			$hbox->addBlock(new TextBlock($this->pPlannedData['cost'], $this->pFont, $this->pFontSize));

			$hbox->setMinHeight($this->pMinHeight);
			$hbox->setWidth($this->pMaxWidth);

			$mainVBox->addBlock($hbox);
		}

		/* Planned Timeframe */
		if ($this->pPlannedTimeframe != null) {
			$hbox = new HorizontalBoxBlock($this->pBorderSize);
			$hbox->setMerge(true);
			$hbox->setSpace(1);
			$hbox->setHomogeneous(true);

			$hbox->addBlock(new TextBlock($this->pPlannedTimeframe['start'], $this->pFont, $this->pFontSize));
			$hbox->addBlock(new TextBlock($this->pPlannedTimeframe['end'], $this->pFont, $this->pFontSize));

			$hbox->setMinHeight($this->pMinHeight);
			$hbox->setWidth($this->pMaxWidth);

			$mainVBox->addBlock($hbox);
		}

		/* Resources */
		if ($this->pResources != null) {
			for ($i = 0, $txt = ''; $i < count($this->pResources); $i++) {
				$res = $this->pResources[$i];

				if (isset($res['actual_effort']))
					$txt .= '<u>'.$res['actual_effort'].'</u>/';

				$txt .= $res['planned_effort']." ph, ".$res['name'].", ".$res['role'];
				if ($i < count($this->pResources)-1) $txt .= "\n";
			}

			$hbox = new HorizontalBoxBlock($this->pBorderSize);
			$hbox->setMerge(true);
			$hbox->setSpace(2);
			$hbox->setHomogeneous(true);

			$txtblk = new TextBlock($txt, $this->pFont, $this->pFontSize, "left");
			$txtblk->setWrap(new ResourceWrapping($txtblk));		
			$hbox->addBlock($txtblk);

			$hbox->setMinHeight($this->pMinHeight);
			$hbox->setWidth($this->pMaxWidth);

			$mainVBox->addBlock($hbox);
		}

		/* Actual Timeframe */
		if ($this->pActualTimeframe != null) {
			$hbox = new HorizontalBoxBlock($this->pBorderSize);
			$hbox->setMerge(true);
			$hbox->setSpace(1);
			$hbox->setHomogeneous(true);

			$txt = "<u>".$this->pActualTimeframe['start']."</u>";
			$hbox->addBlock(new TextBlock($txt, $this->pFont, $this->pFontSize));
			$txt = "<u>".$this->pActualTimeframe['end']."</u>";
			$hbox->addBlock(new TextBlock($txt, $this->pFont, $this->pFontSize));

			$hbox->setMinHeight($this->pMinHeight);
			$hbox->setWidth($this->pMaxWidth);

			$mainVBox->addBlock($hbox);
		}

		/* Actual Data */
		if ($this->pActualData != null) {
			$hbox = new HorizontalBoxBlock($this->pBorderSize);
			$hbox->setMerge(true);
			$hbox->setSpace(1);
			$hbox->setHomogeneous(true);

			$txt = "<u>".$this->pActualData['duration']."</u>";
			$hbox->addBlock(new TextBlock($txt, $this->pFont, $this->pFontSize));
			$txt = "<u>".$this->pActualData['effort']."</u>";
			$hbox->addBlock(new TextBlock($txt, $this->pFont, $this->pFontSize));
			$txt = "<u>".$this->pActualData['cost']."</u>";
			$hbox->addBlock(new TextBlock($txt, $this->pFont, $this->pFontSize));

			$hbox->setMinHeight($this->pMinHeight);
			$hbox->setWidth($this->pMaxWidth);

			$mainVBox->addBlock($hbox);
		}

		/* Progress bar */
		if ($this->pProgress !== null && $this->pProgress > -1) {
			$hbox = new HorizontalBoxBlock($this->pBorderSize);
			$hbox->setMerge(true);
			$hbox->setSpace(0);
			$hbox->setHomogeneous(false); // should be false!

			if ($this->isMinimal()) {
				$w = $this->pMinWidth;
			} else {
				$w = $this->pMaxWidth;
			}

			$w -= $this->pBorderSize*2;

			if ($this->pProgress > 0) {
				$progress_width = ($w * $this->pProgress / 100);
				if ($this->pProgress == 50 && $w % 2 == 0) $progress_width--;
				$progress_blk = new ColorBlock("#bbb");
				$progress_blk->setHeight($this->pFontSize*2/3);
				$progress_blk->setWidth($progress_width);
				$hbox->addBlock($progress_blk);
			}

			if ($this->pProgress < 100) {
				$missing_width = $w - $progress_width;
				if ($this->pProgress == 50 && $w % 2 == 0) $missing_width--;
				if ($missing_width < 1) $missing_width = 1;
				$missing_blk = new ColorBlock("#fff");
				$missing_blk->setHeight($this->pFontSize*2/3);
				$missing_blk->setWidth($missing_width);
				$hbox->addBlock($missing_blk);
			}


//			$hbox->setMinHeight($this->pMinHeight);
			$mainVBox->addBlock($hbox);
		}

		$outBox = new BorderedBlock($mainVBox, $this->pBorderSize, 0);

//		if ($this->isMinimal()) {
//			$outBox->setMinWidth($this->pMinWidth);
//			$outBox->setMaxWidth($this->pMaxWidth);
//		} else {
//			$outBox->setWidth($this->pMaxWidth);

//			//$outBox->setMinWidth($this->pMaxWidth);
//			//$outBox->setMaxWidth($this->pMaxWidth);
//		}

		/* Alerts */
		if ($this->pShowAlerts === TaskBox::ALERT_WARNING || $this->pShowAlerts === TaskBox::ALERT_ERROR) {
			$txt = 'Δ'.($this->pShowAlerts == TaskBox::ALERT_ERROR ? '!' : '');
			$alert_size = $this->isMinimal() ? $this->pFontSize*1.1 : $this->pFontSize*1.5;
			$alert = new CircleBlock(new TextBlock($txt, $this->pFont, $alert_size));
			$alert->setPadding(1);
			$alert->setBorder($this->pBorderSize*2);
			$this->pAlertSize = $alert->getHeight();

			$tmp = new FixedBlock();
			$tmp->addContent($outBox, 0, $alert->getHeight()/2);
			$tmp->addContent($alert, $outBox->getWidth()-$alert->getWidth()/2, 0);
			$outBox = $tmp;
		}

		$this->pImgBlock = $outBox;
		$this->pChanged = false;
	}

	public function getId() {
		return $this->pID;
	}

	public function getName(){
		return $this->pName;
	}

	public function getWidth() {
		$this->buildTaskBox();
		return $this->pImgBlock->getWidth();
	}

	public function getHeight() {
		$this->buildTaskBox();
		return $this->pImgBlock->getHeight();
	}

	public function getAlertSize(){
		$this->buildTaskBox();
		return $this->pAlertSize;
	}

	public function getProgress() {
		return $this->pProgress;
	}

	public function getPlannedTimeframe() {
		return $this->pPlannedTimeframe;
	}

	public function getActualTimeframe() {
		return $this->pActualTimeframe;
	}

	public function getPlannedData() {
		return $this->pPlannedData;
	}

	public function getActualData() {
		return $this->pActualData;
	}

	public function getResources() {
		return $this->pResources;
	}

	private function buildImage() {
		if ($this->pGDImage && !$this->pChanged)
			return;

		$this->buildTaskBox();
		$this->pGDImage = $this->pImgBlock->getImage();
	}

	public function getImage() {
		$this->buildImage();
		return $this->pGDImage;
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
}

class ResourceWrapping implements TextBlockWrapping {
	private $pTxtBlock;
	
	public function ResourceWrapping(TextBlock $txtblk) {
		$this->pTxtBlock = $txtblk;
	}
	
	public function wrap($text, $max_size) {
		
		$ressize = $this->pTxtBlock->getTextSize(strip_tags($text));

		if ($ressize['w'] < $max_size)
			return $text;

		$res = explode(', ', $text);
		
		$fixed_size = $this->pTxtBlock->getTextSize(strip_tags($res[0].', , '));
		$max_cuttable_size = round(intval(($max_size - $fixed_size['w'])/2));
		
		$tsize[1] = $this->pTxtBlock->getTextSize($res[1]);
		$tsize[2] = $this->pTxtBlock->getTextSize($res[2]);

		$cut[1] = strlen($res[1]) * $max_cuttable_size / $tsize[1]['w'] + 1;
		$cut[2] = strlen($res[2]) * $max_cuttable_size / $tsize[2]['w'] + 1;
		
		$i = 0;
		while ($ressize['w'] > $max_size && strlen($text) > 1) {
			
			if ($cut[1] != 0)
				$res[1] = trim(substr($res[1], 0, $cut[1]));
			
			if ($cut[2] != 0)
				$res[2] = trim(substr($res[2], 0, $cut[2]));

			$text = $res[0].", ".$res[1]."..., ".$res[2]."...";
			$ressize = $this->pTxtBlock->getTextSize(strip_tags($text));
			
			if ($i % 2) {
				$cut[1] = -1;
				$cut[2] = 0;
			} else {
				$cut[1] = 0;
				$cut[2] = -1;
			}
			
			$i++;
		}

		return $text;
	}
}

?>
