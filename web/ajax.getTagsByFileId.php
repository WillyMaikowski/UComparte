<?php
require( 'config.php' );

if( !isset( $_REQUEST['id'] ) ) {
	exit( json_encode(
		Array(
			'type' => 'error',
			'msg' => 'error de conexion'
		)
	) );
}

$id = intval( $_REQUEST['id'] );
$tags = getAllTagsByFile( $id );

exit( json_encode(
	Array(
		'tags' => $tags,
		'type' => 'success'
	)
) ); 
