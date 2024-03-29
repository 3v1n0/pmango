<?php
include ("TaskBox.class.php");
include "TaskBoxDB.class.php";


$baseDir = "..";

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

Header("Content-Type: image/jpeg");

class TNNode extends TaskBox {

	private $leftx, $lefty, $rightx, $righty;

	//leftx = ascissa punto mediano sx del tb (dove arrivano le frecce)
	//lefty = ordinata punto mediano sx del tb (dove arrivano le frecce)
	//rightx = ascissa punto mediano dx del tb (dove partono le frecce)
	//righty = ordinata punto mediano dx del tb (dove partono le frecce)

	function setLeft($x,$y){
		$this->leftx = $x;
		$this->lefty = $y;
	}

	function setRight($x,$y){
		$this->rightx = $x;
		$this->righty = $y;
	}

	function getLeftX(){
		return $this->leftx;
	}

	function getLeftY(){
		return $this->lefty;
	}

	function getRightX(){
		return $this->rightx;
	}

	function getRightY(){
		return $this->righty;
	}
}


class TaskNetwork {

	//matrice di Taskboxnodes, le righe sono i lv della wbs, le colonne i tbx di quel livello
	private $index;
	private $img, $x, $y;		//immagine e dimensioni della stessa

	private $SpM,$EpM; //gli array associativi delle start e end project milestone
	private $mapBlank; //mappa de punti vuoti della TN

	private $project; //numero pregetto

	function TaskNetwork($project=null){
		$this->index = array();
		$this->img = ImageCreate(1,1); //immagine vuota iniziale della TN
		$this->x = 1; 					//larghezza della TN
		$this->y = 1;					//altezza della TN
		$this->project = $project;
	}

	
//------Funzioni di classe------------
	public function createTN($level=0,$vertical=false,$tasks=null,$tbxsettings=null){
		
		$res=$this->getTbxFromLevel($level,$tasks);
		
		for($j=0;$j<sizeof($res);$j++){
			$res[$j] = TaskNetwork::orderWbsId($res[$j]);
					
			for($k=0;$k<sizeof($res[$j]);$k++){	
				$wbslv = CTask::getWBS($res[$j][$k]);
				 				
						$DB = new taskBoxDB($res[$j][$k]);
						$tbx = new TNNode($res[$j][$k]);
						$tbx->setFontPath("../fonts/Droid");
					if($tbxsettings['task_name']){
						$tbx->setName($wbslv." ".$DB->getTaskName());
					}
					if($tbxsettings['planned_data']){
						$tbx->setPlannedDataArray($DB->getPlannedData());
					}
					if($tbxsettings['actual_data']){
						$tbx->setActualDataArray($DB->getActualData());
					}
					if($tbxsettings['planned_timeframe']){
						$tbx->setPlannedTimeframeArray($DB->getPlannedTimeframe());
					}
					if($tbxsettings['actual_timeframe']){
						$tbx->setActualTimeframeArray($DB->getActualTimeframe());
					}
					if($tbxsettings['resources']){
						if($tbxsettings['resources'] == "actual"){
							$tbx->setResourcesArray($DB->getActualResources());
						}
						elseif($tbxsettings['resources']== "planned"){
							$tbx->setResourcesArray($DB->getPlannedResources());
						}
					}
					if($tbxsettings['task_progress']){
						$tbx->setProgress($DB->getProgress());
					}
					if($tbxsettings['task_alerts']){
						$tbx->setAlerts($DB->isAlerted());
					}
			
					if($vertical){$this->addTbx($tbx,$k,$j);}else{$this->addTbx($tbx,$j,$k);}
						
			}
		}
		
		
		//creazione immagine
		$rows=array();
		for($i=0;$i<sizeof($this->index);$i++){
			$rows[$i] = $this->mergeArrayRight($this->index[$i]);
		}

		$final = $this->mergeArrayUnder($rows,$vertical);
		
		$this->index = $final->index; 
		$this->img = $final->img;
		$this->x = $final->x;
		$this->y = $final->y;
	
		$this->mergeSpM();//aggiunta Spm
		$this->mergeEpM();// aggiunta Epm

		//mappo tutti i punti vuoti della TN
		$this->mapBlank = $this->mapBlank();
			
	}

	public function addDefaultDependancies($allArrow=false){
	$index = $this->getIndex();
		
	$query = "SELECT dependencies_task_id FROM task_dependencies t;";
	$results = TaskNetwork::doQuery($query);

			//start to tbx dependancies
			for($a=0;$a<sizeof($index);$a++){
					for($b=0;$b<sizeof($index[$a]);$b++){
					$bool =false;
						foreach($results as $x){
							if($x[0] ==	$index[$a][$b]->getId()){
								$bool=true;
							}
							
						}
						if(!$bool){
							$ID2["riga"] = $a; $ID2["colonna"] = $b;
												 //TN  			 cr.path dash  under upper dist vertical color timegap arrow
							TaskNetwork::connect($this,null,$ID2, false, true,false,false,38,false,"gray",false,$allArrow);
						}
					}
			}
	
	$query = "SELECT dependencies_req_task_id FROM task_dependencies t;";
	$results = TaskNetwork::doQuery($query);
		
			//tbx to end dependancies
			for($a=0;$a<sizeof($index);$a++){
					for($b=0;$b<sizeof($index[$a]);$b++){
						$bool =false;
						foreach($results as $x){
							if($x[0] ==	$index[$a][$b]->getId()){
								$bool=true;
							}
							
						}
						if(!$bool){
							$ID1["riga"] = $a; $ID1["colonna"] = $b;
												 //TN  			 cr.path dash  under upper dist
							TaskNetwork::connect($this,$ID1,null, false, false,false,false,45,false,"gray",false,$allArrow);
						}
					}

			}
			
		}

	public function printTN(){
		Imagepng($this->img);
		ImageDestroy($this->img);
	}

	public function drawConnections($vertical=false,$allArrow=false,$timeGaps=false){
		$connex= array(); //array di connessioni gia effettuate
		
		$query = "SELECT * FROM task_dependencies t where dependencies_req_task_id in".
				" (SELECT task_id FROM tasks t WHERE task_project = $this->project) or ".
				"dependencies_task_id in (SELECT task_id FROM tasks t WHERE "."
				task_project = $this->project)";
		$results = TaskNetwork::doQuery($query);
					
