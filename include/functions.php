<?php
//Falta implementar upsert... BBB

function getTopTags() {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );
	$sql = "
select TAG_ID as name, count( * ) as c
from TAGS
where TAG_ESTADO = 1
group by TAG_ID
order by c desc
limit 0,10
";

	$res = $link->query( $sql );
	if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
		LOGGER::sql_error( $servicio, $sql );
		exit( 'Error en la BD' );
	}

	$retorno = Array();
	while( $row = $res->fetchRow( DB_FETCHMODE_ASSOC ) ) {
		$retorno[] = $row;
	}
	
	return $retorno;	
}

//testea la cantidad de usuarios que hay en la base de datos con el rut dado
function testUser( $rut ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );
	$rut = intval( $rut );

	$sql = "
select count( distinct( USU_RUT ) ) as number
from USUARIOS
where USU_RUT = $rut
";

	$res = $link->query( $sql );
	if( DB::isError( $res ) ) exit( 'Error en la base de datos' );

	$row = $res->fetchRow( DB_FETCHMODE_ASSOC );

	return $row[ 'number' ];
}


//ingresa usuario a la base de datos... Retorna el id en la DB del usuario
function insertUser( $data ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );
	
	$now = time();
	$rut = intval( $data['rut'] );
	$cv = UTIL::dv( $rut );
	if( $cv == 'K' ) {
		$cv = 11;
	} 
	$nombre1 = $link->escapeSimple( $data['nombre1'] );	
	$apellido1 = $link->escapeSimple( $data['apellido1'] );
	$alias = $link->escapeSimple( $data['alias'] );
	$foto = "";
	if( isset( $data['foto'] ) ) {
		$foto = $link->escapeSimple( $data['foto'] );
	}
	
	$sql_usuario = "
insert into USUARIOS ( USU_RUT, USU_RUT_CV, USU_NOMBRE, USU_APELLIDO, USU_ALIAS, USU_ESTADO, USU_FOTO, USU_FECHA_C, USU_FECHA_M)
values ( $rut, $cv, '$nombre1', '$apellido1', '$alias', 1, '$foto', $now, $now )
on duplicate key update USU_NOMBRE = values( USU_NOMBRE ), USU_APELLIDO = values( USU_APELLIDO ), USU_ALIAS = values( USU_ALIAS ), USU_ESTADO = USUARIOS.USU_ESTADO, USU_FOTO = values( USU_FOTO ), USU_FECHA_M = values( USU_FECHA_M )
";
	
	$res_usuario = $link->query( $sql_usuario );
	if( DB::isError( $res_usuario ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql_usuario );
		exit( 'Error en la base de datos' );
	}

	$usu_id = intval( mysqli_insert_id( $link->connection ) );

	return Array(
		'id' => $usu_id
	);	
}

//Ordena los cursos en los que son actuales y antiguos
function fixSelection( &$session ) {
	
	$ano = date( 'Y' );
        $semestre = 'Otoño'; //Otoño, Primavera, Verano
        $actuales = Array();
	$antiguos = Array();

        foreach( $session['cursos'] as $k=>$v ) {
                if( $v['semestre'] == $semestre && $v['ano'] == $ano )
                        $actuales[] = $v;
		else
			$antiguos[] = $v;
        }

	$session['cursos'] = Array(
		'actuales' => $actuales,
		'antiguos' => $antiguos
	);
}

