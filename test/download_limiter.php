<?php
//$sucesss = @apache_setenv('no-gzip', 1);
//TODO via mmc fai un altro script che regola la velocità... mi da info ecc...
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
@ini_set('output_buffering',0);
ob_start();
$size=filesize("2");
/*
header('Content-type: ' . "application/octet-stream");
header('Content-Disposition: attachment; filename=[Anime ITA] Neon Genesis Evangelion 2003 - Renewal - 01 - L\'attacco dell\'Angelo.avi');
header('Last-Modified: Sat, 28 Jul 2012 18:22:56 GMT');

header("Content-Length: $size");
		*/	


$res = fopen("2", 'rb');


$job_size = $size;
$bufsize=10;
echo ("file da $size byte");
//printa($this);
while ( $job_size > 0) {
    //Se ho un frammeto di dati piccolo lo invio e basta
    if ($job_size < $bufsize) {
	echo fread($res, $job_size);
	$job_size=0;
	usleep(500);
	
    } else {//Altrimenti bufferizzo e invio
	echo fread($res, $bufsize); 
	$job_size -= $bufsize;
	usleep(200);
	}

    
    ob_flush();
    
}


