<?php
define("VERBOSE", true);
require_once '../class/stream.php';
require_once '../util/funkz.php';

exo("controllo gli imput ?user_id=1&record_id=1 ??? oppure ?file_id=1");
/*
 if (isset($_GET['record_id']) && isset($_GET['user_id'])) {
 $record_id = $_GET['record_id'];
 $user_id = $_GET['user_id'];
 } else {

 //Errata come richiesta
 //TODO logga anche questo, ma reporta l'errore
 //TODO stampa a schermo l'errore per l'utente ecc
 header("Status: 400 Bad Request");
 exit();
 }
 */
//$record_id = (int)$record_id;
//$user_id = (int)$user_id;
$stream -> file_id = $_GET['file_id'];

exo("Imput ok, $stream->file_id");

$stream = new stream();

//Passo l'id del record
//$stream -> record_id = $record_id;
//E dell'utente che lo ha richiesto, cosi se non combacia amen da subito...
//$stream -> user_id = $user_id;

$stream -> file_id = $_GET['file_id'];

$stream -> memcache_init();
$stream -> mmc_get_file_info();
$stream -> mmc_get_file_status();
$stream -> mmc_get_file_pos();

echo "file info";
printa($stream -> file_info);
echo "file status";
printa($stream -> file_status);
echo "file pos";
printa($stream -> file_pos);

$status="000";
echo "e invece ci scrivo che è $status";
$stream->file_status=$status;
$stream->mmc_set_file_status();


