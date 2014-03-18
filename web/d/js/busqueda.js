
function agregarHandlers( lugarBusquedaS, liTagsDelWrapS, dondeVanLasSugerenciasS ) {

	var lugarBusqueda = $( lugarBusquedaS );
        var liTagsDelWrap = $( liTagsDelWrapS );
        var dondeVanLasSugerencias = $( dondeVanLasSugerenciasS );
	var guion = lugarBusquedaS.lastIndexOf( "-" );

	dondeVanLasSugerencias.focusin( function() {
                $( this ).find( '.tag a' ).first().addClass( 'current' );
        } ).focusout( function() {
                $( this ).find( '.current' ).removeClass( 'current' );
        } ).keydown( function( e ) {
                if( e == undefined ) {
                        return;
                }
                var current =  $( this ).find( '.current' );
                var tags = $( this ).find( '.tag a' );
                switch( e.keyCode ) {
                        case 37:
                                if( current.html() == tags.first().html() ) {
                                        lugarBusqueda.focus();
                                }
                                else {
                                        var prev = current.parent().prev().find( 'a' );
                                        current.removeClass( 'current' );
                                        prev.addClass( 'current' );
                                }
                                break;
                        case 39:
                                if( current.html() != tags.last().html() ) {
                                        var next = current.parent().next().find( 'a' );
                                        current.removeClass( 'current' );
                                        next.addClass( 'current' );
                                }
                                break;
                        case 13:
                                current.click();
                                if( guion == -1 ) {
	                                doSearch( e, lugarBusquedaS );
	                        }
	                        else {
	                                var fileId = parseInt( lugarBusquedaS.substr( guion + 1 ) );
	                                addTagToFile( e, fileId, lugarBusquedaS );
	                        }
                                break;
                        case 32:
                                current.click();
                                break;
                        case 27:
                                lugarBusqueda.focus();
                                break;
                }
        } );


	lugarBusqueda.keydown( function( e ) {
		if( e == undefined ) return; 
                if( ( e.keyCode == 13 || e.keyCode == 188 ) && $( this ).val() != '' ) {
                        if( guion == -1 ) {
				doSearch( e, lugarBusquedaS );
			}
			else {
				var fileId = parseInt( lugarBusquedaS.substr( guion + 1 ) );
				addTagToFile( e, fileId, lugarBusquedaS );
			}
                }
                else if ( guion == -1 && e.keyCode == 8 && $( this ).val() == '' && parent.location.search != "" ) {
                        rmvLastTag( liTagsDelWrapS );
                }
                else if ( e.keyCode == 39 && getCaretPosition( this, 'right' ) == this.value.length  ) {
                        dondeVanLasSugerencias.focus();
                }
        } ).focus( function() {
                //ir al final
        } );	
}

function addTagToFile( event, fileId, lugarBusquedaS ) {
	event.preventDefault();
        event.stopPropagation();
	console.log( "subir tag al archivo" );
	var nuevos = $( lugarBusquedaS ).val().split( ',' );
	var fecha = parseInt( lugarBusquedaS.substring( lugarBusquedaS.indexOf( "-" )+1, lugarBusquedaS.lastIndexOf( "-" ) ) );

	var div = '#div-' + fecha + '-' + fileId + ' ';
        $.ajax( {
                type: "POST",
                url: "ajax.uploadTagsOfFile.php",
                cache: false,
                data: {
                        fileId: fileId,
                        tags: nuevos
                },
                success: function( data, textStatus, jqXHR ) {
			console.log( data );
                        var result = $.parseJSON( data );
                        if( result.type != undefined && result.type == 'error' ) {
				//supuesto: si el sql falla, es porque ya existia el tag
                                var noty_id = $( '.message_container' ).noty( {
                                        text: 'Este archivo ya contiene al menos uno de los tags que se intenta asociar',
                                        type: 'error'
                                } );
                                console.log( "error al subir los tags al archivo" );
				return;
                        }

			$( lugarBusquedaS ).val( '' );
                        console.log( "tags asociados satisfactoriamente al archivo" );
                },
                error: function() {
                        //borrar los tags de la zona de tags
                        var noty_id = $( '.message_container' ).noty( {
                                text: 'Los tags no pudieron ser asociados.',
                                type: 'error'
                        } );
                        console.log( "error al subir los tags al archivo" );
                },
		complete: function() {
			actualizarTagWrap( div, fileId );
		}
        } );
	
}