		for($i=0;$i<sizeof($results);$i++){
			$upper =false; $under=false;
	
	
			$tbxfrom = $results[$i]["dependencies_req_task_id"];
			$coordfrom = $this->getTbxIndex($tbxfrom);
			if(!isset($coordfrom)){
				$control = true;
				while ($control){
					$q = "SELECT task_parent FROM tasks t WHERE task_project = ".$this->project." and task_id <> task_parent and task_id = ".$tbxfrom;
					$result = TaskNetwork::doQuery($q);
					if($result[0][0]){
						$coordfrom = $this->getTbxIndex($result[0][0]);
						if(isset($coordfrom)){
							$under = true;	
							$control = false;
						} 
						else{
							$tbxfrom = $result[0][0];
						}
					}else{
						$coordfrom= null;
						$control = false;
					}
					unset($result);
					
				}
			}
			$tbxto = $results[$i]["dependencies_task_id"];		
			$coordto = $this->getTbxIndex($tbxto);
			if(!isset($coordto)){
				$control = true;
				while ($control){
					$q = "SELECT task_parent FROM tasks t WHERE task_project = ".$this->project." and task_id <> task_parent and task_id = ".$tbxto;
					$result = TaskNetwork::doQuery($q);
					if($result[0][0]){
						$coordto = $this->getTbxIndex($result[0][0]);
						if(isset($coordto)){
							$upper = true;	
							$control = false;
						} 
						else{
							$tbxto = $result[0][0];
						}
					}else{
						$coordto= null;
						$control = false;
					}
					unset($result);
					
				}
			}
			if($coordfrom and $coordto){
				$couple = implode(".",$coordfrom).",".implode(".",$coordto);
				if(!in_array($couple,$connex)){
												//			cr.path dash				dist	mode	  color	 timeGap
					$this->connect($this,$coordfrom,$coordto,false,false,$under,$upper,20+2*$i,$vertical,"black",($timeGaps)?(($upper or $under)?false:true):false,$allArrow);
					$connex[sizeof($connex)] = implode(".",$coordfrom).",".implode(".",$coordto);
				}
			}				
		}
	}
	
	public function drawCriticalPath($level, $vertical=false,$allArrow=false){
		
		$index = $this->getTbxFromLevel($level);

		//pezzo per ottenere le tbx da quali partire
		$query = "SELECT dependencies_task_id FROM task_dependencies t;";
		$results = TaskNetwork::doQuery($query);
		
		
		$tbxarray;
		for($a=0;$a<sizeof($index);$a++){
				for($b=0;$b<sizeof($index[$a]);$b++){
					$bool =false;
					foreach($results as $x){
						if($x[0] ==	$index[$a][$b]){
							$bool=true;
						}
						
					}
					if(!$bool){
						$liv = sizeof($tbxarray);
						$DB = new taskBoxDB($index[$a][$b]);
						
						$tbxarray[$liv]["id"] = $index[$a][$b];
						$pdata = $DB->getPlannedData();
						$tbxarray[$liv]["duration"] = intval($pdata["duration"]);
						$tbxarray[$liv]["effort"] = intval($pdata["effort"]);
						$tbxarray[$liv]["cost"] = intval($pdata["cost"]);
					}
				}
	
		}//ho tutte le tbx su una sola riga
		
		
		$out;$cont=0;//array che conterrà i cammini possibili di tbx
		$stack; $ind=0;// pila per simulare la ricorsione
		
	
		$tbxarray = TaskNetwork::computeDependence($tbxarray);
	
	
		$output;
		foreach($tbxarray as $tbx){
			$stack[sizeof($stack)] = $tbx;
			
			$taskout;
			while(sizeof($stack)>0){
				$task = $stack[sizeof($stack)-1];
				$taskout["id"] = $taskout["id"].",".$task["id"];
				$taskout["duration"] = $taskout["duration"]+$task["duration"];
				$taskout["effort"] = $taskout["effort"]+$task["effort"];
				$taskout["cost"] = $taskout["cost"]+$task["cost"];
				unset($stack[sizeof($stack)-1]);
				if(isset($task["dependencies"])){
					foreach($task["dependencies"] as $depen){
						$stack[sizeof($stack)]= $depen;
					}
				}else{
					$output[sizeof($output)] = $taskout;
					unset($stack);
					unset($taskout);
				}
				
			}
			
		}//ora ottengo per ogni riga di $output una raccolta di cammini partendo da ciascun tbx
		
		$output = $this->orderArray($output);
	 
		
		$cPath = $output[sizeof($output)-1]; // prendo il critical path
		$tbxidpath = explode(",",$cPath["id"]);
		
	//controlla se dipendenze sono nascoste	
				
		$tbxfrom = $tbxidpath[1];
		$coordfrom = $this->getTbxIndex($tbxfrom);
		$control = true;
		if(!isset($coordfrom)){
			while ($control){
				$q = "SELECT task_parent FROM tasks t WHERE task_project = ".$this->project." and task_id = ".$tbxfrom;
				$result = TaskNetwork::doQuery($q);
				$coordfrom = $this->getTbxIndex($result[0][0]);
				if(isset($coordfrom)){
					$upper = true;	
					$control = false;
				} 
				else{
					$tbxfrom = $result[0][0];
				}
				unset($result);
				
			}
		}
		
		$this->connect($this,null,$coordfrom,true,false,$under,$upper,25,$vertical,"black",false,$allArrow);//connetto la start al primo task
	
		for($i=2;$i<sizeof($tbxidpath)-1;$i++){
			$tbxto =  $tbxarray[$i];
			
			$coordfrom = $this->getTbxIndex($tbxfrom);
			if(!isset($coordfrom)){
				$control = true;
				while ($control){
					$q = "SELECT task_parent FROM tasks t WHERE task_project = ".$this->project." and task_id = ".$tbxfrom;
					$result = TaskNetwork::doQuery($q);
					$coordfrom = $this->getTbxIndex($result[0][0]);
					if(isset($coordfrom)){
						$under = true;	
						$control = false;
					} 
					else{
						$tbxfrom = $result[0][0];
					}
					unset($result);
					
				}
			}
			$coordto = $this->getTbxIndex($tbxto);
			if(!isset($coordto)){
				$control = true;
				while ($control){
					$q = "SELECT task_parent FROM tasks t WHERE task_project = ".$this->project." and task_id = ".$tbxto;
					$result = TaskNetwork::doQuery($q);
					$coordto = $this->getTbxIndex($result[0][0]);
					if(isset($coordto)){
						$upper = true;	
						$control = false;
					} 
					else{
						$tbxto = $result[0][0];
					}
					unset($result);
					
				}
			}
		
			
			
			$this->connect($this,$coordfrom,$coordto,true,false,$under,$upper,25,$vertical,"black",($timeGaps)?(($upper or $under)?false:true):false,$allArrow);
			
			$coordfrom = $coordto;
		}
		
		$tbxfrom = $tbxidpath[sizeof($tbxidpath)-1];
		$coordfrom = $this->getTbxIndex($tbxfrom);
		if(!isset($coordfrom)){
			$control = true;
			while ($control){
				$q = "SELECT task_parent FROM tasks t WHERE task_project = ".$this->project." and task_id = ".$tbxfrom;
				$result = TaskNetwork::doQuery($q);
				$coordfrom = $this->getTbxIndex($result[0][0]);
				if(isset($coordfrom)){
					$under = true;	
					$control = false;
				} 
				else{
					$tbxfrom = $result[0][0];
				}
				unset($result);
				
			}
		}
		
		$this->connect($this,$coordfrom,null,true,false,$under,$upper,25,$vertical,"black",false,$allArrow);
		
	}

