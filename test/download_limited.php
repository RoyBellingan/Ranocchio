<?php
//$sucesss = @apache_setenv('no-gzip', 1);
//TODO via shmop fai un altro script che regola la velocità... mi da info ecc...

define("VERBOSE", true);

define("PATH","../");
require_once PATH.'util/funkz.php';
require_once PATH.'class/stream.php';
require_once PATH.'class/sqlmem.php';

ob_start();


$stream=new stream();
$file="3";
$stream->file_path=$file;
$stream->file_dimension=filesize($file);
$stream->mime="text/plain";
$stream->file_name="Try.txt";
$stream->data_mod=1343915765;

$stream->throttle=2;
$stream -> use_resume = true;

$stream->memcache_init();
$stream -> mem_info="limiter";
$stream->mmc_set_file_info();

$stream->mem_speed_pos=5001;
$stream->mmc_init_speed();

$stream->mem_flush_pos=5002;
$stream->mmc_init_flush();

$stream->mem_buf_pos=5003;
$stream->mmc_init_buf();

$stream->mem_head_pos=5004;
$stream->mmc_init_head();

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


