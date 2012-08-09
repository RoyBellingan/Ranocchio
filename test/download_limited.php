<?php
//$sucesss = @apache_setenv('no-gzip', 1);
//TODO via shmop fai un altro script che regola la velocità... mi da info ecc...

define("VERBOSE", true);

define("PATH","../");
require_once PATH.'util/funkz.php';
require_once PATH.'class/stream.php';
require_once PATH.'class/sqlmem.php';

$pid=posix_getpid();
exo("---------------------------------------");
exo("ciao io sono $pid");

ob_start();


$stream=new stream();
$stream->stream_init();

if (isset($_GET['file'])){
	$file=$_GET['file'];
	$stream -> file_id = $file;
}else{
	$file="1"; //un file da un mega che contiene solo la parola scaricami ripetuta alla nausea
	$stream -> file_id = 1;	
}
//$file="3";
//echo "is".$file;
$file=1;
$stream -> file_id = 2;	
$stream->file_path=$file;
$stream->file_dimension=filesize($file);
$stream->mime="text/plain";
$stream->file_name="file_".$file;

//La data deve essere fissa altrimenti contro prokka il refresh e quindi il resume fallisce
$stream->data_mod=1343915765;

$stream->throttle=2;
$stream -> use_resume = true;

$stream->memcache_init();
$stream -> mem_info="limiter";
$stream->mmc_set_file_info();


$val=$stream->sqlmem_speed->select();

exo ("il file lo spariamo a $val");

$stream->speed=$val;
$stream->download_adv();

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


