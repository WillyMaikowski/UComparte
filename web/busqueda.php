<?php
require( 'config.php' );
if( ! $puede_ver ) KERNEL::error( ERR_SIN_PERMISO );
$_SESSION['servicio'] = 'BUSQUEDA';

$searchTags = Array();
$clave = "";
if( isset( $_REQUEST['clave'] ) ) {
	$clave = $_REQUEST['clave'];
	$searchTags = preg_split( "/\s+/", $clave, -1, PREG_SPLIT_NO_EMPTY );
}
$tags = getTopTags();


$archivos = $searchTags ? getFileByTagList( $searchTags ) : getAllFiles();

if( isset( $_REQUEST['id'] ) ) {
	$id = intval( $_REQUEST['id'] );
	if( $id ) {
		$archivos = Array( 0 => $id );
	}
}

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

$archivos = getDataByFileList( $archivos );
foreach( $archivos as $k => $v ) {
	$archivos[$k]['tags'] = getAllTagsByFileId( $v['id'] );
}



include( template( '../template/head.html' ) );
include( template( "../template/busqueda.html" ) );
include( template( '../template/foot.html' ) );
