<?php
// don't load directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'UACF7_code_editor' ) ) {
	class UACF7_code_editor extends UACF7_Fields {

		public $version = '5.65.15';

		public function __construct( $field, $value = '', $settings_id = '', $parent_field = '', $section_key = '' ) {
			parent::__construct( $field, $value, $settings_id, $parent_field, $section_key );
			$this->enqueue();
		}

		public function render() {
			$default_settings = array(
				'tabSize'     => 2,
				'lineNumbers' => true,
				'theme'       => 'monokai',
				'mode'        => 'css',
			);

			$settings = ( ! empty( $this->field['settings'] ) ) ? $this->field['settings'] : array();
			$settings = wp_parse_args( $settings, $default_settings );
			?>
			<div class="tf-field-textarea tf-field-code-editor">
				<?php
					echo '<textarea name="' . esc_attr( $this->field_name() ) . '"' . esc_attr( $this->field_attributes() ) . ' data-editor="' . esc_attr( json_encode( $settings ) ) . '">' . wp_kses_post( $this->value ) . '</textarea>';
				?>
			</div>
			<?php
		}

		public function enqueue() {
			$page = filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW );
			$page = is_string( $page ) ? sanitize_key( $page ) : '';

			// Do not loads CodeMirror in revslider page.
			if ( in_array( $page, array( 'revslider' ) ) ) { return; }

			$code = UACF7_URL . 'assets/admin/libs/codemirror/';

			if ( ! wp_script_is( 'tf-code' ) ) {
				wp_enqueue_script( 'tf-code', $code . 'code.min.js', array(), $this->version, true );
				wp_enqueue_script( 'tf-code-loadmode', $code . 'loadmode.min.js', array( 'tf-code' ), $this->version, true );
        		wp_enqueue_script( 'tf-code-mode-css', $code . 'mode/css.min.js', array(), $this->version, true );
			}

			if ( ! wp_style_is( 'tf-code' ) ) {
				wp_enqueue_style( 'tf-code', $code . 'code.min.css', array(), $this->version );
			}
      
			if ( ! wp_style_is( 'tf-code-theme-monokai' ) ) {
				wp_enqueue_style( 'tf-code-theme-monokai', UACF7_URL . 'assets/admin/libs/codemirror/monokai.css', array( 'tf-code' ), $this->version );
			}

		}

		public function sanitize() {
			return wp_kses_post($this->value);
		}
	}
}