//Ingresa los datos de un archivo a la DB, retorna el id del archivo en la DB
function uploadFile( $user_id, $file_name, $file_ext, $file_size, $file_path, $file_md5 ) {

	$eliminado = isSameFileDeleted( $file_md5 );
	$link = InfoNucleo::getConexionDB( 'ucomparte' );

	$now = time();
	$id = intval( $user_id );
	$name = $link->escapeSimple( $file_name );
	$size = $link->escapeSimple( $file_size );
	$path = $link->escapeSimple( $file_path );
	$md5 = $link->escapeSimple( $file_md5 );
	$ext = $link->escapeSimple( $file_ext );

	$sql = "
insert into ARCHIVOS ( ARC_USU_ID, ARC_TITULO, ARC_EXTENSION, ARC_TAMANO, ARC_PATH, ARC_MD5, ARC_ESTADO, ARC_TOCADO, ARC_FECHA_C, ARC_FECHA_M )
values ( $id, '$name', '$ext', '$size', '$path', '$md5', 1, 0, $now, $now )
";

	if( $eliminado ) {
		$sql = "
update ARCHIVOS
set ARC_USU_ID = $id, ARC_TITULO = '$name', ARC_EXTENSION = '$ext', ARC_TAMANO = '$size', ARC_PATH = '$path', ARC_ESTADO = 1, ARC_TOCADO = 0, ARC_FECHA_C = $now, ARC_FECHA_M = $now
where ARC_MD5 = '$md5'
";
	}

	$sql_arc_id = "
select ARC_ID as id, ARC_FECHA_C as fecha
from ARCHIVOS
where ARC_MD5 = '$md5'
";

	$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
        $res = $link->query( $sql );
        if( DB::isError( $res ) && $eliminado ) {
                LOGGER::sql_error( $servicio, $sql );
		return Array(
			'msg' => 'Error en la peticion. Bug!?',
			'type' => 'error'
		);
	}

	$res_arc_id = $link->query( $sql_arc_id );
	if( DB::isError( $res_arc_id ) ) {
                LOGGER::sql_error( $servicio, $sql );
		return Array(
			'msg' => 'Error en la base de datos.',
			'type' => 'error'
		);
	}
	$row = $res_arc_id->fetchRow( DB_FETCHMODE_ASSOC );

	
	if ( DB::isError( $res ) && !$eliminado ) {
		return Array(
                        'msg' => 'El archivo '.$name.' ya existe en U-Comparte.',
                        'type' => 'duplicado',
			'id' => $row['id']
                );
	}

	LOGGER::write( $servicio, $id, $row['id'], 'INSERT_FILE' ); 
        return $row + Array(
		'type'	=> 'success',
		'msg'	=> 'Archivo subido correctamente',
	);
}

//Esta el archivo en la base de datos?
function isFile( $file_md5 ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );

        $md5 = $link->escapeSimple( $file_md5 );

        $sql = "
select count( distinct( ARC_ID ) ) as number
from ARCHIVOS
where ARC_MD5 = '$md5' 
";
        $res = $link->query( $sql );
        if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		exit( 'Error en la base de datos' );
	}

        $row = $res->fetchRow( DB_FETCHMODE_ASSOC );

        return $row[ 'number' ] > 0;
}

function isSameFileDeleted( $file_md5 ) {
        $link = InfoNucleo::getConexionDB( 'ucomparte' );

        $md5 = $link->escapeSimple( $file_md5 );

        $sql = "
select count( distinct( ARC_ID ) ) as number
from ARCHIVOS
where ARC_MD5 = '$md5'
and ARC_ESTADO = 0
";
        $res = $link->query( $sql );
        if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		exit( 'Error en la base de datos' );
	}

        $row = $res->fetchRow( DB_FETCHMODE_ASSOC );

        return $row[ 'number' ] > 0;
}


function getFileByUserId( $user_id ) {
        $link = InfoNucleo::getConexionDB( 'ucomparte' );

	$user_id = intval( $user_id );

        $sql = "
select ARC_ID as id
from ARCHIVOS
where ARC_USU_ID = $user_id
and ARC_ESTADO = 1
order by ARC_ID desc
";

        $res = $link->query( $sql );
        if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		exit( 'Error en la BD' );
	}

        $retorno = Array();
        while( $row = $res->fetchRow( DB_FETCHMODE_ASSOC ) ) {
                $retorno[] = $row['id'];
        }

        return $retorno;
}
function getTopFiles() {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );
	$sql = "
select ARC_ID id
from ARCHIVOS
where ARC_ESTADO = 1
order by ARC_DESCARGAS desc
limit 0, 25
";

	$res = $link->query( $sql );
	if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		exit( 'error' );
	}

	$retorno = Array();
	while( $row = $res->fetchRow( DB_FETCHMODE_ASSOC ) ) {
		$retorno[] = $row['id'];
	}
	
	return $retorno;
}

function getAllFiles() {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );
        $sql = "
select ARC_ID id
from ARCHIVOS
where ARC_ESTADO = 1
order by ARC_DESCARGAS desc
";

        $res = $link->query( $sql );
        if( DB::isError( $res ) ) {
                $servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
                exit( 'error' );
        }

        $retorno = Array();
        while( $row = $res->fetchRow( DB_FETCHMODE_ASSOC ) ) {
                $retorno[] = $row['id'];
        }

        return $retorno;
}

