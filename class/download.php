<?php

/**
 @author Nguyen Quoc Bao <quocbao.coder@gmail.com>
 @version 1.3
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

 1: File Download
 $object = new downloader;
 $object->set_byfile($filename); //Download from a file
 $object->use_resume = true; //Enable Resume Mode
 $object->download(); //Download File

 2: Data Download
 $object = new downloader;
 $object->set_bydata($data); //Download from php data
 $object->use_resume = true; //Enable Resume Mode
 $object->set_filename($filename); //Set download name
 $object->set_mime($mime); //File MIME (Default: application/otect-stream)
 $object->download(); //Download File

 3: Manual Download
 $object = new downloader;
 $object->set_filename($filename);
 $object->download_ex($size);
 //output your data here , remember to use $this->seek_start and $this->seek_end value :)

 **/

class httpdownload {

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
		$old_status = ignore_user_abort(true);
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
?>