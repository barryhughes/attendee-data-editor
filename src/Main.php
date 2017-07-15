<?php
class Tribe__Tickets__Attendee_Data_Editor__Main {
	protected $plugin_url = '';

	public function __construct( $plugin_url ) {
		if ( ! class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
			return;
		}

		$this->plugin_url = $plugin_url;

		add_action( 'wp_ajax_load_attendee_data_editor_form', array( $this, 'editor_content' ) );
		add_action( 'wp_ajax_save_attendee_data_editor_form', array( $this, 'save_fields' ) );
		add_action( 'load-tribe_events_page_tickets-attendees', array( $this, 'assets' ) );
		add_filter( 'tribe_events_tickets_attendees_table_column', array( $this, 'add_edit_link' ), 20, 3 );
	}

	public function assets() {
		wp_enqueue_style(
			'attendee-data-editor-styles',
			$this->plugin_url . 'src/styles/attendee-data-editor.css'
		);

		wp_enqueue_script(
			'attendee-data-editor-script',
			$this->plugin_url . 'src/scripts/attendee-data-editor.js',
			array( 'jquery' ),
			false,
			true
		);

		wp_localize_script(
			'attendee-data-editor-script',
			'attendee_data_editor',
			array(
				'check' => wp_create_nonce( 'attendee_data_editor' ),
				'save_btn' => __( 'Save!', 'attendee-data-editor' ),
				'oh_oh_on_save' => __( 'Something went wrong: your attendee data could not be saved.', 'attendee-data-editor' ),
				'success_on_save' => __( 'Successful update!', 'attendee-data-editor' ),
			)
		);
	}

	public function add_edit_link( $value, $item, $column ) {
		if ( 'meta_details' !== $column ) {
			return $value;
		}

		$event_id = absint( $item['event_id'] );
		$product_id = absint( $item['product_id'] );
		$attendee_id = absint( $item['qr_ticket_id'] );

		if ( ! $attendee_id || ! $event_id || ! $product_id ) {
			return $value;
		}

		$label = __( 'Edit details', 'attendee-data-editor' );
		$link  = " 
			<div> <a
				class='edit-attendee-data' 
				data-attendee-id='$attendee_id'
				data-event-id='$event_id'
				data-product-id='$product_id'
				href='#'>$label</a> 
 			</div> 
 		";

		 return $link . $value;
	}

	/**
	 * Get the editor HTML and send back to the browser.
	 *
	 * This repurposes the same view used for this purpose on the frontend.
	 */
	public function editor_content() {
		if ( ! $this->editor_content_checks() ) {
			wp_send_json_error();
		}

		$attendee_data = $this->get_attendee_array();
		if ( ! $attendee_data ) {
			wp_send_json_error();
		}

		ob_start();
		$tickets_view = Tribe__Tickets_Plus__Tickets_View::instance();
		$tickets_view->output_attendee_meta( $attendee_data );

		wp_send_json_success( array(
			'html' => ob_get_clean()
		) );
	}

	/**
	 * Looks like a valid request to edit the attendee data?
	 *
	 * @return bool
	 */
	protected function editor_content_checks() {
		return (
			! empty( $_POST['event_id'] )
			&& ! empty( $_POST['attendee_id'] )
			&& wp_verify_nonce( $_POST['check'], 'attendee_data_editor' )
		);
	}

	/**
	 * Obtain the attendee array expected and needed to build the edit-attendee-data
	 * form.
	 *
	 * This isn't particularly efficient, but the ET/ET+ API doesn't seem to expose
	 * any better ways at present.
	 *
	 * @return null|array
	 */
	protected function get_attendee_array() {
		$event_id = absint( $_POST['event_id' ] );
		$attendee_id = absint( $_POST['attendee_id'] );

		foreach ( Tribe__Tickets__Tickets_View::instance()->get_event_attendees_by_order( $event_id ) as $order ) {
			foreach ( $order as $order_item ) {
				if ( $attendee_id == $order_item['attendee_id'] ) {
					return $order_item;
				}
			}
		}

		return null;
	}

	/**
	 * Update the attendee data.
	 */
	public function save_fields() {
		if ( empty( $_POST['check'] ) || ! wp_verify_nonce( $_POST['check'], 'attendee_data_editor' ) ) {
			wp_send_json_error();
		}

		$event_id = absint( $_POST['event_id' ] );
		$product_id = absint( $_POST['product_id' ] );
		$attendee_id = absint( $_POST['attendee_id' ] );

		parse_str( urldecode( $_POST['fields'] ), $fields );
		$to_commmit = array();

		if ( ! is_array( $fields ) || count( $fields ) !== 1 ) {
			wp_send_json_error();
		}

		// Unpack outer layer
		$fields = current( $fields );

		if ( ! is_array( $fields ) || count( $fields ) !== 1 ) {
			wp_send_json_error();
		}

		foreach ( current( $fields ) as $name => $value ) {
			if ( ! is_scalar( $name ) || ! is_scalar( $value ) ) {
				continue;
			}

			$to_commit[ filter_var( $name, FILTER_SANITIZE_STRING ) ] = filter_var( $value, FILTER_SANITIZE_STRING );
		};

		update_post_meta( $attendee_id, '_tribe_tickets_meta', $to_commit );

		wp_send_json_success( array(
			'meta_row_html' => $this->get_updated_meta_row_html( $product_id, $attendee_id ),
		) );
	}

	/**
	 * Once we've modified the custom attendee data, we need to rebuild the
	 * sometimes hidden row that exposes the meta details to reflect the
	 * changes.
	 *
	 * @param $product_id
	 * @param $attendee_id
	 *
	 * @return string
	 */
	protected function get_updated_meta_row_html( $product_id, $attendee_id ) {
		ob_start();

		Tribe__Tickets_Plus__Meta::instance()->render()->table_meta_data( array(
			'attendee_id' => $attendee_id,
			'product_id' => $product_id,
		) );

		$row = ob_get_clean();

		// Strip the outer row HTML
		$row = str_replace( '<tr class="event-tickets-meta-row">', '', $row );
		$row = str_replace( '</tr>', '', $row );

		return $row;
	}
}