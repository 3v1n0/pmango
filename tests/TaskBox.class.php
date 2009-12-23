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
	private $pProgress;
	private $pGDImage;
	private $pShowExpand;          // show expand sign (+)


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
		$this->pMaxHeight = $tmp->getHeight() + intval(($tmp->getHeight()/100) * 50);

		$this->pBorderSize = 1;
	}

	private function isMinimal() {
		if ($this->pName == null &&
		    $this->pPlannedData == null &&
		    $this->pActualData == null &&
		    $this->pPlannedTimeframe == null &&
		    $this->pActualTimeframe == null &&
		    $this->pResources == null) {
			return false;
		} else {
			return true;
		}
	}

	private function buildTaskBox() {
		$mainVBox = new VerticalBoxBlock(0);
		$mainVBox->setSpace(-1);

		$txt = $this->pID.($this->pName != null ? " ".$this->pName : "");
		$header = new TextBlock($txt, $this->pFontBold, $this->pFontSize);
		$header->setMinHeight($this->pMaxHeight);

		$hbox = new HorizontalBoxBlock($this->pBorderSize);
		$hbox->addBlock($header);
		$mainVBox->addBlock($hbox);

		$outBox = new BorderedBlock($mainVBox, $this->pBorderSize, 0);

		if (!$this->isMinimal()) {
			$outBox->setMinWidth($this->pMinWidth);
			$outBox->setMaxWidth($this->pMaxWidth);
		} else {
			$outBox->setWidth($this->pMaxWidth);
		}

		$this->pGDImage = $outBox->getImage();
	}

	public function getImage() {
		$this->buildTaskBox();
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

	public function setXxxx() {}

	public function getXxxx() {}

	public function getSize() {/*return gd_size($pGDImage);*/}
}

?>