function actualizarTagWrap( div, fileId ) {
	$.ajax( {
		type: "POST",
		url: "ajax.getTagsByFileId.php",
		cache: "false",
		data: { id: fileId },
		success: function( data, textStatus, jqXHR ) {
			console.log( data );
			result = $.parseJSON( data );
			if( result.type == 'error' ) {
				//error
				return;
			}

			var nuevos = result.tags;
			var wrap = $.isEmptyObject( $( div + ".archivo_tags_wrap .mCSB_container" ) ) ? $( div + ".archivo_tags_wrap" ):$( div + ".archivo_tags_wrap .mCSB_container" );
			wrap.html( '' );
			for( i = 0; i < nuevos.length; i++ ) {
				if( $.trim( nuevos[i] ) != "" ) {
					var escapar = escapeHtml( nuevos[i] );
					delete nuevos[i];
					nuevos[i] = escapar;
					wrap.append( ''+
						'<li class="in_li tag">' +
							' <a href="" class="tag-lightblue" data-cat="'+$.trim( nuevos[i].substr( 0, nuevos[i].indexOf( ":" ) ) )+'">' +
								$.trim( nuevos[i].substr( nuevos[i].indexOf( ":" )+1 ) ) +
							'</a>' +
						'</li>' );
				}
			}
			//mCSB Destroy and reUp
			$( div + ".archivo_tags_wrap" ).mCustomScrollbar( "destroy" );
			$( div + ".archivo_tags_wrap" ).mCustomScrollbar( { horizontalScroll: true, scrollInertia: 0 } );
			//$( div + ".archivo_tags_wrap" ).mCustomScrollbar( "scrollTo", "last" );

		},
		error: function() {
			var noty_id = $( '.message_container' ).noty( {
				text: 'Error al actualizar la zona de tags',
				type: 'error'
			} );
		}
	} );
}

function doSearch( event, lugarBusquedaS ) {
	event.preventDefault();
	event.stopPropagation();
	var busqActual = parent.location.search;
	var nuevos = $( lugarBusquedaS ).val().split( ',' );
	var extra = "";
	for( var i = 0; i < nuevos.length; i++ ) {
		nuevos[i] = encodeURIComponent( $.trim( nuevos[i] ) );
		if( nuevos[i] == "" ) continue;
		extra += ',' + nuevos[i];
	}
	
	busqActual == "" || busqActual.indexOf( "clave" ) == -1 ?
		parent.location.search = "?clave=" + extra.substr( 1 )
	:
		parent.location.search = busqActual + extra; 
}

function agregarBusqueda( tag, lugarBusquedaS, liTagsDelWrapS, dondeVanLasSugerenciasS ) {
	var lugarBusqueda = $( lugarBusquedaS );
	var value = lugarBusqueda.val();
	var coma = value.lastIndexOf( "," );
	lugarBusqueda.focus();
	coma != -1 ?
		lugarBusqueda.val( value.substr( 0, coma+1 )+' '+tag+', ' )
	:
		lugarBusqueda.val( tag+', ' );
		
	searchKeyUp( lugarBusquedaS, liTagsDelWrapS, dondeVanLasSugerenciasS );
}

function rmvLastTag( liTagsDelWrapS ) {
	var search = parent.location.search;
	var last = $( liTagsDelWrapS ).last();
	var cleanSearch = search.substr( 0, search.lastIndexOf( "," ) ); 
	//podria confirmar que es el mismo elemento que estoy eliminando, digo yo por si acaso. Ahi velo.
	last.remove();
	parent.location.search = cleanSearch;
}

function searchKeyUp( lugarBusquedaS, liTagsDelWrapS, dondeVanLasSugerenciasS ) {

	var lugarBusqueda = $( lugarBusquedaS );
	var liTagsDelWrap = $( liTagsDelWrapS );
	var dondeVanLasSugerencias = $( dondeVanLasSugerenciasS );
	var term = '' 
	var term2 = lugarBusqueda.val() == undefined ? '' : lugarBusqueda.val();
	var coma = term2.lastIndexOf( "," ); 
	if( coma != -1 ) {
		term = term2;
		term = term.substr( 0, coma );
		term2 = term2.substr( coma+1 );
	}
	term = $.trim( term );
	term2 = $.trim( term2 );
	liTagsDelWrap.find( 'a' ).each( function() {
		var categoria = $.trim( $( this ).attr( 'data-cat' ) );
		if( categoria != '' ) categoria += ':';
		term == '' ? term += categoria + $.trim( $( this ).html() ) : term += ',' + categoria + $.trim( $( this ).html() );
	} );

	$.ajax( {
	        type: "POST",
	        url: "ajax.tagAutoComplete.php",
	        cache: false,
	        data: { 
			termino: term2,
			excepciones: term
		},
		success: function( data, textStatus, jqXHR ) {
			var result = $.parseJSON( data );
			if( result.type != undefined && result.type == 'error' ) {
				console.log( result );
				return;
			}

			dondeVanLasSugerencias.html( '' );
			for( var i=0; i<result.length; i++ ) {
				var escapar = escapeHtml( result[i].name );
				delete result[i].name;
				result[i].name = escapar; 
				dondeVanLasSugerencias.append(
					'<li class="in_li tag" onclick="agregarBusqueda( \''+result[i].name+'\', \''+lugarBusquedaS+'\', \''+liTagsDelWrapS+'\', \''+dondeVanLasSugerenciasS+'\' )">'+
						'<a class="tag-lightblue">'+result[i].name.substr( result[i].name.lastIndexOf( ":" )+1 )+'</a>'+
					'</li>'
				);
			}
		},
		error: function( jqXHR, textStatus, errorThrown ) {
			console.log( 'error' );
		}	
	} );
}

