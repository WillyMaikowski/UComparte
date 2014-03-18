
function init( usuId ) {
	var body = $( 'body' );
	var fileselect = $( '#fileselect' );
	var filecell = $( '.dragdrop' );
	fileselect.on( "change", { userId: usuId }, fileSelectHandler );
	
	//el explorador es mi amigo?
	var xhr = new XMLHttpRequest();
	if( xhr.upload ) {
		console.log( "* Haciendo los body.on " );
		body.on( "dragover", fileDragOver );
		body.on( "dragleave", fileDragLeave );
		body.on( "drop", { userId: usuId }, fileSelectHandler );
	}
	//Si no, no hago dragdrop
}

/* Estan lloviendo archivos, indica por ultimo donde arrojarlos */
function fileDragOver( e ) {
	e = e || event;
	e.preventDefault();
	e.stopPropagation();
	$( '.dragdrop' ).addClass( 'dragdrop-active' ).children( 'p' ).text( 'DRAG & DROP' ); 
}
function fileDragLeave( e ) {
	e = e || event;
	e.preventDefault();
	e.stopPropagation();
	var rect = this.getBoundingClientRect();
	var getXY = function getCursorPosition( ev ) {
		var x, y;
		if ( typeof ev.clientX === 'undefined' ) {
			// try touch screen
			x = ev.pageX + document.documentElement.scrollLeft;
			y = ev.pageY + document.documentElement.scrollTop;
			console.log( x+' '+y );
		}
		else {
			x = ev.clientX + document.body.scrollLeft + document.documentElement.scrollLeft;
			y = ev.clientY + document.body.scrollTop + document.documentElement.scrollTop;
			console.log( x+' '+y );
		}
		return { x: x, y : y };
	};
	var oe = getXY( e.originalEvent );
	// Check the mouseEvent coordinates are outside of the rectangle
	if (oe.x > rect.left + rect.width - 1 || oe.x < rect.left || oe.y > rect.top + rect.height - 1 || oe.y < rect.top) {
		$( '.dragdrop' ).removeClass( 'dragdrop-active' ).children( 'p' ).html( 'Arrastra los ficheros en la ventana o haz click <a href="#" onclick="doClick( event, \'#fileselect\' )">aqui</a>' );
	}
}
/* */


//Llego un archivo!! o varios!!
function fileSelectHandler( e ) {
	e = e || event;
	e.preventDefault();
	e.stopPropagation();
	$( '.dragdrop' ).removeClass( 'dragdrop-active' ).children( 'p' ).html( 'Arrastra los ficheros en la ventana o haz click <a href="#" onclick="doClick( event, \'#fileselect\' )">aqui</a>' );
	
	if( e.originalEvent.dataTransfer != undefined && e.originalEvent.dataTransfer.items != undefined ) {
		var items = e.originalEvent.dataTransfer.items;
		for( var i = 0; i < items.length; i++ ) {
			var entry = false;
			if( items[i].getAsEntry ) {
				entry = items[i].getAsEntry();
			}
			else if( items[i].webkitGetAsEntry ) {
				entry = items[i].webkitGetAsEntry();
			}

			if( entry ) {
				var tags = new Array();
				traverseFileTree( entry, e.data.userId, tags );
			}
		}
	}
	else {
		var files = e.target.files || e.originalEvent.dataTransfer.files;
		for( var i = 0; i < files.length; i++ ) { 
			var unique = (new Date).getTime();
			var tags = new Array();
			window.forks += 1;
			uploadFile( files[i], unique, e.data.userId, tags );	
		}
	}

}

function traverseFileTree( entry, userId, tags ) {
	var dirReader, i;
	if( entry.isFile ) {
		entry.file( function( file ) {
			if( $.trim( file.name ).indexOf( '.' ) != 0 ) {
				var unique = (new Date).getTime();
				window.forks += 1;
				uploadFile( file, unique, userId, tags );
			}
			else {
				console.log( 'archivos ocultos o no soportados' );
			}
		});
	}
	else if( entry.isDirectory ) {
		dirReader = entry.createReader();
		dirReader.readEntries( function( entries ) {
			for( i = 0; i < entries.length; i += 1 ) {
				traverseFileTree( entries[i], userId, tags.concat( new Array( entry.name.replace( /\s/, "_" ) ) ) );
			}
		});
	}
}

