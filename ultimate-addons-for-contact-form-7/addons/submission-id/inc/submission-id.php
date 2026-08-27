<?php

// Do not access directly

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UACF7_SUBMISSION_ID_PANEL {

	public function __construct() {
		add_action( 'admin_init', [ $this, 'uacf7_create_submission_id_database_col' ] );
		add_filter( 'uacf7_metabox_before_save_option', [ $this, 'tf_metabox_before_save_option_submission_id_callback' ], 10, 2 );

	}



	/**
	 * Before Save Metabox Action
	 */
	public function tf_metabox_before_save_option_submission_id_callback( $value, $form_id ) {

		$submission = WPCF7_Submission::get_instance();
		$submission_data = $value['submission_id'];
		// exit;
		if ( $submission_data['uacf7_submission_id'] < 0 || $submission_data['uacf7_submission_id'] === null || $submission_data['uacf7_submission_id'] === '' ) {
			$initial_value = 1;
			$value['submission_id']['uacf7_submission_id'] = $initial_value;
		} else {

			global $wpdb;
			$uacf7_db = $wpdb;
			$table_name = $uacf7_db->prefix . 'uacf7_form';
			$last_item = $uacf7_db->get_row(
				$uacf7_db->prepare(
					"SELECT * FROM `{$uacf7_db->prefix}uacf7_form`
					WHERE form_id = %d
					ORDER BY submission_id DESC
					LIMIT 1",
					absint( $form_id )
				)
			);

			/** Submission ID Conditional Update */
			if ( $last_item !== null && $last_item->submission_id != 0 ) {
				$default_step = $submission_data['uacf7_submission_id_step'] != '' ? $submission_data['uacf7_submission_id_step'] : 1;

				if ( isset( $submission_data['uacf7_submission_id'] ) && $submission_data['uacf7_submission_id'] > $last_item->submission_id ) {
					$value['submission_id']['uacf7_submission_id'] = $submission_data['uacf7_submission_id'];
					// update_post_meta( $form->id(), 'uacf7_submission_id', sanitize_text_field($submission_data['uacf7_submission_id']) );
				} else {
					$value['submission_id']['uacf7_submission_id'] = $last_item->submission_id + intval( $default_step );
					// update_post_meta( $form->id(), 'uacf7_submission_id', sanitize_text_field($last_item->submission_id + intval($default_step))  );
				}
			} else {
				$value['submission_id']['uacf7_submission_id'] = $submission_data['uacf7_submission_id'];
				// update_post_meta( $form->id(), 'uacf7_submission_id', sanitize_text_field($submission_data['uacf7_submission_id']) );
			}

		}

		return $value;

	}



	/**
	 * Create the submission_id database column.
	 *
	 * @return void
	 */
	public function uacf7_create_submission_id_database_col() {
		global $wpdb;
		$uacf7_db = $wpdb;
		$table_name = $uacf7_db->prefix . 'uacf7_form';

		$table_exists = $uacf7_db->get_var(
			$uacf7_db->prepare(
				'SHOW TABLES LIKE %s',
				$uacf7_db->esc_like( $table_name )
			)
		);

		if ( $table_name !== $table_exists ) {
			return;
		}

		$column_exists = $uacf7_db->get_var(
			$uacf7_db->prepare(
				'SELECT COLUMN_NAME
				FROM information_schema.COLUMNS
				WHERE TABLE_SCHEMA = %s
				AND TABLE_NAME = %s
				AND COLUMN_NAME = %s
				LIMIT 1',
				$uacf7_db->dbname,
				$table_name,
				'submission_id'
			)
		);

		if ( 'submission_id' === $column_exists ) {
			return;
		}

		$uacf7_db->query(
			"ALTER TABLE `{$uacf7_db->prefix}uacf7_form`
			MODIFY COLUMN form_date DATETIME NULL,
			ADD submission_id BIGINT(20) DEFAULT 0 NULL AFTER form_value"
		);
	}

}
new UACF7_SUBMISSION_ID_PANEL();

