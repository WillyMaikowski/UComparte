<?php
require( 'config.php' );
if( !$puede_ver ) KERNEL::error( ERR_SIN_PERMISO );
$_SESSION['servicio'] = 'START';
$toptags = getTopTags();

foreach( $_SESSION['cursos']['actuales'] as $k => $v ) {
	$taglist[$k] = array_merge( array($v['codigo']),  preg_split( '/((((\s+)(a|e|de|al|la|y|o|en|con|para|un|una))+)(\s+))|(\s+)/', $v['nombre'] ) );
	$archivos_in[$k] = getDataByFileList( array_slice( getFileByTagList( $taglist[$k], FALSE ), 0, 3 ) );
}

//print_a( $_SESSION ); exit();

include( template( '../template/head.html' ) );
include( template( '../template/start.html' ) );
include( template( '../template/foot.html' ) );
