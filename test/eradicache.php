<?php

if (isset($_GET['file'])) {
	$file = $_GET['file'];
	define("VERBOSE", true);

	define("PATH", "../");
	require_once PATH . 'util/funkz.php';
	require_once PATH . 'util/mysqli.php';
	require_once PATH . 'class/stream.php';
	require_once PATH . 'class/sqlmem.php';
	
	$sql="UPDATE `mokba`.`file` SET `complete` = 'false' WHERE `file`.`file_id` =$file;";
	$line=qmod($sql);
	if ($line==0){
		$line=0;
	}
	
	if ($line===false){
		
		echo "Query errata\n";
	}else{
	echo "nel db affettate $line linee\n";	
	}
	
	
	
	if(unlink("/wd/5/cache/$file")){
		echo "file cancellato in /wd/5/cache/$file \n";
	}else{
		echo "file NON cancellato in /wd/5/cache/$file\n";
	}
	
	
	
	
	
	
	
	
	
	

} else {
	echo "io cancello i file dalla cache e dal db, ma se non mi dici quale io non cancello nulla!";
}
