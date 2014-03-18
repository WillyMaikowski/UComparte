<?php
require( 'config.php' );

if( !isset( $_REQUEST['tag'] ) || !isset( $_REQUEST['fileId'] ) || !isset( $_REQUEST['userId'] ) ) {
	exit( json_encode( array(
		'type'	=> 'error',
		'msg' 	=> 'error de conexion'
	) ) );
}

$tag = $_REQUEST['tag'];
$fileId = intval( $_REQUEST['fileId'] );
$userId = intval( $_REQUEST['userId'] );

$del = deleteTag( $tag, $fileId, $userId );
exit( json_encode( $del ) );

