<?php

/**Serverotto, da troppo tempo era attesa una cosa del genere..
 *
 */
class serverotto {

	var $file_id;
	//!<	Id del file

	/** Info sul file
	 *
	 * Alias indirizzo remoto
	 * hosting remoto
	 * ecc da definire come cosa...
	 * siccome ci accedo solo una volta è una stdclass per comodità invece che un array...
	 * 
	 * E uso memcached per questa fase senza occupare le shmop
	 */
	var $mem_info;

	/** Info che serverotto mi stà passando
	 *
	 * È un array cosi formato
	 * @param byte 0  -> start = bool
	 * @param byte 1 -> end = bool
	 * e lo usi con un banale stringa[0] e stringa[1], minimo overhead ci vuole...!
	 * l'indirizzo è file_id_status, ed è comune a tutti i processi che attendono serverotto
	 */
	var $mem_status;

	var $mem_pos;
	//!< Byte scaricati (partendo da 1), se vale zero ancora non ho iniziato...

	function serverotto($file_id) {
		$this -> mem_info = $file_id . "_info";
		$this -> mem_status = $file_id . "_status";
		$this -> mem_pos = $file_id . "_pos";
	}

	function memcache_init() {

		$this -> mmc = new memcache();
		$this -> mmc -> connect('localhost', 11211) or die("Could not connect");
		echo "scrivo 10 in $this->mem_status";
		$this -> mmc -> set($this -> mem_status, "00");

	}

}
