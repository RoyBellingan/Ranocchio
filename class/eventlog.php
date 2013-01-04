<?php
/**Implementa il RED SPY in BLUE BASE system
 *
 */
class eventlog {
	var $source;

	var $tag;

	var $level;

	var $param;

	var $msg;

	var $quando;

	var $server_id;

	var $pid;

	var $ts_start;

	var $remote_ip;

	/* Ricicla la connessione al db di sistema...
	 * Unica accortezza è che l'utente "generale" possa scrivere nel log...
	 *
	 * Poi basta che nella query metti insert into "logg"."record" e non hai noie...
	 *
	 *Usando le funkz mysqli non ho problemi...
	 */
	function init_log() {
		//Per ora nulla
		if (isset($this -> init) && $this -> init == true) {
			//Cane già era inizializzato!

		} else {
			$string = "'api','scarichino','serverotto','remote','local','server','memcached','shmop','user','file','unknown','logging'";
			$string = str_replace("'", "", $string);
			$this -> source_set = explode(",", $string);

			$string = "'malicious','ended','download','rate','file','bandwith','speed','exhausted','time','limit','responding','slow','fast','request','unknown','corruption','prematurely','not','exceeded','banned','tried','parameter','check','not','found'";
			$string = str_replace("'", "", $string);
			$this -> tag_set = explode(",", $string);

			$string = "'error','warning','notice','strict','trashable','debug','unknown'";
			$string = str_replace("'", "", $string);
			$this -> level_set = explode(",", $string);

			GLOBAL $config;
			$this -> pid = posix_getpid();
			$this -> ts_start = msec(1);
			$this -> server_id = $config['server'];

			$this -> remote_ip = $_SERVER['REMOTE_ADDR'];

			$this -> chain = false;
			$this -> init = true;
		}
	}

	//Stripp e controlla che siano veri e che non mi sono inventato niente, anche nel caso mi sia inventato loggami perfavore!
	function source($source) {

		if (!isset($source) || $source == "") {
			$this -> source = "logging";
			$this -> tag = "unknown,parameter";
			$this -> level = "error";
			$this -> param = "0,$value";
			$this -> log_type = 0;
			$this -> msg = "Qualcuno NON ha specificato la sorgente degli errori... il messaggio era $this->msg";
			$this -> logg();
		}

		if (strpos($source, ",")) {
			$l1 = explode(",", $source);
		} else {
			$l1[] = $source;
		}

		foreach ($l1 as $key => $value) {
			$t1[$key] = false;
			foreach ($this->source_set as $key2 => $value2) {
				if ($value == $value2) {
					$t1[$key] = true;
					break;
				}
			}
			if ($t1[$key] == false) {
				//Ti sei inventato qualcosa
				$this -> source = "logging";
				$this -> tag = "unknown,parameter";
				$this -> level = "warning";
				$this -> param = "0,$value";
				$this -> log_type = 0;
				$this -> msg = "Qualcuno si è inventato una sorgente di errori... il messaggio era $this->msg";
				$this -> logg();
			}
		}
	}

	//idem sopra
	function tag($tag) {

		if (!isset($tag) || $tag == "") {
			$this -> source = "logging";
			$this -> tag = "unknown,parameter";
			$this -> level = "error";
			$this -> param = "0,$value";
			$this -> log_type = 0;
			$this -> msg = "Qualcuno NON ha specificato la TIPOLOGIA degli errori...il messaggio era $this->msg";
			$this -> logg();
		}

		if (strpos($tag, ",")) {
			$l1 = explode(",", $tag);
		} else {
			$l1[] = $tag;
		}
		foreach ($l1 as $key => $value) {
			$t1[$key] = false;
			foreach ($this->tag_set as $key2 => $value2) {
				if ($value == $value2) {
					$t1[$key] = true;
					break;
				}
			}
			if ($t1[$key] == false) {
				//Ti sei inventato qualcosa
				$this -> source = "logging";
				$this -> tag = "unknown,parameter";
				$this -> level = "warning";
				$this -> param = "0,$value";
				$this -> log_type = 0;
				$this -> msg = "Qualcuno si è inventato una tipologia di errori...il messaggio era $this->msg";
				$this -> logg();
			}
		}
	}

	//idem
	function level($level) {

		if (!isset($level) || $level == "") {
			$this -> source = "logging";
			$this -> tag = "unknown,parameter";
			$this -> level = "error";
			$this -> param = "0,$value";
			$this -> log_type = 0;
			$this -> msg = "Qualcuno NON ha specificato il LIVELLO degli errori...il messaggio era $this->msg";
			$this -> logg();
		}

		if (strpos($level, ",")) {
			$l1 = explode(",", $level);
		} else {
			$l1[] = $level;
		}

		foreach ($l1 as $key => $value) {
			$t1[$key] = false;
			foreach ($this->level_set as $key2 => $value2) {
				if ($value == $value2) {
					$t1[$key] = true;
					break;
				}
			}
			if ($t1[$key] == false) {
				//Ti sei inventato qualcosa
				$this -> source = "logging";
				$this -> tag = "unknown,parameter";
				$this -> level = "warning";
				$this -> param = "0,$value";
				$this -> log_type = 0;
				$this -> msg = "Qualcuno si è inventato un livello di errori...il messaggio era $this->msg";
				$this -> logg();
			}
		}
	}

