<?php

/** Sulla base dei parametri decide se il file va servito in streamging o salvato sul disco
 * Se il file va salvato subentra il dove va salvato per bilanciare il carico...
 */
function save_me($creation, $dwl_number, $dwl_last){
	
	
} 


/*Decide DOVE salvare i dati
 * TODO scrive i dati un pò su un disco ed un pò su un altro, lo fa leggendo le stats di lettura nelle ultime ore da 
 *http://www.cyberciti.biz/tips/linux-disk-performance-monitoring-howto.html
 * IOSTAT!
 * 
 * iostat -d -x 5 3
 * 
 * Linux 2.6.18-53.1.4.el5 (moon.nixcraft.in)   12/17/2007
Device:         rrqm/s   wrqm/s   r/s   w/s   rsec/s   wsec/s avgrq-sz avgqu-sz   await  svctm  %util
sda               1.10    39.82  3.41 13.59   309.50   427.48    43.36     0.17   10.03   1.03   1.75
sdb               0.20    18.32  1.15  6.08   117.36   195.25    43.22     0.51   71.14   1.26   0.91
Device:         rrqm/s   wrqm/s   r/s   w/s   rsec/s   wsec/s avgrq-sz avgqu-sz   await  svctm  %util
sda               0.00   108.40  1.40 64.40    49.60  1382.40    21.76     0.04    0.67   0.44   2.92
sdb               0.00    37.80  0.00 245.20     0.00  2254.40     9.19    28.91  108.49   1.08  26.36
Device:         rrqm/s   wrqm/s   r/s   w/s   rsec/s   wsec/s avgrq-sz avgqu-sz   await  svctm  %util
sda               0.00    97.01  1.00 57.29    39.92  1234.33    21.86     0.03    0.58   0.50   2.89
sdb               0.00    38.32  0.00 288.42     0.00  2623.55     9.10    32.97  122.30   1.15  33.27
 * 
 * rrqm/s : The number of read requests merged per second that were queued to the hard disk
    wrqm/s : The number of write requests merged per second that were queued to the hard disk
    r/s : The number of read requests per second
    w/s : The number of write requests per second
    rsec/s : The number of sectors read from the hard disk per second
    wsec/s : The number of sectors written to the hard disk per second
    avgrq-sz : The average size (in sectors) of the requests that were issued to the device.
    avgqu-sz : The average queue length of the requests that were issued to the device
    await : The average time (in milliseconds) for I/O requests issued to the device to be served. This includes the time spent by the requests in queue and the time spent servicing them.
    svctm : The average service time (in milliseconds) for I/O requests that were issued to the device
    %util : Percentage of CPU time during which I/O requests were issued to the device (bandwidth utilization for the device). Device saturation occurs when this value is close to 100%.
 * 
 */ 
function select_disk(){
	
}


function exo($txt){
	if(VERBOSE==true){
		//echo "$txt <br>\n";
		logg($txt);
	}
}

function printa($txt){
	//echo "<pre>";
	if(VERBOSE==true){
	logg(var_export($txt,true));
	}else{
		echo "<pre>";
		print_r($txt);
		echo "</pre>";
	}
	//echo "</pre>";
}

function logg($cosa,$file="/srv/www/htdocs/mokba/log_comune.log"){
	error_log($cosa."\n",3,$file);
}
