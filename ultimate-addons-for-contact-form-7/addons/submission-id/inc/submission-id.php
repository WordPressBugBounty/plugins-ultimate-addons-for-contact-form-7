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

		if ( ! isset( $value['submission_id'] ) || ! is_array( $value['submission_id'] ) ) {
			return $value;
		}

		$submission_data = $value['submission_id'];

		if ( ! isset( $submission_data['uacf7_submission_id'] ) || $submission_data['uacf7_submission_id'] === '' || $submission_data['uacf7_submission_id'] === null || intval( $submission_data['uacf7_submission_id'] ) < 0 ) {
			$value['submission_id']['uacf7_submission_id'] = 1;
		} else {
			$value['submission_id']['uacf7_submission_id'] = intval( $submission_data['uacf7_submission_id'] );
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

		$alter_query = $uacf7_db->prepare(
			"ALTER TABLE `{$uacf7_db->prefix}uacf7_form`
			MODIFY COLUMN form_date DATETIME NULL,
			ADD submission_id BIGINT(20) DEFAULT 0 NULL AFTER form_value"
		);

		$uacf7_db->query( $alter_query );
	}

}
new UACF7_SUBMISSION_ID_PANEL();

