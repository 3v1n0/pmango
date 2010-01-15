<?php
$baseDir = "../";
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

$query = "SELECT task_id, task_name, task_parent FROM tasks t ".
         " ORDER BY task_id";

class taskBoxDB {
	private $pWBS_ID;
	private $pCTask;

	public function taskBoxDB($id) {
		$this->pWBS_ID = $id;
		$this->pCTask = new CTask($id);
		if ($this->pCtask->isLeaf())
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

	public function getWBS() {
		return $this->pCTask->getWBS();
	}

//inizio pezzo creato da matteo !!! ATTENZIONE!!!!!//
//da aggiungere i casi dove i vari campi non sono definiti
//caso_prova_1.3.1.1 la funzione ritorna il nome del task.
	public function getTaskName() {
		$sql = "SELECT task_name FROM tasks t where task_id = ".$this->pWBS_ID;
		$query = $this->doQuery($sql); // devi usare $this->Funzione oppure taskBoxDB::Funzione !!!
		return $query[0]['task_name'];
	}
//caso_prova_1.3.1.4 la funzione ritorna i numero dei giorni di lavoro assegnati ad un task, le ore effettive di lavoro, il totale del budget pianificate.
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

//caso_prova_1.3.1.2 la funzione restituisce la data di inizio e fine di un task in un'unica stringa separate da una "|"
	public function getPlannedTimeframe()
	{
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
		return $this->pCTask->getProgress();
	}

	public function getActualData() {

		$x = $this->pCTask;

		$today =$this->doQuery("SELECT task_today FROM tasks t WHERE task_id = ".$this->pWBS_ID);

		$aDay = $this->doQuery("SELECT datediff(".($today>$x->getActualFinishDate(null, null)) ? $x->getActualFinishDate(null, null) : $today." , ".$x->getActualStartDate(null, null).")
				 FROM tasks t
				 WHERE task_id =".$this->pWBS_ID); // stampa il numero dei giorni che corrono tra la data di inizio e quella attuale (task non ancora concluso) o a quella di fine
		$aEffort = $x->getActualEffort(null, null);
		$aBudget = $x->getActualCost(null, null);
		//$d = new Date_Calc();$d->dateDiff();
		//$d->compare($a, $b);

		return $aDay." d / ".$aEffort." ph / ".$aBudget." ".$dPconfig['currency_symbol']; //restituisce una stringa contenente tutte le variabili sopra elencate.
	}
//caso_prova_1.3.1.3 la funzione restituisce la data di inizio e fine attuali di un task in un'unica stringa separate da una "|"
	public function getActualTimeframe(){
		$x = new CTask($this->pWBS_ID);
		$pStart = $x->getActualStartDate(null, null);
		$pFinish = $x->getActualFinishDate(null, null);

	//	$start = substr($pStart[0][0],8 ,2 )."/".substr($pStart[0][0],5 ,2 )."/".substr($pStart[0][0],0 ,4 );
	//	$finish =  substr($pFinish[0][0],8 ,2 )."/".substr($pFinish[0][0],5 ,2 )."/".substr($pFinish[0][0],0 ,4 );
		return  "<br>".$start."    |    ".$finish; //restituisce una stringa contenente tutte le variabili sopra elencate.

	}

	public function getActualResources() {
		$x = new CTask($this->pWBS_ID);



	}

	private function getActualPersonEffort($tid = null, $setTid, $pID) {
		$tid = !empty($tid) ? $tid : $this->task_id;
		if ($tid == 0)
        	return 0;
		$q = new DBQuery;
		$q->addQuery('SUM(tl.task_log_hours)');
		$q->addTable('tasks','t');
		$q->addJoin('task_log','tl','tl.task_log_task = t.task_id');
		if (($setTid == null) || ($setTid == ''))
			$q->addWhere("t.task_id = $tid");
		else
			$q->addWhere("t.task_id IN ($setTid) && (SELECT COUNT(*) FROM tasks AS tt WHERE t.task_id <> tt.task_id && tt.task_parent = t.task_id) < 1");

		$q->addWhere("tl.task_creator = $pID");
		$r = $q->loadResult();
		if ($r < 0 || is_null($r))
			$r = 0;
        return round($r,2);
	}

	public function getPlannedResources() {
		$x = new CTask($this->pWBS_ID);

		// trovo tutti le persone assegnate al task, il loro ruolo e l'effort
			$q->clear();
			$q->addTable('user_tasks','ut');
			$q->addQuery('CONCAT_WS(", ",u.user_last_name,u.user_first_name) as nm, u.user_email as um, pr.proles_name as pn, ut.effort as ue');
			$q->addJoin('users','u','u.user_id=ut.user_id');
			$q->addJoin('project_roles','pr','pr.proles_id = ut.proles_id');
			$q->addWhere('ut.proles_id > 0 && ut.task_id = '.$task_id);
			$ar_ur = $q->loadList();

			if (!is_null($ar_ur)){
				foreach ($ar_ur as $ur) {
					echo "nome: ".$ur['nm']."|";
					echo "ruolo: ".$ur['pn']."|";
					echo "effort: ".$ur['ue']." ph<br>";
				}
			}


	}
	//FINE PEZZO CREATO DA MATTEO!!! DA QUI SI VA TRANQUILLI//
}

//////////////////// Test classe ////////////////////

$tdb = new taskBoxDB(86);
echo $tdb->getTaskName();
echo $tdb->getPlannedData();
echo $tdb->getActualData();
echo $tdb ->getPlannedTimeframe();
echo $tdb ->getActualTimeframe();
echo $tdb ->getProgress();
?>
