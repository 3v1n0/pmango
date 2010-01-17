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

	private $connections=array(), $i=0; // array associativo delle dipendenze

	function TaskNetwork(){
		$this->index = array();
		$this->img = ImageCreate(1,1); //immagine vuota iniziale della TN
		$this->x = 1; 					//larghezza della TN
		$this->y = 1;					//altezza della TN
	}

	//il gioco sta nel creare tante righe di tbx quanti sono i lv della wbs. Inizialmente le righe saranno
	//separate tra di loro e successivamente verraranno unite per creare la TN finale

//------Funzioni di classe------------
	public function getIndex(){
		return $this->index;
	}

	public function addTbx($tbx,$row,$col){
		$this->index[$row][$col] = $tbx;
	}

	public function createTN(){
		$rows=array();
		for($i=0;$i<sizeof($this->index);$i++){
			$rows[$i] = $this->mergeArrayRight($this->index[$i]);
		}

		$final = $this->mergeArrayUnder($rows);
		$final = $this->mergeSpM($final);//aggiunta Spm
		$final = $this->mergeEpM($final);// aggiunta Epm

		//mappo tutti i punti vuoti della TN
		$final->mapBlank = $this->mapBlank($final);

		return $final;
	}

	public function addDefaultDependancies(TaskNetwork $TN){
			$index = $TN->getIndex();

			//start to tbx dependancies
			for($a=0;$a<sizeof($index);$a++){
					for($b=0;$b<sizeof($index[$a]);$b++){
						$ID2["riga"] = $a; $ID2["colonna"] = $b;
										 //TN  			 cr.path dash  under upper dist color
						$TN = $TN->connect($TN,null,$ID2, false, true,false,false,0,"gray");
					}
			}
	
			for($a=0;$a<sizeof($index);$a++){
					for($b=0;$b<sizeof($index[$a]);$b++){
						$ID1["riga"] = $a; $ID1["colonna"] = $b;
										 //TN  			 cr.path dash  under upper dist
						$TN = $TN->connect($TN,$ID1,null, false, false,false,false,10,"gray");
					}

			}
			
		}

	public function printTN(){
		Imagepng($this->img);
		ImageDestroy($this->img);
	}

	public function addDependence($ID1, $ID2){
		$this->connections[$this->i]["FROM"] = $ID1;
		$this->connections[$this->i]["TO"] = $ID2;
		$this->i++;
	}
	//da perfezionare che ricerchi se i figli della tbx hanno dipendenze
	public function drawConnections($TN){
		$index = $TN->getIndex();
		
		for($i=0;$i<sizeof($TN->connections);$i++){
			$upper =false; $under=false;

			$ID1 = $TN->connections[$i]["FROM"];
			$ID2 = $TN->connections[$i]["TO"];

			$tbx1 = $index[$ID1["riga"]][$ID1["colonna"]];
			$tbx2 = $index[$ID2["riga"]][$ID2["colonna"]];


//FIXME get from DB
			if($tbx1 && rand(0,1)){
				$under = true;
			}
			if($tbx2 && rand(0,1)){
				$upper =true;
			}

			$TN = $TN->connect($this,$ID1,$ID2,false,false,$under,$upper,$i*2);

		}
		return $TN;
	}
	
	public function drawCriticalPath(){//TODO
		
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
			imagestring($im, 5, $px, $py, "2d", $color);
			
			
		}		
	}
	private function connect(TaskNetwork $TaskNetwork, $ID1,$ID2,$criticalPath=false, $dash = false,$under= false,$upper=false, $dist=0,$color="black"){
		$type;$colore;
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
			case "gray":$colore = ImageColorAllocate($img,130,130,130);break;//grigio
			default:$colore = ImageColorAllocate($img,0,0,0);break;//nero
		}



		//ID 1 e 2 sono due array ID["riga"] ID["colonna"]
		$index = $TaskNetwork->index;
		$tbx1 = $index[$ID1["riga"]][$ID1["colonna"]];
		$tbx2 = $index[$ID2["riga"]][$ID2["colonna"]];

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
			$tbx1BlankFirst = $TaskNetwork->mapBlank["inizio"]-$dist;$tbx1BlankLast = $TaskNetwork->mapBlank["fine"]+$dist;
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
			$tbx2BlankFirst = $TaskNetwork->mapBlank["inizio"]-$dist;$tbx2BlankLast = $TaskNetwork->mapBlank["fine"]+$dist;
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
					$points[4]["x"]= $tbx2lx	;$points[4]["y"]=$tbx2ly+($tbx2shift/2); //arrivato
				}else{
					$points[4]["x"]= $tbx1BlankRight					;$points[4]["y"]=$tbx2BlankUp; 
					$points[5]["x"]= $tbx2lx+($tbx2x/2)-($tbx2shift/2)	;$points[5]["y"]=$tbx2BlankUp;
					$points[6]["x"]= $tbx2lx+($tbx2x/2)-($tbx2shift/2)	;$points[6]["y"]=$tbx2ly-($tbx2y/2)+$tbx2shift;//arrivato
				}
				TaskNetwork::patharrow($img,$points,$colore,$type);
					
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
				TaskNetwork::patharrow($img,$points,$colore,$type);
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
				TaskNetwork::patharrow($img,$points,$colore,$type);
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
					
				if($tbx1BlankDown!=$tbx2BlankUp){
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
				TaskNetwork::patharrow($img,$points,$colore,$type);
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
			$b->setLeft($imgTNx+250, ($imgby/2)+25+$centerb);
			$b->setRight( $imgTNx+250+$imgbx, ($imgby/2)+25+$centerb);
			
			if($centerTN!=0){
				for($i=0;$i<$val;$i++){
					$array[$i]->setLeft($array[$i]->getLeftX(),$array[$i]->getLeftY()+$centerTN);
					$array[$i]->setRight($array[$i]->getRightX(),$array[$i]->getRightY()+$centerTN);
				}
			}

			$out = ImageCreate($outx , $outy);
			$bianco = ImageColorAllocate($out,255,255,255);

			//copio la prima immagine nell'output
			imagecopy($out,$imgTN,0,$centerTN,0,0,$imgTNx,$imgTNy);
			//e poi la seconda
			imagecopy($out,$imgb,$outx-($imgbx+50),$centerb,0,0,$imgbx,$imgby);

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

	private function mergeArrayUnder($array){
		$TN = new TaskNetwork();

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
			imagecopy($out,$imgTN,($outx/2)-($imgTNx/2),0,0,0,$imgTNx,$imgTNy);
			//e poi la seconda centrata
			imagecopy($out,$imgTN2,($outx/2)-($imgTN2x/2),$outy-$imgTN2y,0,0,$imgTN2x,$imgTN2y);


			imagedestroy($imgTN);
			imagedestroy($imgTN2);
			//cambio le coordinate della y e della x delle tbx inserite
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
			$map["y"][$j] =  ($index[$j][0]->getLeftY()-($index[$j][0]->GetHeight()/2))-25;
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
	
//------Funzioni di setting------------fine

}

/// creazione TN vuota
	$TN = new TaskNetwork();



$project_id = 5;
$query = "SELECT task_id, task_name, task_parent, task_start_date, task_finish_date FROM tasks t ".
         "WHERE t.task_project = ".$project_id." ORDER BY task_id";

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

for($j=0;$j<sizeof($res);$j++){
	for($k=0;$k<sizeof($res[$j]);$k++){	
		$wbslv = CTask::getWBS($res[$j][$k]['task_id']);
		
		
				$tbx = new TNNode($wbslv);
				$tbx->setFontPath("../../fonts/Droid");
				$tbx->setName($res[$j][$k]['task_name']);
				$tbx->setPlannedData("14 d", "40 ph", "1350 €");
				$tbx->setActualData("4 d", "6 ph", "230 €");
				$tbx->setPlannedTimeframe(substr($res[$j][$k]['task_start_date'], 0, 10), substr($res[$j][$k]['task_finish_date'], 0, 10));
				//$tbx->setActualTimeframe(substr($tstart['task_log_start_date'], 0, 10),"");
				//$tbx->setResources("22 ph, Dilbert, Requirement Engineering\n".
				   //                "14 ph, Wally, Sales Manager\n".
				  //                 "04 ph, The Boss, Manager");
				$tbx->setProgress(CTask::getPr($res[$j][$k]['task_id']));
				$tbx->setAlerts(rand(0,2));
	
				$TN->addTbx($tbx,$j,$k);
				
	}
}
	


$TN = $TN->createTN();

$TN->addDefaultDependancies($TN);
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

	$ID1["riga"] = 2; $ID1["colonna"] = 10;
	$ID2["riga"] = 2; $ID2["colonna"] = 1;
	$TN->addDependence($ID1,$ID2);


$TN = $TN->drawConnections($TN);



$TN->printTN();
?>