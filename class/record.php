<?php
/*
 * Gestisce i record, ovvero i file prenotati per il download, ed per semplicità anche i file
 */
class record {

	var $id;
	//!<	L'id del record in questione

	var $file_id;
	//!<	L'id del file fisico a cui punta il record

	var $user_id;
	//!<	L'id dell'utente che ha prenotato il file

	var $ip;
	//!<	L'Ip del computer che si è collegato

	var $light;
	//!<	Simpatico dai

	var $green_light;
	//!<	Tutto ok

	var $red_light;
	//!<	Qualche problema
	
	/** Un array di info sul file
	 * 
	 * file_id, 
	 * complete, 
	 * banned, 
	 * dimension, 
	 * creation, 
	 * dwl_number, 
	 * dwl_last
	 */ 
	var $file_info;

	/**Solito costruttore fake tipico di Roy che non fa niente...
	 */
	function record() {

	}

	/** Cerco un pò di info sul file e sull'utente...
	 *
	 */
	function file_info() {
		$sql="select file_id, complete, banned, dimension, creation, dwl_number, dwl_last from  record, file where record.record_id = $this->id AND record.file_id = file.file_id";
		$this->res=row($sql);
		$this->file_info=$this->res;
		if(isset($this->res[0])){
			//se è tutto ok ... non faccio niente!
			//TODO questa roba dovrebbe essere gestita da remoto... alias per ora scrivo come se non fosse distribuita la cosa...
			//TODO utente esiste / bannato / scaduto ?
			//TODO l'utente ha superato il cap ?
			//TODO registra ip richiesto			
			
			
		}else{
			$this->light(false);
			$this->error("file non trovato");
			return false;
		}

		if($this->res[1]==true){
			$this->complete=true;
		}else{
			$this->complete=false;
		}
		return true;

	}

	/** Setta la luce!
	 * @param bool
	 */
	function set_light($value = true) {
		$this -> light = $value;
		if ($value === true) {
			$this -> green_light = true;
			$this -> red_light = false;
		}else{
			$this -> green_light = false;
			$this -> red_light = true;
		}
	}

}
