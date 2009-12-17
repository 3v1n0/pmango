<?php
$baseDir = "../";
include "$baseDir/includes/config.php";
include "$baseDir/includes/db_adodb.php";
include "$baseDir/includes/db_connect.php";
include "$baseDir/includes/main_functions.php";

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

	public function getTaskName() {
		$sql = "SELECT task_name FROM tasks t where task_id = ".$this->pWBS_ID;
		$query = $this->doQuery($sql); // devi usare $this->Funzione oppure taskBoxDB::Funzione !!!
		return $query[0]['task_name'];
	}

	public function getPlannedData() {
		$pDay = $this->doQuery("SELECT datediff(".$end_date.",".$start_date.")
			 FROM tasks t
			 WHERE task_id =".$this->pWBS_ID);
		$pEffort = $this->getEffort(/**$this->task_id*/);  // puo essere usato o meno
		$pBudget = $this->getBudget(/**$this->task_id*/);  // idem

//TODO connettere i vari pezzi dentro allo stessa riga, da fare prob a una classe di liv superiore

		return $pDay." d / ".$pEffort." ph / ".$pBudget." ".$dPconfig['currency_symbol']; //restituisce una stringa contenente tutte le variabili sopra elencate.
}
	public function getActualData() {
		$aDay = $this->doQuery("SELECT datediff(".($today>$actual_finish_date) ? $actual_finish_date : $today." , ".$actual_start_date.")
				 FROM tasks t
				 WHERE task_id =".$this->pWBS_ID); // stampa il numero dei giorni che corrono tra la data di inizio e quella attuale (task non ancora concluso) o a quella di fine
		$aEffort = $this->getActualEffort($this->task_id,$this->getChild());
		$aBudget = $this->getActualBudget($this->task_id,$this->getChild());

//TODO connettere i vari pezzi dentro allo stessa riga, da fare prob a una classe di liv superiore

		return $pDay." d / ".$pEffort." ph / ".$pBudget." ".$dPconfig['currency_symbol']; //restituisce una stringa contenente tutte le variabili sopra elencate.

	}
	//FINE PEZZO CREATO DA MATTEO!!! DA QUI SI VA TRANQUILLI//
}

//////////////////// Test classe ////////////////////

$tdb = new taskBoxDB(86);
echo $tdb->getTaskName();

?>