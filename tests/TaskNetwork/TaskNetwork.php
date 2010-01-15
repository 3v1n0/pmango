<?php
include ("../TaskBox.class.php");

Header("Content-Type: image/jpeg");

class TBXNode {

	var $tbx,$leftx, $lefty, $rightx, $righty;

	//leftx = ascissa punto mediano sx del tb (dove arrivano le frecce)
	//lefty = ordinata punto mediano sx del tb (dove arrivano le frecce)
	//rightx = ascissa punto mediano dx del tb (dove partono le frecce)
	//righty = ordinata punto mediano dx del tb (dove partono le frecce)

	function TBXNode(TaskBox $tbx){
		 $this->tbx = $tbx;
	}

	function setLeft($x,$y){
		$this->leftx = $x;
		$this->lefty = $y;
	}

	function setRight($x,$y){
		$this->rightx = $x;
		$this->righty = $y;
	}

	function getImage(){
		return $this->tbx->getImage();
	}

	function getX(){
		return $this->tbx->getWidth();
	}

	function getY(){
		return $this->tbx->getHeight();
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

	function getAlertSize(){
		return $this->tbx->getAlertSize();
	}

	function isLeaf(){
		return $this->tbx->isLeaf();
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
			$space=10;
			for($a=0;$a<sizeof($index);$a++){
					for($b=0;$b<sizeof($index[$a]);$b++){
						$ID2["riga"] = $a; $ID2["colonna"] = $b;
										 //TN  			 cr.path dash  under upper dist color
						$TN = $TN->connect($TN,null,$ID2, false, true,false,false,0,"gray");
					}
			$space--;
			}

			$space=10;
			for($a=0;$a<sizeof($index);$a++){
					for($b=0;$b<sizeof($index[$a]);$b++){
						$ID1["riga"] = $a; $ID1["colonna"] = $b;
										 //TN  			 cr.path dash  under upper dist
						$TN = $TN->connect($TN,$ID1,null, false, false,false,false,$space,"gray");
					}

			}
			return $TN;
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



			if($tbx1 && !$tbx1->isLeaf()){
				$under = true;
			}
			if($tbx2 && !$tbx2->isLeaf()){
				$upper =true;
			}

			$TN = $TN->connect($this,$ID1,$ID2,false,false,$under,$upper,2*$i);

		}
		return $TN;
	}
//------Funzioni di classe------------fine

//------Funzioni di disegno------------
	private function drawSpM(){
		$img = ImageCreate(50,50);
		$bianco = ImageColorAllocate($img,255,255,255);
		$nero = ImageColorAllocate($img,0,0,0);

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

		$epm["x"] =  50;
		$epm["y"] =  50;
		imageFilledEllipse($img,25,25,40,40,$nero);
		imageEllipse($img,25,25,48,48,$nero);
		imageEllipse($img,25,25,49,49,$nero);
		$epm["img"] = $img;
		return $epm;
	}

	private function imageboldline($image, $x1, $y1, $x2, $y2, $color, $thick = 3){
	   /* this way it works well only for orthogonal lines
	   imagesetthickness($image, $thick);
	   return imageline($image, $x1, $y1, $x2, $y2, $color);
	   */
	   if ($thick == 1) {
	       return imageline($image, $x1, $y1, $x2, $y2, $color);
	   }
	   $t = $thick / 2 - 0.5;
	   if ($x1 == $x2 || $y1 == $y2) {
	       return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t), round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
	   }
	   $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
	   $a = $t / sqrt(1 + pow($k, 2));
	   $points = array(
	       round($x1 - (1+$k)*$a), round($y1 + (1-$k)*$a),
	       round($x1 - (1-$k)*$a), round($y1 - (1+$k)*$a),
	       round($x2 + (1+$k)*$a), round($y2 - (1-$k)*$a),
	       round($x2 + (1-$k)*$a), round($y2 + (1+$k)*$a),
	   );
	   imagefilledpolygon($image, $points, 4, $color);
	   return imagepolygon($image, $points, 4, $color);
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


