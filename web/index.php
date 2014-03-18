<?php
require( 'config.php' );

if( $_SESSION[ 'valido' ] ) KERNEL::redirect( 'start' );

$ruta_upasaporte = InfoNucleo::getParametro( 'ucomparte', 'ruta_upasaporte' );

include( template( '../template/index.html' ) );
