<?php
require( 'config.php' );

//Ajax para subir un archivo
//print_a( $_FILES );

if( $_FILES['file']['error'] ) {
        exit( json_encode( Array( 
			"type" => "error",  
			"msg" => var_export( $_FILES, TRUE )
	) ) );
}

if( ! $path_archivos = FS::mkdir( InfoNucleo::getParametro( 'ucomparte', 'ruta_archivos' ), 0777 ) ) { //apache no puede crear una carpeta en mi home
        exit( json_encode( Array(
                        "type" => "error",
                        "msg" => "Error al crear la carpeta principal"
        ) ) );
}

$name = $_FILES['file']['name'];
$ext = pathinfo( $name, PATHINFO_EXTENSION );

$tmp_name = $_FILES['file']['tmp_name'];
$md5 = md5_file( $tmp_name );
$sh = substr( $md5, 0, 2 );
if( $md5 == '' || $sh == '' ) {
	exit( json_encode( Array(
                        "type" => "error",
                        "msg" => "Error con el archivo"
        ) ) );
}
if( ! $pathdir = FS::mkdir( $path_archivos.$sh.'/', 0777 ) ) {
        exit( json_encode( Array(
                        "type" => "error",
                        "msg" => "Error al crear el path del archivo"
        ) ) );
}
$finalPath = FS::move( $tmp_name, $pathdir.$md5.".$ext", 0777 );
if( !$finalPath  ) {
	exit( json_encode( Array( 
                        "type" => "error",  
                        "msg" => "Error interno, no se pudo mover el archivo"
        ) ) );
}

//Bien... Subiendo archivo
$row = uploadFile( $_SESSION['id'], $name,  $ext, UTIL::toSize( $_FILES['file']['size'] ), $finalPath, $md5 );

exit( json_encode( $row ) );

