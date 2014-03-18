<?php
require( 'config.php' );
if( !$puede_ver ) KERNEL::error( ERR_SIN_PERMISO );
$_SESSION['servicio'] = 'ARCHIVO';

if( !isset( $_REQUEST[ 'id' ] ) ) {
	KERNEL::redirect( 'start' );
}

$file_id = intval( $_REQUEST['id'] );

if( isset( $_REQUEST[ 'comentario' ] ) ) {
	$comentario = trim( $_REQUEST['comentario'] );
	if( $comentario == "" || mb_strlen( $comentario, 'UTF-8' ) < 3 || mb_strlen( $comentario, 'UTF-8' ) > 300 ) {
		KERNEL::mensaje( '-El comentario debe contener entre 3 y 300 carcteres.' );
	}
	else {
		$set = setComment( $file_id, $_SESSION['id'], $comentario  );
		if( isset( $set['id'] ) ) 
			KERNEL::mensaje( '+Comentario subido exitosamente' );
		else
			KERNEL::mensaje( '-Un error ha ocurrido, intentalo nuevamente' );
	}
}
else if( isset( $_REQUEST['tags'] ) ) {
	$tags = preg_split( "/\s+/", $_REQUEST['tags'], -1, PREG_SPLIT_NO_EMPTY );
	if( !$tags ){
		KERNEL::mensaje( '-No se permite el tag vacio. Ingresa un tag valido' );
	}
	else {
		$set = uploadTagsByFileId( $file_id, $tags );
		if( $set['type'] == "succes" )
			KERNEL::mensaje( '+Tags asociados correctamente' );
		else
			KERNEL::mensaje( '-Un error ha ocurrido, intentalo nuevamente' );	 
	}
}

//Que fea implementacion... mejorala para ahorrar conexiones a la db
$data = getDataByFileList( Array( $file_id ) );
$file_data = $data[0];
$file_data['tags'] = getAllTagsByFileId( $file_id );
$file_data['comentarios'] = getCommentsByFileId( $file_id ); 
//print_a( $file_data ); exit();

include( template( '../template/head.html' ) );
include( template( '../template/archivo.html' ) );
include( template( '../template/foot.html' ) );
