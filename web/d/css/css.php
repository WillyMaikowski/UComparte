<?php
header( "Content-type: text/css" );
$servicio = $_REQUEST["servicio"];



ob_start();

include( "$servicio.css" );

ob_end_flush();


