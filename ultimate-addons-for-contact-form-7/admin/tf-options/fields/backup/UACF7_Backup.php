<?php
//don't load directly
defined( 'ABSPATH' ) || exit;
//backup import export field
if ( ! class_exists( 'UACF7_Backup' ) ) {
	class UACF7_Backup extends UACF7_Fields {
		public function __construct( $field, $value = '', $settings_id = '', $parent_field = '' ) {
			parent::__construct( $field, $value, $settings_id, $parent_field );
		}
		public function render() {
			$form_id = 0;
			if ( isset( $this->field['form_id'] ) && ! empty( $this->field['form_id'] ) && $this->field['form_id'] != '0' ) {
				$form_id = absint( $this->field['form_id'] );
			}

			// Verify authorization before exporting settings
			if ( ! empty( $form_id ) ) {
				if ( ! current_user_can( 'wpcf7_edit_contact_form', $form_id ) && ! current_user_can( 'edit_post', $form_id ) ) {
					return;
				}
				$current_settings = get_post_meta( $form_id, $this->settings_id, true );
			} else {
				if ( ! current_user_can( 'wpcf7_edit_contact_forms' ) && ! current_user_can( 'manage_options' ) ) {
					return;
				}
				$current_settings = get_option( $this->settings_id );
			}
			$current_settings = isset( $current_settings ) && ! empty( $current_settings ) ? wp_json_encode( $current_settings ) : '';

			$placeholder = ( ! empty( $this->field['placeholder'] ) ) ? 'placeholder="' . esc_attr( $this->field['placeholder'] ) . '"' : '';
			echo '<div class="tf-export-wrapper">';
			echo '<div id="copyIndicator"></div>';
			echo '<textarea cols="50" rows="15" class="tf-exp-imp-field tf-export-field"  data-option="' . esc_attr( $this->settings_id ) . '" name="tf_export_option" id="export' . esc_attr( $this->field_name() ) . '" ' . $placeholder . ' ' . esc_attr( $this->field_attributes() ) . ' disabled >' . esc_textarea( $current_settings ) . '</textarea>';
			echo '<a class="tf-export-button tf-admin-btn tf-btn-secondary">' . esc_html__( 'Copy', 'ultimate-addons-for-contact-form-7' ) . '</a>';
			echo '</div>';
			echo '<hr>';
			echo '<textarea class="tf-exp-imp-field tf-import-field" cols="50" rows="15" data-form-id="' . esc_attr( $form_id ) . '" name="tf_import_option" id="' . esc_attr( $this->field_name() ) . '" ' . $placeholder . ' ' . esc_attr(  $this->field_attributes() ) . '> </textarea>';
			// echo '<a href="#" class="tf-import-btn button button-primary">' . esc_html__( 'Import', 'ultimate-addons-for-contact-form-7' ) . '</a>';
			echo '<button type="submit" class="tf-import-btn tf-admin-btn tf-btn-secondary" data-option="' . esc_attr( $this->settings_id ) . '" data-submit-type="tf_import_data">' . esc_html__( 'Import', 'ultimate-addons-for-contact-form-7' ) . '</button>';

		}
	}
}