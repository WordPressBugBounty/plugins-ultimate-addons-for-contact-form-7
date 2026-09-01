<?php
// don't load directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'UACF7_code_editor' ) ) {
	class UACF7_code_editor extends UACF7_Fields {

		public $version = '5.65.15';
		public $editor_settings = false;

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

			$editor_settings = is_array( $this->editor_settings ) ? $this->editor_settings : array();
			$editor_codemirror = isset( $editor_settings['codemirror'] ) && is_array( $editor_settings['codemirror'] )
				? $editor_settings['codemirror']
				: array();

			$editor_codemirror = array_merge( $editor_codemirror, $settings );
			$editor_settings['codemirror'] = $editor_codemirror;

			$editor_id = wp_unique_id( 'uacf7-code-editor-' );
			?>
			<div class="tf-field-textarea tf-field-code-editor">
				<?php
					echo '<textarea id="' . esc_attr( $editor_id ) . '" name="' . esc_attr( $this->field_name() ) . '"' . esc_attr( $this->field_attributes() ) . ' data-editor="' . esc_attr( wp_json_encode( $editor_settings ) ) . '">' . esc_textarea( $this->value ) . '</textarea>';
				?>
			</div>
			<?php
		}

		public function enqueue() {
			$page = filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW );
			$page = is_string( $page ) ? sanitize_key( $page ) : '';

			// Do not load the code editor on the RevSlider page.
			if ( in_array( $page, array( 'revslider' ), true ) ) {
				return;
			}

			if ( ! function_exists( 'wp_enqueue_code_editor' ) ) {
				return;
			}

			$this->editor_settings = wp_enqueue_code_editor(
				array(
					'type'       => 'text/css',
					'codemirror' => array(
						'indentUnit'       => 2,
						'tabSize'          => 2,
						'lineNumbers'      => true,
						'lineWrapping'     => true,
						'autoCloseBrackets' => true,
						'matchBrackets'    => true,
						'theme'            => 'monokai',
						'mode'             => 'text/css',
					),
				)
			);

			if ( false === $this->editor_settings ) {
				return;
			}

			wp_enqueue_style(
				'uacf7-code-editor-monokai',
				UACF7_URL . 'assets/admin/css/uacf7-code-editor-monokai.css',
				array( 'code-editor' ),
				$this->version
			);
		}

		public function sanitize() {
			return wp_kses_post( $this->value );
		}
	}
}
