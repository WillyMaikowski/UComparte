<?php
ini_set( 'include_path', ini_get('include_path').PATH_SEPARATOR.'../lib/' );
require( 'InfoNucleo.php' );
require( '../include/functions.php' );
session_id( $_GET[ session_name() ] );
session_start();
$_SESSION[ 'valido' ] ? KERNEL::redirect( 'start' ) : KERNEL::redirect('index');




