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
	 * ASSOCIATIVOOOO
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
