<?php
//$sucesss = @apache_setenv('no-gzip', 1);
//TODO via shmop fai un altro script che regola la velocità... mi da info ecc...
require_once '../util/funkz.php';
require_once '../class/stream.php';

ob_start();
$file="1.txt";

$stream=new stream();
$stream->file_path=$file;
$stream->file_dimension=filesize($file);
$stream->mime="text/plain";
$stream->file_name="Try";
$this -> data_mod=time();

$stream->download();

die();
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
	usleep(200000);
	}

    
    ob_flush();
    
}


