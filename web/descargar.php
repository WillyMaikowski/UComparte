<?php
require( 'config.php' );
if( ! $puede_ver ) KERNEL::error( ERR_SIN_PERMISO );
$_SESSION['servicio'] = 'DESCARGAR';

if( !isset( $_REQUEST['id'] ) ) {
	KERNEL::redirect( 'start' ); 
}

$id = intval( $_REQUEST['id'] );
$fileArray = Array( $id );
$fileData = getDataByFileList( $fileArray );
if( empty( $fileData ) ) {
	KERNEL::redirect( 'busqueda', 'El archivo que esta buscando no existe' ); 
}

$filePath = $fileData[0]['path'];
$fileForceName = $fileData[0]['titulo'];
increaseDownloads( $id );
//Aumentar descargas :)
FS::streamFile( $filePath, 'attachment', $fileForceName );
