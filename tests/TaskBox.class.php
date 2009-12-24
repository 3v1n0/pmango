<?php

//	 - Show task name
// - Show planned data: duration, efforts, costs
// - Show actual data
// - Show planned timeframe: start/end dates
// - Show actual timeframe
// - Show planned/actual resources: effort, person, role
// - Show alerts
// - Show progress

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


	///
	private $pFont;
	private $pFontBold;
	private $pFontSize;
	private $pMinWidth;
	private $pMaxWidth;
	private $pMaxLineHeight;


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
		$this->pGDImage = null; /* STUB */

		////

		$this->init();
	}

	public function setName($n) {
		if (strlen($n) > 0)
			$this->pName = $n;
	}

	public function setPlannedData($duration, $effort, $cost) {
		$this->pPlannedData['duration'] = $duration;
		$this->pPlannedData['effort'] = $effort;
		$this->pPlannedData['cost'] = $cost;
	}

	public function setActualData($duration, $effort, $cost) {
		$this->pActualData['duration'] = $duration;
		$this->pActualData['effort'] = $effort;
		$this->pActualData['cost'] = $cost;
	}

	public function setPlannedTimeframe($start, $end) {
		$this->pPlannedTimeframe['start'] = $start;
		$this->pPlannedTimeframe['end'] = $end;
	}

	public function setActualTimeframe($start, $end) {
		$this->pActualTimeframe['start'] = $start;
		$this->pActualTimeframe['end'] = $end;
	}

	public function setResources($res) {
		$this->pResources = $res;
	}

	public function setProgress($p) {
		$this->pProgress = intval($p);

		if ($this->pProgress > 100)
			$this->pProgress = 100;
	}

	private function computeFontSize() {
		//Depends on setSize() ...
	}

	private function init() {
		putenv('GDFONTPATH=' . realpath('../fonts/Droid'));
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

		if ($this->pProgress != null && $this->pProgress > 0) {
			$hbox = new HorizontalBoxBlock($this->pBorderSize);
			$hbox->setMerge(true);
			$hbox->setSpace(0);
			$hbox->setHomogeneous(false); // should be false!

			if ($this->isMinimal()) {
				$w = $this->pMinWidth;
			} else {
				$w = $this->pMaxWidth;
			}

			$progress_width = ($w - $this->pBorderSize*2) * $this->pProgress / 100;
			$progress_blk = new ColorBlock("#bbb");
			$progress_blk->setHeight($this->pFontSize);
			$progress_blk->setWidth($progress_width);
			$hbox->addBlock($progress_blk);

			if ($this->pProgress < 100) {
				$missing_width = $w - $this->pBorderSize*2 - $progress_width - $this->pProgress%2;
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

		$this->pImgBlock = $outBox;
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
		$this->buildTaskBox();
		$this->pGDImage = $this->pImgBlock->getImage();
	}

	public function getImage() {
		$this->buildImage();
		return $this->pGDImage;  //GD image!
	}

	public function draw($format = "png") {
		header("Content-type: image/png");
		switch ($format) {
			case "png":
				imagepng($this->getImage());
				break;
		}
	}

	public function getProgress() {}

	public function getXxxx() {}

	//public function getSize() {/*return gd_size($pGDImage);*/}
}

?>
