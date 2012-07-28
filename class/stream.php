<?php
/*TODO
 * 
 *-------- Togli OGNI out a schermo e mandali su un file di testo / db
 * Rendi operativo il limitare di velocità anche per i download lato utente
 * Fai il download con mmc_cap e testalo usando down them all e amici vari... 
 * 
 * Inizia ad usare i test, in teoria puoi benissimo forkare un processo che fa la prima richiesta, e forkare anche gli altri...
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 */

 
 
 
 
  
/** La classe che si occupa di gestire
 * l'invio dei dati
 * &
 * la lettura da remoto dei dati
 * &
 * il salvataggio su disco dei dati
 * ALIAS
 * È la classe che si occupa materialmente della gestione dei dati
 *
 @author Roy Bellingan <admin@seisho.us>
 based on the work of Nguyen Quoc Bao <quocbao.coder@gmail.com>
 @version 1.0
 TODO
 @desc A simple object for processing download operation , support section downloading
 Please send me an email if you find some bug or it doesn't work with download manager.
 I've tested it with
 - Reget
 - FDM
 - FlashGet
 - GetRight
 - DAP

 @copyright It's free as long as you keep this header .
 @example
 */
class stream {

	var $record_id;
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

	var $type;
	//!<	La sorgente è il disco o la rete ?

	var $complete;
	//!<	Se il file è completo, se è da disco e questo è falso in auto cerca il memcached con il valore da non superare, disattiva le ranged request ecc

	var $throttle;
	//!<	Se la velocità deve essere limitata, <b>Attenzione!!!</b>, se il file è letto dalla rete e non è pronto, il sistema comunque limita la velocità...

	var $throttle_speed;
	//!<	Velocità massima di invio.

	/** Un array di info sul file che viene dalla query a mysql...
	 *ASSOCIATIVOOOOOOO
	 * file_id,
	 * complete,
	 * dimension,
	 * creation,
	 * dwl_number,
	 * dwl_last
	 file.file_id, complete, dimension, creation, dwl_number, dwl_last, user_id, hosting_id, path, banned, record_name, mime
	 */
	var $file_info;

	var $file_path;
	//!<	Dove stà il file ?

	var $file_dimension;
	//!<	Dimensione del file

	var $cache_pat;
	//!<	Dove stanno i file ?

	/** Info che serverotto mi stà passando
	 *
	 * È un array cosi formato
	 * @param byte 0 -> serverotto avviato e cosciente della richiesta
	 * @param byte 1 -> start = bool
	 * @param byte 2 -> end = bool
	 * e lo usi con un banale stringa[0] e stringa[1], minimo overhead ci vuole...!
	 * l'indirizzo è file_id_status, ed è comune a tutti i processi che attendono serverotto
	 */
	var $mem_status;

	/** Info su dove si trova l'head del file reportato da serverotto,
	 *
	 * È aggiornato ogni 5 megabyte che LUI scarica
	 * L'indirizzo è file_id_pos, ed è comune a tutti i processi che attendono serverotto
	 *
	 */
	var $mem_pos;

	/** Info sul file
	 *
	 * Alias indirizzo remoto
	 * hosting remoto
	 * ecc da definire come cosa...
	 * siccome ci accedo solo una volta è una stdclass per comodità invece che un array...
	 */
	var $mem_info;

	/**Il pid che si occupa di scaricare questo film
	 */
	var $mem_pid;

	/**Spazio libero MINIMO da lasciare sul disco
	 */
	var $space_free_min;

	/** Spazio libero rimasto
	 */
	var $space_free;

	function stream() {
		//Mettiamo qualche valore di default suvvia!

		$this -> complete = true;
		$this -> cache_path = "/wd/5/cache/";
	}