function getFileByTagList( $tags, $and = TRUE ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );
	
	$cantidad = count( $tags );
	$tags = array_map( array( $link, 'escapeSimple' ), $tags );

	$restriccion = implode( "','", $tags );

        $sql = "
select ARC_ID as id
from ARCHIVOS, TAGS
where ARC_ID = TAG_ARC_ID
and TAG_ESTADO = 1
and ARC_ESTADO = 1
and TAG_ID in ( '$restriccion' )
group by ARC_ID ".( $and ? "having count( TAG_ID ) = $cantidad" : "" )."
order by ARC_DESCARGAS desc
";

	$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
	$res = $link->query( $sql );
        if( DB::isError( $res ) ) {
                LOGGER::sql_error( $servicio, $sql );
		exit( "error en la DB" );
	}

        $retorno = Array();
        while( $row = $res->fetchRow( DB_FETCHMODE_ASSOC ) ) {
                $retorno[] = $row['id'];
        }

	if( $servicio != 'START' ) {
		LOGGER::write( $servicio, $_SESSION['id'], 0, 'SEARCH', $tags );
	}
        return $retorno;

}

function getSugerencias( $clave, $number ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );

	$clave = $link->escapeSimple( $clave );
	$number = intval( $number );

	$condicion = makeQuery( $clave );

	$sql = "
select TAG_ID as name, count( TAG_ID ) as weight
from TAGS
where $condicion
and TAG_ESTADO = 1
group by TAG_ID
order by weight desc
limit 0,$number
";

	$res = $link->query( $sql );
	if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		return( 'Error en la BD' );
	}

	$retorno = Array();
        while( $row = $res->fetchRow( DB_FETCHMODE_ASSOC ) ) {
                $retorno[] = $row['name'];
        }

        return $retorno;
}


function getTopTagsByFileList( $filelist ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );

	$filelist = array_map( 'intval', $filelist );
	$txt_arc_id = implode( ",", $filelist );

	$sql = "
select TAG_ID name
from TAGS
where TAG_ARC_ID in ( $txt_arc_id )
and TAG_ESTADO = 1
group by TAG_ID
order by count(*) desc
limit 10
";

	$res = $link->query( $sql );
	if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		exit( "Error en la DB" );
	}
	
	$retorno = Array();
	while( $row = $res->fetchRow( DB_FETCHMODE_ASSOC ) ) {
		$retorno[] = $row['name'];
	}

	return $retorno;
}

function getTagsWeight( $tags ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );

	$restriccion = "";
	foreach( $tags as $k => $v ) {
		$v = $link->escapeSimple( $v );
		$tags[$k] = $v;
	}
        $restriccion = implode( "','", $tags );

        $sql = "
select TAG_ID name, count(*) as c
from TAGS
where TAG_ID in ( '$restriccion' )
group by TAG_ID
";

	$res = $link->query( $sql );
        if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		exit( "Error en la DB" );
	}

        $retorno = Array();
        while( $row = $res->fetchRow( DB_FETCHMODE_ASSOC ) ) {
                $retorno[] = $row;
        }

        return $retorno;
}

function getAliasByUserId( $id ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );

	$usu_id = intval( $id );

	$sql = "
select USU_ALIAS alias
from USUARIOS
where USU_ID = $usu_id
";

	$res = $link->query( $sql );
	if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		exit( "Error en la DB" );
	}

	$retorno = "";
	while( $row = $res->fetchRow( DB_FETCHMODE_ASSOC ) ) {
		$retorno = $row['alias'];
	}

	return $retorno;
}

function getDataByUserId( $id ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );

	$usu_id = intval( $id );

	$sql = "
select USU_ID id, USU_RUT rut, USU_NOMBRE nombre, USU_APELLIDO apellido, USU_ALIAS alias, USU_FOTO foto, USU_FECHA_C fecha_c, USU_FECHA_M fecha_m
from USUARIOS
where USU_ID = $usu_id
";

	$res = $link->query( $sql );
	if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		exit( "Error en la DB" );
	}

	$retorno = array();
	while( $row = $res->fetchRow( DB_FETCHMODE_ASSOC ) ) {
		$retorno = $row;
	}

	return $retorno;
}

