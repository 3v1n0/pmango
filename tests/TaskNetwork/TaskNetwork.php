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
}


class TaskNetwork {

	//matrice di Taskboxnodes, le righe sono i lv della wbs, le colonne i tbx di quel livello
	private $index;
	private $img, $x, $y;		//immagine e dimensioni della stessa

	private $SpM,$EpM; //gli array delle start e end project milestone

	function TaskNetwork(){
		$this->index = array();
		$this->img = ImageCreate(1,1); //immagine vuota iniziale della TN
		$this->x = 1; 					//larghezza della TN
		$this->y = 1;					//altezza della TN
	}

	//il gioco sta nel creare tante righe di tbx quanti sono i lv della wbs. Inizialmente le righe saranno
	//separate tra di loro e successivamente verraranno unite per creare la TN finale

	function addTbx($tbx,$row,$col){
		$this->index[$row][$col] = $tbx;
	}

	function printTN(){
		Imagepng($this->img);
		ImageDestroy($this->img);
	}

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


	//------Funzioni di merging------------

//esegue il merging di due immagini separete da 50 px di spazio

	public function createTN(){
		$rows=array();
		for($i=0;$i<sizeof($this->index);$i++){
			$rows[$i] = $this->mergeArrayRight($this->index[$i]);
		}

		$final = $this->mergeArrayUnder($rows);
		$finalSpm = $this->mergeSpM($final);//aggiunta Spm
		$finalpm = $this->mergeEpM($finalSpm, $epm);// aggiunta Epm

		return $finalpm;
	}



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

			$TN->img = $out; $TN->y =+ 50;


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
		$this->SpM = $this->drawSpM();

		$imgTN = $TN->img;
		$imgTNx = $TN->x;
		$imgTNy = $TN->y;

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

		$TN->img=$out; $TN->x = $outx; $TN->y = $outy;
		return $TN;

	}

	private function mergeEpM($TN){

		$this->EpM = $this->drawEpM();

		$imgTN = $TN->img;
		$imgTNx = $TN->x;
		$imgTNy = $TN->y;

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
	public function mapBlank(TaskNetwork $TN){
		$index = $TN->index;
		$map; //mappa di output, matrice

		//alloco il primo spazio in cima alla TN
		$map["y"][0] = 1;

		//codifico nella prima riga della mappa tutte gli indici degli spazi tra righe della TN
		for($j=1;$j<sizeof($index);$j++){
			$map["y"][$j] =  ($index[$j][0]->getLeftY()-($index[$j][0]->getY()/2))-25;
		}
		//alloco l'ultimo spazio in fondo alla TN
		$map["y"][(sizeof($index))] =  $TN->y - 1;

		//codifico nelle restanti righe della matrice gli spazi tra tbx della stessa TN
		for($h=0;$h<sizeof($index);$h++){
			$map[$h]["inizio"] = 200;
			$map[$h]["fine"] = $TN["x"] - 200;

			for($i=0;$i<sizeof($index[$h]);$i++){
				$map[$h][$i] = $index[$h][$i]->getLeftX - 50;
			}
		}
		return $map;
	}

}

/// creazione TN vuota
	$TN = new TaskNetwork();



//ogni immagine Image è dentro un array im dove im["img"] è l'immagine, im["x"] e im["y"] la x e la y

putenv('GDFONTPATH=' . dirname($_SERVER['SCRIPT_FILENAME']).'/../../fonts/Droid');

for($r=0;$r<4;$r++){

	$l = rand ( 1 , 5 );


		for ($j=0;$j<$l;$j++){

			$tbx = new TaskBox(($r+1).".$j.");
			$tbx->setName("TaskBox test row $r, col $j");
			$tbx->setPlannedData("14 d", "40 ph", "1350 €");
			$tbx->setActualData("4 d", "6 ph", "230 €");
			$tbx->setPlannedTimeframe("2009.10.15", "2009.10.29");
			$tbx->setActualTimeframe("2009.10.16", "NA");
			$tbx->setResources("22 ph, Dilbert, Requirement Engineering\n".
			                   "14 ph, Wally, Sales Manager\n".
			                   "04 ph, The Boss, Manager");
			$tbx->setProgress(rand(0,100));


			$TN->addTbx(new TBXNode($tbx),$r,$j);
		}
}

$TN = $TN->createTN();


///


//mappo tutti i punti vuoti della TN
//$mapB = $TN->mapBlank($finalpm);
/*
//$ID1["riga"] = $finalpm["h"]; $ID1["colonna"] = 0; //Spm
$ID2["riga"] = $finalpm["h"]; $ID2["colonna"] = 1; //Epm
$space=10;
for($a=0;$a<$finalpm["h"];$a++){
		for($b=0;$b<sizeof($finalpm["index"][$a]);$b++){
			$ID1["riga"] = $a; $ID1["colonna"] = $b;
								//img	 map			cr.path dash  under upper dist
			$finalpm = connect($finalpm,$mapB,$ID1,$ID2, false, true,true,false, $space);
		}
$space--;
}

$ID1["riga"] = $finalpm["h"]; $ID1["colonna"] = 0; //Spm
$ID2["riga"] = 2; $ID2["colonna"] = 0; //Epm
//$finalpm = connect($finalpm,$mapB,$ID1,$ID2, false,false,false, true,0);


*/
$TN->printTN();
?>