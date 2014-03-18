<?php
ini_set( 'include_path', ini_get('include_path').PATH_SEPARATOR.'../lib/' );
require_once( 'InfoNucleo.php' );
require( '../include/logLib.php' );
require( '../include/functions.php' );

ini_set( 'session.use_cookies', 0 );
ini_set( 'magic_quotes_runtime', 0 );
ini_set( 'magic_quotes_gpc', 0 );

$firma = base64_decode( $_POST[ 'firma' ] );
unset( $_POST[ 'firma' ] );

$public_key = openssl_pkey_get_public( file_get_contents( InfoNucleo::getParametro( 'ucomparte', 'ruta_upasaporte' ).'/certificado' ) );
$result = openssl_verify( array_reduce( $_POST, create_function( '$a,$b', 'return $a.$b;' ) ), $firma, $public_key );

openssl_free_key( $public_key );

if( ! $result ) exit( '-1' );

session_start();

//Agrego todo lo del POST a la sesion
$_SESSION = $_POST + Array( 'valido' => TRUE );

//Defino el path de la foto y la alojo en mi servidor
if( isset( $_POST['img'] ) ) {
	$_SESSION[ 'foto' ] = $_POST['img'];
}
//Despliego todas las carreras
if( isset( $_POST['carreras'] ) ) {
	$_SESSION[ 'carreras' ] = unserialize( urldecode( $_POST['carreras'] ) );
}

//Convierto desde iso a utf todos los datos
array_walk_recursive( $_SESSION, create_function( '&$a', '$a = UTIL::iso2utf( $a );' ) );	

//Limpio y ordeno la seleccion
fixSelection( $_SESSION );

/* Quizas esto que viene se puede hacer de mejor manera... VER EN PRODUCCION!!! */
//Ingreso el usuario a la base de datos y pongo en la session el id del usuario
$_SESSION += insertUser( $_SESSION );

exit( InfoNucleo::getParametro( 'ucomparte', 'ruta_web' ).'/login?'.session_name().'='.session_id() );