	/** Cerco un pò di info sul file e sull'utente...
	 *
	 */
	function file_info() {

		$sql = "select file.file_id, complete, dimension, creation, dwl_number, dwl_last, user_id, hosting_id, path, banned, remote_name, mime from  record, file,remote where record.record_id = $this->record_id AND record.file_id = file.file_id AND file.file_id = remote.file_id";
		exo($sql);
		$this -> res = qrow($sql);
		printa($this -> res);
		$this -> file_info = $this -> res;
		$this -> file_id = $this -> res['file_id'];
		$this -> file_path = $this -> cache_path . $this -> file_id;
		$this -> mime = $this -> res['mime'];
		$this->file_name=$this->res['remote_name'];
		

		// Per ora salvo tutti e amen...
		//TODO controlla se hai spazio e cosine varie, se un file è candidabile per davvero bla bla bla bla
		//fai una apposita funzione, RICORDA serverotto poi cancella se finice lo spazio, se a lui dici salva salva e basta...
		$this -> candidate = true;
		$this->file_name = $this -> res['remote_name'];

		if (isset($this -> res['file_id'])) {
			//se è tutto ok ... non faccio niente!
			//TODO questa roba dovrebbe essere gestita da remoto... alias per ora scrivo come se non fosse distribuita la cosa...
			//TODO utente esiste / bannato / scaduto ?
			//TODO l'utente ha superato il cap ?
			//TODO registra ip richiesto
			exo("trovato qualcosa, controllo utente");
			if ($this -> res['user_id'] == $this -> user_id) {
				exo("utente ok, via libera alla fase 1");
			} else {

				exo("utente errato nel db -> {$this->res['user_id']} nella richiesta $this->user_id");
				$this -> reason = "utente errato nel db -> {$this->res['user_id']} nella richiesta $this->user_id";
				$this -> set_light(false);
				return false;
			}

		} else {
			$this -> set_light(false);
			$this -> error = "file non trovato";
			return false;
		}

		if ($this -> res['complete'] == "true") {
			exo("file completo");

			if (file_exists($this -> file_path)) {
				exo("il file $this->file_path effettivamente esiste nella cache");

				$this -> file_dimension = filesize($this -> file_path);
				if ($this -> file_dimension == $this -> file_info['dimension']) {
					exo("il file effettivamente ha le stesse dimensioni");
					$this -> complete = true;
					$this -> set_light(true);
				} else {
					//Pure questo non va bene che un file scaricato sia di dimensione diversa da quello che risulta salvato...
					//avvia serverotto e mettici una pezzah

					$this -> reason = "dimensione del file trovato nella cache diversa doveva essere {$this->file_info['dimension']} invece è $this->file_dimension";
					exo($this -> reason);
					$this -> set_light(false);
					return false;
				}
			} else {
				//Questo è un errore grave, non dovrebbe MAI accadere una cosa del genere
				//avvia serverotto e procedi come fosse un file da scaricare...
				$this -> reason = "iol file $this->file_path non trovato nella cache";
				exo($this -> reason);
				$this -> set_light(false);
				return false;
			}

		} else {
			exo("file incompleto");
			$this -> complete = false;
		}

		return true;

	}

	/*
	 * Setta la luce!
	 * @param bool
	 */
	function set_light($value = true) {
		$this -> light = $value;
		if ($value === true) {
			$this -> green_light = true;
			$this -> red_light = false;
		} else {
			$this -> green_light = false;
			$this -> red_light = true;
		}
	}

	/** Controllo se è tutto ok per iniziare lo stream dal disco
	 */
	function memcache_init() {

		//I 3 puntatori del memcache che uso...

		$this -> mem_info = $this -> file_id . "_info";
		$this -> mem_status = $this -> file_id . "_status";
		$this -> mem_pos = $this -> file_id . "_pos";
		$this -> mem_pid = $this -> file_id . "_pid";
		exo("Mem usa $this->mem_info + $this->mem_status $this->mem_pos");
		$this -> mmc = new memcache();
		$this -> mmc -> connect('localhost', 11211) or die("Could not connect");
		//echo "scrivo 10 in $this->mem_status";
		//$this -> mmc -> set($this -> mem_status, "00");

	}

	/**Legge dal memcached info sul file
	 */
	function mmc_get_file_info() {
		$this -> file_info = $this -> mmc -> get($this -> mem_info);
	}

	/**Setta nel mmc le info sul file
	 *
	 */
	function mmc_set_file_info() {
		$this -> mmc -> set($this -> mem_info, $this -> file_info);
	}

	/**Legge dal memcached info sul file
	 */
	function mmc_get_file_status() {
		$this -> file_status = $this -> mmc -> get($this -> mem_status);
		if ($this -> file_status === false) {
			$this -> file_status = "000";
		}
	}

	/**Setta nel mmc le info sul file
	 *
	 */
	function mmc_set_file_status() {
		$this -> mmc -> set($this -> mem_status, $this -> file_status);
	}

	function mmc_get_file_pos() {
		$this -> file_pos = $this -> mmc -> get($this -> mem_pos);
		if ($this -> file_pos === false) {
			$this -> file_pos = "0";
		}
		return $this -> file_pos;
	}

	/**Setta nel mmcil pid che si occupa del file
	 *
	 */
	function mmc_set_pid() {
		$this -> mmc -> set($this -> mem_pid, $this -> posixProcessID);
	}

	function mmc_get_pid() {

		$this -> file_pid = $this -> mmc -> get($this -> mem_pid);

		return $this -> file_pid;
	}

