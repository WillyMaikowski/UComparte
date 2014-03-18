<?php
require( 'config.php' );

//devolver json con busqueda. variable clave definida en GET
if( !isset( $_REQUEST['clave'] ) ) {
        exit( json_encode( Array(
		0 => "Error. Sin clave" 
	) ) );
}
$clave = $_REQUEST['clave'];
$sugerencias = getSugerencias( $clave, 5 );
exit( json_encode( $sugerencias ) );
