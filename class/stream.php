<?php
/** La classe che si occupa di gestire l'invio dei dati
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

	var $cache_pat;
	//!<	Dove stanno i file ?
	
	/** Info che serverotto mi stà passando
	 * 
	 * È un array cosi formato
	 * @param start = bool 
	 * @param end = bool
	 * l'indirizzo è file_id_info, ed è comune a tutti i processi che attendono serverotto
	 */
	var $mem_info;
	
	/** Info su dove si trova l'head del file reportato da serverotto,
	 * 
	 * È aggiornato ogni 5 megabyte che LUI scarica
	 * L'indirizzo è file_id_pos, ed è comune a tutti i processi che attendono serverotto
	 * 
	 */
	var $mem_pos;
	

	function stream() {
		//Mettiamo qualche valore di default suvvia!

		$this -> complete = true;
		$this -> cache_path = "/cache";
	}

	function start() {

		switch ($this->type) {
			case 'disk' :
			//Cerca il file nella cache
				$filename = "$this->cache_path/$this->file_info[0]";
				if (file_exists($filename)) {
					$size = filesize($filename);
					if ($size == $this -> file_info[3]) {

					} else {
						//Pure questo non va bene che un file scaricato sia di dimensione diversa da quello che risulta salvato...
						//avvia serverotto e mettici una pezzah 
						$this -> reason="dimensione del file trovato nella cache diversa";
						$this -> set_light(false);
						return false;
					}
				} else {
					//Questo è un errore grave, non dovrebbe MAI accadere una cosa del genere
					//avvia serverotto e procedi come fosse un file da scaricare...
					$this -> reason="file non trovato nella cache";
					$this -> set_light(false);
					return false;
				}

				$fp = fopen($filename, "r");

				if ($this -> complete === false) {
					/*Collegati al memcache, e cerca i dati sulla posizione che serverotto ha già iniziato a salvare...
					*verifica che sia partito ecc
					 */ 
					
				}else{
					//In questo caso spara il file e amen!
					
				}

				//La dimensione trovata è quella sperata
				//TODO

				break;

			case 'network' :
				break;
			default :
				break;
		}

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

	/*!
	 * A list of events:
	 *	- mouse events
	 *		-# mouse move event
	 *		-# mouse click event\n
	 *			More info about the click event.
	 *		-# mouse double click event
	 *	- keyboard events
	 *		-# key down event
	 *		-# key up event
	 * More text here.
	 */
	var $use_auth = false;
	var $filename = null;
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
		header('Content-type: ' . $this -> mime);
		header('Content-Disposition: attachment; filename="' . $this -> filename . '"');
		header('Last-Modified: ' . date('D, d M Y H:i:s \G\M\T', $this -> data_mod));

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
		if (!$this -> initialize())
			return false;

		$seek = $this -> seek_start;
		$speed = 1024;
		//$bufsize = $this->bufsize;
		$bufsize = 1024 * $speed;
		$packet = 1;

		//do some clean up
		@ob_end_clean();
		$old_status = ignore_user_abort(false);
		@set_time_limit(0);
		$this -> bandwidth = 0;

		$is_resume = true;
		//Per adesso si

		$delay = 10000; //(10 mS)
		$speed = $speed * 1024;
		$chunk = 50;

		session_write_close();
		//NON LO SI TOCCA PER NESSUN MOTIVO

		$size = filesize($this -> data);
		$this -> size = $size;

		if ($seek > ($size - 1))
			$seek = 0;
		if ($this -> filename == null)
			$this -> filename = basename($this -> data);

		$res = fopen($this -> data, 'rb');
		if ($seek)
			fseek($res, $seek);
		if ($this -> seek_end < $seek)
			$this -> seek_end = $size - 1;

		$this -> header($size, $seek, $this -> seek_end);
		//always use the last seek
		$size = $this -> seek_end - $seek + 1;

		while (!($user_aborted = connection_aborted() || connection_status() == 1) && $size > 0) {
			if ($size < $bufsize) {
				echo fread($res, $size);
				$this -> bandwidth += $size;
			} else {
				echo fread($res, $bufsize);
				$this -> bandwidth += $bufsize;
			}

			$size -= $bufsize;
			flush();

			if ($speed > 0 && ($this -> bandwidth > $speed * $packet * 1024)) {
				//sleep(1);
				$packet++;
			}
			usleep($delay);
		}
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
	function download_rate() {
		if (!$this -> initialize()) {
			return false;
		}

		$memcache1 = new Memcache;
		$memcache1 -> connect('localhost', 11211);
		$key = $this -> data . "-head";
		$head = $memcache1 -> get($key);

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
		//$this->header($size,$seek,$this->seek_end); //always use the last seek

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
