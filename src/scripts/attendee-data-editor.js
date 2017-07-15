( function( $ ) {
	var editing = false;
	var event_id = 0;
	var product_id = 0;
	var attendee_id = 0;
	var $row;
	var $editor;
	var $editor_inner;
	var $editor_close;
	var $save_btn;

	function init() {
		create_inline_editor_space();
		$( 'table.attendees' ).on( 'click', '.edit-attendee-data', on_edit );
	}

	function on_edit() {
		// Already editing? Do nothing
		if ( editing ) {
			return false;
		}

		editing = true;
		open_for_editing( $( this ) );
		return false;
	}

	function open_for_editing( $trigger ) {
		$row = $trigger.parents( 'tr' );
		event_id = $trigger.data( 'event-id' );
		product_id = $trigger.data( 'product-id' );
		attendee_id = $trigger.data( 'attendee-id' );

		$editor.fadeIn( 'fast' );
		request_attendee_form();
	}

	function create_inline_editor_space() {
		$editor = $(
			  '<div id="attendee-data-editor-backscreen">'
			+ '<div id="attendee-data-editor-space">'
			+ '<div class="close"> &#10060; </div>'
			+ '<div class="inner"></div>'
			+ '</div> </div>'
		);

		$editor_inner = $editor.find( 'div.inner' );
		$editor_close = $editor.find( 'div.close' );
		$editor_close.on( 'click', terminate_editor );

		$editor.hide();
		$( 'body' ).append( $editor );
	}

	function terminate_editor() {
		$editor.fadeOut( 'fast', function() {
			$editor_inner.html( '' );
			editing = false;
		} );
	}

	function request_attendee_form() {
		$.post( ajaxurl, {
				'action':      'load_attendee_data_editor_form',
				'check':       attendee_data_editor.check,
				'event_id':    event_id,
				'attendee_id': attendee_id
			},
			load_attendee_form,
			'json'
		);
	}

	function load_attendee_form( data ) {
		if (
			'object' !== typeof data
			|| 'undefined' === typeof data.success
			|| ! data.success
			|| 'object' !== typeof data.data
			|| 'string' !== typeof data.data.html
		) {
			console.error( 'Attendee Data Editor: something went wrong when we tried to fetch the data editor content' );
			terminate_editor();
		}

		data.data.html += '<button class="save button button-primary">' + attendee_data_editor.save_btn + '</button>';
		$editor_inner.html( data.data.html );
		setup_form();
	}

	function setup_form() {
		$save_btn = $editor_inner.find( 'button.save' );
		$save_btn.click( save_form );
	}

	function save_form() {
		var $form_fields = $editor_inner.find( 'input, select, textarea' );
		var form_data = $form_fields.serialize();

		$form_fields.each( function() {
			$( this ).prop( 'disabled', true );
		} );

		$.post( ajaxurl, {
				'action':      'save_attendee_data_editor_form',
				'fields':      form_data,
				'check':       attendee_data_editor.check,
				'event_id':    event_id,
				'product_id':  product_id,
				'attendee_id': attendee_id
			},
			wait_on_save,
			'json'
		);

		$save_btn.removeClass( 'button-primary' ).prop( 'disabled', true );
		$editor_inner.addClass( 'working' );
	}

	function wait_on_save( data ) {
		if (
			'object' !== typeof data
			|| 'undefined' === typeof data.success
			|| ! data.success
		) {
			console.error( 'Attendee Data Editor: something went wrong when we tried to save the data' );
			alert( attendee_data_editor.oh_oh_on_save );
			terminate_editor();
		}

		$save_btn.addClass( 'button-primary' ).prop( 'disabled', false );
		$editor_inner.removeClass( 'working' );
		$editor_inner.find( 'input, select, textarea' ).each( function() {
			$( this ).prop( 'disabled', false );
		} );

		if ( 'object' === typeof data.data && 'string' === typeof data.data.meta_row_html ) {
			update_meta_row( data.data.meta_row_html );
		}

		success_msg();
	}

	function update_meta_row( html ) {
		$next_row = $row.next();

		if ( ! $next_row.hasClass( 'event-tickets-meta-row' ) ) {
			return;
		}

		$next_row.html( html );
	}

	function success_msg() {
		var $success_msg = $( '<strong class="successful-update">' + attendee_data_editor.success_on_save + '</strong>' );
		$save_btn.after( $success_msg );

		setTimeout( function() {
				$success_msg.fadeOut( 'fast', function() {
					$success_msg.remove();
				} )
			},
			2000
		);
	}

	$( document ).ready( init );
} )( jQuery );