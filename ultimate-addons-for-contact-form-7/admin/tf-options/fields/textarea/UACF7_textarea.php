<?php
// don't load directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'UACF7_textarea' ) ) {
	class UACF7_textarea extends UACF7_Fields {

		public function __construct( $field, $value = '', $settings_id = '', $parent_field = '', $section_key = '' ) {
			parent::__construct( $field, $value, $settings_id, $parent_field, $section_key  );
		}

		public function render() {
			$placeholder = ( ! empty( $this->field['placeholder'] ) ) ? 'placeholder="' . esc_attr( $this->field['placeholder'] ) . '"' : '';
			$value = $this->field['id'] == 'uacf7_booking_calendar_key' ? stripslashes($this->value) : $this->value;
			echo '<textarea name="' . esc_attr( $this->field_name() ) . '" id="' . esc_attr( $this->field_name() ) . '"' . wp_kses_post($placeholder) . ' '. esc_attr( $this->field_attributes() ) .'>' . esc_textarea( $value ) . '</textarea>';
		}

	}
}