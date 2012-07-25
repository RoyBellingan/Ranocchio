<?php
/*
 * Questo è l'entry point dello script (per gli utenti), che nel 99% dei casi è chiamato in una forma del genere
 * mokba.com/262344/54743738
 * che è rewrittato come
 * mobka.com/dwl.php?user_id=262344&file_id=54743738
 *
 * Anche se è dato all'utente nella forma
 * mokba.com/262344/54743738/Il nome del file così ti ricordi di cosa stiamo parlando
 */

//Soliti check sugli imput degli utenti...

define("VERBOSE", true);
require_once 'util/funkz.php';
require_once 'class/eventlog.php';

$log = new eventlog();

exo("controllo gli imput");
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

//controllo che siano SOLI numeri, mai visto exploit con campi solo numerici fino ad ora...
//E anche di lunghezza massima...
//10 miliardi di utenti sarebbe bello averli in effetti, ma temo non sia possibile, il controllo trimma a 6 alias 1 milione!
//Gli id dei file saliranno parecchio spero, ma un miliardo di file per ora mi sembra ragionevole...

$record_id = (int)$record_id;
$user_id = (int)$user_id;

//echo $record_id;

if (!is_int($record_id) || !is_int($user_id) || $record_id > 10000000000 || $user_id > 1000000) {
	//var_dump($record_id);
	//var_dump($user_id);
	//TODO logga un errore da parte di questo IP, con una richiesta malevola...
	//A lui non dire nulla
	echo "die";
	die();
}

//echo "seem's legit";
exo("Imput ok, $record_id e $user_id");
require_once 'util/mysqli.php';
require_once 'class/stream.php';

//Inizializziamo il sistema yeah!

$stream = new stream();

//Passo l'id del record
$stream -> record_id = $record_id;
//E dell'utente che lo ha richiesto, cosi se non combacia amen da subito...
$stream -> user_id = $user_id;
//E l'immancabile ip, per evitare soliti sharing ecc...
$stream -> ip = $_SERVER['REMOTE_ADDR'];

exo("Controllo se il record esiste");
/*A questo punto controlla, se
 * Il file effettivamente esiste
 * La richiesta è leggittima, alias questo utente può scaricare il file...
 * se il file è completo e quindi siamo a cavallo,
 * e sennò dal rateo delle richieste di download, se è fattibile salvarlo o continuo a fare da proxy nabbo
 */
$stream -> file_info();

if ($stream -> red_light) {
	exo("Il record non esiste o l'utente non lo può scaricare");
	//Niente di che, registra solo il motivo che può essere record non esiste, utente non può scaricare e cosine simili...
	//Nessun controllo su account sharing...
	$log -> event();
	die();
}

exo("Ok fase iniziale completata");

if ($stream -> complete) {

	//inizia lo streaming leggendo dal disco
	//$stream = new stream();
	exo("inizio a leggere un file su disco ed ad inviarlo all'utente");


	//registra che inizio un download, con questo ip, info ecc...
	$log -> event();

	$stream -> use_resume = true;
	$stream -> download();

	//TODO Poi mettici mysql update un download in più,
	//TODO se ti va metti anche  un download per l'utente, banda usata dall'utente e le stats che ti aggradano
	die();

} else {
	//Ok valutiamo se il file vada salvato
	exo("Il file non è presente / è incompleto");
	if ($stream -> candidate) {
		//Benissimo
		exo("È candidato ad essere salvato, vediamo se serverotto già sta scaricandolo o sono il primo");

		$stream -> type = "disk";

		$stream -> memcache_init();
		$stream -> mmc_get_file_status();
		exo("file status è $stream->file_status");
		if ($stream -> file_status[0] == 0) {
			exo("Serverotto è ignaro di tutto ciò..");

			$stream -> new_serverotto();

		} else {
			exo("Serverotto è a conoscenza, quindi mi metto in coda e pesco quel che si trova...");
			$stream -> mmc_get_pid();
			exo("Il serverotto che gestisce la cosa è al $stream->file_pid");
			if ($stream -> file_status[1] == 1) {
				$stream -> mmc_get_file_pos();
				exo("Ha iniziato a scaricare ed è alla posizione $stream->file_pos");
			} else {
				exo("NON ha ancora iniziato a scaricare");
			}

		}

		$stream -> start();

	} else {
		$stream = new stream();
		$stream -> type = "network";
		$stream -> start();
	}

}

/*
 *
 *  ovvero
 *
 *select complete, creation, dwl_number, dwl_last from  record, file where record.record_id = ... AND record.file_id = `file`.`file_id`
 *
 *
 *

 ******** SE esiste ed è completo

 //Fai il più uno di rigore alle richieste file...
 //UPDATE `mokba`.`file` SET `dwl_number` = `dwl_number` + 1 WHERE record.record_id = ... AND record.file_id = `file`.`file_id`  ;
 */

/*
 ******** SE esiste e non è completo

 ******** Se non esiste il file, ma solo il record (utente ha prenotato ma ancora non ha scaricato)

 *
 */
