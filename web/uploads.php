<?php
require( 'config.php' );
if( ! $puede_ver ) KERNEL::error( ERR_SIN_PERMISO );
$_SESSION['servicio'] = 'UPLOADS';

$archivos = getFileByUserId( $_SESSION['id'] );
$max_file_size = size2bytes( ini_get( 'upload_max_filesize' ) );

/* Paginacion... */
$p = 1; $archPorPagina = 10;
if( isset( $_REQUEST['p'] ) ) {
        $p = intval( $_REQUEST['p'] );
}
$pMax = ceil( count( $archivos ) / $archPorPagina );
if( $pMax > 1 ) {
        if( $p > $pMax ) KERNEL::redirect( 'busqueda', 'No existe tal pagina' );
        $aux = array_chunk( $archivos, $archPorPagina );
        $archivos = $aux[$p-1];
}
$pBajo = $p-3>0 ? $p-3 : 1;
$pAlto = $p+3<=$pMax ? $p+3 : $pMax;
/* ...Paginacion */

$archivos = $archivos ? getDataByFileList( $archivos ) : $archivos;
foreach( $archivos as $k => $v ) {
	$archivos[$k]['tags'] = getAllTagsByFileId( $v['id'] );
}

//print_a( $archivos ); exit();

include( template( '../template/head.html' ) );
include( template( '../template/uploads.html' ) );
include( template( '../template/foot.html' ) );