function getDataByFileList( $files ) {

	if( !$files ) return $files;

	$link = InfoNucleo::getConexionDB( 'ucomparte' );

	$files = array_map( 'intval', $files );
	$txt_arc_id = implode( ",", $files );

	$sql = "
select ARC_ID id, ARC_USU_ID usu_id, ARC_TITULO titulo, ARC_EXTENSION ext, ARC_TAMANO tamano, ARC_PATH path, ARC_MD5 md5, ARC_DESCARGAS descargas, ARC_TOCADO tocado, ARC_FECHA_C fecha
from ARCHIVOS
where ARC_ID in ( $txt_arc_id )
and ARC_ESTADO = 1
";

	$res = $link->query( $sql );
	if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		exit( "Error en la DB" );
	}

	$retorno = Array();
	while( $row = $res->fetchRow( DB_FETCHMODE_ASSOC ) ) {
		$row['usu_alias'] = getAliasByUserId( $row['usu_id'] );
                $retorno[] = $row;
        }

	/* Esto para devolver el arreglo tal cual como llego */
	$sortedRetorno = Array();
	$flipped = array_flip( $files );
	foreach( $retorno as $k => $v ) {
		$sortedRetorno[ $flipped[ $v[ 'id' ] ] ] = $v;
	}
	ksort( $sortedRetorno );
	/* */

        return $sortedRetorno;
}

function getAllTagsByFileId( $file_id ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );

	$id = intval( $file_id );

	$sql = "
select TAG_ID as tag
from TAGS
where TAG_ARC_ID = $id
and TAG_ESTADO = 1
";	

	$res = $link->query( $sql );
	if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		exit( "Error en la DB" );
	}

	$retorno = Array();
	while( $row = $res->fetchRow( DB_FETCHMODE_ASSOC ) ) {
		$retorno[] = $row['tag'];
	}

	return $retorno;
}

function uploadTagsByFileId( $id, $tags ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );

	$id = intval( $id );
	$now = time();
	$values = array();	
	foreach( $tags as $k => $v ) {
                $v = trim( $link->escapeSimple( $v ) );
		if( $v !== "" ) {
			$values[] = "( '$v', $id, 1, $now, $now )";
		}
        }
	$values = implode( ",", $values );	

	$sql = "
insert into TAGS ( TAG_ID, TAG_ARC_ID, TAG_ESTADO, TAG_FECHA_C, TAG_FECHA_M )
values $values	 
on duplicate key update TAG_ESTADO = values( TAG_ESTADO ), TAG_FECHA_M = values( TAG_FECHA_M )
";

	$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
	$res = $link->query( $sql );
	if( DB::isError( $res ) ) {
                LOGGER::sql_error( $servicio, $sql );
		return Array(
                        'msg' => 'Error en la base de datos.',
                        'type' => 'error',
			'sql' => $sql
                );
	}

	LOGGER::write( $servicio, $_SESSION['id'], $id, 'INSERT_TAGS', $tags );
	return Array(
		'msg' => 'Bien!',
		'type' => 'succes',
		'sql' => $sql
	);
}

function removeFile( $id, $userId ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );

	$id = intval( $id );
	$userId = intval( $userId );

	$sql = "
select USU_PERMISO
from USUARIOS
where USU_ID = $userId
";

	$res = $link->query( $sql );
	if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		return Array(
			'msg' => 'Error al borrar el archivo',
			'type' => 'error'
		);
	}
	$permiso = $res->fetchRow( DB_FETCHMODE_ASSOC ) == 1;

	$sql = "
update ARCHIVOS
set ARC_ESTADO = 0
where ARC_ID = $id
";
	if( !$permiso ) {
		$sql .= " and ARC_USU_ID = $userId";
	}

	$res = $link->query( $sql );
	if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		return Array(
			'msg' => 'Error al borrar el archivo',
			'type' => 'error',
			'sql' => $sql
		);
	}
	
	if( $link->affectedRows() == 0 ) {
		return Array(
			'msg' => 'Sin permisos para borrar el archivo',
			'type' => 'error'
		);
	}

	return Array(
		'type' => 'success',
		'msg' => 'cool'
	);
}

