<?php

//	 - Show task name
// - Show planned data: duration, efforts, costs
// - Show actual data
// - Show planned timeframe: start/end dates
// - Show actual timeframe
// - Show planned/actual resources: effort, person, role
// - Show alerts
// - Show progress

// TODO add the alert box! Δ!

include "ImgBlock.class.php";

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

	///
	private $pFont;
	private $pFontBold;
	private $pFontSize;
	private $pMinWidth;
	private $pMaxWidth;
	private $pMaxLineHeight;

	const ALERT_WARNING = 0;
	const ALERT_ERROR = 1;


	public function TaskBox($id, $alert = false, $expand = false,
								 $name = null, $progress = null,
	                             $p_data = null, $a_data = null,
	                             $p_timeframe = null, $a_timeframe = null,
                                 $resources = null) {
		$this->pID = $id;
		$this->pName = $name;
		$this->pPlannedData = $p_data;
		$this->pActualData = $a_data;
		$this->pPlannedTimeframe = $p_timeframe;
		$this->pActualTimeframe = $a_timeframe;
		$this->pResources = $resources;
		$this->pShowAlerts = $alert;
		$this->pProgress = $progress;
		$this->pShowExpand = $expand;
		$this->pImgBlock = null;
		$this->pGDImage = null;
		$this->pChanged = true;

		$this->init();
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

	public function setActualData($duration, $effort, $cost) {
		$this->pActualData['duration'] = $duration;
		$this->pActualData['effort'] = $effort;
		$this->pActualData['cost'] = $cost;
		$this->pChanged = true;
	}

	public function setPlannedTimeframe($start, $end) {
		$this->pPlannedTimeframe['start'] = $start;
		$this->pPlannedTimeframe['end'] = $end;
		$this->pChanged = true;
	}

	public function setActualTimeframe($start, $end) {
		$this->pActualTimeframe['start'] = $start;
		$this->pActualTimeframe['end'] = $end;
		$this->pChanged = true;
	}

	public function setResources($res) {
		$this->pResources = $res;
		$this->pChanged = true;
	}

	public function setProgress($p) {
		$this->pProgress = intval($p);

		if ($this->pProgress > 100)
			$this->pProgress = 100;

		if ($this->pProgress < 1)
			$this->pProgress = 0;

		$this->pChanged = true;
	}

	public function setAlerts($a) {
		$this->pShowAlerts = $a;
	}

	private function computeFontSize() {
		//Depends on setSize() ...
	}

	private function init() {
		if (!strlen(getenv("GDFONTPATH")))
			putenv('GDFONTPATH=' . dirname($_SERVER['SCRIPT_FILENAME']).'/../fonts/Droid');
		$this->pFontSize = 10;
		$this->pFont = "DroidSans.ttf";
		$this->pFontBold = "DroidSans-Bold.ttf";

		$tmp = new TextBlock("3.3.3", $this->pFontBold, $this->pFontSize);
		$tmp = new BorderedBlock($tmp, $this->pBorderSize*2, $this->pFontSize);
		$this->pMinWidth = $tmp->getWidth();
		$this->pMaxWidth = $this->pMinWidth * 3;
		$this->pMinHeight = $tmp->getHeight() + intval(($tmp->getHeight()/100) * 50);

		$this->pBorderSize = 1;
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

		$mainVBox = new VerticalBoxBlock(0);
		$mainVBox->setSpace(-1);

		/* Header block */
		$txt = $this->pID.($this->pName != null ? " ".$this->pName : "");
		$header = new TextBlock($txt, $this->pFontBold, $this->pFontSize);

		$hbox = new HorizontalBoxBlock($this->pBorderSize);
		$hbox->setMerge(true);
		$hbox->setSpace($this->pFontSize);
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
			$hbox = new HorizontalBoxBlock($this->pBorderSize);
			$hbox->setMerge(true);
			$hbox->setSpace(2);
			$hbox->setHomogeneous(true);

			$hbox->addBlock(new TextBlock($this->pResources, $this->pFont, $this->pFontSize, "left"));

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
				$progress_blk->setHeight($this->pFontSize);
				$progress_blk->setWidth($progress_width);
				$hbox->addBlock($progress_blk);
			}

			if ($this->pProgress < 100) {
				$missing_width = $w - $progress_width;
				if ($this->pProgress == 50 && $w % 2 == 0) $missing_width--;
				if ($missing_width < 1) $missing_width = 1;
				$missing_blk = new ColorBlock("#fff");
				$missing_blk->setHeight($this->pFontSize);
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
			$alert = new CircleBlock(new TextBlock($txt, $this->pFont, $this->pFontSize*3/2));
			$alert->setPadding(1);
			$alert->setBorder($this->pBorderSize*2);

			$tmp = new FixedBlock();
			$tmp->addContent($outBox, 0, $alert->getHeight()/2);
			$tmp->addContent($alert, $outBox->getWidth()-$alert->getWidth()/2, 0);
			$outBox = $tmp;
		}

		$this->pImgBlock = $outBox;
		$this->pChanged = false;
	}

	public function getWidth() {
		$this->buildTaskBox();
		return $this->pImgBlock->getWidth();
	}

	public function getHeight() {
		$this->buildTaskBox();
		return $this->pImgBlock->getHeight();
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

	public function draw($format = "png") {
		switch ($format) {
			case "png":
				header("Content-type: image/png");
				imagepng($this->getImage());
				break;
			case "jpg":
			case "jpeg":
				header("Content-type: image/jpeg");
				imagejpeg($this->getImage());
				break;
		}
	}

	public function getProgress() {}

	public function getXxxx() {}

	//public function getSize() {/*return gd_size($pGDImage);*/}
}

?>
