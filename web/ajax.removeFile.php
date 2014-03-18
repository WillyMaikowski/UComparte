<?php
require( 'config.php' );
if( !isset( $_POST['fileId'] ) ) {
	exit( 
		json_encode( 
			Array(
				'type' => 'error',
				'msg' => 'id no enviado'
			)
		) 
	);
}

$id = intval( $_POST['fileId'] );
$userId = intval( $_POST['userId'] );
$res = removeFile( $id, $userId );

exit( 
	json_encode( $res )
);

