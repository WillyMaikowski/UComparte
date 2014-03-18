/*
	Funciones Comunes
*/
function clickPagination( event, val ) {
	event.preventDefault();
	event.stopPropagation();
	$( '.paginacion-hidden' ).attr( 'value', val );
	$( '.paginacion-submit' ).click(); 
}

function deleteTag( tag, file_id, user_id ) {
	if( confirm( '¿Realmente desea quitar el tag al archivo' ) ) {
		$.ajax( {
			type: "POST",
			url: "ajax.deleteTag.php",
			cache: false,
			data: {
				tag: tag,
				fileId: file_id,
				userId: user_id
			},
			success: function( data, textStatus, jqXHR ) {
				console.log( data );
				var result = $.parseJSON( data );
				if( result.type != undefined && result.type == 'error' ) {
					console.log( result.msg );
					return;
				}
				$( '.label.label-danger span' ).each( function() {
					if( $(this).html() == tag ) {
						$( this ).parent().remove();
					}
				} );
			},
			error: function() {
				console.log( "Un error ha ocurrido al intentar eliminar el tag" );
			}	
		} );
	}	
}
function closeTag( tag_str ) {
	var search = decodeURIComponent( parent.location.search );
	if( search.indexOf( '+' ) != -1 ) {
		tag_str = "+" + tag_str;
	}
	search = search.replace( tag_str, "" ).substr( 1 );
	if( search === "clave=" ) search = "";
	parent.location.search = search;
}
function displayTags( fecha, id ) {
	event.preventDefault();
	event.stopPropagation();
	var tr_tags_ul = "#tr-tags-"+fecha+"-"+id+" ul";
	$( tr_tags_ul ).fadeToggle( "slow", "linear" );
	console.log( tr_tags_ul + " click" );
}
function doClick( event, target ) {
	event.preventDefault();
	event.stopPropagation();
	$( target ).click();
}
function permalinkHandler() {
	console.log( "PermalinkHandler On" );
	$( 'a[title="Permalink"]' ).click( function( event ) {
		event.preventDefault();
		event.stopPropagation();

		var url = $( this ).attr( 'href' );
		if( window.history.replaceState ) {
			window.history.replaceState( "", "", url );
		}
		else {
			window.prompt( 'Para copiar: Ctrl+C, Enter', url );
		}
	} );
}

//Inpu Caret Functions
function getCaretPosition( field, direction ) {
	if( document.selection ) {
		field.focus();
		var sel = document.selection.createRange();
		sel.moveStrt( 'character', -field.value.lenght );
		caretPos = sel.text.lenght;
	}
	else if( field.selectionStart || field.selectionStart == '0' ) {
		caretPos = direcction == 'left' ? field.selectionStart : field.selectionEnd;
	}

	return caretPos || 0;
}

function escapeHtml( string ) {
	var entityMap = { 
		"&": "&amp;", 
		"<": "&lt;", 
		">": "&gt;", 
		'"': '&quot;', 
		"'": '&#39;', 
		"/": '&#x2F;' 
	};
	return String( string ).replace( /[&<>"'\/]/g, function( s ) {
		return entityMap[s];
	} );
}

//utilLib.php function
function toSize( size /*, $precision = 1, $long FALSE */ ) {
	sizes  = new Array( 'b', 'kb', 'mb', 'gb', 'tb', 'pb', 'eb' );
	//sizes2 = array( 'Bytes', 'Kilobytes', 'Megabytes', 'Gigabytes', 'Terabytes', 'Petabytes', 'Exabytes' );

	for( i=0; size > 1024; ++i ) size /= 1024;

	return number_format( round( size, 1 ), 1, ',', '.' )+' '+sizes[i];
}

//Javascript number_format function [phpjs.org]
function number_format( number, decimals, dec_point, thousands_sep ) {
	number = ( number + '' ).replace( /[^0-9+\-Ee.]/g, '' );
	var 	n = !isFinite( +number ) ? 0 : +number,
		prec = !isFinite( +decimals ) ? 0 : Math.abs( decimals ),
		sep = ( typeof thousands_sep === 'undefined' ) ? ',' : thousands_sep,
		dec = ( typeof dec_point === 'undefined' ) ? '.' : dec_point,
		s = '',
		toFixedFix = function ( n, prec ) {
			var k = Math.pow( 10, prec );
			return '' + Math.round( n*k ) / k;
		};

	// Fix for IE parseFloat(0.55).toFixed(0) = 0;
	s = ( prec ? toFixedFix( n, prec ) : '' + Math.round( n ) ).split( '.' );
	if( s[0].length > 3 ) {
		s[0] = s[0].replace( /\B(?=(?:\d{3})+(?!\d))/g, sep );
	}
	if( ( s[1] || '' ).length < prec ) {
		s[1] = s[1] || '';
		s[1] += new Array( prec - s[1].length + 1 ).join( '0' );
	}
	
	return s.join( dec );
}

function round( value, precision, mode ) {
	var m, f, isHalf, sgn; // helper variables
	precision |= 0; // making sure precision is integer
	m = Math.pow( 10, precision );
	value *= m;
	sgn = ( value > 0 ) | -( value < 0 ); // sign of the number
	isHalf = value % 1 === 0.5 * sgn;
	f = Math.floor( value );

	if( isHalf ) {
		switch( mode ) {
			case 'PHP_ROUND_HALF_DOWN':
				value = f + ( sgn < 0 ); // rounds .5 toward zero
				break;
			case 'PHP_ROUND_HALF_EVEN':
				value = f + ( f % 2 * sgn ); // rouds .5 towards the next even integer
				break;
			case 'PHP_ROUND_HALF_ODD':
				value = f + !( f % 2 ); // rounds .5 towards the next odd integer
				break;
			default:
				value = f + ( sgn > 0 ); // rounds .5 away from zero
		}
	}

	return ( isHalf ? value : Math.round( value ) ) / m;
}
