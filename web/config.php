<?php
mb_internal_encoding( 'utf-8' );
ini_set( 'error_reporting', E_ALL ^ E_NOTICE );
ini_set( 'include_path', ini_get('include_path').PATH_SEPARATOR.'../lib/' );
define( 'SISTEMA', 'ucomparte' );
require_once( 'kernel.class.php' );
require( '../include/logLib.php' );
require( '../include/functions.php' );
require_once( 'xmlLib.php' );
require_once( 'fsLib.php' );

session_start();
/* En todas las paginas veo si quiere salir */
if( $_REQUEST[ 'salir' ] ) {
	$_SESSION = Array( 'valido' => FALSE );
	KERNEL::redirect( 'index' );
}
$puede_ver = $_SESSION['valido'] == 1;
