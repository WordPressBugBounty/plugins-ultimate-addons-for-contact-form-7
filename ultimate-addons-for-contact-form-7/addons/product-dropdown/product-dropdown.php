<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UACF7_PRODUCT_DROPDOWN {

	private $hidden_fields = array();
	/*
	 * Construct function
	 */
	public function __construct() {
		add_action( 'wpcf7_init', array( $this, 'add_shortcodes' ) );
		add_action( 'admin_init', array( $this, 'tag_generator' ) );
		add_filter( 'wpcf7_validate_uacf7_product_dropdown', array( $this, 'wpcf7_product_dropdown_validation_filter' ), 10, 2 );
		add_filter( 'wpcf7_validate_uacf7_product_dropdown*', array( $this, 'wpcf7_product_dropdown_validation_filter' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_script' ) );
	}

	public function admin_enqueue_script() {

		wp_enqueue_script( 'uacf7-product-dropdown', UACF7_ADDONS . '/product-dropdown/assets/admin-script.js', array( 'jquery' ), UACF7_VERSION, true );
	}


	/*
	 * Form tag
	 */
	public function add_shortcodes() {

		wpcf7_add_form_tag( array( 'uacf7_product_dropdown', 'uacf7_product_dropdown*' ),
			array( $this, 'tag_handler_callback' ), array( 'name-attr' => true ) );
	}

	public function tag_handler_callback( $tag ) {

		if ( empty( $tag->name ) ) {
			return '';
		}

		$validation_error = wpcf7_get_validation_error( $tag->name );
		$class = wpcf7_form_controls_class( $tag->type );

		if ( $validation_error ) {
			$class .= ' wpcf7-not-valid';
		}

		$atts = array();
		$atts['class'] = $tag->get_class_option( $class );
		$atts['id'] = $tag->name;
		$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );

		if ( $tag->is_required() ) {
			$atts['aria-required'] = 'true';
		}

		$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

		if ( $tag->has_option( 'size' ) ) {
			$size = $tag->get_option( 'size', 'int', true );
			if ( $size ) {
				$atts['size'] = $size;
			}
		}

		if ( $data = (array) $tag->get_data_option() ) {
			$tag->values = array_merge( $tag->values, array_values( $data ) );
		}

		$values = $tag->values;
		$query_args = array(
			'post_type' => 'product',
			'posts_per_page' => -1,
			'post_status' => 'publish',
		);

		$field_state = apply_filters( 'uacf7_product_dropdown_field_state', array(
			'query_args' => $query_args,
			'select_atts' => $atts,
			'field_name' => $tag->name,
			'default_option_args' => array(),
		), $tag, $values );

		$query_args = isset( $field_state['query_args'] ) && is_array( $field_state['query_args'] ) ? $field_state['query_args'] : $query_args;
		$atts = isset( $field_state['select_atts'] ) && is_array( $field_state['select_atts'] ) ? $field_state['select_atts'] : $atts;
		$field_name = isset( $field_state['field_name'] ) ? $field_state['field_name'] : $tag->name;
		$default_option_args = isset( $field_state['default_option_args'] ) && is_array( $field_state['default_option_args'] ) ? $field_state['default_option_args'] : array();

		$field_state['query_args'] = $query_args;
		$field_state['select_atts'] = $atts;
		$field_state['field_name'] = $field_name;
		$field_state['default_option_args'] = $default_option_args;

		$default_choice = $tag->get_default_option( null, $default_option_args );
		$hangover = wpcf7_get_hangover( $tag->name );
		$products = new WP_Query( $query_args );

		$dropdown = '<option value="">-Select-</option>';
		while ( $products->have_posts() ) {
			$products->the_post();

			if ( $hangover ) {
				$selected = in_array( get_the_title(), (array) $hangover, true );
			} else {
				$selected = in_array( get_the_title(), (array) $default_choice, true );
			}

			$item_atts = array(
				'value' => get_the_title(),
				'selected' => $selected ? 'selected' : '',
				'product-id' => get_the_id(),
			);

			$item_atts = wpcf7_format_atts( $item_atts );
			$label = get_the_title();
			$dropdown .= sprintf( '<option %1$s>%2$s</option>', $item_atts, esc_html( $label ) );
		}
		wp_reset_postdata();

		$atts['aria-invalid'] = $validation_error ? 'true' : 'false';
		$atts['name'] = $field_name;
		$atts = wpcf7_format_atts( $atts );

		$dropdown = sprintf(
			'<div class="%1$s"><span class="wpcf7-form-control-wrap %1$s" data-name="%1$s"><select %2$s>%3$s</select></span><span>%4$s</span></div>',
			sanitize_html_class( $tag->name ), $atts, $dropdown, $validation_error
		);

		return apply_filters( 'uacf7_product_dropdown_html', $dropdown, $tag, $field_state, $hangover, $default_choice, $validation_error );
	}

	public function wpcf7_product_dropdown_validation_filter( $result, $tag ) {

		$name = $tag->name;
		$submission = WPCF7_Submission::get_instance();
		$posted_data = $submission ? $submission->get_posted_data() : array();
		$posted_value = isset( $posted_data[ $name ] ) ? $posted_data[ $name ] : null;

		if ( isset( $posted_value ) ) {
			if ( is_array( $posted_value ) ) {
				$posted_value = array_map( 'sanitize_text_field', $posted_value );
				$posted_value = array_filter( $posted_value, static function ( $value ) {
					return '' !== $value;
				} );
			} else {
				$posted_value = sanitize_text_field( $posted_value );
			}
		}

		$empty = null === $posted_value || '' === $posted_value || array() === $posted_value;

		if ( $tag->is_required() && $empty ) {
			$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
		}

		return $result;
	}

	/*
	 * Generate tag - conditional
	 */
	public function tag_generator() {

		$tag_generator = WPCF7_TagGenerator::get_instance();

		$tag_generator->add(
			'uacf7_product_dropdown',
			__( 'Product Dropdown', 'ultimate-addons-for-contact-form-7' ),
			[ $this, 'tg_pane_product_dropdown' ],
			array( 'version' => '2' )
		);

	}

	static function tg_pane_product_dropdown( $contact_form, $options ) {

		$field_types = array(
			'uacf7_product_dropdown' => array(
				'display_name' => __( 'Product Dropdown', 'ultimate-addons-for-contact-form-7' ),
				'heading' => __( 'Generate Product Dropdown', 'ultimate-addons-for-contact-form-7' ),
				'description' => '',
			),
		);

		$tgg = new WPCF7_TagGeneratorGenerator( $options['content'] );

		?>

		<header class="description-box">
			<h3><?php
			echo esc_html( $field_types['uacf7_product_dropdown']['heading'] );
			?></h3>

			<p><?php
			echo wp_kses(
				$field_types['uacf7_product_dropdown']['description'],
				array(
					'a' => array( 'href' => true ),
					'strong' => array(),
				),
				array( 'http', 'https' )
			);

			?></p>
			<div class="uacf7-doc-notice">
				<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %1$s: Link to the Product Dropdown documentation. */
							__(
								'Confused? Check our Documentation on %1$s.',
								'ultimate-addons-for-contact-form-7'
							),
							'<a href="' . esc_url( 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-woocommerce/' ) . '" target="_blank" rel="noopener noreferrer">' .
								esc_html__( 'Product Dropdown', 'ultimate-addons-for-contact-form-7' ) .
							'</a>'
						)
					);
				?>
			</div>
		</header>

		<div class="control-box uacf7-control-box">
			<?php

			$tgg->print( 'field_type', array(
				'with_required' => true,
				'select_options' => array(
					'uacf7_product_dropdown' => $field_types['uacf7_product_dropdown']['display_name'],
				),
			) );

			$tgg->print( 'field_name' );

			?>

			<?php

				/**
				 * Allow extensions to register additional
				 * Product Dropdown generator fields.
				 */
				do_action(
					'uacf7_product_dropdown_tag_generator_fields',
					$tgg,
					$contact_form,
					$options
				);

			?>

			<?php $tgg->print( 'class_attr' ); ?>

		</div>

		<footer class="insert-box">
			<?php
			$tgg->print( 'insert_box_content' );

			$tgg->print( 'mail_tag_tip' );
			?>
		</footer>
		<?php
	}

}
new UACF7_PRODUCT_DROPDOWN();
