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


if(isset($_GET['speed']) && $_GET['speed']>0){
	
	$speed=$_GET['speed'];
		
	if(isset($_GET['c'])){
		$bit=$_GET['c'];
		//echo $bit;
		if($bit=="on"){
			$bit=true;
			$speed=floor(1024*1024*$speed/8);
		}
	}else{
			$speed=1024*1024*$speed;
	}
	
	require_once '../class/sqlmem.php';
	$sm=new sqlmem(5001,16,true);
	echo " era ".$sm->select()." byte al secondo"; 
	//echo "$speed bytes al secondo";
	$speed=(int)$speed;
	$sm->update($speed);

	
}

$html=<<<EOD
<!DOCTYPE html>
<html>
<body>
<form action="download_limiter.php" method="get">
<input type="submit" value="setta"> <br>
<input type="checkbox" name="c"> Bit invece di byte ? <br>
<input type="number" min="0" value="" name="speed"> MByte/s
	

<br><br>
Per evitare troppe richieste e cose varie, viene RILETTA la velocità ogni 10 millisecondi circa sul downloader di prova
(per quello in produzione invece è circa ogni 5 secondi)...

Ne consegue che anche il buffer cambia dimensione ecc ecc... 
</form>
</body>
</html>


EOD;

echo $html;

