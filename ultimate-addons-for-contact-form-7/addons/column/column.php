<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UACF7_COLUMN {

	private $hidden_fields = array();
	/*
	 * Construct function
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_column_enqueue_script' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_column_style' ) );
		add_action( 'wpcf7_init', array( __CLASS__, 'add_shortcodes' ), 10, 0 );
		add_action( 'admin_init', array( $this, 'tag_generator' ) );
		add_filter( 'wpcf7_contact_form_properties', array( $this, 'uacf7_column_properties' ), 10, 2 );
		add_filter( 'wpcf7_contact_form_properties', array( $this, 'uacf7_row_properties' ), 10, 2 );
	}

	public function admin_column_enqueue_script() {

		$screen = get_current_screen();

		$wpcf7_admin_pages = array(
			'toplevel_page_wpcf7',
			'contact_page_wpcf7-new',
		);

		if ( in_array( $screen->id, $wpcf7_admin_pages, true ) ) {
			wp_enqueue_script( 'uacf7-column', UACF7_ADDONS . '/column/assets/js/column-admin.js', array( 'jquery' ), UACF7_VERSION, true );
			wp_enqueue_style( 'uacf7-column', UACF7_ADDONS . '/column/assets/css/column-admin.css', array(), UACF7_VERSION, 'all' );
		}
	}

	public function enqueue_column_style() {
		wp_enqueue_style( 'uacf7-column', UACF7_ADDONS . '/column/grid/columns.css', array(), UACF7_VERSION, 'all' );
	}

	/*
	 * Form tag
	 */
	public static function add_shortcodes() {

		wpcf7_add_form_tag( 'uacf7-col', array( __CLASS__, 'column_tag_handler' ), true );

		wpcf7_add_form_tag( 'uacf7-row', array( __CLASS__, 'column_tag_handler' ), true );
	}

	public static function column_tag_handler( $tag ) {
		ob_start();
		$tag = new WPCF7_FormTag( $tag );
		?>
		<div>
			<?php $tag->content; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/*
	 * Generate tag - conditional
	 */
	public function tag_generator() {

		$tag_generator = WPCF7_TagGenerator::get_instance();

		$tag_generator->add(
			'uacf7-col',
			__( 'Add Column', 'ultimate-addons-for-contact-form-7' ),
			[ $this, 'tg_pane_column' ],
			array( 'version' => '2' )
		);

	}

	static function tg_pane_column( $contact_form, $options ) {
		$uacf7_field_type = 'uacf7-col';
		$field_types = array(
			'uacf7-col' => array(
				'display_name' => __( 'Add Column', 'ultimate-addons-for-contact-form-7' ),
				'heading' => __( 'Contact form 7 columns / Grid Layout', 'ultimate-addons-for-contact-form-7' ),
				'description' => __( 'You can easily create two columns, three Columns even Four columns form with Contact form 7 using this feature. Just insert tag you need from below list.', 'ultimate-addons-for-contact-form-7' ),
			),
		);

		$tgg = new WPCF7_TagGeneratorGenerator( $options['content'] );
		?>
		<header class="description-box">
			<h3><?php
			echo esc_html( $field_types['uacf7-col']['heading'] );
			?></h3>

			<p><?php
			echo wp_kses(
				$field_types['uacf7-col']['description'],
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
							/* translators: 1: Link to Columns/Grid documentation, 2: Link to Custom Columns documentation. */
							__(
								'Confused? Check our Documentation on %1$s and %2$s.',
								'ultimate-addons-for-contact-form-7'
							),
							'<a href="' . esc_url( 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-columns/' ) . '" target="_blank" rel="noopener noreferrer">' .
								esc_html__( 'Columns / Grid', 'ultimate-addons-for-contact-form-7' ) .
							'</a>',
							'<a href="' . esc_url( 'https://themefic.com/docs/uacf7/pro-addons/custom-columns-for-contact-form-7/' ) . '" target="_blank" rel="noopener noreferrer">' .
								esc_html__( 'Custom Columns', 'ultimate-addons-for-contact-form-7' ) .
							'</a>'
						)
					);
				?>
			</div>
			<h3><?php echo esc_html__( "Generate tag: Column", "ultimate-addons-for-contact-form-7" ); ?></h3>
		</header>
		<div class="control-box">
			
			<fieldset class="uacf7-column-select example-active" data-column-codes="[uacf7-row][uacf7-col col:12] --your code-- [/uacf7-col][/uacf7-row]">
				<legend>
					<?php echo esc_html__( '1 Column', 'ultimate-addons-for-contact-form-7' ); ?>
					<a class="button uacf7-column-button">
						<?php echo esc_html__( 'Insert tag', 'ultimate-addons-for-contact-form-7' ); ?>
					</a>
				</legend>
					<pre>
[uacf7-row]
	[uacf7-col col:12] --your code-- [/uacf7-col]
[/uacf7-row]37096  
					</pre>
			</fieldset>
			
			<fieldset class="uacf7-column-select" data-column-codes="[uacf7-row][uacf7-col col:6] --your code-- [/uacf7-col][uacf7-col col:6] --your code-- [/uacf7-col][/uacf7-row]">
				<legend>
					<?php echo esc_html__( '2 Column', 'ultimate-addons-for-contact-form-7' ); ?>
					<a class="button uacf7-column-button">
						<?php echo esc_html__( 'Insert tag', 'ultimate-addons-for-contact-form-7' ); ?>
					</a>
				</legend>

					<pre>
[uacf7-row]
	[uacf7-col col:6] --your code-- [/uacf7-col]
	[uacf7-col col:6] --your code-- [/uacf7-col]
[/uacf7-row]
					</pre>
			</fieldset>

			<fieldset class="uacf7-column-select" data-column-codes="[uacf7-row][uacf7-col col:4] --your code-- [/uacf7-col][uacf7-col col:4] --your code-- [/uacf7-col][uacf7-col col:4] --your code-- [/uacf7-col][/uacf7-row]">
				<legend>
					<?php echo esc_html__( '3 Column', 'ultimate-addons-for-contact-form-7' ); ?>
					<a class="button uacf7-column-button">
						<?php echo esc_html__( 'Insert tag', 'ultimate-addons-for-contact-form-7' ); ?>
					</a>
				</legend>
				<pre>
[uacf7-row]
	[uacf7-col col:4] --your code-- [/uacf7-col]
	[uacf7-col col:4] --your code-- [/uacf7-col]
	[uacf7-col col:4] --your code-- [/uacf7-col]
[/uacf7-row]
				</pre>
			</fieldset>

			<fieldset class="uacf7-column-select" data-column-codes="[uacf7-row][uacf7-col col:3] --your code-- [/uacf7-col][uacf7-col col:3] --your code-- [/uacf7-col][uacf7-col col:3] --your code-- [/uacf7-col][uacf7-col col:3] --your code-- [/uacf7-col][/uacf7-row]">
				<legend>
					<?php echo esc_html__( '4 Column', 'ultimate-addons-for-contact-form-7' ); ?>
					<a class="button uacf7-column-button">
						<?php echo esc_html__( 'Insert tag', 'ultimate-addons-for-contact-form-7' ); ?>
					</a>
				</legend>
				<pre>
[uacf7-row]
	[uacf7-col col:3] --your code-- [/uacf7-col]
	[uacf7-col col:3] --your code-- [/uacf7-col]
	[uacf7-col col:3] --your code-- [/uacf7-col]
	[uacf7-col col:3] --your code-- [/uacf7-col]
[/uacf7-row]
				</pre>
			</fieldset>
			<?php
				/**
				 * Allow extensions to add additional Column generator controls.
				 */
				$extra_fields = apply_filters(
					'uacf7_column_tag_generator_extra_fields',
					'',
					$contact_form,
					$options
				);

				if ( ! empty( $extra_fields ) ) {
					echo wp_kses_post( $extra_fields );
				}
			?>
		</div>

		<div class="insert-box uacf7-column-insert-box">
			<input type="text" name="<?php echo esc_attr( $uacf7_field_type ); ?>" class="tag code uacf7-column-tag-insert"
				readonly="readonly" onfocus="this.select()" />

			<div class="submitbox">
				<input type="button" class="button button-primary insert-tag uacf7-column-insert-tag"
					value="<?php echo esc_attr__('Insert Tag', 'ultimate-addons-for-contact-form-7' ); ?>" />
			</div>
		</div>
		<?php
	}

	public function uacf7_column_properties( $properties, $cfform ) {

		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

			$form = $properties['form'];

			$form_parts = preg_split( '/(\[\/?uacf7-col(?:\]|\s.*?\]))/', $form, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

			ob_start();

			foreach ( $form_parts as $form_part ) {

				if ( substr( $form_part, 0, 11 ) == '[uacf7-col ' ) {

					$tag_parts = explode(
						' ',
						rtrim( $form_part, ']' )
					);

					array_shift( $tag_parts );

					$ucaf7_column_class = '';

					foreach ( $tag_parts as $tag_part ) {

						if ( $tag_part == 'col:12' ) {

							$ucaf7_column_class = 'uacf7-col-12';

						} elseif ( $tag_part == 'col:6' ) {

							$ucaf7_column_class = 'uacf7-col-6';

						} elseif ( $tag_part == 'col:4' ) {

							$ucaf7_column_class = 'uacf7-col-4';

						} elseif ( $tag_part == 'col:3' ) {

							$ucaf7_column_class = 'uacf7-col-3';
						}
					}

					$html = '<div class="' . esc_attr( $ucaf7_column_class ) . '">';

					$html = apply_filters(
						'uacf7_column_opening_tag_html',
						$html,
						$tag_parts,
						$ucaf7_column_class,
						$form_part
					);

					echo wp_kses_post( $html );

				} elseif ( $form_part == '[/uacf7-col]' ) {

					echo wp_kses_post( '</div>' );

				} else {

					echo wp_kses_post( $form_part );
				}
			}

			$properties['form'] = ob_get_clean();
		}
		return $properties;
	}

	public function uacf7_row_properties( $properties, $cfform ) {

		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

			$form = $properties['form'];

			$form_parts = preg_split( '/(\[\/?uacf7-row(?:\]|\s.*?\]))/', $form, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

			ob_start();

			foreach ( $form_parts as $form_part ) {
				if ( substr( $form_part, 0, 10 ) == '[uacf7-row' ) {
					$tag_parts = explode( ' ', rtrim( $form_part, ']' ) );

					array_shift( $tag_parts );

					echo '<div class="uacf7-row">';
				} else if ( $form_part == '[/uacf7-row]' ) {
					echo '</div>';
				} else {
					echo wp_kses_post( $form_part );
				}
			}

			$properties['form'] = ob_get_clean();
		}
		return $properties;
	}


}
new UACF7_COLUMN();