//------Funzioni di classe------------fine

//------Funzioni di disegno------------
	private function drawSpM(){
		$img = ImageCreate(50,50);
		$bianco = ImageColorAllocate($img,255,255,255);
		$nero = ImageColorAllocate($img,0,0,0);
		if (function_exists('imageantialias'))
			imageantialias($img, true);

		$spm["x"] =  50;
		$spm["y"] =  50;
		imageFilledEllipse($img,25,25,49,49,$nero);
		$spm["img"] = $img;
		return $spm;
	}

	private function drawEpM(){
		$img = ImageCreate(50,50);
		$bianco = ImageColorAllocate($img,255,255,255);
		$nero = ImageColorAllocate($img,0,0,0);
		if (function_exists('imageantialias'))
			imageantialias($img, true);
		$epm["x"] =  50;
		$epm["y"] =  50;
		imageFilledEllipse($img,25,25,40,40,$nero);
		imageEllipse($img,25,25,48,48,$nero);
		imageEllipse($img,25,25,49,49,$nero);
		$epm["img"] = $img;
		return $epm;
	}

	private function imageboldline($image, $x1, $y1, $x2, $y2, $color, $thick = 3){
	   
	   imagesetthickness($image, $thick);
	   return imageline($image, $x1, $y1, $x2, $y2, $color);
	  
	}

	//wrapper funzioni di base disegno
	private function imageline($im, $x1, $y1, $dx, $dy, $color){
		 imageline($im, $x1, $y1, $dx, $dy, $color);
	}

	private function imagedashedline($im, $x1, $y1, $dx, $dy, $color){
		 imagedashedline($im, $x1, $y1, $dx, $dy, $color);
	}

	private function arrow($im, $x1, $y1, $x2, $y2, $alength, $awidth, $color) {
	    $distance = sqrt(pow($x1 - $x2, 2) + pow($y1 - $y2, 2));
		if($distance==0){$distance=1;}
	    $dx = $x2 + ($x1 - $x2) * $alength / $distance;
	    $dy = $y2 + ($y1 - $y2) * $alength / $distance;

	    $k = $awidth / $alength;

	    $x2o = $x2 - $dx;
	    $y2o = $dy - $y2;

	    $x3 = $y2o * $k + $dx;
	    $y3 = $x2o * $k + $dy;

	    $x4 = $dx - $y2o * $k;
	    $y4 = $dy - $x2o * $k;

	    imageline($im, $x1, $y1, $dx, $dy, $color);
	    imagefilledpolygon($im, array($x2, $y2, $x3, $y3, $x4, $y4), 3, $color);
	}

	private function dashedarrow($im, $x1, $y1, $x2, $y2, $alength, $awidth, $color) {
	    $distance = sqrt(pow($x1 - $x2, 2) + pow($y1 - $y2, 2));
		if($distance==0){$distance=1;}
	    $dx = $x2 + ($x1 - $x2) * $alength / $distance;
	    $dy = $y2 + ($y1 - $y2) * $alength / $distance;

	    $k = $awidth / $alength;

	    $x2o = $x2 - $dx;
	    $y2o = $dy - $y2;

	    $x3 = $y2o * $k + $dx;
	    $y3 = $x2o * $k + $dy;

	    $x4 = $dx - $y2o * $k;
	    $y4 = $dy - $x2o * $k;

	    imagedashedline($im, $x1, $y1, $dx, $dy, $color);
	    imagefilledpolygon($im, array($x2, $y2, $x3, $y3, $x4, $y4), 3, $color);
	}

	private function boldarrow($im, $x1, $y1, $x2, $y2, $alength, $awidth, $color) {
	   $distance = sqrt(pow($x1 - $x2, 2) + pow($y1 - $y2, 2));
		if($distance==0){$distance=1;}
	    $dx = $x2 + ($x1 - $x2) * $alength / $distance;
	    $dy = $y2 + ($y1 - $y2) * $alength / $distance;

	    $k = $awidth / $alength;

	    $x2o = $x2 - $dx;
	    $y2o = $dy - $y2;

	    $x3 = $y2o * $k + $dx;
	    $y3 = $x2o * $k + $dy;

	    $x4 = $dx - $y2o * $k;
	    $y4 = $dy - $x2o * $k;

	    $this->imageboldline($im, $x1, $y1, $dx, $dy, $color);
	    imagefilledpolygon($im, array($x2, $y2, $x3, $y3, $x4, $y4), 3, $color);
	}

	//traccia una freccia passante per tutti i punti dell'array dato
	private function patharrow($im,$points/*array*/,$color,$type,$allArrow=false,$text=""){
		$line;$arrow;
		switch($type){
			case "criticalPath":$line="imageboldline";$arrow="boldarrow";break;
			case "dash": $line="imagedashedline";$arrow="dashedarrow";break;
			default: 
			case "normal":$line="imageline";$arrow="arrow";break;
		}
		$points = TaskNetwork::trimArray($points);
		$fx=$points[0]["x"];
		$fy=$points[0]["y"];
		
		$tx=$points[1]["x"];
		$ty=$points[1]["y"];
		//aggiungo il testo
		if($tx-$fx==0){$px=$fx+5;$py=$fy + (abs($ty-$fy))/4;}
		else{$px=$fx + (abs($tx-$fx))/4;$py=$fy-15;}
		imagestring($im, 5, $px, $py, $text, $color);
		
		if(isset($points)){
			for($i=1;$i<sizeof($points)-1;$i++){
				$tx=$points[$i]["x"];
				$ty=$points[$i]["y"];
				
				if($allArrow){TaskNetwork::$arrow($im, $fx,$fy, $tx,$ty,3,3, $color);}
				else{ TaskNetwork::$line($im, $fx,$fy, $tx,$ty, $color);}
				
				$fx=$tx;
				$fy=$ty;
			}
			
			$tx=$points[sizeof($points)-1]["x"];
			$ty=$points[sizeof($points)-1]["y"];
			TaskNetwork::$arrow($im, $fx,$fy, $tx,$ty,5,5, $color);
			
			//aggiungo il testo
			if($tx-$fx==0){$px=$fx+5;$py=$fy + (abs($ty-$fy))/4;}
			else{$px=$fx + (abs($tx-$fx))/4;$py=$fy-15;}
			imagestring($im, 5, $px, $py, $text, $color);
			
			
		}		
	}
	
	private function connect(TaskNetwork $TaskNetwork, $ID1,$ID2,$criticalPath=false, $dash = false,$under= false,$upper=false, $dist=0,$vertical=false,$color="black",$timeGap = false,$allArrow= false){
		$type;$colore;$text=true;
		if(!$criticalPath){
			if($dash){
				$type="dash";
			}
			else{
				$type="normal";
			}
		}else{
			$type="criticalPath";
		}

		$img = $TaskNetwork->img;

		switch($color){
			case "black":$colore = ImageColorAllocate($img,0,0,0);break;//nero
			case "red":$colore = ImageColorAllocate($img,255,0,0);break;//rosso
			case "green":$colore = ImageColorAllocate($img,0,255,0);break;//verde
			case "blue":$colore = ImageColorAllocate($img,0,0,255);break;//blu
			case "gray":$colore = ImageColorAllocate($img,150,150,150);break;//grigio
			default:$colore = ImageColorAllocate($img,0,0,0);break;//nero
		}



		//ID 1 e 2 sono due array ID["riga"] ID["colonna"]
		$index = $TaskNetwork->index;
		$tbx1 = $index[$ID1["riga"]][$ID1["colonna"]];
		$tbx2 = $index[$ID2["riga"]][$ID2["colonna"]];

		//trovo il time gap tra i due tbx
		if($timeGap){
			if($text and (isset($tbx1) and isset($tbx2))){$value=TaskNetwork::getTimeDiff($tbx1,$tbx2)."d";}
		}
		
		//nel caso in cui siano Smp o Epm
		if(!isset($tbx1)){
			$tbx1rx=$TaskNetwork->SpM["rightx"];
			$tbx1ry=$TaskNetwork->SpM["righty"];
			$tbx1BlankUp= $TaskNetwork->SpM["righty"];
			$tbx1BlankDown= $TaskNetwork->SpM["righty"];
			$tbx1BlankRight= $TaskNetwork->SpM["rightx"]+50;
			$tbx1BlankFirst=$TaskNetwork->SpM["rightx"]+50;
			$tbx1BlankLast=$TaskNetwork->SpM["rightx"]+50;
			$tbx1x = $TaskNetwork->SpM["x"];$tbx1y = $TaskNetwork->SpM["y"];
		}else{
			//interi					ordinate												ascisse
			$tbx1rx = $tbx1->getRightX(); $tbx1BlankUp = $TaskNetwork->mapBlank["y"][$ID1["riga"]]+50-$dist; $tbx1BlankLeft = $TaskNetwork->mapBlank[$ID1["riga"]][$ID1["colonna"]]+50;
			$tbx1ry = $tbx1->getRightY(); $tbx1BlankDown = $TaskNetwork->mapBlank["y"][$ID1["riga"]+1]-50+$dist; $tbx1BlankRight =($ID1["colonna"]<sizeof($TaskNetwork->mapBlank[$ID1["riga"]])-1) ? $TaskNetwork->mapBlank[$ID1["riga"]][$ID1["colonna"]+1]-50 : $tbx1->getRightX()+50;
			$tbx1BlankFirst = (!$vertical)?($TaskNetwork->mapBlank["inizio"]-$dist):$tbx1BlankLeft-$dist;$tbx1BlankLast = (!$vertical)?($TaskNetwork->mapBlank["fine"]+$dist):$tbx1BlankRight+$dist ;
			$tbx1x = $tbx1->GetWidth(); $tbx1y = $tbx1->GetHeight();

			$tbx1shift = $tbx1->getAlertSize()/2;
		}

		if(!isset($tbx2)){
			$tbx2lx=$TaskNetwork->EpM["leftx"];
			$tbx2ly=$TaskNetwork->EpM["lefty"];
			$tbx2BlankUp= $TaskNetwork->EpM["lefty"];
			$tbx2BlankDown= $TaskNetwork->EpM["lefty"];
			$tbx2BlankLeft= $TaskNetwork->EpM["leftx"]-50;
			$tbx2BlankFirst=$TaskNetwork->EpM["leftx"]-50;
			$tbx2BlankLast=$TaskNetwork->EpM["leftx"]-50;
			$tbx2x = $TaskNetwork->EpM["x"];$tbx2y = $TaskNetwork->EpM["y"];
		}else{
			//interi					ordinate												ascisse
			$tbx2lx = $tbx2->getLeftX(); $tbx2BlankUp = $TaskNetwork->mapBlank["y"][$ID2["riga"]]+50-$dist; $tbx2BlankLeft = $TaskNetwork->mapBlank[$ID2["riga"]][$ID2["colonna"]]+50;
			$tbx2ly = $tbx2->getLeftY(); $tbx2BlankDown = $TaskNetwork->mapBlank["y"][$ID2["riga"]+1]-50+$dist; $tbx2BlankRight =($ID2["colonna"]<sizeof($TaskNetwork->mapBlank[$ID2["riga"]])-1) ? $TaskNetwork->mapBlank[$ID2["riga"]][$ID2["colonna"]+1]-50 : $tbx2->getRightX()+50;
			$tbx2BlankFirst = (!$vertical)?($TaskNetwork->mapBlank["inizio"]-$dist):$tbx2BlankLeft-$dist;$tbx2BlankLast = (!$vertical)?($TaskNetwork->mapBlank["fine"]+$dist):$tbx2BlankRight+$dist;
			$tbx2x = $tbx2->GetWidth(); $tbx2y = $tbx2->GetHeight();

			$tbx2shift = $tbx2->getAlertSize()/2;
		}

		/////////////////////////////////
		if(abs($tbx1ry-$tbx2ly)<10){//le tbx sono sulla stessa riga
			$points;
			if(abs($tbx2lx-$tbx1rx)<300){ //adiacenti

				if(!$under){
					$points[0]["x"]= $tbx1rx-$tbx1shift	;$points[0]["y"]=$tbx1ry+($tbx1shift/2);
					$points[1]["x"]= $tbx1BlankRight	;$points[1]["y"]=$tbx1ry+($tbx1shift/2);
				}else{
					$points[0]["x"]= $tbx1rx-($tbx1x/2)-($tbx1shift/2) ;$points[0]["y"]=$tbx1ry+($tbx1y/2);
					$points[1]["x"]= $tbx1rx-($tbx1x/2)-($tbx1shift/2) ;$points[1]["y"]=$tbx1BlankDown;
					$points[2]["x"]= $tbx1BlankRight				   ;$points[2]["y"]=$tbx1BlankDown;
					$points[3]["x"]= $tbx1BlankRight				   ;$points[3]["y"]=$tbx1ry+($tbx1shift/2);
					}
					
				if(!$upper){
					$points[4]["x"]= $tbx2BlankLeft		;$points[4]["y"]=$tbx1ry+($tbx1shift/2);
					$points[5]["x"]= $tbx2BlankLeft		;$points[5]["y"]=$tbx2ly+($tbx2shift/2); 
					$points[6]["x"]= $tbx2lx			;$points[6]["y"]=$tbx2ly+($tbx2shift/2);//arrivato
				}else{
					$points[4]["x"]= $tbx1BlankRight					;$points[4]["y"]=$tbx2BlankUp; 
					$points[5]["x"]= $tbx2lx+($tbx2x/2)-($tbx2shift/2)	;$points[5]["y"]=$tbx2BlankUp;
					$points[6]["x"]= $tbx2lx+($tbx2x/2)-($tbx2shift/2)	;$points[6]["y"]=$tbx2ly-($tbx2y/2)+$tbx2shift;//arrivato
				}
				TaskNetwork::patharrow($img,$points,$colore,$type,$allArrow,$value);
					
			}
			else{// non adiacenti
				if(!$under){
					$points[0]["x"]= $tbx1rx-$tbx1shift	;$points[0]["y"]=$tbx1ry+($tbx1shift/2);
					$points[1]["x"]= $tbx1BlankRight	;$points[1]["y"]=$tbx1ry+($tbx1shift/2);
				}else{
					$points[0]["x"]= $tbx1rx-($tbx1x/2)-($tbx1shift/2) ;$points[0]["y"]=$tbx1ry+($tbx1y/2);
					$points[1]["x"]= $tbx1rx-($tbx1x/2)-($tbx1shift/2) ;$points[1]["y"]=$tbx1BlankDown;
					$points[2]["x"]= $tbx1BlankRight				   ;$points[2]["y"]=$tbx1BlankDown;
				}

				$points[3]["x"]= $tbx1BlankRight	;$points[3]["y"]=min($tbx2BlankUp,$tbx1BlankUp);

				if(!$upper){
					$points[4]["x"]= $tbx2BlankLeft	;$points[4]["y"]=min($tbx2BlankUp,$tbx1BlankUp);
					$points[5]["x"]= $tbx2BlankLeft	;$points[5]["y"]=$tbx2ly+($tbx2shift/2);
					$points[6]["x"]= $tbx2lx		;$points[6]["y"]=$tbx2ly+($tbx2shift/2); //arrivato
					
				}else{
					$points[4]["x"]= $tbx2lx+($tbx2x/2)-($tbx2shift/2)	;$points[4]["y"]=min($tbx2BlankUp,$tbx1BlankUp);
					$points[5]["x"]= $tbx2lx+($tbx2x/2)-($tbx2shift/2)	;$points[5]["y"]=$tbx2ly-($tbx2y/2)+$tbx2shift;//arrivato
				}
				TaskNetwork::patharrow($img,$points,$colore,$type,$allArrow,$value);
			}
			
			$TaskNetwork->img = $img;
			return $TaskNetwork;
		}
		else{
			if($tbx1ry<$tbx2ly){//se tbx1 è piu in alto di tbx2
			
				$points;
				if(!$under){
					$points[0]["x"]= $tbx1rx-$tbx1shift	;$points[0]["y"]=$tbx1ry+($tbx1shift/2);
					$points[1]["x"]= $tbx1BlankRight	;$points[1]["y"]=$tbx1ry+($tbx1shift/2);
					$points[2]["x"]= $tbx1BlankRight	;$points[2]["y"]=$tbx1BlankDown;
				}else{
					$points[0]["x"]= $tbx1rx-($tbx1x/2)-($tbx1shift/2) ;$points[0]["y"]=$tbx1ry+($tbx1y/2);
					$points[1]["x"]= $tbx1rx-($tbx1x/2)-($tbx1shift/2) ;$points[1]["y"]=$tbx1BlankDown;
					}
					
				if(abs($tbx1BlankDown-$tbx2BlankUp)>50){
					if($tbx1rx>($TaskNetwork->x/2) or $tbx2lx>($TaskNetwork->x/2)){//se tbx1 o tbx2 è nella metà di destra della TN
						$points[3]["x"]= $tbx1BlankLast	;$points[3]["y"]=$tbx1BlankDown;
						$points[4]["x"]= $tbx1BlankLast	;$points[4]["y"]=$tbx2BlankUp;
					}
					else{//tbx1 e tbx2 sono a sinistra
						$points[3]["x"]= $tbx1BlankFirst ;$points[3]["y"]=$tbx1BlankDown;
						$points[4]["x"]= $tbx1BlankFirst ;$points[4]["y"]=$tbx2BlankUp;
					}
				}
				else{
					$points[3]["x"]=$points[1]["x"];$points[3]["y"]=($tbx1BlankDown+$tbx2BlankUp)/2;
					$points[4]["x"]=$points[1]["x"];$points[4]["y"]=$tbx2BlankUp;
				}
									
				if(!$upper){
					$points[5]["x"]= $tbx2BlankLeft	;$points[5]["y"]=$tbx2BlankUp;
					$points[6]["x"]= $tbx2BlankLeft	;$points[6]["y"]=$tbx2ly+($tbx2shift/2);
					$points[7]["x"]= $tbx2lx		;$points[7]["y"]=$tbx2ly+($tbx2shift/2); //arrivato
						
				}else{
					$points[5]["x"]= $tbx2lx+($tbx2x/2)-($tbx2shift/2)	;$points[5]["y"]=$tbx2BlankUp;
					$points[6]["x"]= $tbx2lx+($tbx2x/2)-($tbx2shift/2)	;$points[6]["y"]=$tbx2ly-($tbx2y/2)+$tbx2shift;//arrivato
				}
				TaskNetwork::patharrow($img,$points,$colore,$type,$allArrow,$value);
			}
			elseif($tbx1ry>$tbx2ly){//tbx2 è piu in alto di tbx1
				$points;
				if(!$under){
					$points[0]["x"]= $tbx1rx-$tbx1shift	;$points[0]["y"]=$tbx1ry+($tbx1shift/2);
					$points[1]["x"]= $tbx1BlankRight	;$points[1]["y"]=$tbx1ry+($tbx1shift/2);
				}else{
					$points[0]["x"]= $tbx1rx-($tbx1x/2)-($tbx1shift/2) ;$points[0]["y"]=$tbx1ry+($tbx1y/2);
					$points[1]["x"]= $tbx1rx-($tbx1x/2)-($tbx1shift/2) ;$points[1]["y"]=$tbx1BlankDown;
					$points[2]["x"]= $tbx1BlankRight				   ;$points[2]["y"]=$tbx1BlankDown;
				}
				$points[3]["x"]= $tbx1BlankRight	;$points[3]["y"]=$tbx1BlankUp;
					
				if(abs($tbx1BlankUp-$tbx2BlankDown)>50){
					if($tbx1rx>($TaskNetwork->x/2) or $tbx2lx>($TaskNetwork->x/2)){//se tbx1 o tbx2 è nella metà di destra della TN
						$points[4]["x"]= $tbx1BlankLast	;$points[4]["y"]=$tbx1BlankUp;
						$points[5]["x"]= $tbx1BlankLast	;$points[5]["y"]=$tbx2BlankDown;
					}
					else{//tbx1 e tbx2 sono a sinistra
						$points[4]["x"]= $tbx1BlankFirst ;$points[4]["y"]=$tbx1BlankUp;
						$points[5]["x"]= $tbx1BlankFirst ;$points[5]["y"]=$tbx2BlankDown;
					}
				}
				else{
				$points[4]["x"]= $tbx1BlankRight	;$points[4]["y"]=($tbx1BlankUp+$tbx2BlankDown)/2;
				$points[5]["x"]= $tbx2BlankLeft		;$points[5]["y"]=($tbx1BlankUp+$tbx2BlankDown)/2;
				
				}
				$points[6]["x"]= $tbx2BlankLeft	;$points[6]["y"]=$tbx2BlankDown;				
				
				if(!$upper){
					$points[7]["x"]= $tbx2BlankLeft	;$points[7]["y"]=$tbx2ly+($tbx2shift/2);
					$points[8]["x"]= $tbx2lx		;$points[8]["y"]=$tbx2ly+($tbx2shift/2); //arrivato
					
				}else{
					$points[7]["x"]= $tbx2BlankLeft						;$points[7]["y"]=$tbx2BlankUp;
					$points[8]["x"]= $tbx2lx+($tbx2x/2)-($tbx2shift/2)	;$points[8]["y"]=$tbx2BlankUp;
					$points[9]["x"]= $tbx2lx+($tbx2x/2)-($tbx2shift/2)	;$points[9]["y"]=$tbx2ly-($tbx2y/2)+$tbx2shift;//arrivato
				}
				TaskNetwork::patharrow($img,$points,$colore,$type,$allArrow,$value);
			}
		}
		

		$TaskNetwork->img = $img;
		return $TaskNetwork;
	}

