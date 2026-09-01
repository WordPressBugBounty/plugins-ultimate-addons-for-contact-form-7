<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class UACF7_DBMigrator {


	public function __construct() {
	}

	public function uacf7dp_check_free_db() {
		global $wpdb;
		$uacf7_db = $wpdb;
		$uacf7_form_table = $uacf7_db->prefix . 'uacf7_form';
		$Saved_form_data = $uacf7_db->get_results( $uacf7_db->prepare( 'SELECT * FROM %i', $uacf7_form_table ) );
		$ExtraFields = [];

		if ( ! empty( $Saved_form_data ) ) {

			// Delete all data from wp_uacf7dp_data
			$uacf7_data_table = $uacf7_db->prefix . 'uacf7dp_data';
			$uacf7_db->query( $uacf7_db->prepare( 'TRUNCATE TABLE %i', $uacf7_data_table ) );

			// Delete all data from wp_uacf7dp_data_entry
			$uacf7_data_entry_table = $uacf7_db->prefix . 'uacf7dp_data_entry';
			$uacf7_db->query( $uacf7_db->prepare( 'TRUNCATE TABLE %i', $uacf7_data_entry_table ) );

			$getting_old_from_entrys = [];

			foreach ( $Saved_form_data as $form_key => $form_data ) {
				$old_subform_input_data = $form_data->form_value;
				$form_cf7_id = $form_data->form_id;

				// Making extra fields
				$ExtraFields['submit_ip'] = null;
				$ExtraFields['submit_time'] = $form_data->form_date;
				$ExtraFields['cf7_form_id'] = $form_cf7_id;

				// Convert the object to an array
				$old_subform_input_data = json_decode( $old_subform_input_data );
				$old_subform_input_data_array = is_array( $old_subform_input_data ) ? $old_subform_input_data : json_decode( json_encode( $old_subform_input_data ), true );


				$org_old_subform_data = array_merge( $old_subform_input_data_array, $ExtraFields );
				$getting_old_from_entrys[] = $org_old_subform_data;
			}

			$this->uacf7dp_get_form_data_migrat( $getting_old_from_entrys );
		}


	}

	public function uacf7dp_get_form_data_migrat( $insert_data ) {
		global $wpdb;
		$uacf7_db = $wpdb;
		$Get_all_form_entry = $insert_data;

		// Insert data to the pro data table 
		foreach ( $Get_all_form_entry as $form_key => $data ) {
			$submit_form_id = $data['cf7_form_id'];
			$submit_ip = $data['submit_ip'];
			$submit_time = $data['submit_time'];

			$uacf7_db->query( $uacf7_db->prepare( 'INSERT INTO ' . $uacf7_db->prefix . 'uacf7dp_data(`cf7_form_id`, `submit_ip`, `submit_time`) VALUES (%d, %d, %s)', $submit_form_id, $submit_ip, $submit_time ) );
			$data_id = $uacf7_db->insert_id;

			$uacf7dp_no_save_fields = uacf7dp_no_save_fields();

			foreach ( $data as $k => $v ) {
				if ( in_array( $k, $uacf7dp_no_save_fields ) ) {
					continue;
				} else {
					if ( is_array( $v ) ) {
						$v = implode( "\n", $v );
					}
					$uacf7_db->query( $uacf7_db->prepare( 'INSERT INTO ' . $uacf7_db->prefix . 'uacf7dp_data_entry(`cf7_form_id`, `data_id`, `fields_name`, `value`) VALUES (%d,%d,%s,%s)', $submit_form_id, $data_id, $k, $v ) );
				}
			}

		}
	}
}