	/******************************************/
	/************S*E*R*V*E*R*O*T*T*O*!*********/
	/******************************************/

	function new_serverotto() {

		$this -> space_free_min = 1073741824;
		//un giga minimo

		exo("serverotto start!");
		//Carichiamo le info sugli hosting...
		require_once 'hosting/hosting_id.php';
		//Mi serve da sapere per adesso il percorso remoto
		//printa($hosting_id);
		$this -> remote_address = "http://" . $hosting_id[$this -> file_info['hosting_id']] . "/" . $this -> file_info['path'];
		exo($this -> remote_address);

		/*****/
		//Chiudo le cose che verrebbero sharate
		/*****/

		//MySQL
		qclose();
		//Memcache
		$this -> mmc -> close();

		//$pid = pcntl_fork();
		$pid = 0;
		//E le riapro
		new_mysqli();
		$this -> memcache_init();

		if ($pid == -1) {
			exo("could not fork");
			$this -> reason = "could not fork";
			return false;

		} else if ($pid) {
			//Papà
			$posixProcessID = posix_getpid();
			exo("fork fatto! pid $pid, io sono $posixProcessID");

			return true;

		} else {
			//Figghio
			//ok cosa devo dare io esattamente ???

			//1 Faccio sapere al mondo che esisto
			$this -> posixProcessID = posix_getpid();
			$this -> mmc_set_pid();

			//2 dico al memcache che esisto e sono cosciente
			$this -> file_status = "100";
			$this -> mmc_set_file_status();

			//3 il file che è da salvare fai spazio se serve
			//alias spazio disponibile
			//spazio minimo da lasciare

			$df = disk_free_space($this -> cache_path);

			logg("Spazio libero $df, richiesto {$this->file_info['dimension']}, minimo $this->space_free_min");

			if (($df - $this -> file_info['dimension']) < $this -> space_free_min) {
				//se è inferiore sql select file_id order by dwl_last DESC
			}

			//4 inizia a salvare e dillo al memcache...
			logg("inizio a salvare");
			logg("apro $this->remote_address");
			$rfp = fopen($this -> remote_address, "r");
			logg("stream aperto $this->remote_address @");
			printa($rfp);

			$file_path = $this -> cache_path . $this -> file_id;

			$lfp = fopen($file_path, "w");
			logg("stream aperto $file_path");
			$pos = 0;
			$ite = 0;
			while ($buf = fread($rfp, 8)) {
				//logg("letto $buf");
				//echo $buf;
				fwrite($lfp, $buf);
				$pos += strlen($buf);
				//Non chiamo la funzione che spreca tempoh!
				$this -> mmc -> set($this -> mem_pos, $pos);
				$ite++;
			}
			exo("iterazioni $ite");
			//diciamo a mysql che il file ora è completo, e non ricevo più richeste per questa cosa...
			//L'ordine con cui notifico che non esisto più non deve essere cambiato!!

			$sql = "UPDATE `mokba`.`file` SET `complete` = 'true' WHERE `file`.`file_id` =$this->file_id;";
			qq($sql);

			fclose($rfp);
			fclose($lfp);

			//Create a new file with the process id in it.
			//$filePointer = fopen("/var/run/srv_" . $this -> file_id . ".pid", "w");
			//fwrite($filePointer, $posixProcessID);
			//top
			//fclose($filePointer);
			sleep(5);
			exit ;

		}

	}

	/******************************************/
	/******************************************/
	/******************************************/

	var $data = null;
	var $data_len = 0;
	var $data_mod = 0;
	var $data_type = 0;
	var $data_section = 0;
	//section download
	/**
	 * @var ObjectHandler
	 **/
	var $handler = array('auth' => null);
	var $use_resume = true;
	var $use_autoexit = false;

	var $use_auth = false;
	var $mime = null;
	var $bufsize = 2048;
	var $seek_start = 0;
	var $seek_end = -1;
	var $size = 0;

	/**
	 * Total bandwidth has been used for this download
	 * @var int
	 */
	var $bandwidth = 0;
	/**
	 * Speed limit
	 * @var float
	 */
	var $speed = 0;

