<?php
$baseDir = "../";
include "$baseDir/includes/config.php";
include "$baseDir/includes/db_adodb.php";
include "$baseDir/includes/db_connect.php";
include "$baseDir/includes/main_functions.php";
include "$baseDir/modules/tasks/tasks.class.php";

$query = "SELECT task_id, task_name, task_parent FROM tasks t ".
         " ORDER BY task_id";

class taskBoxDB {
	private $pWBS_ID;

	public function taskBoxDB($id) {
		$this->pWBS_ID = $id;
	}

	private function doQuery($query) {
		$result = db_exec($query);
		$error = db_error();

		if ($error)
			return null;

		for ($i = 0; $i < db_num_rows($result); $i++)
			$results[] = db_fetch_assoc($result);

		return $results;
	}

//inizio pezzo creato da matteo !!! ATTENZIONE!!!!!//
//da aggiungere i casi dove i vari campi non sono definiti
	public function getTaskName() {
		$sql = "SELECT task_name FROM tasks t where task_id = ".$this->pWBS_ID;
		$query = $this->doQuery($sql); // devi usare $this->Funzione oppure taskBoxDB::Funzione !!!
		return $query[0]['task_name'];
	}
	public function getPlannedData() {
		$pDay = $this->doQuery("SELECT datediff(task_finish_date , task_start_date)
			 		FROM tasks t 
					WHERE task_id =".$this->pWBS_ID);
		$pEffort = $this->doQuery("SELECT sum(effort) FROM user_tasks u WHERE task_id = ".$this->pWBS_ID);
		$pBudget = $this->doQuery("SELECT SUM(ut.effort * pr.proles_hour_cost) 
				  	   FROM (user_tasks as ut JOIN project_roles as pr) 
	 				   WHERE ut.proles_id = pr.proles_id and task_id = ".$this->pWBS_ID);
	

//TODO connettere i vari pezzi dentro allo stessa riga, da fare prob a una classe di liv superiore

		return  "<br>".$pDay[0][0]." d <br> ".$pEffort[0][0]." ph <br> ".$pBudget[0][0]." ".$dPconfig['currency_symbol']; //restituisce una stringa contenente tutte le variabili sopra elencate.
}

	public function getPlannedTimeframe() {
		
		$pStart = $this->doQuery("SELECT task_start_date
			 		FROM tasks t 
					WHERE task_id =".$this->pWBS_ID);
		
		$pFinish = $this->doQuery("SELECT task_finish_date
			 		FROM tasks t 
					WHERE task_id =".$this->pWBS_ID);
		
		$start = substr($pStart[0][0],8 ,2 )."/".substr($pStart[0][0],5 ,2 )."/".substr($pStart[0][0],0 ,4 );
		$finish =  substr($pFinish[0][0],8 ,2 )."/".substr($pFinish[0][0],5 ,2 )."/".substr($pFinish[0][0],0 ,4 );
		return  "<br>".$start."    |    ".$finish; //restituisce una stringa contenente tutte le variabili sopra elencate.
		
	}
	
	public function getProgress() {
		return CTask::getProgress($this->pWBS_ID);
	}
//Non mi riesce
	public function getActualData() {
$today =$this->doQuery("SELECT task_today FROM tasks t WHERE task_id = ".$this->pWBS_ID);

if($task_id > 0 && count($actual_start_date) > 0 && $actual_start_date['task_log_start_date'] <>"-") { 
	$aDay = $actual_start_date ? $actual_start_date['task_log_start_date']->format( $df1 ) : '-';
} 
else {$aDay = "-";} 
/*
		$aDay = $this->doQuery("SELECT datediff(".($today>$actual_finish_date) ? $actual_finish_date : $today." , ".$actual_start_date.")
				 FROM tasks t
				 WHERE task_id =".$this->pWBS_ID); // stampa il numero dei giorni che corrono tra la data di inizio e quella attuale (task non ancora concluso) o a quella di fine
		$aEffort = $this->getActualEffort($this->task_id,$this->getChild());
		$aBudget = $this->getActualBudget($this->task_id,$this->getChild());

//TODO connettere i vari pezzi dentro allo stessa riga, da fare prob a una classe di liv superiore

	*/	return $aDay." d / ".$aEffort." ph / ".$aBudget." ".$dPconfig['currency_symbol']; //restituisce una stringa contenente tutte le variabili sopra elencate.

	}
	//FINE PEZZO CREATO DA MATTEO!!! DA QUI SI VA TRANQUILLI//
}

//////////////////// Test classe ////////////////////

$tdb = new taskBoxDB(86);
echo $tdb->getTaskName();
echo $tdb->getPlannedData();
//echo $tdb->getActualData();
echo $tdb ->getPlannedTimeframe();
echo $tdb ->getProgress();
?>