function parseFile( file, id ) {
	var ultimoPunto = file.name.lastIndexOf( '.' );
	var ext = file.name.substr( ultimoPunto+1 ).toLowerCase();
	var fileName = file.name.substring( 0, ultimoPunto ).length > 35 ? file.name.substr( 0, 35 )+'.'+ext : file.name;
	var tr = 	'<tr id="tr-'+id+'">' +
				'<td rowspan="2" style="max-width:30px;"> ' +
					'<div class="file-type-icon pull-right">' +
						'<span class="corner"></span>' +
						'<span class="type '+ext+'">'+ext+'</span>' +
					'</div>' +
				'</td>' +
				'<td class="nombre">'+fileName+'</td>' +
				'<td>'+toSize( file.size )+'</td>' +
				'<td>'+'yo'+'</td>' +
				'<td>' +
					'<ul class="list-group list-inline text-right" style="margin-bottom:5px;">' +
						'<li class="porc_load"></li>' +
					'</ul>' +
				'</td>' +
			'</tr>' +
			'<tr id="tr-tags-'+id+'">' +
				'<td colspan="4" style="padding:0;border:none;">' +
					'<ul class="list-group list-inline" style="margin-bottom:0px;">';
	for( i = 0; i < file.tags.length; i++ ) {
		var escapar = escapeHtml( file.tags[i] );
		delete file.tags[i];
		file.tags[i] = escapar;
		tr += 				'<li class="label label-danger">' +
							' <span data-cat="'+$.trim( file.tags[i].substr( 0, file.tags[i].indexOf( ":" ) ) )+'">' +
								$.trim( file.tags[i].substr( file.tags[i].indexOf( ":" )+1 ) ) +
							'</span>' +
						'</li>';
	}
        tr += 				'</ul>' +
				'</td>' +
			'</tr>';
	$( '.file-list > tbody' ).prepend( tr );
}


function uploadFile( fileIn, id, userId, tags ) {
	//Me falta verificar el tipo y agregar ciertas restricciones
	if( fileIn.size <= parseInt( $( 'input[name="MAX_FILE_SIZE"]' ).val() ) ) {

		var aux = getNamesTags( fileIn.name ); //Veo si el nombre tiene tags y lo limpio
		/* bug raro... tuve que borrar el antiguo nombre */
		delete fileIn.name;
		fileIn.name = aux[0];
		/**/

		fileIn.tags = aux[1].concat( tags );
		//fileIn.name = processName( fileIn.name ); //Veo si es muy largo

		parseFile( fileIn, id ); //Pongo en pantalla el "archivo"	
		var tr = '#tr-' + id + ' '; //Elemento padre
		var tr_tags = '#tr-tags-'+id+' ';
		var fd = new FormData();
		fd.append( 'file', fileIn );

		$.ajax({
      			type: "POST",
      			url: "ajax.uploadFile.php",
      			enctype: 'multipart/form-data',
			cache: false,
        		contentType: false,
			processData: false,
     			data: fd,
			xhr: function() {
            			xhr = $.ajaxSettings.xhr();
				
				//Bind del progreso de la carga del archivo
            			if( xhr.upload ){
                			xhr.upload.addEventListener( 'progress', function( e ){ 
						if( e.lengthComputable ) {
							$( tr + ".porc_load" ).html( parseInt( 100*e.loaded/e.total )+'%' );
						}
					}, false );
         			}

				//Bind de abortar la carga del archivo
                                $( tr + ".porc_load" ).bind( 'click', function(){
                                        xhr.abort();
					/*Tengo que agregar un ajax manager porque esto a veces me lo deja colgado*/
                                });

         			return xhr;
        		},
     			success: function( data, textStatus, jqXHR ) {
				var result = $.parseJSON( data );
				if( result.type == "error" ) {
					$( tr+','+tr_tags ).fadeOut( 'slow', function(){
						$( tr+','+tr_tags ).remove();
					} );
					console.log( "Error: " + result.msg );
					return;
				}
				if( result.type == "duplicado" ) {
                                        $( tr+','+tr_tags ).fadeOut( 'slow', function(){
                                                $( tr+','+tr_tags ).remove();
                                        } );
					console.log( "Error: " + result.msg );
                                        return;
				}
				
				// attr("id", "newID");
				var tr2 = 'tr-' + result.fecha + '-' + result.id;
				var tr2_tags = 'tr-tags-' + result.fecha + '-' + result.id
				$( tr ).attr( 'id', tr2 );
				$( tr_tags ).attr( 'id', tr2_tags );
				tr = '#' + tr2 + ' ';
				tr_tags = '#' + tr2_tags + ' ';

				//Le agrego un click para notificar que esta listo arriba
				$( tr + ".porc_load" ).parent().html( 
					'<li><a href="#" title="Tags"><span class="glyphicon glyphicon-tags"></span></a></li>' +
					'<li><a href="#" title="Eliminar"><span class="glyphicon glyphicon-trash"></span></a></li>' +
					'<li><a href="descargar?id='+result.id+'" title="Bajar"><span class="glyphicon glyphicon-download"></span></a></li>'
				);
				$( tr + ".nombre" ).html(  '<a href="archivo?id='+result.id+'">'+$( tr + ".nombre" ).html()+'</a>' );
				$( tr + ".glyphicon-trash" ).click( function() {
					fileRemove( result.fecha, result.id, userId );
				} );

				var nuevosTags = new Array();
				$( tr_tags ).find( 'span' ).each( function() {
					var categoria = $.trim( $( this ).attr( 'data-cat' ) );
					if( categoria != '' ) categoria += ':';
					nuevosTags.push( categoria + $.trim( $( this ).html() ) );
				} );


				
				if( nuevosTags.length > 0 ) {
					uploadTagsOfFile( result.fecha, result.id, nuevosTags );
				}

	

			},
			error: function( jqXHR, textStatus, errorThrown ) {

				//Error! quito el div
				$( tr+', '+tr_tags ).fadeOut( 'slow', function(){
					$( tr+', '+tr_tags ).remove();
				} );

				console.log( "Archivo no subido, error : " + errorThrown );
			},
			complete: function( jqXHR, textStatus ) {
				window.forks -= 1;
				//Solo por si acaso... avisar de algo, log, o da igual
				//console.log( "Complete: " + textStatus );
			}
    		});
	}
	else {
		console.log( 'Archivo muy grande' );
		window.forks -= 1;
	}
}