function makeQuery( $input ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );
	$campos = array( 
		'seccion' 	=> 'number',
		'profesor' 	=> 'string',
		'ramo' 		=> 'string',
		'nombre' 	=> 'string',
		'semestre' 	=> 'number',
		'ano' 		=> 'number'
	);
	$condiciones = array();
	$having = array();
	$palabras = preg_split( "/\s+/", trim( $input ), -1, PREG_SPLIT_NO_EMPTY );
	foreach( $palabras as $palabra ) {
		list( $campo, $dato ) = explode( ':', $palabra, 2 );
		if( !$dato ) {
			$dato = $campo;
			$campo = '';
		}
		$tmp = "";
		$q = preg_split( '/[\/,]+/', $dato, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_OFFSET_CAPTURE );
		foreach( $q as $k => $p ) {
			$like  = 'like';
			if( $p[0][0] == '-' ) {
				$like = 'not like';
				$p[0] = substr( $p[0], 1 );
			}
			$v = ( $campo ? $link->escapeSimple( $campo ).":" : "" ).( $campos[$campo] == 'number' ? intval( $p[0] ) : $link->escapeSimple( $p[0] ) );
			if( $k !== 0 )
				$tmp .= ( $dato[$p[1]-1] == ',' ? " and ":" or " );	
			$tmp .= "TAG_ID $like '%$v%'";
		}
		$condiciones[] = "( $tmp )";
	}
	if( !$condiciones ) 
		return "TRUE";
	return implode( " and ", $condiciones );
}

function getCommentsByFileId( $file_id ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );
	
	$file_id = intval( $file_id );

	$sql = "
select COM_MENSAJE as mensaje, COM_FECHA_M as fecha, COM_USU_ID as usuario
from COMENTARIOS
where COM_ARC_ID = $file_id
order by fecha
";

	$res = $link->query( $sql );
	if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		exit( 'error DB' );
	}

	$retorno = array();

	//ineficiente... cambiar a lo que hago ahora
	while( $row = $res->fetchRow( DB_FETCHMODE_ASSOC ) ) {
		$row['usuario'] = getDataByUserId( $row['usuario'] );
		$retorno[] = $row;
	}

	return $retorno;
}

function setComment( $file_id, $user_id, $mensaje ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );

	$file_id = intval( $file_id );
	$usu_id = intval( $user_id );
	$mensaje = $link->escapeSimple( $mensaje );
	$now = time();

	$sql = "
insert into COMENTARIOS ( COM_USU_ID, COM_ARC_ID, COM_ESTADO, COM_MENSAJE, COM_FECHA_C, COM_FECHA_M )
values ( $usu_id, $file_id, 1, '$mensaje', $now, $now )
";

	$res = $link->query( $sql );
	if( DB::isError( $res ) ) {
		$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
                LOGGER::sql_error( $servicio, $sql );
		exit( 'DB Error' );
	}	

	$comment_id = intval( mysqli_insert_id( $link->connection ) );
	
	return array( 'id' => $comment_id );
}

function deleteTag( $tag, $fileId, $userId ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );

	$fileId = intval( $fileId );
	$userId = intval( $userId );
	$tag = $link->escapeSimple( $tag );

	$sql = "
update TAGS
set TAG_ESTADO = 0
where TAG_ID = '$tag'
and TAG_ARC_ID = $fileId
";

	$servicio = $_SESSION['servicio'] ? $_SESSION['servicio'] : ' ';
	$res = $link->query( $sql );
	if( DB::isError( $res ) ) {
                LOGGER::sql_error( $servicio, $sql );
		exit( array(
			'type' => 'error',
			'msg' => $sql
		) );
	}

	LOGGER::write( $servicio, $userId, $fileId, 'DELETE_TAG', $tag );
	return array(
		'type' => 'success',
		'msg' => $sql
	);
}

function increaseDownloads( $file_id ) {
	$link = InfoNucleo::getConexionDB( 'ucomparte' );

	$fileId = intval( $file_id );

	$sql = "
update ARCHIVOS
set ARC_DESCARGAS = ARC_DESCARGAS+1
where ARC_ID = $fileId
";
	$res = $link->query( $sql );
        if( DB::isError( $res ) ) {
                LOGGER::sql_error( $servicio, $sql );
                exit( "Error en la DB" );
        }
}

function size2bytes( $str ) {
	$mult = Array(
		'b' => 1,
		'k' => 1024,
		'm' => 1024*1024,
		'g' => 1024*1024*1024,
		't' => 1024*1024*1024*1024
	);
	$l = mb_strtolower( mb_substr( $str, mb_strlen( $str ) -1 ) );
	$n = floatval( mb_substr( $str, 0, mb_strlen( $str ) -1 ) );
	return intval( $n*$mult[$l] );
}