//------Funzioni di disegno------------fine

//------Funzioni di merging------------
//esegue il merging di due immagini separete da 50 px di spazio
	private function mergeArrayRight($array){
		$TN = new TaskNetwork();

		$array = TaskNetwork::trimArray($array);
		for($val=0;$val<sizeof($array);$val++){
			$b =$array[$val];

			$imgTN = $TN->img;
			$imgTNx = $TN->x;
			$imgTNy = $TN->y;

			$imgb = $b->getImage();
			$imgbx = $b->GetWidth();
			$imgby = $b->GetHeight();

			$outx = ($imgTNx+$imgbx+200);
			$outy = max($imgTNy, $imgby);
						
			$centerTN = $outy-$imgTNy;
			$centerb = $outy-$imgby;
			
			//alloco i punti mediani dei lati della tbx dentro b
			$b->setLeft($imgTNx+300, ($imgby/2)+50+$centerb/2);
			$b->setRight( $imgTNx+300+$imgbx, ($imgby/2)+50+$centerb/2);
			
			if($centerTN!=0){
				for($i=0;$i<$val;$i++){
					$array[$i]->setLeft($array[$i]->getLeftX(),$array[$i]->getLeftY()+$centerTN/2);
					$array[$i]->setRight($array[$i]->getRightX(),$array[$i]->getRightY()+$centerTN/2);
				}
			}

			$out = ImageCreate($outx , $outy);
			$bianco = ImageColorAllocate($out,255,255,255);

			//copio la prima immagine nell'output
			imagecopy($out,$imgTN,0,$centerTN/2,0,0,$imgTNx,$imgTNy);
			//e poi la seconda
			imagecopy($out,$imgb,$outx-($imgbx+100),$centerb/2,0,0,$imgbx,$imgby);

			imagedestroy($imgTN);
			imagedestroy($imgb);

			$index = $TN->index;
			$index[0][$val]= $b;



			$TN->img = $out; $TN->x = $outx; $TN->y = $outy; $TN->index = $index;

		}



			//ingrandisco il disegno di 25 px in alto e in basso
			$out = ImageCreate($TN->x , $TN->y+100);
			$bianco = ImageColorAllocate($out,255,255,255);

			imagecopy($out,$TN->img,0,50,0,0,$TN->x,$TN->y);
			imagedestroy($TN->img);

			$TN->img = $out; $TN->y += 100;


		return $TN;


	}

	private function mergeArrayUnder($array,$vertical=false){
		$TN = new TaskNetwork();

		$array = TaskNetwork::trimArray($array);
		for($val=0;$val<sizeof($array);$val++){
			$TN2 = $array[$val];

			$imgTN = $TN->img;
			$imgTNx = $TN->x;
			$imgTNy = $TN->y;
			$indexTN = $TN->index;

			$imgTN2 = $TN2->img;
			$imgTN2x = $TN2->x;
			$imgTN2y = $TN2->y;
			$indexTN2 = $TN2->index[0];


			$outx = max($imgTNx,$imgTN2x);
			$outy = ($imgTNy+$imgTN2y);

			
			$out = ImageCreate($outx , $outy);
			$bianco = ImageColorAllocate($out,255,255,255);


			$tbxl= $TN2->index[0][0]->getWidth();
			$tbxlv =intval(substr($TN2->index[0][0]->getName(),0,1))-1;
			$gap=$tbxl + 200;
			
			//copio la prima immagine nell'output centrata
			imagecopy($out,$imgTN,(!$vertical)?(($outx/2)-($imgTNx/2)):0,0,0,0,$imgTNx,$imgTNy);
			//e poi la seconda centrata
			imagecopy($out,$imgTN2,(!$vertical)?(($outx/2)-($imgTN2x/2)):($tbxlv*$gap),$outy-$imgTN2y,0,0,$imgTN2x,$imgTN2y);


			imagedestroy($imgTN);
			imagedestroy($imgTN2);
			//cambio le coordinate della y e della x delle tbx inserite
			if(!$vertical){	
				if(($imgTNx-$imgTN2x)>=0){
					for($cont=0;$cont<sizeof($indexTN2);$cont++){
						$t = $indexTN2[$cont];
						$t->setLeft($t->getLeftX()+(($imgTNx/2)-($imgTN2x/2)),$t->getLeftY()+$outy-$imgTN2y);//la quantità che lo porta centrato
						$t->setRight($t->getRightX()+(($imgTNx/2)-($imgTN2x/2)),$t->getRightY()+$outy-$imgTN2y);//la quantità che lo porta centrato
					}
				}
				else {
	
					$indexTN = $this->incrementmatrix($indexTN,(($imgTN2x/2)-($imgTNx/2)));
					for($cont=0;$cont<sizeof($indexTN2);$cont++){
						$t = $indexTN2[$cont];
						$t->setLeft($t->getLeftX(),$t->getLeftY()+$outy-$imgTN2y);
						$t->setRight($t->getRightX(),$t->getRightY()+$outy-$imgTN2y);
					}
				}
			}else{
				for($cont=0;$cont<sizeof($indexTN2);$cont++){
						$t = $indexTN2[$cont];
						$t->setLeft($t->getLeftX()+($tbxlv*$gap),$t->getLeftY()+$outy-$imgTN2y);
						$t->setRight($t->getRightX()+($tbxlv*$gap),$t->getRightY()+$outy-$imgTN2y);
				}
			}



			$indexTN[$val]= $indexTN2;

			$TN->img = $out; $TN->x = $outx; $TN->y = $outy; $TN->index = $indexTN;

		}


		return $TN;
	}

	private function mergeSpM(){
		$this->SpM = $this->drawSpM();

		$imgTN = $this->img;
		$imgTNx = $this->x;
		$imgTNy = $this->y;

		$imgspm = $this->SpM["img"];
		$imgspmx = $this->SpM["x"];
		$imgspmy = $this->SpM["y"];


		$outx = ($imgTNx+$imgspmx+(200-$imgspmx)); //200 - $imgTNx
		$outy = max($imgTNy, $imgspmy);

		//alloco i punti mediani del lato destro della SpM
		$this->SpM["rightx"] = $imgspmx+1;
		$this->SpM["righty"] = ($outy/2)+1;

		$out = ImageCreate($outx , $outy);
		$bianco = ImageColorAllocate($out,255,255,255);

		//copio la prima SpM nell'output
		imagecopy($out,$imgspm,0,($outy/2)-($imgspmy/2),0,0,$imgspmx,$imgspmy);
		//e poi la TN
		imagecopy($out,$imgTN,($outx-$imgTNx),0,0,0,$imgTNx,$imgTNy);

		imagedestroy($imgspm);
		imagedestroy($imgTN);

		$this->img=$out; $this->x = $outx; $this->y = $outy;

	}

	private function mergeEpM(){

		$this->EpM = $this->drawEpM();

		$imgTN = $this->img;
		$imgTNx = $this->x;
		$imgTNy = $this->y;

		$imgepm = $this->EpM["img"];
		$imgepmx = $this->EpM["x"];
		$imgepmy = $this->EpM["y"];

		$outx = ($imgTNx+$imgepmx+(200-$imgepmx));
		$outy = max($imgTNy, $imgepmy);

		//alloco i punti mediani del lato destro della SpM

		$this->EpM["leftx"] = ($outx-$imgepmx)+1;
		$this->EpM["lefty"] = ($outy/2)+1;

		$out = ImageCreate($outx , $outy);
		$bianco = ImageColorAllocate($out,255,255,255);

		//copio la prima SpM nell'output
		imagecopy($out,$imgTN,0,0,0,0,$imgTNx,$imgTNy);
		//e poi la TN
		imagecopy($out,$imgepm,($outx-$imgepmx),($outy/2)-($imgepmy/2),0,0,$imgepmx,$imgepmy);

		imagedestroy($imgepm);
		imagedestroy($imgTN);


		$this->img=$out; $this->x = $outx; $this->y = $outy;

	}

