<?php
// don't load directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'UACF7_Options' ) ) {
	class UACF7_Options {

		private static $instance = null;

		/**
		 * Singleton instance
		 * @since 1.0.0
		 */
		public static function instance() {
			if ( self::$instance == null ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		public function __construct() {
			//load files
			$this->load_files();

			//load metaboxes
			$this->load_metaboxes();

			//load options
			$this->load_options();

			//load taxonomy
			$this->load_taxonomy();

			//enqueue scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'tf_options_admin_enqueue_scripts' ), 1 );
			add_action( 'admin_enqueue_scripts', array( $this, 'uacf7_admin_dequeue_scripts' ), 999 );
			// add_action( 'wp_enqueue_scripts', array( $this, 'tf_options_wp_enqueue_scripts' ) );

			// Import Export
			add_action( 'wp_ajax_uacf7_option_import', array( $this, 'uacf7_option_import_callback' ) );

		}

		public function tf_options_version() {
			return '1.0.0';
		}

		public function uacf7_options_file_path( $file_path = '' ) {
			return plugin_dir_path( __FILE__ ) . $file_path;
		}

		public function tf_options_file_url( $file_url = '' ) {
			return plugin_dir_url( __FILE__ ) . $file_url;
		}

		/**
		 * Import Export Callback
		 * @author Sydur Rahman
		 */
		public function uacf7_option_import_callback() {

			if ( ! isset( $_POST['ajax_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ajax_nonce'] ) ), 'tf_options_nonce' ) ) {
				return;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					[ 'message' => __( 'You do not have sufficient permissions to access this page.', 'ultimate-addons-for-contact-form-7' ) ]
				);
			}

			$imported_data_json = isset( $_POST['tf_import_option'] ) ? sanitize_textarea_field( wp_unslash( $_POST['tf_import_option'] ) ) : '';
			$imported_data = ! empty( $imported_data_json ) ? json_decode( trim( $imported_data_json ), true ) : '';
			$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;

			$response = [ 
				'status' => 'error',
				'message' => __( 'Something went wrong!', 'ultimate-addons-for-contact-form-7' ),
			];

			if ( ! empty( $imported_data ) && is_array( $imported_data ) ) {
				if ( $form_id != 0 ) {

					update_post_meta( $form_id, 'uacf7_form_opt', $imported_data );

				}

				$response = [ 
					'status' => 'success',
					'message' => __( 'Options imported successfully!', 'ultimate-addons-for-contact-form-7' ),
				];
			} else {
				$response = [ 
					'status' => 'error',
					'message' => __( 'Your imported data is not valid', 'ultimate-addons-for-contact-form-7' ),
				];
			}


			wp_send_json_success( $response );
		}

		/**
		 * Load files
		 * @author Foysal
		 */
		public function load_files() {
			// Metaboxes Class
			require_once $this->uacf7_options_file_path( 'classes/UACF7_Metabox.php' );
			// Settings Class
			require_once $this->uacf7_options_file_path( 'classes/UACF7_Settings.php' );
			//Taxonomy Class
			require_once $this->uacf7_options_file_path( 'classes/UACF7_Taxonomy_Metabox.php' );

		}

		/**
		 * Load metaboxes
		 * @author Foysal
		 */
		public function load_metaboxes() {

			$metaboxes = glob( $this->uacf7_options_file_path( 'metaboxes/*.php' ) );

			if ( ! empty( $metaboxes ) ) {
				foreach ( $metaboxes as $metabox ) {
					if ( file_exists( $metabox ) ) {
						require_once $metabox;
					}
				}
			}
		}

		/**
		 * Load Options
		 * @author Foysal
		 */
		public function load_options() {

			$options = glob( $this->uacf7_options_file_path( 'options/*.php' ) );

			if ( ! empty( $options ) ) {
				foreach ( $options as $option ) {
					if ( file_exists( $option ) ) {
						require_once $option;
					}
				}
			}
		}

		/**
		 * Load Taxonomy
		 * @author Foysal
		 */
		public function load_taxonomy() {

			$taxonomies = glob( $this->uacf7_options_file_path( 'taxonomies/*.php' ) );

			if ( ! empty( $taxonomies ) ) {
				foreach ( $taxonomies as $taxonomy ) {
					if ( file_exists( $taxonomy ) ) {
						require_once $taxonomy;
					}
				}
			}
		}

		/**
		 * Admin Enqueue scripts
		 * @author Foysal
		 */
		public function tf_options_admin_enqueue_scripts( $screen ) {
			global $post_type;

			$tf_options_screens = array(
				'toplevel_page_uacf7_settings',
				'cf7-addons_page_uacf7_addons',
				'toplevel_page_wpcf7',
				'contact_page_wpcf7-new',
				'admin_page_uacf7-setup-wizard',
				'cf7-addons_page_uacf7_license_info',
			);
			$tf_options_post_type = array( 'uacf7_review' );

			//Uacf7 CDN CSS
			if ( in_array( $screen, $tf_options_screens ) || in_array( $post_type, $tf_options_post_type ) ) {

				wp_enqueue_style( 'uacf7-admin', UACF7_URL . 'assets/admin/css/uacf7-admin.min.css', '', UACF7_VERSION );
				// wp_enqueue_style('wp-color-picker');

				wp_enqueue_style( 'uacf7-fontawesome-4', UACF7_URL . 'assets/admin/libs/font-awesome/fontawesome4/css/font-awesome.min.css', array(), $this->tf_options_version() );
				wp_enqueue_style( 'uacf7-fontawesome-5', UACF7_URL . 'assets/admin/libs/font-awesome/fontawesome5/css/all.min.css', array(), $this->tf_options_version() );
				wp_enqueue_style( 'uacf7-fontawesome-6', UACF7_URL . 'assets/admin/libs/font-awesome/fontawesome6/css/all.min.css', array(), $this->tf_options_version() );
				wp_enqueue_style( 'uacf7-remixicon', UACF7_URL . 'assets/admin/libs/remixicon/remixicon.css', array(), $this->tf_options_version() );
				wp_enqueue_style( 'uacf7-select2', UACF7_URL . 'assets/admin/libs/select2/select2.min.css', array(), $this->tf_options_version() );
				wp_enqueue_style( 'uacf7-flatpickr', UACF7_URL . 'assets/admin/libs/flatpickr/flatpickr.min.css', array(), $this->tf_options_version() );

			}

			//Uacf7 Js
			if ( in_array( $screen, $tf_options_screens ) || in_array( $post_type, $tf_options_post_type ) ) {
				// Custom

				// wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_script( 'uacf7-admin', UACF7_URL . 'assets/admin/js/uacf7-admin-scripts.min.js', array( 'jquery', 'wp-data', 'wp-editor', 'wp-edit-post' ), UACF7_VERSION, true );
				wp_enqueue_script( 'uacf7-flatpickr', UACF7_URL . 'assets/admin/libs/flatpickr/flatpickr.min.js', array( 'jquery' ), $this->tf_options_version(), true );
				wp_enqueue_script( 'uacf7-select2', UACF7_URL . 'assets/admin/libs/select2/select2.min.js', array( 'jquery' ), $this->tf_options_version(), true );
			
				// }
				wp_enqueue_script( 'jquery-ui-autocomplete' );

				if ( ! wp_script_is( 'jquery-ui-sortable' ) ) {
					wp_enqueue_script( 'jquery-ui-sortable' );
				}
				wp_enqueue_media();
				wp_enqueue_editor();
			}

			// Wp color picker
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );

			wp_localize_script(
				'uacf7-admin',
				'uacf7_setting_options',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'tf_options_nonce' ),
					'tf_export_import_msg' => array(
						'imported' => __( 'Imported successfully!', 'ultimate-addons-for-contact-form-7' ),
						'import_confirm' => __( 'Are you sure you want to import this data?', 'ultimate-addons-for-contact-form-7' ),
						'import_empty' => __( 'Import Data cannot be empty!', 'ultimate-addons-for-contact-form-7' ),
					)
				)
			);
		}



		/**
		 * Dequeue scripts
		 */
		public function tf_options_admin_dequeue_scripts( $screen ) {
			$tf_options_post_type = array( 'uacf7_review' );

			if ( $screen == 'toplevel_page_uacf7_settings' ) {
				wp_dequeue_script( 'theplus-admin-js-pro' );
			}
		}

		/**
		 * Enqueue scripts
		 * @author Foysal
		 */
		public function tf_options_wp_enqueue_scripts() {
		
			wp_enqueue_style( 'uacf7-fontawesome-4', UACF7_URL . 'assets/admin/libs/font-awesome/fontawesome4/css/font-awesome.min.css', array(), $this->tf_options_version() );
			wp_enqueue_style( 'uacf7-fontawesome-5', UACF7_URL . 'assets/admin/libs/font-awesome/fontawesome5/css/all.min.css', array(), $this->tf_options_version() );
			wp_enqueue_style( 'uacf7-fontawesome-6', UACF7_URL . 'assets/admin/libs/font-awesome/fontawesome6/css/all.min.css', array(), $this->tf_options_version() );
			wp_enqueue_style( 'uacf7-remixicon', UACF7_URL . 'assets/admin/libs/remixicon/remixicon.css', array(), $this->tf_options_version() );

		}


		public function uacf7_admin_dequeue_scripts( $screen ) {

			global $wp_scripts;

			$tf_options_screens = array(
				'toplevel_page_uacf7_settings',
				'cf7-addons_page_uacf7_addons',
				'toplevel_page_wpcf7',
				'contact_page_wpcf7-new',
				'admin_page_uacf7-setup-wizard',
				'cf7-addons_page_uacf7_license_info',
			);
			if ( in_array( $screen, $tf_options_screens ) ) {
				if ( wp_script_is( 'acf-color-picker-alpha', 'enqueued' ) ) {

					$acf_script_handle = 'acf-color-picker-alpha';
					$acf_script_data = $wp_scripts->registered[ $acf_script_handle ];

					wp_dequeue_script( $acf_script_handle );

					if ( isset( $acf_script_data ) ) {
						wp_enqueue_script( $acf_script_handle, $acf_script_data->src, $acf_script_data->deps, $acf_script_data->ver, true );
					}
				}
			}
		}

		/*
		 * Field Base
		 * @author Foysal
		 */
		public function field( $field, $value, $settings_id = '', $parent = '', $section_key = '' ) {
			if ( $field['type'] == 'repeater' ) {
				$id = ( ! empty( $settings_id ) ) ? $settings_id . '[' . $field['id'] . '][0]' . '[' . $field['id'] . ']' : $field['id'] . '[0]' . '[' . $field['id'] . ']';
			} else {
				$id = $settings_id . '[' . $field['id'] . ']';
			}

			// uacf7_print_r($field);
			$class = isset( $field['class'] ) ? $field['class'] : '';

			$badge_up = isset( $field['badge_up'] ) ? $field['badge_up'] : '';

			/**
			 * Generic field badges.
			 *
			 * Free plugin only knows about its own generic/default badges.
			 * Extensions can add additional badges through
			 * `uacf7_option_field_state`.
			 */
			$badges = array();

			if ( $badge_up == true ) {

				$class .= ' tf-field-disable tf-field-upcoming';

				$badges[] = array(
					'label' => __(
						'Upcoming',
						'ultimate-addons-for-contact-form-7'
					),
					'class' => 'tf-upcoming',
				);
			}

			/**
			 * Generic field UI state.
			 *
			 * Extensions can modify:
			 *
			 * - field classes
			 * - badges
			 * - disabled state
			 */
			$field_state = apply_filters(
				'uacf7_option_field_state',
				array(
					'class'    => $class,
					'badges'   => $badges,
					'disabled' => false,
				),
				$field,
				$value,
				$settings_id,
				$parent,
				$section_key
			);

			$class = isset( $field_state['class'] )
				? $field_state['class']
				: '';

			$badges = isset( $field_state['badges'] ) && is_array( $field_state['badges'] )
				? $field_state['badges']
				: array();

			/**
			 * Pass generic disabled state to the actual
			 * field renderer if an extension sets it.
			 */
			if ( ! empty( $field_state['disabled'] ) ) {
				$field['disabled'] = true;
			}

			$depend = '';
			if ( ! empty( $field['dependency'] ) ) {

				$dependency = $field['dependency'];
				$depend_visible = '';
				$data_controller = '';
				$data_condition = '';
				$data_value = '';
				$data_global = '';

				if ( is_array( $dependency[0] ) ) {
					$data_controller = implode( '|', array_column( $dependency, 0 ) );
					$data_condition = implode( '|', array_column( $dependency, 1 ) );
					$data_value = implode( '|', array_column( $dependency, 2 ) );
					$data_global = implode( '|', array_column( $dependency, 3 ) );
					$depend_visible = implode( '|', array_column( $dependency, 4 ) );
				} else {
					$data_controller = ( ! empty( $dependency[0] ) ) ? $dependency[0] : '';
					$data_condition = ( ! empty( $dependency[1] ) ) ? $dependency[1] : '';
					$data_value = ( ! empty( $dependency[2] ) ) ? $dependency[2] : '';
					$data_global = ( ! empty( $dependency[3] ) ) ? $dependency[3] : '';
					$depend_visible = ( ! empty( $dependency[4] ) ) ? $dependency[4] : '';
				}

				$depend .= ' data-controller="' . esc_attr( $data_controller ) . '' . $parent . '"';
				$depend .= ' data-condition="' . esc_attr( $data_condition ) . '"';
				$depend .= ' data-value="' . esc_attr( $data_value ) . '"';
				$depend .= ( ! empty( $data_global ) ) ? ' data-depend-global="true"' : '';

				$visible = ( ! empty( $depend_visible ) ) ? ' tf-depend-visible' : ' tf-depend-hidden';
			}

			//field width
			$field_width = isset( $field['field_width'] ) && ! empty( $field['field_width'] ) ? esc_attr( $field['field_width'] ) : '100';
			if ( $field_width == '100' ) {
				$field_style = 'width:100%;';
			} else {
				$field_style = 'width:calc(' . $field_width . '% - 10px);';
			}
			?>

			<div class="tf-field tf-field-<?php echo esc_attr( $field['type'] ); ?> <?php echo esc_attr( $class ); ?> <?php echo ! empty( $visible ) ? esc_attr( $visible ) : ''; ?>"
				<?php echo ! empty( $depend ) ? wp_kses_post( $depend ) : ''; ?>
				style="<?php echo esc_attr( $field_style ); ?>">
				<div class="tf-field-wrap">
					<?php if ( ! empty( $field['label'] ) ) : ?>
						<label for="<?php echo esc_attr( $id ); ?>" class="tf-field-label">
							<?php echo esc_html( $field['label'] ); ?>
							<?php
							/**
							 * Render all field badges generically.
							 *
							 * The Free plugin does not know whether a badge
							 * represents Pro, Upcoming, or another extension.
							 */
							foreach ( $badges as $badge ) :
								if ( empty( $badge['label'] ) ) {
									continue;
								}
								$badge_class = isset( $badge['class'] )
									? $badge['class']
									: '';
								?>
								<div class="tf-csf-badge">
									<span class="<?php echo esc_attr( $badge_class ); ?>">
										<?php echo esc_html( $badge['label'] ); ?>
									</span>
								</div>
							<?php endforeach; ?>
						</label>
					<?php endif; ?>

					<?php if ( ! empty( $field['subtitle'] ) ) : ?>
						<span class="tf-field-sub-title">
							<?php
							if ( $field['id'] == 'styler_heading_label' ) {

								echo esc_html(
									$field['subtitle']
								);

							} else {

								echo wp_kses_post(
									$field['subtitle']
								);
							}
							?>
						</span>
					<?php endif; ?>

					<div class="tf-fieldset">
						<?php
							$fieldClass = 'UACF7_' . $field['type'];

							if ( class_exists( $fieldClass ) ) {

								$_field = new $fieldClass(
									$field,
									$value,
									$settings_id,
									$parent,
									$section_key
								);

								$_field->render();

							} else {

								echo '<p>' . esc_html__( 'Field not found!', 'ultimate-addons-for-contact-form-7' ) . '</p>';
							}
						?>
					</div>
					<?php if ( ! empty( $field['description'] ) ) : ?>

						<p class="description"><?php echo wp_kses_post( $field['description'] ); ?></p>

					<?php endif; ?>

				</div>

			</div>
			<?php
		}

	}
}

UACF7_Options::instance();
