<?php
require( 'config.php' );

//devolver json con nuevos posibles tags. variable termino y excepciones definidas en GET

if( !isset( $_REQUEST['termino'] ) || !isset( $_REQUEST['excepciones'] ) ) {
        die( json_encode( Array(
                        "type" => "error",
                        "msg" => "Error de conexion?"
        ) ) );
}

$palabra = $_REQUEST['termino'];
$excepciones = array_map( 'trim', explode( ',', $_REQUEST['excepciones'] ) );
$newSugerencias = getNewSugerencias( $palabra, $excepciones, 3 );
die( json_encode( $newSugerencias ) );