//------Funzioni di merging------------fine

//------Funzioni di setting------------
	private function incrementmatrix($matrix,$inc){
		for($i=0;$i<sizeof($matrix);$i++){
			for($j=0;$j<sizeof($matrix[$i]);$j++){
				$t = $matrix[$i][$j];
					$t->setLeft($t->getLeftX()+$inc,$t->getLeftY());
					$t->setRight($t->getRightX()+$inc,$t->getRightY());
			}
		}
		return $matrix;
}

	//mappa tutti i punti vuoti della TN
	private function mapBlank(){
		$index = $this->index;
		$map; //mappa di output, matrice

		//alloco il primo spazio in cima alla TN
		$map["y"][0] = 1;
		//alloco il primo e l'ultimo spazio in orizzontale della TN
		$map["inizio"] = 200;
		$map["fine"] = $this->x - 200;

		//codifico nella prima riga della mappa tutte gli indici degli spazi tra righe della TN
		for($j=1;$j<sizeof($index);$j++){
			
			$rawy=0;
			for($a=0;$a<sizeof($index[$j]);$a++){if($index[$j][$a]->GetHeight()>$rawy) {$rawy=$index[$j][$a]->GetHeight();}}
			$map["y"][$j] =  ($index[$j][0]->getLeftY()-($rawy/2))-50;
		}
		//alloco l'ultimo spazio in fondo alla TN
		$map["y"][(sizeof($index))] =  $this->y - 1;

		//codifico nelle restanti righe della matrice gli spazi tra tbx della stessa TN
		for($h=0;$h<sizeof($index);$h++){
			for($i=0;$i<sizeof($index[$h]);$i++){
				$map[$h][$i] = $index[$h][$i]->getLeftX() - 100;
			}
		}
		return $map;
	}

	private function trimArray($array){
		$result;$j=0;
		foreach($array as $coord){
			$result[$j] = $coord;
			$j++; 
		}
		return $result;
	}
	
	private function getTbxIndex($tid){
		$index= $this->index;
		for($i=0;$i<sizeof($index);$i++){
			for($j=0;$j<sizeof($index[$i]);$j++){
				if($tid==$index[$i][$j]->getId()){
					$coord["riga"]=$i;
					$coord["colonna"]=$j;
					return $coord;
				}
			}
		}
	}
	
	private function getTimeDiff($tbx1,$tbx2){
		if(is_string($tbx1)){
			$end = strtotime(str_replace(".","-",$tbx1));
		}else{
			$end1= $tbx1->getPlannedTimeframe();
			$end = strtotime(str_replace(".","-",$end1["end"]));
		}
		$start2= $tbx2->getPlannedTimeframe();
		$start = strtotime(str_replace(".","-",$start2["start"]));
		
		$result= $start-$end;
		return round($result/24/60/60);//in giorni		
	}

	private function getIndex(){
		return $this->index;
	}

	private function addTbx($tbx,$row,$col){
		$this->index[$row][$col] = $tbx;
	}

	private function arrayConcat($arr1,$arr2){
		foreach($arr2 as $x){
			$arr1[sizeof($arr1)] = $x;
		}
		return $arr1;
	}
	
	private function orderWbsId($array){
		$task = new CTask();
		for($i=0; $i< sizeof($array); $i++) {
	 
		    //imposto il min a i (quelli prima di i sono stati gia' ordinati)
		    $min=$i;
	 
		    //parto da i+1 e, se trovo un elemento piu' piccolo, cambio min
		    for($j=$i+1; $j<sizeof($array); $j++){
				$minwbs = $task->getWBS($array[$min]);
				$jwbs = $task->getWBS($array[$j]);
				
				if($jwbs<$minwbs){
		 			$min=$j;
		 		}
		    }
		 
			    //se durante il for ho cambiato min, scambio arr[i] e arr[min].
			    if ($min != $i){
			    	$array = TaskNetwork::arraySwap($array, $i, $min);
			    }
		}
		return $array;
	}
	
	private function getTbxFromLevel($level,$tasks=null){
		if(!$tasks){//se non mi è stato passato un array di task aperti me li creo io
			$id = "SELECT task_id FROM tasks t WHERE task_parent=task_id and task_project=".$this->project." ORDER BY task_id";
			$result = TaskNetwork::doQuery($id);
			foreach($result as $res){
				$results[sizeof($results)]= $res[0];
			}	
			unset($res);
			
			$int=0;$sons;
			while($int<$level){
				$size = sizeof($results);
				for($i=0;$i<$size;$i++){
					$DB = new taskBoxDB($results[$i]);
					$q = "SELECT task_id FROM tasks t WHERE task_parent <> task_id and task_parent=".$DB->getId()." and task_project=".$this->project." ORDER BY task_wbs_index";
					$children = TaskNetwork::doQuery($q);
					if(isset($children[0])){
						unset($results[$i]);
						foreach($children as $child){
							$sons[sizeof($sons)] = $child["task_id"];
						}
					}
				}
				$results = TaskNetwork::trimArray($results);
				$results = TaskNetwork::arrayConcat($results,$sons);
				
				unset($sons);			
				$int++;
			}
			unset($result);
		}
		else{// uso quello passatomi
			$results = $tasks;
		}
		
		foreach($results as $task){
			$wbslv = CTask::getWBS($task);
			$wbs = intval(substr($wbslv,0,1));
			$res[$wbs-1][sizeof($res[$wbs-1])]= $task;
		}
		
		return $res;
	}
	
	private function doQuery($query){
		$result = db_exec($query);
		$error = db_error();
		if ($error) {
			echo $error;
			exit;
		}
		$results = array();
		for ($i = 0; $i < db_num_rows($result); $i++) {
			$results[] = db_fetch_assoc($result);
		}
	
		return $results;
	}
	
	//funzione ricorsiva per il calcolo delle dipendenze interne
	private function computeDependence($tbxarray){	
			for($i=0;$i<sizeof($tbxarray);$i++){
				$query = "SELECT dependencies_task_id FROM task_dependencies td WHERE td.dependencies_req_task_id =".$tbxarray[$i]["id"];
				$results = TaskNetwork::doQuery($query);// restituisce i task dipendenti da tbx
				if(isset($results[0])){
					foreach($results as $dep){
						$liv = sizeof($arraydep);
						$arraydep[$liv]["id"] = $dep["dependencies_task_id"];
						
						$tdb = new TaskBoxDB($dep["dependencies_task_id"]);
						$pdata = $tdb->getPlannedData();
						$arraydep[$liv]["duration"] = intval($pdata["duration"]);
						$arraydep[$liv]["effort"] = intval($pdata["effort"]);
						$arraydep[$liv]["cost"] = intval($pdata["cost"]);
						
						$tbxarray[$i]["dependencies"] = TaskNetwork::computeDependence($arraydep);
						unset($arraydep);
					}			
				}
			}
			return $tbxarray;
	}
	
	private function arraySwap($array,$i,$j){
		$side=$array[$i];
		$array[$i] = $array[$j];
		$array[$j]= $side; 
		unset($side);
		return $array;
	}
	
	private function orderArray($array){
		$query = "SELECT project_finish_date FROM projects p WHERE p.project_id =".$this->project;
		$res = TaskNetwork::doQuery($query);
		$EpD = str_replace("-",".",substr($res[0][0],0,10));
		$EpD = substr($EpD,8,2).".".substr($EpD,5,2).".".substr($EpD,0,4); //End Project Date
		
		//per ogni elemento di arr,
		for($i=0; $i< sizeof($array); $i++) {
	 
		    //imposto il min a i (quelli prima di i sono stati gia' ordinati)
		    $min=$i;
	 
		    //parto da i+1 e, se trovo un elemento piu' piccolo, cambio min
		    for($j=$i+1; $j<sizeof($array); $j++){
		        
				if ($array[$j]["duration"] < $array[$min]["duration"]){
					$min=$j;
				}
				elseif($array[$j]["duration"] == $array[$min]["duration"]){
					if ($array[$j]["effort"] < $array[$min]["effort"]){
						$min=$j;
					}
					elseif($array[$j]["effort"] == $array[$min]["effort"]){
						if ($array[$j]["cost"] < $array[$min]["cost"]){
							$min=$j;
						}
						elseif($array[$j]["cost"] == $array[$min]["cost"]){
							$jtask = new taskBoxDB(substr($array[$j]["id"],(strrchr($array[$j]["id"],',')+1),(strlen($array[$j]["id"])-strrchr($array[$j]["id"],',')+1)));
							$mintask = new taskBoxDB(substr($array[$min]["id"],(strrchr($array[$min]["id"],',')+1),(strlen($array[$min]["id"])-strrchr($array[$min]["id"],',')+1)));
							
						}
					}
				}
		    }
		 
			    //se durante il for ho cambiato min, scambio arr[i] e arr[min].
			    if ($min != $i){
			    	$array = TaskNetwork::arraySwap($array, $i, $min);
			    }
		}
		return $array;
	}
		
