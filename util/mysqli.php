<?php
$db_host="localhost";
$db_user="mokba";
$db_password="";
$db_name="mokba";
$db = new mysqli($db_host, $db_user, $db_password, $db_name);

/** Usata di solito per le stored procedure, che ne posso avere solo una alla volta attiva
 */  
function new_mysqli(){
	GLOBAL $db,$db_host,$db_user,$db_password,$db_name;
	$db= new mysqli($db_host, $db_user, $db_password, $db_name);
	return $db;
}


/* check connection */
if ($db->connect_error) {
	printf("Connect failed: %s\n", mysqli_connect_error());

	$errori->add("impossibile collegarsi al db!",3,0);

	exit();
}



/**Fa la query e mi ritorna l'errore
 * @param unknown_type $sql
* @param unknown_type $db
*/
function qq($sql,$db=false){

	//echo $sql."<br>";
	if ($db===false){
		GLOBAL $db;
	}

	$res=$db->query($sql);


	if ($db->error){
		echo "\n<br> \n<br>".$db->error."\n<br>";
		$err=true;

		unset($db->error);

		$res= false;
	}
	return $res;
}


/**Fa la query di inserimento e mi ritorna l'errore o l'id di inserimento
 * @param unknown_type $sql
* @param unknown_type $db
*/
function qi($sql,$db=false){

	//echo $sql."<br>";
	if ($db===false){
		GLOBAL $db;
	}

	$res=$db->query($sql);


	if ($db->error){
		echo "\n<br> \n<br>".$db->error."\n<br>";
		$err=true;

		unset($db->error);

		return false;
	}
	//print_r($db);
	return $db->insert_id;
}




/**Fai la query e dai i risultati
 * RICORDA!
 * ANCHE se richiedi un solo valore è sempre un ARRAY!!!
 * se vuoi l'array "normale" chiama invece la funziona qr_proper($sql)
 *
 * @param query $sql
* #param defaul MYSQLI_NUM oppure MYSQLI_ASSOC e MYSQLI_BOTH
* @param un db fra tanti... oppure legge l'unico $db
*/
function qr($sql,$asso=MYSQLI_BOTH,$db=false){


	if ($db===false){
		GLOBAL $db;
	}

	$res=$db->query($sql);

	if ($db->error){
		echo "<br>\n $sql \n<br>".$db->error."\n<br>";
		$err=true;

		unset($db->error);

		return false;
	}else{
		$val=$res->fetch_all($asso);
		return $val;
	}
}



/**
 Fai la query e dai i risultati
* RICORDA! usa una singola colonna!!!!
* ritorna questo
    [0] => 31.7.176.2
    [1] => 31.7.176.3
    [2] => 31.7.176.4
    [3] => 31.7.176.5
    [4] => 31.7.176.6

    invece che il


    (
    [0] => Array
        (
            [0] => 31.7.176.2
        )

    [1] => Array
        (
            [0] => 31.7.176.3
        )

    [2] => Array
        (
            [0] => 31.7.176.4
        )

    [3] => Array
        (
            [0] => 31.7.176.5
        )

    [4] => Array
        (
            [0] => 31.7.176.6


* @param query $sql
* #param defaul MYSQLI_NUM oppure MYSQLI_ASSOC e MYSQLI_BOTH
* @param un db fra tanti... oppure legge l'unico $db
*/
function qr_proper($sql,$asso=MYSQLI_BOTH,$db=false){


	if ($db===false){
		GLOBAL $db;
	}

	$res=$db->query($sql);

	if ($db->error){
		echo "<br>\n $sql \n<br>".$db->error."\n<br>";
		$err=true;

		unset($db->error);

		return false;
	}else{
		$val=$res->fetch_all($asso);


		$b=array();
		foreach ($val as $value) {
			$b[]=$value[0];
		}
		return $b;

	}
}

/**Fai la query e dai il primo risultato
 * @param query $sql
* @param un db fra tanti... $db
*/
function qx($sql,$db=false){


	if ($db===false){
		GLOBAL $db;
	}

	$res=$db->query($sql);

	if ($db->error){
		echo "<br>\n<br> $sql \n<br>".$db->error."\n<br>";
		$err=true;

		unset($db->error);

		return false;
	}else{
		$val=$res->fetch_row();
		return $val[0];
	}
}

/**Fai la query e dai la prima riga
 * @param query $sql
* @param un db fra tanti... $db
*/
function qrow($sql,$asso=MYSQLI_BOTH,$db=false){


	if ($db===false){
		GLOBAL $db;
	}

	$res=$db->query($sql);

	if ($db->error){
		echo "<br>\n<br> $sql \n<br>".$db->error."\n<br>";
		$err=true;

		unset($db->error);

		return false;
	}else{
		$val=$res->fetch_all($asso);

			$b=$val[0];
		return $b;

	}
}

/**Chiude la connessione
 * utile per quando uso i forkkkk
 */
function qclose(){
	GLOBAL $db;
	$db->close();
}
