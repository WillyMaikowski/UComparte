<?php
require( 'config.php' );
//Subir tags que vienen como un arreglo en "tags" a un archivo identificado con su id nombrado como "fileId"
if( !isset( $_REQUEST['tags'] ) || !isset( $_REQUEST['fileId'] ) ) {
        die( json_encode( Array(
                        "type" => "error",
                        "msg" => "Error de conexion?",
			"request" => $_REQUEST
        ) ) );
}

$fileId = intval( $_REQUEST['fileId'] );
$tags = $_REQUEST['tags']; 

$resultado = uploadTagsByFileId( $fileId, $tags ); 

die( json_encode( $resultado ) );