	function connect(TaskNetwork $TaskNetwork, $ID1,$ID2,$criticalPath=false, $dash = false,$under= false,$upper=false, $dist=0,$color="black"){
		$line;$arrow;$colore;
		if(!$criticalPath){
			if($dash){
				$line="imagedashedline";
				$arrow="dashedarrow";
			}
			else{
				$line="imageline";
				$arrow="arrow";
			}
		}else{
			$line="imageboldline";
			$arrow="boldarrow";
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
			$tbx1x = $tbx1->getX(); $tbx1y = $tbx1->getY();

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
			$tbx2x = $tbx2->getX(); $tbx2y = $tbx2->getY();

			$tbx2shift = $tbx2->getAlertSize()/2;
		}

		/////////////////////////////////
		if($tbx1ry<$tbx2ly){//se tbx1 è piu in alto di tbx2
				if($tbx1rx>($TaskNetwork->x/2) or $tbx2lx>($TaskNetwork->x/2)){//se tbx1 o tbx2 è nella metà di destra della TN
					if(!$under){
						TaskNetwork::$line($img, $tbx1rx-$tbx1shift,$tbx1ry+($tbx1shift/2), $tbx1BlankRight,$tbx1ry+($tbx1shift/2), $colore);
						TaskNetwork::$line($img, $tbx1BlankRight,$tbx1ry+($tbx1shift/2), $tbx1BlankRight,$tbx1BlankDown, $colore);
					}else{
						TaskNetwork::$line($img, $tbx1rx-($tbx1x/2)-($tbx1shift/2),$tbx1ry+($tbx1y/2), $tbx1rx-($tbx1x/2)-($tbx1shift/2),$tbx1BlankDown, $colore);
						TaskNetwork::$line($img, $tbx1rx-($tbx1x/2)-($tbx1shift/2),$tbx1BlankDown,$tbx1BlankRight,$tbx1BlankDown, $colore);
					}
					if($tbx1BlankDown!=$tbx2BlankUp){
						if(!$under){
							TaskNetwork::$line($img,$tbx1BlankRight,$tbx1BlankDown, $tbx1BlankLast,$tbx1BlankDown, $colore);
						}else{
							TaskNetwork::$line($img,$tbx1rx-($tbx1x/2)+$tbx1shift,$tbx1BlankDown, $tbx1BlankLast,$tbx1BlankDown, $colore);
						}
						TaskNetwork::$line($img,$tbx1BlankLast,$tbx1BlankDown,$tbx1BlankLast,$tbx2BlankUp, $colore);
						if(!$upper){
							TaskNetwork::$line($img,$tbx1BlankLast,$tbx2BlankUp,$tbx2BlankLeft,$tbx2BlankUp, $colore);
						}else{
							TaskNetwork::$line($img,$tbx1BlankLast,$tbx2BlankUp,$tbx2lx+($tbx2x/2)-($tbx2shift/2),$tbx2BlankUp, $colore);
						}
					}else{
						TaskNetwork::$line($img,$tbx1BlankRight,$tbx1BlankDown,$tbx2BlankLeft,$tbx1BlankDown, $colore);
					}
					if(!$upper){
						TaskNetwork::$line($img, $tbx2BlankLeft,$tbx2BlankUp, $tbx2BlankLeft ,$tbx2ly+($tbx2shift/2), $colore);
						TaskNetwork::$arrow($img,  $tbx2BlankLeft ,$tbx2ly+($tbx2shift/2), $tbx2lx ,$tbx2ly+($tbx2shift/2),5,5, $colore);
					}else{
						TaskNetwork::$arrow($img, $tbx2lx+($tbx2x/2)-($tbx2shift/2),$tbx2BlankUp, $tbx2lx+($tbx2x/2)-($tbx2shift/2),$tbx2ly-($tbx2y/2)+$tbx2shift,5,5, $colore);
					}
				}
				else{//tbx1 e tbx2 sono a sinistra
					if(!$under){
						TaskNetwork::$line($img, $tbx1rx-$tbx1shift,$tbx1ry+($tbx1shift/2), $tbx1BlankRight,$tbx1ry+($tbx1shift/2), $colore);
						TaskNetwork::$line($img, $tbx1BlankRight,$tbx1ry+($tbx1shift/2), $tbx1BlankRight,$tbx1BlankDown, $colore);
					}else{
						TaskNetwork::$line($img, $tbx1rx-($tbx1x/2)-($tbx1shift/2),$tbx1ry+($tbx1y/2), $tbx1rx-($tbx1x/2)-($tbx1shift/2),$tbx1BlankDown, $colore);
					}
					if($tbx1BlankDown!=$tbx2BlankUp){
						if(!$under){
							TaskNetwork::$line($img,$tbx1BlankRight,$tbx1BlankDown, $tbx1BlankFirst,$tbx1BlankDown, $colore);
						}else{
							TaskNetwork::$line($img,$tbx1rx-($tbx1x/2)-($tbx1shift/2),$tbx1BlankDown, $tbx1BlankFirst,$tbx1BlankDown, $colore);
						}
						TaskNetwork::$line($img,$tbx1BlankFirst,$tbx1BlankDown,$tbx1BlankFirst,$tbx2BlankUp, $colore);
						if(!$upper){
							TaskNetwork::$line($img,$tbx1BlankFirst,$tbx2BlankUp,$tbx2BlankLeft,$tbx2BlankUp, $colore);
						}else{
							TaskNetwork::$line($img,$tbx1BlankFirst,$tbx2BlankUp,$tbx2lx+($tbx2x/2)-($tbx2shift/2),$tbx2BlankUp, $colore);
						}
					}else{
						TaskNetwork::$line($img,$tbx1BlankRight,$tbx1BlankDown,$tbx2BlankLeft,$tbx2BlankUp, $colore);
					}
					if(!$upper){
						TaskNetwork::$line($img, $tbx2BlankLeft,$tbx2BlankUp, $tbx2BlankLeft ,$tbx2ly+($tbx2shift/2), $colore);
						TaskNetwork::$arrow($img,  $tbx2BlankLeft ,$tbx2ly+($tbx2shift/2), $tbx2lx ,$tbx2ly+($tbx2shift/2),5,5, $colore);
					}
					else{
						TaskNetwork::$arrow($img,  $tbx2lx+($tbx2x/2)-($tbx2shift/2) ,$tbx2BlankUp, $tbx2lx+($tbx2x/2)-($tbx2shift/2),$tbx2ly-($tbx2y/2)+$tbx2shift,5,5, $colore);
					}
				}
		}
/**/	else{//tbx2 è piu in alto o uguale a tbx1
			if($tbx1ry==$tbx2ly){//le tbx sono sulla stessa riga
				if(abs($tbx2lx-$tbx1rx)<300){ //adiacenti
					TaskNetwork::$arrow($img,  $tbx1rx-$tbx1shift ,$tbx1ry+($tbx1shift/2), $tbx2lx ,$tbx2ly+($tbx1shift/2),5,5, $colore);$TaskNetwork->img = $img;return $TaskNetwork;
				}
				else{//non adiacenti
					if($tbx2lx>$tbx1rx){//tbx1 viene prima di tbx2
						TaskNetwork::$line($img,$tbx1rx-$tbx1shift ,$tbx1ry+($tbx1shift/2),$tbx1BlankRight, $tbx1ry+($tbx1shift/2), $colore);
						TaskNetwork::$line($img,$tbx1BlankRight,$tbx1ry+($tbx1shift/2),$tbx1BlankRight,$tbx1BlankUp, $colore);
						TaskNetwork::$line($img,$tbx1BlankRight,$tbx1BlankUp,$tbx2BlankLeft,$tbx2BlankUp, $colore);
						TaskNetwork::$line($img,$tbx2BlankLeft,$tbx2BlankUp,$tbx2BlankLeft,$tbx2ly+($tbx2shift/2),$colore);
						TaskNetwork::$arrow($img,$tbx2BlankLeft,$tbx2ly+($tbx2shift/2), $tbx2lx,$tbx2ly+($tbx2shift/2),5,5, $colore);
						$TaskNetwork->img = $img;return $TaskNetwork;
					}
					//TODO fare i casi under e upper

				}
			}

			if($tbx1rx>($TaskNetwork->x/2) or $tbx2lx>($TaskNetwork->x/2)){//se tbx1 o tbx2 è nella metà di destra della TN
					if(!$under){
						TaskNetwork::$line($img, $tbx1rx-$tbx1shift,$tbx1ry+($tbx1shift/2), $tbx1BlankRight,$tbx1ry+($tbx1shift/2), $colore);
						TaskNetwork::$line($img, $tbx1BlankRight,$tbx1ry+($tbx1shift/2), $tbx1BlankRight,$tbx1BlankUp, $colore);
					}else{
						TaskNetwork::$line($img, $tbx1rx-($tbx1x/2)-($tbx1shift/2),$tbx1ry+($tbx1y/2), $tbx1rx-($tbx1x/2)-($tbx1shift/2),$tbx1BlankDown, $colore);
						TaskNetwork::$line($img, $tbx1rx-($tbx1x/2)-($tbx1shift/2),$tbx1BlankDown, $tbx1BlankRight,$tbx1BlankDown, $colore);
						TaskNetwork::$line($img,$tbx1BlankRight,$tbx1BlankDown,$tbx1BlankRight,$tbx1BlankUp,$colore);
					}
					if($tbx1BlankUp!=$tbx2BlankDown){
						TaskNetwork::$line($img,$tbx1BlankRight,$tbx1BlankUp, $tbx1BlankLast,$tbx1BlankUp, $colore);
						TaskNetwork::$line($img,$tbx1BlankLast,$tbx1BlankUp,$tbx1BlankLast,$tbx2BlankDown, $colore);
						TaskNetwork::$line($img,$tbx1BlankLast,$tbx2BlankDown,$tbx2BlankLeft,$tbx2BlankDown, $colore);
					}
					else{
						TaskNetwork::$line($img,$tbx1BlankRight,$tbx2BlankDown,$tbx2BlankLeft,$tbx2BlankDown, $colore);
					}
					if(!$upper){
						TaskNetwork::$line($img, $tbx2BlankLeft,$tbx2BlankDown, $tbx2BlankLeft ,$tbx2ly+($tbx2shift/2), $colore);
						TaskNetwork::$arrow($img,  $tbx2BlankLeft ,$tbx2ly+($tbx2shift/2), $tbx2lx ,$tbx2ly+($tbx2shift/2),5,5, $colore);

					}else{
						TaskNetwork::$line($img, $tbx2BlankLeft,$tbx2BlankDown, $tbx2BlankLeft ,$tbx2BlankUp, $colore);
						TaskNetwork::$line($img,$tbx2BlankLeft ,$tbx2BlankUp, $tbx2lx+($tbx2x/2)-($tbx2shift/2),$tbx2BlankUp, $colore);
						TaskNetwork::$arrow($img, $tbx2lx+($tbx2x/2)-($tbx2shift/2) ,$tbx2BlankUp, $tbx2lx+($tbx2x/2)-($tbx2shift/2) ,$tbx2ly-($tbx2y/2)+$tbx2shift,5,5, $colore);
					}
			}
			else{//tbx1 e tbx2 sono a sinistra
					if(!$under){
						TaskNetwork::$line($img, $tbx1rx-$tbx1shift,$tbx1ry+($tbx1shift/2), $tbx1BlankRight,$tbx1ry+($tbx1shift/2), $colore);
						TaskNetwork::$line($img, $tbx1BlankRight,$tbx1ry+($tbx1shift/2), $tbx1BlankRight,$tbx1BlankUp, $colore);
					}else{
						TaskNetwork::$line($img, $tbx1rx-($tbx1x/2),$tbx1ry+($tbx1y/2), $tbx1rx-($tbx1x/2),$tbx1BlankDown, $colore);
						TaskNetwork::$line($img,$tbx1rx-($tbx1x/2),$tbx1BlankDown,$tbx1BlankRight,$tbx1BlankDown,$colore);
						TaskNetwork::$line($img,$tbx1BlankRight,$tbx1BlankDown,$tbx1BlankRight,$tbx1BlankUp,$colore);
					}
					if($tbx1BlankUp!=$tbx2BlankDown){
						TaskNetwork::$line($img,$tbx1BlankRight,$tbx1BlankUp, $tbx1BlankFirst,$tbx1BlankUp, $colore);
						TaskNetwork::$line($img,$tbx1BlankFirst,$tbx1BlankUp,$tbx1BlankFirst,$tbx2BlankDown, $colore);
						TaskNetwork::$line($img,$tbx1BlankFirst,$tbx2BlankDown,$tbx2BlankLeft,$tbx2BlankDown, $colore);
					}else{
						TaskNetwork::$line($img,$tbx1BlankRight,$tbx2BlankDown,$tbx2BlankLeft,$tbx2BlankDown, $colore);
					}
					if(!$upper){
						TaskNetwork::$line($img, $tbx2BlankLeft,$tbx2BlankDown, $tbx2BlankLeft ,$tbx2ly+($tbx2shift/2), $colore);
						TaskNetwork::$arrow($img,  $tbx2BlankLeft ,$tbx2ly+($tbx2shift/2), $tbx2lx ,$tbx2ly+($tbx2shift/2),5,5, $colore);

					}else{
						TaskNetwork::$line($img, $tbx2BlankLeft,$tbx2BlankDown, $tbx2BlankLeft ,$tbx2BlankUp, $colore);
						TaskNetwork::$line($img,$tbx2BlankLeft ,$tbx2BlankUp, $tbx2lx+($tbx2x/2),$tbx2BlankUp, $colore);
						TaskNetwork::$arrow($img, $tbx2lx+($tbx2x/2) ,$tbx2BlankUp, $tbx2lx+($tbx2x/2) ,$tbx2ly-($tbx2y/2),5,5, $colore);
					}
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
			$imgbx = $b->getX();
			$imgby = $b->getY();


			//alloco i punti mediani dei lati della tbx dentro b
			$b->setLeft($imgTNx+250, ($imgby/2)+25);
			$b->setRight( $imgTNx+250+$imgbx, $imgby/2+25);

			$outx = ($imgTNx+$imgbx+100);
			$outy = max($imgTNy, $imgby);



			$out = ImageCreate($outx , $outy);
			$bianco = ImageColorAllocate($out,255,255,255);

			//copio la prima immagine nell'output
			imagecopy($out,$imgTN,0,0,0,0,$imgTNx,$imgTNy);
			//e poi la seconda
			imagecopy($out,$imgb,$outx-($imgbx+50),0,0,0,$imgbx,$imgby);

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
			$map["y"][$j] =  ($index[$j][0]->getLeftY()-($index[$j][0]->getY()/2))-25;
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

//------Funzioni di setting------------fine

}

/// creazione TN vuota
	$TN = new TaskNetwork();



//ogni immagine Image è dentro un array im dove im["img"] è l'immagine, im["x"] e im["y"] la x e la y

for($r=0;$r<4;$r++){

	$l = rand ( 1 , 5 );


		for ($j=0;$j<$l;$j++){

			$tbx = new TaskBox(($r+1).".$j.");
			$tbx->setFontPath("../../fonts/Droid");
			//$tbx->setName("TaskBox test row $r, col $j");
			$tbx->setPlannedData("14 d", "40 ph", "1350 €");
			$tbx->setActualData("4 d", "6 ph", "230 €");
			//$tbx->setPlannedTimeframe("2009.10.15", "2009.10.29");
			//$tbx->setActualTimeframe("2009.10.16", "NA");
			//$tbx->setResources("22 ph, Dilbert, Requirement Engineering\n".
			   //                "14 ph, Wally, Sales Manager\n".
			  //                 "04 ph, The Boss, Manager");
			$tbx->setProgress(rand(0,100));
			$tbx->setAlerts(TaskBox::ALERT_ERROR);

			$TN->addTbx(new TBXNode($tbx),$r,$j);
		}
}

$TN = $TN->createTN();
/*
$TN = $TN->addDefaultDependancies($TN);
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

	$ID1["riga"] = 0; $ID1["colonna"] = 2;
	$ID2["riga"] = 0; $ID2["colonna"] = 1;
	$TN->addDependence($ID1,$ID2);


$TN = $TN->drawConnections($TN);



$TN->printTN();
?>