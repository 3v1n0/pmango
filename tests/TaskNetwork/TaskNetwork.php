<?php
include ("../TaskBox.class.php");

$baseDir = "../..";

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

	private $connections=array(); // array associativo delle dipendenze

	function TaskNetwork(){
		$this->index = array();
		$this->img = ImageCreate(1,1); //immagine vuota iniziale della TN
		$this->x = 1; 					//larghezza della TN
		$this->y = 1;					//altezza della TN
	}

	
//------Funzioni di classe------------
	public function getIndex(){
		return $this->index;
	}

	public function addTbx($tbx,$row,$col){
		$this->index[$row][$col] = $tbx;
	}

	public function createTN($vertical=false){
		$rows=array();
		for($i=0;$i<sizeof($this->index);$i++){
			$rows[$i] = $this->mergeArrayRight($this->index[$i]);
		}

		$final = $this->mergeArrayUnder($rows,$vertical);
		$final = $this->mergeSpM($final);//aggiunta Spm
		$final = $this->mergeEpM($final);// aggiunta Epm

		//mappo tutti i punti vuoti della TN
		$final->mapBlank = $this->mapBlank($final);

		return $final;
	}

	public function addDefaultDependancies(){
			$index = $this->getIndex();

			//start to tbx dependancies
			for($a=0;$a<sizeof($index);$a++){
					for($b=0;$b<sizeof($index[$a]);$b++){
						$ID2["riga"] = $a; $ID2["colonna"] = $b;
										 //TN  			 cr.path dash  under upper dist color
						TaskNetwork::connect($this,null,$ID2, false, true,false,false,0,false,"gray");
					}
			}
	
			for($a=0;$a<sizeof($index);$a++){
					for($b=0;$b<sizeof($index[$a]);$b++){
						$ID1["riga"] = $a; $ID1["colonna"] = $b;
										 //TN  			 cr.path dash  under upper dist
						TaskNetwork::connect($this,$ID1,null, false, false,false,false,8,false,"gray");
					}

			}
			
		}

	public function printTN(){
		Imagepng($this->img);
		ImageDestroy($this->img);
	}

	public function addDependence($tbxID1, $tbxID2){
		$ID1 = TaskNetwork::getTbxIndex($tbxID1);
		$ID2 = TaskNetwork::getTbxIndex($tbxID2);
		
		$i = sizeof($this->connections);
		$this->connections[$i]["FROM"] = $ID1;
		$this->connections[$i]["TO"] = $ID2;
	}
	//da perfezionare che ricerchi se i figli della tbx hanno dipendenze
	public function drawConnections($vertical=false){//TODO creare le varie dipendenze upper under tra tbx collassati
		$index = $this->getIndex();
		for($i=0;$i<sizeof($this->connections);$i++){
			$upper =false; $under=false;

			$ID1 = $this->connections[$i]["FROM"];
			$ID2 = $this->connections[$i]["TO"];

			$tbx1 = $index[$ID1["riga"]][$ID1["colonna"]];
			$tbx2 = $index[$ID2["riga"]][$ID2["colonna"]];


			//FIXME check internal dependences
			if(false/*!CTask::isLeafSt($tbx1->getId())*/){
				$under = true;
			}
			if(false/*!CTask::isLeafSt($tbx2->getId())*/){
				$upper =true;
			}
			
			if (!$tbx1 || !$tbx2) continue;
			
			$this->connect($this,$ID1,$ID2,false,false,$under,$upper,3*$i,$vertical);
			//print_r($this);
			//echo "connecting ",$tbx1->getID()," ",$tbx2->getID()."\n";

		}
	}
	
	//FIXME ABBESTIA!
	public function drawCriticalPath(TaskNetwork $TaskNetwork){//TODO
		$tbxarray ;// qua ci va la query che mi restituisce i task del critical path
		
		$tbxfrom = $tbxarray[0];
		for($i=1;$i<sizeof($tbxarray);$i++){
			$tbxto =  $tbxarray[$i];
			$coordfrom = $TaskNetwork->getTbxIndex($tbxfrom);
			$coordto = $TaskNetwork->getTbxIndex($tbxto);
			$under;//controlli del caso
			$upper;//controlli del caso
			
			$TaskNetwork = $TaskNetwork->connect($TaskNetwork,$coordfrom,$coordto,true,$under,$upper);
		}
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

	//wrapper fiunzioni di base disegno
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
	private function patharrow($im,$points/*array*/,$color,$type,$text=""){
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
				
				TaskNetwork::$arrow($im, $fx,$fy, $tx,$ty,3,3, $color);
				//TaskNetwork::$line($im, $fx,$fy, $tx,$ty, $color);
				
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
	
	private function connect(TaskNetwork $TaskNetwork, $ID1,$ID2,$criticalPath=false, $dash = false,$under= false,$upper=false, $dist=0,$vertical=false,$color="black"){
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
		if($text and (isset($tbx1) and isset($tbx2))){$value=TaskNetwork::getTimeDiff($tbx1,$tbx2);}
		
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
			$tbx1rx = $tbx1->getRightX(); $tbx1BlankUp = $TaskNetwork->mapBlank["y"][$ID1["riga"]]+$dist; $tbx1BlankLeft = $TaskNetwork->mapBlank[$ID1["riga"]][$ID1["colonna"]]+$dist;
			$tbx1ry = $tbx1->getRightY(); $tbx1BlankDown = $TaskNetwork->mapBlank["y"][$ID1["riga"]+1]-$dist; $tbx1BlankRight =($ID1["colonna"]<sizeof($TaskNetwork->mapBlank[$ID1["riga"]])-1) ? $TaskNetwork->mapBlank[$ID1["riga"]][$ID1["colonna"]+1]-$dist : $tbx1->getRightX()+50-$dist;
			$tbx1BlankFirst = (!$vertical)?($TaskNetwork->mapBlank["inizio"]-$dist):$tbx1BlankLeft;$tbx1BlankLast = (!$vertical)?($TaskNetwork->mapBlank["fine"]+$dist):$tbx1BlankRight ;
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
			$tbx2lx = $tbx2->getLeftX(); $tbx2BlankUp = $TaskNetwork->mapBlank["y"][$ID2["riga"]]+$dist; $tbx2BlankLeft = $TaskNetwork->mapBlank[$ID2["riga"]][$ID2["colonna"]]+$dist;
			$tbx2ly = $tbx2->getLeftY(); $tbx2BlankDown = $TaskNetwork->mapBlank["y"][$ID2["riga"]+1]-$dist; $tbx2BlankRight =($ID2["colonna"]<sizeof($TaskNetwork->mapBlank[$ID2["riga"]])-1) ? $TaskNetwork->mapBlank[$ID2["riga"]][$ID2["colonna"]+1]-$dist : $tbx2->getRightX()+50-$dist;
			$tbx2BlankFirst = (!$vertical)?($TaskNetwork->mapBlank["inizio"]-$dist):$tbx2BlankLeft;$tbx2BlankLast = (!$vertical)?($TaskNetwork->mapBlank["fine"]+$dist):$tbx2BlankRight;
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
				TaskNetwork::patharrow($img,$points,$colore,$type,$value);
					
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
				TaskNetwork::patharrow($img,$points,$colore,$type,$value);
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
					
				if($tbx1BlankDown!=$tbx2BlankUp){
					if($tbx1rx>($TaskNetwork->x/2) or $tbx2lx>($TaskNetwork->x/2)){//se tbx1 o tbx2 è nella metà di destra della TN
						$points[3]["x"]= $tbx1BlankLast	;$points[3]["y"]=$tbx1BlankDown;
						$points[4]["x"]= $tbx1BlankLast	;$points[4]["y"]=$tbx2BlankUp;
					}
					else{//tbx1 e tbx2 sono a sinistra
						$points[3]["x"]= $tbx1BlankFirst ;$points[3]["y"]=$tbx1BlankDown;
						$points[4]["x"]= $tbx1BlankFirst ;$points[4]["y"]=$tbx2BlankUp;
					}
				}
									
				if(!$upper){
					$points[5]["x"]= $tbx2BlankLeft	;$points[5]["y"]=$tbx2BlankUp;
					$points[6]["x"]= $tbx2BlankLeft	;$points[6]["y"]=$tbx2ly+($tbx2shift/2);
					$points[7]["x"]= $tbx2lx		;$points[7]["y"]=$tbx2ly+($tbx2shift/2); //arrivato
						
				}else{
					$points[5]["x"]= $tbx2lx+($tbx2x/2)-($tbx2shift/2)	;$points[5]["y"]=$tbx2BlankUp;
					$points[6]["x"]= $tbx2lx+($tbx2x/2)-($tbx2shift/2)	;$points[6]["y"]=$tbx2ly-($tbx2y/2)+$tbx2shift;//arrivato
				}
				TaskNetwork::patharrow($img,$points,$colore,$type,$value);
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
					
				if($tbx1BlankUp!=$tbx2BlankDown){
					if($tbx1rx>($TaskNetwork->x/2) or $tbx2lx>($TaskNetwork->x/2)){//se tbx1 o tbx2 è nella metà di destra della TN
						$points[4]["x"]= $tbx1BlankLast	;$points[4]["y"]=$tbx1BlankUp;
						$points[5]["x"]= $tbx1BlankLast	;$points[5]["y"]=$tbx2BlankDown;
					}
					else{//tbx1 e tbx2 sono a sinistra
						$points[4]["x"]= $tbx1BlankFirst ;$points[4]["y"]=$tbx1BlankUp;
						$points[5]["x"]= $tbx1BlankFirst ;$points[5]["y"]=$tbx2BlankDown;
					}
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
				TaskNetwork::patharrow($img,$points,$colore,$type,$value);
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

			$outx = ($imgTNx+$imgbx+100);
			$outy = max($imgTNy, $imgby);
						
			$centerTN = $outy-$imgTNy;
			$centerb = $outy-$imgby;
			
			//alloco i punti mediani dei lati della tbx dentro b
			$b->setLeft($imgTNx+250, ($imgby/2)+25+$centerb/2);
			$b->setRight( $imgTNx+250+$imgbx, ($imgby/2)+25+$centerb/2);
			
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
			imagecopy($out,$imgb,$outx-($imgbx+50),$centerb/2,0,0,$imgbx,$imgby);

			imagedestroy($imgTN);
			imagedestroy($imgb);

			$index = $TN->index;
			$index[0][$val]= $b;



			$TN->img = $out; $TN->x = $outx; $TN->y = $outy; $TN->index = $index;

		}



			//ingrandisco il disegno di 25 px in alto e in basso
			$out = ImageCreate($TN->x , $TN->y+50);
			$bianco = ImageColorAllocate($out,255,255,255);

			imagecopy($out,$TN->img,0,25,0,0,$TN->x,$TN->y);
			imagedestroy($TN->img);

			$TN->img = $out; $TN->y += 50;


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

			//copio la prima immagine nell'output centrata
			imagecopy($out,$imgTN,(!$vertical)?(($outx/2)-($imgTNx/2)):0,0,0,0,$imgTNx,$imgTNy);
			//e poi la seconda centrata
			imagecopy($out,$imgTN2,(!$vertical)?(($outx/2)-($imgTN2x/2)):0,$outy-$imgTN2y,0,0,$imgTN2x,$imgTN2y);


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
						$t->setLeft($t->getLeftX(),$t->getLeftY()+$outy-$imgTN2y);
						$t->setRight($t->getRightX(),$t->getRightY()+$outy-$imgTN2y);
				}
			}



			$indexTN[$val]= $indexTN2;

			$TN->img = $out; $TN->x = $outx; $TN->y = $outy; $TN->index = $indexTN;

		}


		return $TN;
	}

	private function mergeSpM($TN){
		$TN->SpM = $this->drawSpM();

		$imgTN = $TN->img;
		$imgTNx = $TN->x;
		$imgTNy = $TN->y;

		$imgspm = $TN->SpM["img"];
		$imgspmx = $TN->SpM["x"];
		$imgspmy = $TN->SpM["y"];


		$outx = ($imgTNx+$imgspmx+(200-$imgspmx)); //200 - $imgTNx
		$outy = max($imgTNy, $imgspmy);

		//alloco i punti mediani del lato destro della SpM
		$TN->SpM["rightx"] = $imgspmx+1;
		$TN->SpM["righty"] = ($outy/2)+1;

		$out = ImageCreate($outx , $outy);
		$bianco = ImageColorAllocate($out,255,255,255);

		//copio la prima SpM nell'output
		imagecopy($out,$imgspm,0,($outy/2)-($imgspmy/2),0,0,$imgspmx,$imgspmy);
		//e poi la TN
		imagecopy($out,$imgTN,($outx-$imgTNx),0,0,0,$imgTNx,$imgTNy);

		imagedestroy($imgspm);
		imagedestroy($imgTN);

		$TN->img=$out; $TN->x = $outx; $TN->y = $outy;
		return $TN;

	}

	private function mergeEpM($TN){

		$TN->EpM = $this->drawEpM();

		$imgTN = $TN->img;
		$imgTNx = $TN->x;
		$imgTNy = $TN->y;

		$imgepm = $TN->EpM["img"];
		$imgepmx = $TN->EpM["x"];
		$imgepmy = $TN->EpM["y"];

		$outx = ($imgTNx+$imgepmx+(200-$imgepmx));
		$outy = max($imgTNy, $imgepmy);

		//alloco i punti mediani del lato destro della SpM

		$TN->EpM["leftx"] = ($outx-$imgepmx)+1;
		$TN->EpM["lefty"] = ($outy/2)+1;

		$out = ImageCreate($outx , $outy);
		$bianco = ImageColorAllocate($out,255,255,255);

		//copio la prima SpM nell'output
		imagecopy($out,$imgTN,0,0,0,0,$imgTNx,$imgTNy);
		//e poi la TN
		imagecopy($out,$imgepm,($outx-$imgepmx),($outy/2)-($imgepmy/2),0,0,$imgepmx,$imgepmy);

		imagedestroy($imgepm);
		imagedestroy($imgTN);


		$TN->img=$out; $TN->x = $outx; $TN->y = $outy;
		return $TN;

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
	private function mapBlank(TaskNetwork $TN){
		$index = $TN->index;
		$map; //mappa di output, matrice

		//alloco il primo spazio in cima alla TN
		$map["y"][0] = 1;
		//alloco il primo e l'ultimo spazio in orizzontale della TN
		$map["inizio"] = 200;
		$map["fine"] = $TN->x - 200;

		//codifico nella prima riga della mappa tutte gli indici degli spazi tra righe della TN
		for($j=1;$j<sizeof($index);$j++){
			
			$rawy=0;
			for($a=0;$a<sizeof($index[$j]);$a++){if($index[$j][$a]->GetHeight()>$rawy) {$rawy=$index[$j][$a]->GetHeight();}}
			$map["y"][$j] =  ($index[$j][0]->getLeftY()-($rawy/2))-25;
		}
		//alloco l'ultimo spazio in fondo alla TN
		$map["y"][(sizeof($index))] =  $TN->y - 1;

		//codifico nelle restanti righe della matrice gli spazi tra tbx della stessa TN
		for($h=0;$h<sizeof($index);$h++){
			for($i=0;$i<sizeof($index[$h]);$i++){
				$map[$h][$i] = $index[$h][$i]->getLeftX() - 50;
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
	
	public function getTbxIndex($tid){
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
		$end1= $tbx1->getPlannedTimeframe();
		$end["year"] = substr($end1["end"],0,4);
		$end["month"] = substr($end1["end"],5,2);
		$end["day"] = substr($end1["end"],8,2);
		$start2= $tbx2->getPlannedTimeframe();
		$start["year"] = substr($start2["start"],0,4);
		$start["month"] = substr($start2["start"],5,2);
		$start["day"] = substr($start2["start"],8,2);
		//TODO usare una funzione di php
		$result= ($start["year"]-$end["year"])*365+($start["month"]-$end["month"])*31 + ($start["day"]-$end["day"]);
		
		return $result."d";		
	}
//------Funzioni di setting------------fine

}

/// creazione TN vuota
	$TN = new TaskNetwork();



$project_id = 5;
$id = "SELECT task_id FROM tasks t WHERE task_parent=task_id and task_project=".$project_id." ORDER BY task_id";

for($int=0;$int<1;$int++){
	$id = "SELECT task_id FROM tasks t WHERE task_parent in (".$id.") and task_id != task_parent and task_project=".$project_id." ORDER BY task_id";
}
$query = "SELECT task_id, task_name, task_parent, task_start_date, task_finish_date FROM tasks t WHERE task_id in(".$id.") and task_project=".$project_id." ORDER BY task_id";
/*
$query = "SELECT task_id, task_name, task_parent, task_start_date, task_finish_date FROM tasks t ".
         "WHERE t.task_project = ".$project_id." ORDER BY task_id";
*/
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

$res;
foreach($results as $task){
	$wbslv = CTask::getWBS($task['task_id']);
	
	$wbs = intval(substr($wbslv,0,1));
	$res[$wbs-1][sizeof($res[$wbs-1])]= $task;
}

$vertical = true;

for($j=0;$j<sizeof($res);$j++){
	for($k=0;$k<sizeof($res[$j]);$k++){	
		$wbslv = CTask::getWBS($res[$j][$k]['task_id']);
		
		
				$tbx = new TNNode($res[$j][$k]['task_id']);
				$tbx->setFontPath("../../fonts/Droid");
				//$tbx->setName($wbslv." ".$res[$j][$k]['task_name']);
				//$tbx->setPlannedData("14 d", "40 ph", "1350 €");
				//$tbx->setActualData("4 d", "6 ph", "230 €");
				//$tbx->setPlannedTimeframe(substr($res[$j][$k]['task_start_date'], 0, 10), substr($res[$j][$k]['task_finish_date'], 0, 10));
				//$tbx->setActualTimeframe(substr($tstart['task_log_start_date'], 0, 10),"");
				//$tbx->setResources("22 ph, Dilbert, Requirement Engineering\n".
				   //                "14 ph, Wally, Sales Manager\n".
				  //                 "04 ph, The Boss, Manager");
				//$tbx->setProgress(CTask::getPr($res[$j][$k]['task_id']));
				//$tbx->setAlerts(rand(0,2));
	
				if($vertical){$TN->addTbx($tbx,$k,$j);}else{$TN->addTbx($tbx,$j,$k);}
				
	}
}
	

$TN = $TN->createTN($vertical);

$TN->addDefaultDependancies($TN);

$query = "SELECT * FROM task_dependencies t";

$result = db_exec($query);
$error = db_error();
if ($error) {
	echo $error;
	exit;
}
$results = array();
$num = db_num_rows($result);
for ($i = 0; $i < $num; $i++) {
	$results[] = db_fetch_assoc($result);
}


foreach($results as $conn){
	$TN->addDependence($conn["dependencies_req_task_id"],$conn["dependencies_task_id"]);
}

/*
$index = $TN->getIndex();
for($i=0;$i<5;$i++){
	$r = rand(0,3);
	$c = rand(0,sizeof($index[$r])-1);

	$ID1["riga"] = $r; $ID1["colonna"] = $c;

	$r = rand(0,3);
	$c = rand(0,sizeof($index[$r])-1);

	$ID2["riga"] = $r; $ID2["colonna"] = $c;


	$TN->addDependence($ID1,$ID2);
}*/

	$ID1["riga"] = 2; $ID1["colonna"] = 12;
	$ID2["riga"] = 1; $ID2["colonna"] = 7;
	//$TN->addDependence($ID1,$ID2);


$TN->drawConnections($vertical);



$TN->printTN();
?>