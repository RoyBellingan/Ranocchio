<?php
/** L'interfaccia per serverotto sarebbe che riceve le richieste e vede se vanno eseguite per davvero!
 * 
 */

require_once 'class/eventlog.php';
$log = new eventlog();

if (isset($_GET['file_id'])) {
	$file_id = $_GET['file_id'];
	
} else {

	//Errata come richiesta
	//TODO logga anche questo, ma reporta l'errore
	//TODO stampa a schermo l'errore per l'utente ecc
	header("Status: 400 Bad Request");
	exit();
}

$file_id = (int)$file_id;

if (!is_int($file_id) ||  $file_id > 10000000000 ) {
	//var_dump($record_id);
	//var_dump($user_id);
	//TODO logga un errore da parte di questo IP, con una richiesta malevola...
	//A lui non dire nulla
	echo "die";
	die();
}



require_once 'class/stream.php';
$srv=new stream();
$srv->file_id($file_id);
$srv->memcache_init();
$srv->mmc_get_file_info();

//Verifica se ho spazio su disco
$size=$srv->file_info[3];
//Con un certo margine ecc...

//Se lo ho alloca un file sparse e usa quello per scriverci dentro...















/*
$memcache->set('key', $tmp_object, false, 10) or die ("Failed to save data at the server");

for ($i=0; $i<100000; $i++){

$memcache->set($i, $tmp_object);
}


echo "Store data in the cache (data will expire in 10 seconds)<br/>\n";
*/

/*
$get_result = $memcache->get($num);

var_dump($get_result);

*/