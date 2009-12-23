<?php

//	 - Show task name
// - Show planned data: duration, efforts, costs
// - Show actual data
// - Show planned timeframe: start/end dates
// - Show actual timeframe
// - Show planned/actual resources: effort, person, role
// - Show alerts
// - Show progress


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


	public function taskBox($id, $alert = false, $expand = false,
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
	}

	public function getImage() {
		return $this->pGDImage;  //GD image!
	}

	public function draw() {
		return $this->pGDImage; //draw(this->getImage()); draw GD image!
	}

	public function getProgress() {}

	public function setXxxx() {}

	public function getXxxx() {}

	public function getSize() {/*return gd_size($pGDImage);*/}
}

?>