function uploadTagsOfFile( fecha, id, tags ) {
	var tr = '#tr-' + fecha + '-' + id + ' ';
	var tr_tags = '#tr-tags-' + fecha + '-' + id + ' ';
	$.ajax( {
		type: "POST",
		url: "ajax.uploadTagsOfFile.php",
		cache: false,
		data: {
			fileId: id,
			tags: tags
		},
		success: function( data, textStatus, jqXHR ) {
			var result = $.parseJSON( data );
			if( result.type != undefined && result.type == 'error' ) {
				//supuesto: si el sql falla, es porque ya existia el tag
				$( tr_tags+'> ul ' ).html( '' );
				console.log( "error al subir los tags al archivo" );		
				return;
			}
			//apend el sumar mas tags e inicializar handlers
			console.log( "tags asociados satisfactoriamente al archivo" );
		},
		error: function() {
			//borrar los tags de la zona de tags
			$( tr_tags+'> ul ' ).html( '' );
			console.log( "error al subir los tags al archivo" );	
		},
		complete: function() {
			// Primero arreglar el busqueda.js
			//actualizarTagWrap( tr_tags, id );
		}
	} );
	
}

function fileRemove( fecha, id, usuId ) {
	var tr = '#tr-' + fecha + '-' + id + ' ';
	var tr_tags = '#tr-tags-' + fecha + '-' + id + ' ';
	if( confirm( '¿Realmente desea elminar el archivo?' ) ) {
		$.ajax( {
			type: "POST",
	                url: "ajax.removeFile.php",
	                cache: false,
	                data: {
	                        fileId: id,
				userId: usuId
			},
			success: function( data, textStatus, jqXHR ) {
				console.log( data );
				var result = $.parseJSON( data );
	                        if( result.type != undefined && result.type == 'error' ) {
	                                console.log( "error en la BD al intentar borrar el archivo" );
	                                return;
	                        }
				$( tr+', '+tr_tags ).fadeOut( 'slow', function(){
	                        	$( tr+', '+tr_tags ).remove();
	                        } );
				console.log( 'archivo borrado' );
			},
			error: function() {
				console.log( "error en la BD al intentar borrar el archivo" );
			}
		} );
	}
}

function getNamesTags( nombre ) {
	//Los tags vendran entre corchetes [], ejemplo nombre[curso:computacion i]
	var auxiliar = nombre;
	var j = 0;
	var tag = "";
	var tags = new Array();
	var nombreFinal = "";

	for( i = 0; i < nombre.length; i++ ) {

		auxiliar = auxiliar.substr( i );
		j = auxiliar.indexOf( "[" );
		if( j == -1 ) {
			nombreFinal += auxiliar;
			break;
		}
		nombreFinal += auxiliar.substr( 0, j );

		auxiliar = auxiliar.substr( j+1 );
		i = auxiliar.indexOf( "]" );
		if( i == -1 ) {
			nombreFinal += auxiliar;
			break;
		}

		tag = auxiliar.substr( 0, i );
		if( tag != "" ) { 
			tags.push( tag );
		}
	}

	return [ nombreFinal, tags ];
}

function processName( nombre ) {
	//Si es muy grande el nombre lo achico
	return nombre;
}