	/*-------------------
	 | Download Function |
	 -------------------*/
	/**
	 * Check authentication and get seek position
	 * @return bool
	 **/
	function initialize() {
		global $HTTP_SERVER_VARS;

		if ($this -> use_auth)//use authentication
		{
			if (!$this -> _auth())//no authentication
			{
				header('WWW-Authenticate: Basic realm="Please enter your username and password"');
				header('HTTP/1.0 401 Unauthorized');
				header('status: 401 Unauthorized');
				if ($this -> use_autoexit)
					exit();
				return false;
			}
		}
		if ($this -> mime == null)
			$this -> mime = "application/octet-stream";
		//default mime

		if (isset($_SERVER['HTTP_RANGE']) || isset($HTTP_SERVER_VARS['HTTP_RANGE'])) {

			if (isset($HTTP_SERVER_VARS['HTTP_RANGE']))
				$seek_range = substr($HTTP_SERVER_VARS['HTTP_RANGE'], strlen('bytes='));
			else
				$seek_range = substr($_SERVER['HTTP_RANGE'], strlen('bytes='));

			$range = explode('-', $seek_range);

			if ($range[0] > 0) {
				$this -> seek_start = intval($range[0]);
			}

			if ($range[1] > 0)
				$this -> seek_end = intval($range[1]);
			else
				$this -> seek_end = -1;

			if (!$this -> use_resume) {
				$this -> seek_start = 0;

				//header("HTTP/1.0 404 Bad Request");
				//header("Status: 400 Bad Request");

				//exit;

				//return false;
			} else {
				$this -> data_section = 1;
			}

		} else {
			$this -> seek_start = 0;
			$this -> seek_end = -1;
		}

		return true;
	}

	/**
	 * Send download information header
	 **/
	function header($size, $seek_start = null, $seek_end = null) {
		exo("il mime è: $this->mime");
		header('Content-type: ' . $this -> mime);
		header('Content-Disposition: attachment; filename="' . $this -> file_name . '"');
		header('Last-Modified: ' . date('D, d M Y H:i:s \G\M\T', $this -> data_mod));
//die();
		if ($this -> data_section && $this -> use_resume) {
			header("HTTP/1.0 206 Partial Content");
			header("Status: 206 Partial Content");
			header('Accept-Ranges: bytes');
			header("Content-Range: bytes $seek_start-$seek_end/$size");
			header("Content-Length: " . ($seek_end - $seek_start + 1));
		} else {
			header("Content-Length: $size");
		}
	}

	function download_ex($size) {
		if (!$this -> initialize())
			return false;
		ignore_user_abort(true);
		//Use seek end here
		if ($this -> seek_start > ($size - 1))
			$this -> seek_start = 0;
		if ($this -> seek_end <= 0)
			$this -> seek_end = $size - 1;
		$this -> header($size, $seek, $this -> seek_end);
		$this -> data_mod = time();
		return true;
	}

	/**
	 * Start download
	 * @return bool
	 **/
	function download() {
		if (!$this -> initialize()) {
			return false;
		}

		///*** Mistero 1 *****///
		$seek = $this -> seek_start;

		$speed = 1024;

		//$bufsize = $this->bufsize;

		$bufsize = 1024 * $speed;

		$packet = 1;
		/////////////////////

		//do some clean up

		//Consente di avere sessioni parallele
		session_write_close();

		//Flush eventuale dei dati CANCELLATO che corrompe
		@ob_clean();

		//Se l'utente chiude mica mando i dati a casaccio...
		$old_status = ignore_user_abort(false);

		//Ed ha tutto il tempo che gli aggrada...
		@set_time_limit(0);

		//Alcuni config

		//Banda usata ora è zero
		$this -> bandwidth = 0;

		$is_resume = $this -> use_resume;

		$delay = 10000;
		//(10 mS)
		$speed = $speed * 1024;

		$chunk = 50;

		$size = $this -> file_dimension;

		//Se la richiesta è fuori dal range la resetto a zero
		//TODO in teoria dovrei notificare la cosa ?? indaga...
		if ($seek > ($size - 1)) {
			$seek = 0;
		}


		$res = fopen($this -> file_path, 'rb');
		if ($seek){
			fseek($res, $seek);
		}
		//Se il range si estende oltre la fine lo cappo alla dimensione del file
		if ($this -> seek_end < $seek){
			$this -> seek_end = $size - 1;
		}
		
		//Primo header
		$this -> header($size, $seek, $this -> seek_end);
		
		ob_end_flush();
		//always use the last seek
		exo("sono qua 34");
		//Dimensione dei dati che devo inviare
		$job_size = $this -> seek_end - $seek + 1;
		exo("Devo inviare $job_size byte");
		//printa($this);
		while (!($user_aborted = connection_aborted() || connection_status() == 1) && $job_size > 0) {
			//Se ho un frammeto di dati piccolo lo invio e basta
			if ($job_size < $bufsize) {
				echo fread($res, $job_size);
				$this -> bandwidth += $job_size;
				$job_size=0;
				usleep(500);
				
			} else {//Altrimenti bufferizzo e invio
				echo fread($res, $bufsize);
				$this -> bandwidth += $bufsize;
				$job_size -= $bufsize;
				usleep(10000);
			}
			
			flush();
			//A cosa serve non lo ho capito bene... per ora lo sopprimo sto pezzo
			/*
			if ($speed > 0 && ($this -> bandwidth > $speed * $packet * 1024)) {
				//sleep(1);
				$packet++;
			}
			 */
			
			usleep($delay);
		}
		ob_end_clean();
		
		fclose($res);

		if ($this -> use_autoexit)
			exit();

		//restore old status
		ignore_user_abort($old_status);
		set_time_limit(ini_get("max_execution_time"));

		return $size;
		//return true;
	}

