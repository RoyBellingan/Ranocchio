<?php

//Le condizioni se NON divesamente indicato sono sempre:
//SERVEROTTO : velocità LIMITATA
//SCARICHINO : velocità ILLIMITATA


// ************** Primo test:
/*
 Richiedi un file e scaricalo.

 Edito:
 File scaricato e file nella cache

 */

//Secondo test
/*

 Richiedi lo stesso file
 Esito File scaricato in istant...

 */

//Terzo Test
/*
 Richiedi un file, scaricane un pezzetto e annulla
 
 Esito:
Il file continua ad essere scaricato e dopo poco sarà pronto nella cache...
 
 
 */
//Quarto Test
 /*
 Scarica il file del test 3
 
 Esito:
 Il file è servito in istant.
 
 */

//Quinto Test
/*
 Processo uno richiede il file, dopo pochi secondi processo due richiede lo stesso
 
 Esito:
 Processo uno scarica alla velocità normale fino alla fine, processo due parte instant, raggiunge Processo uno e poi 
 prosegue a velocità normale. 
 
 
 */

//Sesto Test
/*
 Processo uno richiede il file, dopo decine di secondi processo due richiede lo stesso, 
 dopo un poco SERVEROTTO non riesce più a scaricare, si ferma e poi riparte dopo un poco, (scarica a tratti), ovvero simula un problema di rete...
 
 ESITO:
 Processo uno scarica normale fino a intopparsi, processo due scarica instant una parte, poi va a velocità normale poi si ferma.
 Entrambi poi scaricheranno a tratti...
 
 */