//------Funzioni di setting------------fine
	
}

$project_id = defVal(@$_REQUEST['project_id'], 0);

$tbxsettings['task_name'] = defVal(@$_REQUEST['task_name'], false);
$tbxsettings['task_alerts'] = defVal(@$_REQUEST['task_alerts'], false);
$tbxsettings['task_progress'] = defVal(@$_REQUEST['task_progress'], false);
$tbxsettings['planned_data'] = defVal(@$_REQUEST['planned_data'], false);
$tbxsettings['actual_data'] = defVal(@$_REQUEST['actual_data'], false);
$tbxsettings['planned_timeframe'] = defVal(@$_REQUEST['planned_timeframe'], false);
$tbxsettings['actual_timeframe'] = defVal(@$_REQUEST['actual_timeframe'], false);
$tbxsettings['resources'] = defVal(@$_REQUEST['resources'], null);

$explode_tasks = defVal(@$_REQUEST['explode_tasks'], 0);
$max_wbs_lv =  defVal(@$_REQUEST['max_wbs_lv'], 3);
$vertical_view = defVal(@$_REQUEST['vertical_view'], false);
$task_to_view = $tasks_opened = $AppUI->getState("tasks_opened");
$all_arrow =  defVal(@$_REQUEST['all_arrow'], false);
$time_gaps =  defVal(@$_REQUEST['time_gaps'], false);

$critical_path = defVal(@$_REQUEST['critical_path'], false);
$dependencies = defVal(@$_REQUEST['dependencies'], false);
$default_dep = defVal(@$_REQUEST['default_dep'], false);

// creazione TN vuota
$TN = new TaskNetwork($project_id);

//$tasks = array(86,92,124,274,545,93,97,98);
		  // lv vertical
$TN->createTN($explode_tasks,$vertical_view,$task_to_view,$tbxsettings);

if ($default_dep){
	$TN->addDefaultDependancies($all_arrow);
}

if($dependencies){
	$TN->drawConnections($vertical_view,$all_arrow,$time_gaps);
}

if($critical_path){
	$TN->drawCriticalPath($max_wbs_lv,$vertical_view,$all_arrow,$time_gaps);
}

$TN->printTN();
//esempio query
//http://localhost/pmango/tests/TaskNetwork.php?project_id=5&task_name=true&task_alerts=true&task_progress=true&resources=planned&explode_tasks=1&vertical_view=false&default_dep=true&all_arrow=true&dependencies=true&time_gaps=true&critical_path=true
?>