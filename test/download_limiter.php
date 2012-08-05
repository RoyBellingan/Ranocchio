<?php
//Mi serve questo per configurare la velocità di download di download_limited.php

/*
 * TODO impostare degli intoppi, dei rallentamenti artificiosi e cose simili...
 * Per ora imposto una velocità e quella mantengo...
 * TODO sarebbe bello potere vedere delle info dettagliate
 * Aggiornate ogni X secondi, ma che mi dicono ad esempio
 * TODO Data inizio
 * Velocità Media
 * Velocità Max
 * Velocità Min
 * Ip destinazione
 * Punti di passaggio (array di ip)
 * Ping ai vari hop (array di latenze)
 *
 * Questo per pubblicare una mappa di latenze e velocità, se uno scarica lento gli dico di chi è la colpa...
 *
 */
define("VERBOSE", false);
$speed = false;
if (isset($_GET['speed']) && $_GET['speed'] > 0) {

	$speed = 1024 * 1024 * $_GET['speed'];

} else if (isset($_GET['bspeed']) && $_GET['bspeed'] > 0) {

	$speed = $_GET['bspeed'];
}

if ($speed == true) {
	if (isset($_GET['c']) && $_GET['c'] == "on") {

		$speed = floor($speed / 8);
	}

	require_once '../class/sqlmem.php';
	$sm = new sqlmem(5001, 16, true);

	//echo "$speed bytes al secondo";
	$speed = (int)$speed;
	$sm -> update($speed);

}

$flush = false;
if (isset($_GET['flush']) && $_GET['flush'] > 0) {

	$flush = $_GET['flush'];
	require_once '../class/sqlmem.php';
	$fm = new sqlmem(5002, 10, true);

	//echo "$speed bytes al secondo";
	$flush = (int)$flush*1000;
	$fm -> update($flush);
	
}

$head = false;
if (isset($_GET['head']) && $_GET['head'] > 0) {

	$head = $_GET['head'];
	require_once '../class/sqlmem.php';
	$hm = new sqlmem(5004, 16, true);

	$hms=$hm->select();
	//Non andare indietro!!!
	
	//if ($head > $hms){
		$hm -> update($head);
	//}
	//echo "$speed bytes al secondo";
	
	
}

if (@!isset($sm)) {
	require_once '../class/sqlmem.php';
	$sm = new sqlmem(5001, 16, true);
}
if (@!isset($fm)) {
	require_once '../class/sqlmem.php';
	$fm = new sqlmem(5002, 10, true);
}

if (@!isset($hm)) {
	require_once '../class/sqlmem.php';
	$hm = new sqlmem(5004, 16, true);
	$hms=$hm->select();
}


	require_once '../class/sqlmem.php';
	$bm = new sqlmem(5003, 10, true);


	require_once '../util/funkz.php';
	$mmc = new memcache();
	$mmc -> connect('localhost', 11211) or die("Could not connect");
	$mem_info="limiter";
	$file_status = $mmc -> get($mem_info);
	printa($file_status);
	
$fms=$fm->select()/1000;
$html = <<<EOD
<!DOCTYPE html>
<html>
<body>
<h2> velocità {$sm->select()} byte al secondo</h2>
<h2> Header a {$hms} byte </h2>
<h2> Flush ogni $fms MILLisecondi</h2>
<h2> Buffer di {$bm->select()} Byte </h2>
<form action="download_limiter.php" method="get">
<input type="submit" value="setta"> <br>
<input type="checkbox" name="c"> Bit invece di byte ? <br>
<input type="number" min="0" value="" name="speed"> MByte/s <br>
<input type="number" min="0" value="" name="bspeed"> Byte/s<br>
<input type="number" min="0" value="" name="head"> Head del file da NON superare<br>	
<input type="number" min="0" value="" name="flush"> Tempo per il flush in MILLIsecondi (1/1'000'000) (da uno a 9'999'999 alias 9 secondi)

<br><br>



<hr>
DA FARE!!! ->
Buffer Allocato<br>

Tempo di download<br>

Parti in download<br>

Velocità massima<br>
Singoli: - - - - - = totale
Velocità minima<br>
Singoli: - - - - - = totale
Velocità media<br>
Singoli: - - - - - = totale

 
Per evitare troppe richieste e cose varie, viene RILETTA la velocità ogni 10 millisecondi circa sul downloader di prova
(per quello in produzione invece è circa ogni 5 secondi)...

<br>Ne consegue che anche il buffer cambia dimensione ecc ecc, ma questo non importa molto...

Per ora è un secondo per entrambi.... 
</form>
</body>
</html>


EOD;
//TODO fai si che i timing indicati siano veri....
echo $html;