	/**
	 * Start download limited by memcached values
	 * @return bool
	 **/
	function download_mmc() {
		if (!$this -> initialize()) {
			return false;
		}

		
		$this->mmc_get_file_pos();
		

		//echo $head;
		//die("cry");
		//do some clean up
		@ob_end_clean();
		$old_status = ignore_user_abort(true);
		@set_time_limit(0);
		$this -> bandwidth = 0;

		$is_resume = false;
		//No... mi complica la vita tantissimo...

		//L'utente può avere sessioni parallele dello stesso file ? In teoria non è così cretino...
		//nella pratica sarebbe opportuno evitarlo..
		session_write_close();

		$res = fopen($this -> data, 'rb');

		//Proviamo a NON inviare l'header per niente...
		$this->header($size,$seek,$this->seek_end); //always use the last seek

		//$rimasto è la dimensione RESIDUA del buffer (head la posizione)
		$rimasto = $head;

		$inviato = 0;

		$chunk = 8192;
		$buffer_min = $chunk * 64;
		//alias mezzo megabyte alias 524.288 byte
		$buffer_read = $buffer_min;
		//su questo valore è da fare un pò di prove... ora metto che è uguale a buffer min (il valore massimo...)

		while (!($user_aborted = connection_aborted() || connection_status() == 1) && $rimasto > 0) {

			//per mandare un pò di dati devo avere almeno un quantitativo minimo da inviare...
			//l'unità base sono i chunk da 8192byte (non mi chiedere il motivo di questa dimensione ma è quella che pare vada meglio)
			$head = $memcache1 -> get($key);

			$rimasto = $head - $inviato;

			//se ho meno di mezzo mega di riserva
			//TODO ovvio controlla che il file non sia terminato altrimenti vai in stun lock
			if (abs($rimasto - $inviato) < $buffer_min) {
				//autostunnati per 1 secondo
				sleep(1);
			} else {

				echo fread($res, $buffer_read);
				$inviato += $buffer_read;
				flush();

				//per evitare lavoro inutile ecc considero che inviare i file a 100megabit sia notevole come velocità ?
				//quindi ogni 0.5mega inviati posso riposare per un bel 5 millisecondi ...
				usleep(5000);
				//Peccato che cosi attufo la cache di invio dei dati, che magari vengono scartati e cose simpatiche simili...
				//La velocità di invio deve essere proporzionata a quella di ricezione, o no ?
				//TODO scopri cosa sia vero e cosa no... e fai un post sul blog... per ora lascia che si ferma per X tempo

			}

		}
		fclose($res);

		if ($this -> use_autoexit)
			exit();

		//restore old status
		ignore_user_abort($old_status);
		set_time_limit(ini_get("max_execution_time"));

		return $inviato;
		//return true;
	}

	function set_byfile($dir) {
		if (is_readable($dir) && is_file($dir)) {
			$this -> data_len = 0;
			$this -> data = $dir;
			$this -> data_type = 0;
			$this -> data_mod = filemtime($dir);
			return true;
		} else
			return false;
	}

	function set_bydata($data) {
		if ($data == '')
			return false;
		$this -> data = $data;
		$this -> data_len = strlen($data);
		$this -> data_type = 1;
		$this -> data_mod = time();
		return true;
	}

	function set_byurl($data) {
		$this -> data = $data;
		$this -> data_len = 0;
		$this -> data_type = 2;
		return true;
	}

	function set_lastmodtime($time) {
		$time = intval($time);
		if ($time <= 0)
			$time = time();
		$this -> data_mod = $time;
	}

	/**
	 * Check authentication
	 * @return bool
	 **/
	function _auth() {
		if (!isset($_SERVER['PHP_AUTH_USER']))
			return false;
		if (isset($this -> handler['auth']) && function_exists($this -> handler['auth'])) {
			return $this -> handler['auth']('auth', $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
		} else
			return true;
		//you must use a handler
	}

}
