jQuery( document ).ready( function( $ ) {
	'use strict';

	// Only show the "remove button" when needed.
	$( '.ipb-media-wrap' ).each( function() {
		var $mediaInput = $( this ).find( '.ipb-media-input' );

		if ( ! $mediaInput.val() ) {
			$( this ).find( '.ipb-media-remove' ).hide();
		}
	} );

	$( document ).on( 'click', '.ipb-media-upload', function( e ) {
		e.preventDefault();

		var $parent     = $( this ).parents( '.ipb-media-wrap' ).first(),
		    $mediaImage = $parent.children( '.ipb-media-image' ),
		    $mediaInput = $parent.find( '.ipb-media-input' );

		/*var mediaFrame;

		// If the frame already exists, re-open it.
		if ( mediaFrame ) {
			mediaFrame.open();
			return;
		}*/

		var mediaFrame = wp.media.frames.mediaFrame = wp.media( {
			title: 'Choose an image',
			button: {
				text: 'Use image'
			},
			className: 'media-frame ipb-media-frame',
			frame: 'select',
			multiple: false,
			library: {
				type: 'image'
			}
		} );

		mediaFrame.on( 'select', function() {
			var attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
			var attachmentThumbnail = attachment.sizes.thumbnail || attachment.sizes.full;

			$mediaInput.val( attachment.id ).trigger( 'change' );
			$mediaImage.find( 'img' ).attr( 'src', attachmentThumbnail.url );
			$parent.find( '.ipb-media-remove' ).show();
		} );

		// Finally, open up the frame, when everything has been set.
		mediaFrame.open();
	} );

	$( document ).on( 'click', '.ipb-media-remove', function( e ) {
		e.preventDefault();

		var $parent     = $( this ).parents( '.ipb-media-wrap' ).first(),
		    $mediaImage = $parent.children( '.ipb-media-image' ).children( 'img' ),
		    $mediaInput = $parent.find( '.ipb-media-input' );

		if ( $mediaImage.attr( 'data-src-placeholder' ) ) {
			$mediaImage.attr( 'src', $mediaImage.attr( 'data-src-placeholder' ) );
		} else {
			$mediaImage.attr( 'src', '' );
		}

		$mediaInput.val( '' );
		$( this ).hide();
	} );
} );