	function init_chain() {
		$sql = " INSERT INTO `mokba`.`log_chain` (`server_id`,`pid`,`ts_start`)
			VALUES ('$this->server_id', '$this->pid', '$this->ts_start')";
		$this -> chain_id = qi($sql);

		if (!$this -> chain_id) {
			//TODO logga senza passare dal db, il fatto che non riesco a fare un insert!!!
		}
	}

	/**Logga
	 */
	function logg() {
		//var_dump($this);
		if (!$this -> chain_id) {
			$this -> init_chain();
		}

		//controlliamoci pls
		$this -> source($this -> source);
		$this -> tag($this -> tag);
		$this -> level($this -> level);
		$this -> param = mysql_escape_string($this -> param);
		$this -> msg = mysql_escape_string($this -> msg);

		if ($this -> log_type == "" || !isset($this -> log_type)) {
			$this -> log_type = 0;
		}

		$sql = " INSERT INTO `mokba`.`log` (`log_type`,`chain_id`,`source` ,`tag` ,`level` ,`parameter` ,`msg`)
			VALUES ($this->log_type,$this->chain_id,'$this->source', '$this->tag', '$this->level', '$this->param', '$this->msg')";
		$this -> last_insert = qi($sql);
		$this -> clear();
		return $this -> last_insert;

	}

	/**Cancella la roba, per ora solo param, gli altri van bene da essere riciclati...
	 */
	function clear() {
		$this -> param = "";
	}

	/**I parametri sono ok
	 * La funzione trashable per eccellenza e giusto per fare dei test
	 *
	 * //TODO un modo simpatico per reverse mappare questi param ... o documentarli
	 */
	function logg_1($record_id, $user_id) {

		$this -> source = "scarichino";
		$this -> tag = "request";
		$this -> level = "trashable";
		$this -> log_type = 1;
		$param['record_id'] = $record_id;
		$param['user_id'] = $user_id;
		$param['remote_ip'] = $this -> remote_ip;
		$this -> param = serialize($param);

		$this -> msg = "Ricevuta una richiesta di download da $user_id per $record_id, ip remoto $this->remote_ip";
		$this -> logg();

	}

	/** dwl con parametri errati
	 */
	function logg_2() {

		$this -> source = "scarichino";
		$this -> tag = "malicious,request";
		$this -> level = "notice";
		$this -> log_type = 2;

		$param['get'] = $_GET;
		$param['post'] = $_POST;
		$param['ip'] = $this -> remote_ip;
		$this -> param = serialize($param);

		$this -> msg = "$this->remote_ip ha usato dei parametri invalidi";
		$this -> logg();
	}

	/** Dwl con parametri maliziosi
	 */
	function logg_3() {

		$this -> source = "scarichino";
		$this -> tag = "malicious,request";
		$this -> level = "warning";
		$this -> log_type = 3;

		$param['get'] = $_GET;
		$param['post'] = $_POST;
		$this -> param = serialize($param);

		$this -> msg = "$this->remote_ip ha usato dei parametri invalidi e sospetti";
		$this -> logg();

	}

	//Un log di debug
	function dlog($tag, $msg, $ltype = "", $param = "") {
		if (DEBUG == true) {

			$this -> msg = $msg;
			$this -> tag = $tag;
			$this -> ltype = $ltype;
			$this -> param = $param;
			$this -> level = "debug";
			$this -> logg();
		}

	}

	//Un log trashable
	function tlog($tag, $msg, $ltype = "", $param = "") {

		$this -> msg = $msg;
		$this -> $tag = $tag;
		$this -> level = "trashable";
		$this -> ltype = $ltype;
		$this -> param = $param;
		$this -> logg();

	}

	//Un log warning
	function wlog($tag, $msg, $ltype = "", $param = "") {

		$this -> msg = $msg;
		$this -> $tag = $tag;
		$this -> level = "warning";
		$this -> ltype = $ltype;
		$this -> param = $param;
		$this -> logg();

	}

	//Un log error
	function elog($tag, $msg, $ltype = "", $param = "") {

		$this -> msg = $msg;
		$this -> $tag = $tag;
		$this -> level = "error";
		$this -> ltype = $ltype;
		$this -> param = $param;
		$this -> logg();

	}

}

/**Una funzione senza classe stadalone per loggare al volo
 */
function logga($chi, $tag, $livello, $param, $msg) {

}

