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
if (@!isset($sm)) {
	require_once '../class/sqlmem.php';
	$sm = new sqlmem(5001, 16, true);
}
$html = <<<EOD
<!DOCTYPE html>
<html>
<body>
<h2> È {$sm->select()} byte al secondo</h2>
<form action="download_limiter.php" method="get">
<input type="submit" value="setta"> <br>
<input type="checkbox" name="c"> Bit invece di byte ? <br>
<input type="number" min="0" value="" name="speed"> MByte/s <br>
<input type="number" min="0" value="" name="bspeed"> Byte/s
	


<br><br>
Tempo di download

Parti in download

Velocità massima

Velocità minima

Velocità media


<hr>
DA FARE!!! -> 
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
