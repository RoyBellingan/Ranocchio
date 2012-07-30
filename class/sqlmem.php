<?php

class sqlmem {

	var $location;

	var $size;

	var $val;

	/** L'id per accedervi
	 */
	var $id;

	/**
	 * Se NON devo fare il serialize e unserialize ogni volta...
	 * @var unknown_type
	 */
	var $raw;

	/**
	 * Esigo che sia nuovo di pacca o riciclato ?
	 * @var boolean
	 */
	var $new;

	/**
	 * Non serve cancellare il contenuto, ATTENZIONE non è ATOMICA come cosa, quindi vacci moooooolto piano
	 * @var unknown_type
	 */
	var $no_d;

	/**
	 * Accedi a una mem loc
	 * @var id_locazione
	 * @var dimensione
	 * @var raw
	 * @var new
	 * @TODO cambia D in enum e mettici e 4 valori...
	 */
	function sqlmem($a, $b, $c = false, $d = false) {
		$this -> location = $a;
		$this -> size = $b;
		$this -> raw = $c;
		$this -> no_d = $d;

		if ($d) {
			//se la voglio nuova allora chiediglielo!
			$this -> id = shmop_open($a, "n", 0777, $b);
		} else {
			$this -> id = shmop_open($a, "c", 0777, $b);
		}
		if ($this->id===false){
			die("controlla lo shmop per favore, era $a, $b, $c");
		}
		return $this -> id;

	}

	/**
	 * Come fosse una sql... la legge
	 * la unserializa e la manda, e la stora in $this->val
	 */
	function select() {
		$raw = shmop_read($this -> id, 0, $this -> size);
		if ($this -> raw === false) {
			$this -> val = unserialize($raw);

		} else {
			$this -> val = $raw;
		}

		return $this -> val;

	}

	/**
	 * Idm alla sql, il parametro è opzionale,
	 * se passato verrà usato è diventa il nuovo $this->val
	 * altrimenti viene usato $this->val
	 * Ovvio il contenuto è falciato senza pietà, se ci sono state modifiche fra
	 * il select e l'update... potevi acquisire il lock...
	 *
	 * @param qualunque cosa / opzionale $val
	 * @return Ambigous <unknown, mixed>
	 */
	function update($val = false) {
		//coincide...!
		if (!$val === false) {
			$this -> val = $val;
		}

		//Prima devo cancellare la locazione
		if (!$this -> no_d) {
			$this -> delete();
		}

		if ($this -> raw === false) {
			$raw = serialize($this -> val);
		} else {
			$raw = $this -> val;
		}

		shmop_write($this -> id, $raw, 0);
		//echo "scrivo su $this->id questo -> $raw che era\n";
		//print_r($this->val);
		//print_r($val);

		return $this -> val;

	}

	function delete() {
		//TODO ma non esiste un metodo migliore ???
		$nop = str_repeat(" ", $this -> size);
		//10 caratteri per diecimila
		shmop_write($this -> id, $nop, 0);

	}

	function piu_uno() {

		//TODO DEVO sempre controllare l'attuale valore, non è bello fare un +1 av "string" fosse na cosa lenta...
		$this -> select();

		$this -> val++;

		return $this -> update();
	}

}
