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
ob_start();
define("DEBUG", true);
define("PATH", "");

require_once 'config.php';
require_once 'util/funkz.php';
require_once 'class/eventlog.php';
require_once 'util/mysqli.php';
require_once 'class/stream.php';

$logs = new eventlog();
$logs -> init_log();
$logs -> source = "scarichino";

$stream = new stream();
$stream -> stream_init();
$stream -> logs = $logs;

$logs -> dlog("request", "controllo gli imput");

//Controllo che gli input siano plausibili
$stream -> input_ck();

//controllo che siano SOLI numeri, mai visto exploit con campi solo numerici fino ad ora...
//E anche di lunghezza massima...
//10 miliardi di utenti sarebbe bello averli in effetti, ma temo non sia possibile, il controllo trimma a 6 alias 1 milione!
//Gli id dei file saliranno parecchio spero, ma un miliardo di file per ora mi sembra ragionevole...
$stream -> was_ist_numeric();

//Sembra ok allora
//logga non si sà mai..
$logs -> logg_1($record_id, $user_id);

// L'immancabile ip, per evitare soliti sharing ecc...
$stream -> ip = $_SERVER['REMOTE_ADDR'];

$logs -> dlog("check", "Controllo se il record esiste");

/*A questo punto controlla, se
 * Il file effettivamente esiste
 * La richiesta è leggittima, alias questo utente può scaricare il file...
 * se il file è completo e quindi siamo a cavallo,
 * e sennò dal rateo delle richieste di download, se è fattibile salvarlo o continuo a fare da proxy nabbo
 */

$stream -> file_info();

if ($stream -> red_light) {
	//Niente di che, registra solo il motivo che può essere record non esiste, utente non può scaricare e cosine simili...
	//Nessun controllo su account sharing...

	$logs -> dlog("check", "Il record non esiste o l'utente non lo può scaricare");

	die();
}

$logs -> dlog("check", "Ok fase iniziale completata");

if ($stream -> complete) {

	//inizia lo streaming leggendo dal disco
	//$stream = new stream();
	//registra che inizio un download, con questo ip, info ecc...

	$stream -> use_resume = true;
	$stream -> download_throttle();

	//TODO Poi mettici mysql update un download in più,
	//TODO se ti va metti anche  un download per l'utente, banda usata dall'utente e le stats che ti aggradano
	die();

} else {
	//Ok valutiamo se il file vada salvato
	$logs -> dlog("check", "Il file non è presente / è incompleto");

	if ($stream -> candidate) {
		//Benissimo
		$logs -> dlog("check", "È candidato ad essere salvato, vediamo se serverotto già sta scaricandolo o sono il primo");

		$stream -> type = "disk";
		//$stream -> shmop_pos_init();
		$stream -> memcache_init();
		$stream -> mmc_get_file_status();
		$logs -> dlog("file status è $stream->file_status");
		if ($stream -> file_status[0] == 0) {
			$logs -> dlog("check", "Serverotto è ignaro di tutto ciò..");

			$stream -> new_serverotto();

		} else {
			$logs -> dlog("check", "Serverotto è a conoscenza, quindi mi metto in coda e pesco quel che si trova...");
			$stream -> mmc_get_pid();
			$logs -> dlog("check", "Il serverotto che gestisce la cosa è al $stream->file_pid");
			if ($stream -> file_status[1] == 1) {
				$stream -> mmc_get_file_pos();
				$logs -> dlog("check", "Ha iniziato a scaricare ed è alla posizione $stream->file_pos");
			} else {
				$logs -> dlog("check", "NON ha ancora iniziato a scaricare");
			}

		}
		//$stream->mem_flush_pos=5002;
		//$stream->mmc_init_flush();
		$stream -> download_adv();

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
