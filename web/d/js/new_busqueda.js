(function( $ ) {

	var BACKSPACE = 8, ENTER = 13, COMMA = 188, LEFTARROW = 37, RIGHTARROW = 39, SPACE = 32, ESC = 27;

	var system = {
		log: function( type, msj ) {

		},
		error: function() {

		}
	};

	var methods = {

		init: function( options ) {
			var settings = $.extend( {
				//opciones por default
				busqInput	: '#busqueda', 		//String que identifica el id del input donde se realizan las busquedas.
				liTags		: '#tags_wrap li', 	//String que identifica todos los li que son tags dentro de los ya buscados. 
				sugWrap		: '#sug_tags_wrap', 	//String que identifica donde seran puestos los li de los tags sugeridos.
				fileId		: 0, 			//Si es >0 es porque this es un li perteneciente a un archivo.  
				fecha		: 0
			}, options );

			this.data( 'ucBusq', settings );

			var _busqInput = $( settings.busqInput ), _sugWrap = $( settings.sugWrap_ ); 
			
			_busqInput.keydown( function( e ) { 
				e = e || event;
				methods.busqInputKeyDown.apply( this, e );
			} );
			
			_sugWrap.focusin( function() {
				methods.sugWrapFocusIn.apply( this );
			} ).focusout( function() { 
				methods.sugWrapFocusOut.apply( this );
			} ).keydown( function( e ) {
				e = e || event; 
				methods.sugWrapKeyDown.apply( this, e );
			} );

		},

		busqInputKeyDown: function( e ) {
			var $this = $( this.data( 'ucBusq' ).busqInput ), 
			fileId = this.data( 'ucBusq' ).fileId;

			if( ( e.keyCode == ENTER || e.keyCode == COMMA ) && $this.val() != '' ) {
				fileId ? methods.addTagToFile.apply( this, e ) : methods.doSearch.apply( this, e );
			}
			else if( !fileId && e.keyCode == BACKSPACE && $this.val() == '' && parent.location.search != "" ) {
				methods.rmvLastTag.apply( this );
			}
			else if( e.keyCode == RIGHTARROW && getCaretPosition( $this, 'right' ) == $this.val().length ) {
				$( this.data( 'ucBusq' ).sugWrap ).focus();
			}
		},

		sugWrapFocusIn: function() {
			var $this = $( this.data( 'ucBusq' ).sugWrap );
			$this.find( '.tag a' ).first().addClass( 'current' );
		},

		sugWrapFocusOut: function() {
			var $this = $( this.data( 'ucBusq' ).sugWrap );
			$this.find( '.current' ).removeClass( 'current' );
		},

		sugWrapKeyDown: function( e ) {
			var $this = $( this.data( 'ucBusq' ).sugWrap );
			var current = $this.find( '.current' ),
			tags = $this.find( '.tag a' );

			switch( e.keyCode ) {
				case LEFTARROW:
					current.html() == tags.first().html() ?
						$( this.data( 'ucBusq' ).busqInput ).focus() : 
						current.removeClass( 'current' ).parent().prev().find( 'a' ).addClass( 'current' );
					break;
				case RIGHTARROW:
					if( current.html() == tags.last().html() ) break;
					current.removeClass( 'current' ).parent().next().find( 'a' ).addClass( 'current' );
					break;
				case ENTER:
					current.click();
					this.data( 'ucBusq' ).fileId ? 
						methods.addTagToFile.apply( this, e ) :
						methods.doSearch.apply( this, e );
					break;
				case SPACE:
					current.click();
					break;
				case ESC:
					$( this.data( 'ucBusq' ).busqInput ).focus();
					break;
			}
		},

		addTagToFile: function( e ) {
			e.preventDefault();
			e.stopPropagation();
			var busqInput = this.data( 'ucBusq' ).busqInput, 
			nuevos = $( busqInput ).val().split( ',' ),
			fileId = this.data( 'ucBusq' ).fileId,
			fecha = this.data( 'ucBusq' ).fecha,
			div = '#div-' + fecha + '-' + fileId + ' ';
			
			var jqxhr = $.getJSON( 'ajax.uploadTagsOfFile.php', { fileId: fileId, tags: nuevos }, function( data, textStatus, jqXHR ) {
				if( data.type != undefined && data.type == 'error' ) {
					system.log.apply( this, data.type, data.msj );
					return;
				}
				busqInput.val( '' );
			} ).fail( function() {
				system.log.apply( this, 'error', 'falla en la conexion' );
			} ).always( function() {
				methods.liTagsRefresh.apply( this );
			} );
		},

		liTagsRefresh: function() {
			var fileId = this.data( 'ucBusq' ).fileId,
			fecha = this.data( 'ucBusq' ).fecha,
			div = '#div-' + fecha + '-' + fileId + ' ';
			var jqxhr = $.getJSON( 'ajax.getTagsByFileId', { id: fileId }, function( data, txtSt, jqXHR ) {
				if( data.type != undefined && data.type == 'error' ) {
					system.log.apply( this, data.type, data.msj );
				}
				
				var nuevos = data.tags,
				wrap = $.isEmptyObject( $( div + '.archivo_tags_wrap .mCSB_container' ) ) ? 
						$( div + '.archivo_tags_wrap' ) : 
						$( div + '.archivo_tags_wrap .mCSB_container' );
				wrap.html( '' );
				for( i = 0; i < nuevos.length; i++ ) {
					if( $.trim( nuevos[i] == '' ) continue;
					var escapar = escapeHtml( nuevos[i] );
					delete nuevos[i];
					nuevos[i] = escapar;
					wrap.append( 
						'<li class="in_li tag">' +
							'<a href="" class="tag-lightblue" data-cat="'+$.trim( nuevos[i].substr( 0, nuevos[i].indexOf( ':' ) ) ) +'">' +
								$.trim( nuevos[i].substr( nuevos[i].indexOf( ':' ) + 1 ) )+
							'</a>' +
						'</li>'
					);
				}
				$( div + '.archivo_tags_wrap' ).mCustomScrollbar( 'destroy' )
				.mCustomScrollbar( { horizontalScroll: true, scrollInertia: 0 } )
				.mCustomScrollbar( 'scrollTo', 'last' );

			} ).fail( function() {
				system.log.apply( this, 'error', 'falla en la conexion' );
			} );
		},

		doSearch: function( e ) {
			e.preventDefault();
			e.stopPropagation();
			var busqActual = parent.location.search,
			nuevos = $( this.data( 'ucBusq' ).busqInput ).val().split( ',' ),
			extra = '';
			for( i = 0; i < nuevos.length; i++ ) {
				nuevos[i] = encodeURIComponent( $.trim( nuevos[i] ) );
				if( nuevos[i] == '' ) continue;
				extra += ',' + nuevos[i];
			}

			busqActual == '' || busqActual.indexOf( 'clave' ) == -1 ?
				parent.location.search = '?clave=' + extra.substr( 1 ):
				parent.location.search = busqActual + extra;

		},

		addSearch: function( tag ) {

		},

		rmvLastTag: function() {

		},

		searchKeyUp: function() {

		}		

	};

	$.fn.ucomparteBusqueda = function( method ) {
		if( methods[ method ] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ) );
		}
		else if( typeof method === 'object' || !method ) {
			return methods.init.apply( this, arguments );
		}
		else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.ucomparteBusqueda' );
		}
	};

} ) ( jQuery );
