<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path(__FILE__) . 'inc/functions.php';
require_once plugin_dir_path(__FILE__) . 'inc/migrator.php';

/*
 * Pre Populate Classs
 */
class UACF7_DATABASE {

	private $uacf7dp_status = '';

	/*
	 * Construct function
	 */
	public function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'wp_enqueue_admin_script' ) );
		add_action( 'wpcf7_before_send_mail', array( $this, 'uacf7_save_to_database' ), 20, 4 );
		add_action( 'admin_menu', array( $this, 'uacf7_add_db_menu' ), 11, 2 );
		
		add_action( 'admin_init', array( $this, 'uacf7_create_database_table' ) );
		//add_filter( 'wpcf7_load_js', '__return_false' );

		/*
		 * Creating tables and start migrator after active the plugin or active the addon
		 */
		add_action( 'admin_init', array( $this, 'uacf7dp_register_activation' ), 11, 2 );

		add_action( 'admin_enqueue_scripts', [ $this, 'wp_enqueue_admin_script_pro' ] );

		add_action( 'wp_ajax_uacf7dp_get_table_data', [ $this, 'ajax_get_table_data' ] );

		// For Viwe the data on popup
		add_action( 'wp_ajax_uacf7dp_view_table_data', [ $this, 'uacf7dp_view_table_data' ] );

		add_action( 'wp_ajax_uacf7dp_deleted_table_datas', [ $this, 'uacf7dp_deleted_table_datas' ] );
		add_action( 'wp_ajax_uacf7dp_bulk_deleted_table_datas', [ $this, 'uacf7_ajax_bulk_delete' ] );

		$option = get_option( 'uacf7_settings' );

		if(isset( $option['uacf7_enable_database_pro'] ) && $option['uacf7_enable_database_pro'] != true || ! class_exists( 'Ultimate_Addons_CF7_PRO' ) ){
			add_filter( 'uacf7dp_send_form_data_before_insert', [ $this, 'uacf7dp_get_form_data_before_insert' ], 10, 2 );
		}

		add_action('wp_ajax_uacf7_ajax_database_export_csv', array($this, 'uacf7_ajax_database_export_csv') );
		
	}

	//Create Ulimate Database   
	function uacf7_create_database_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'uacf7_form';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            form_id bigint(20) NOT NULL,
            form_value longtext NOT NULL,
            form_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public function uacf7dp_register_activation() {
		// Call the function conditionally
		if ( ! $this->uacf7dp_check_tables_existence() ) {
			$this->uacf7dp_data_table_func();
		}

		$this->uacf7dp_status = get_option( 'uacf7dp_database_free_status' );
		if ( empty( $this->uacf7dp_status ) || $this->uacf7dp_status === 'no' ) {

			// Creating tables after addon active
			$this->uacf7dp_data_table_func();

			// Data migrate free to pro
			$migrater = new UACF7_DBMigrator();
			$migrater->uacf7dp_check_free_db();

			update_option( 'uacf7dp_database_free_status', 'done' );
		}

		/*
		 * Creating tables when plugin is active
		 */
		register_activation_hook( UACF7_FILE, [ $this, 'uacf7dp_data_table_func' ] );
	}

	/**
	 * If table not created then this will create the table uacf7dp_data_table_pro_func
	 * @return void
	 */
	public function uacf7dp_data_table_func() {
		global $wpdb;
		$uacf7_db = $wpdb;
		$charset_collate = $uacf7_db->get_charset_collate();

		$uacf7dp_table = $uacf7_db->prefix . 'uacf7dp_data';
		$uacf7dp_table_entry = $uacf7_db->prefix . 'uacf7dp_data_entry';

		// form info table 
		if ( $uacf7_db->get_var( $uacf7_db->prepare( 'SHOW TABLES LIKE %s', $uacf7_db->esc_like( $uacf7dp_table ) ) ) ) {
			$sql = 'CREATE TABLE ' . $uacf7dp_table . ' (
                `data_id` int(11) NOT NULL AUTO_INCREMENT,
				`cf7_form_id` int(11) NOT NULL,
				`submit_ip` int(11) NOT NULL,
                `submit_time` timestamp NOT NULL,
                UNIQUE KEY id (data_id)
                ) ' . $charset_collate . ';';

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		}

		// form entry table 
		if ( $uacf7_db->get_var( "show tables like '$uacf7dp_table_entry'" ) != $uacf7dp_table_entry ) {
			$sql = 'CREATE TABLE ' . $uacf7dp_table_entry . ' (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `cf7_form_id` int(11) NOT NULL,
                `data_id` int(11) NOT NULL,
                `fields_name` varchar(250),
                `value` varchar(250),
                UNIQUE KEY id (id)
                ) ' . $charset_collate . ';';
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

		} else {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			maybe_convert_table_to_utf8mb4( $uacf7dp_table_entry );
			$sql = $uacf7_db->prepare(
				'ALTER TABLE %i CHANGE fields_name fields_name VARCHAR(250) CHARACTER SET utf8, CHANGE value value TEXT CHARACTER SET utf8;',
				$uacf7dp_table_entry
			);
			$uacf7_db->query( $sql );
		}

	}

	/**
	 * This will store contact form data to the database
	 * @param mixed $contact_form
	 * @return void
	 */
	public function uacf7dp_get_form_data_before_insert( $insert_data, $extra ) {
		global $wpdb;
		$uacf7_db = $wpdb;
		$submission = WPCF7_Submission::get_instance();
		$data = array_merge( $insert_data, $extra );
		$submit_ip = $extra['submit_ip'];
		$submit_time = current_time('mysql');

		$submit_form_id = $submission->get_contact_form()->id();

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


	/**
	 * It's check if table are create or not 
	 * @return bool
	 */
	public function uacf7dp_check_tables_existence() {
		global $wpdb;
		$uacf7_db = $wpdb;
		$uacf7dp_table = $uacf7_db->prefix . 'uacf7dp_data';
		$uacf7dp_table_entry = $uacf7_db->prefix . 'uacf7dp_data_entry';

		// Check if tables exist
		$table_exists = $uacf7_db->get_var( "SHOW TABLES LIKE '$uacf7dp_table'" ) == $uacf7dp_table &&
			$uacf7_db->get_var( "SHOW TABLES LIKE '$uacf7dp_table_entry'" ) == $uacf7dp_table_entry;

		return $table_exists;
	}

	/*
	 * Enqueue script Backend
	 */

	public function wp_enqueue_admin_script() {
		wp_enqueue_style( 'uacf7-database-admin-style', UACF7_ADDONS . '/database/assets/css/database-admin.css', array(), UACF7_VERSION, 'all' );
		wp_enqueue_script( 'uacf7-database-admin', UACF7_ADDONS . '/database/assets/js/database-admin.js', array( 'jquery' ), UACF7_VERSION, true );
		wp_localize_script(
			'uacf7-database-admin',
			'uacf7_database_admin_url',
			array(
				'admin_url' => get_admin_url() . 'admin.php',
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'plugin_dir_url' => plugin_dir_url( __FILE__ ),
				'nonce' => wp_create_nonce( 'uacf7-form-database-admin-nonce' ),
			)
		);
	}

	/**
	 * This will load necessary files
	 * @return void
	 */
	public function wp_enqueue_admin_script_pro( $screen ) {

		$tf_options_screens = array(
			'cf7-addons_page_ultimate-addons-db',
			'cf7-addons_page_uacf7_addons',
		);


		if ( in_array( $screen, $tf_options_screens ) ) {
			$url = wp_parse_url( home_url() );

			$option = get_option( 'uacf7_settings' );

			if(isset( $option['uacf7_enable_database_pro'] ) && $option['uacf7_enable_database_pro'] != true || ! is_plugin_active( 'ultimate-addons-for-contact-form-7-pro/ultimate-addons-for-contact-form-7-pro.php' )){

				// Enqueue jQuery UI
				wp_enqueue_script( 'jquery-ui-tabs' );
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-widget' );
				wp_enqueue_script( 'jquery-ui-mouse' );
				wp_enqueue_script( 'jquery-ui-sortable' );

				// Enqueue DataTables CSS
				wp_enqueue_style( 'uacf7-database-modern-admin-style', UACF7_ADDONS . '/database/assets/css/database-modern-style.css', array(), UACF7_VERSION, 'all' );
				wp_enqueue_style( 'uacf7-database-table-style', UACF7_ADDONS . '/database/assets/css/datatables.min.css', array(), UACF7_VERSION, 'all' );

				// Enqueue DataTables JS
				wp_enqueue_script( 'uacf7-database-table-script', UACF7_ADDONS . '/database/assets/js/datatables.min.js', array( 'jquery' ), UACF7_VERSION, true );

				// Enqueue PDFMake
				wp_enqueue_script( 'uacf7-database-modern-pdfmake', UACF7_ADDONS . '/database/assets/js/pdfmake.min.js', array(), UACF7_VERSION, true );
				// Enqueue PDFMake Fonts
				wp_enqueue_script( 'uacf7-database-modern-pdfmake-font', UACF7_ADDONS . '/database/assets/js/vfs_fonts.js', array(), UACF7_VERSION, true );
				

				wp_enqueue_script( 'uacf7dp-database-icons-script', UACF7_ADDONS . '/database/assets/js/icons.js', array(), UACF7_VERSION, true );
				wp_enqueue_script( 'uacf7dp-database-table-script', UACF7_ADDONS . '/database/assets/js/database-modern-main.js', array(), UACF7_VERSION, true );
				wp_localize_script( 'uacf7dp-database-table-script', 'uACF7DP_Pram', array(
					'admin_url' => get_admin_url() . 'admin.php',
					'ajaxurl'   => admin_url( 'admin-ajax.php' ),
					'nonce'     => wp_create_nonce( 'uacf7dp-nonce' ),
				) );

			}
		}

	}


	/*
	 * Export CSV 
	 */

	 public function uacf7_ajax_database_export_csv() {
		// Capability check
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have permission to perform this action.' );
		}
		$nonce = isset( $_POST['ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ajax_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'uacf7dp-nonce' ) ) {
			exit( esc_html__( "Security error", 'ultimate-addons-for-contact-form-7' ) );
		}

		if ( isset( $_POST['form_id'] ) && 0 < $_POST['form_id'] ) {
			global $wpdb;
			$uacf7_db = $wpdb;
			$form_id = intval( $_POST['form_id'] );
			$today = gmdate( "Y-m-d" );
			$upload_dir = wp_upload_dir();
			$dir = $upload_dir['baseurl'];
			$replace_dir = '/uacf7-uploads/';
			$form_title = get_the_title( $form_id );
			$form_title = str_replace( " ", "-", $form_title );

			$site_title = get_bloginfo( 'name' );
			$site_title = str_replace( " ", "-", $site_title );
			$file_name = $today . '-' . $form_title . '—' . $site_title;

			$field_rows = $uacf7_db->get_results( $uacf7_db->prepare(
				'SELECT DISTINCT fields_name FROM ' . $uacf7_db->prefix . 'uacf7dp_data_entry WHERE cf7_form_id = %d',
				$form_id
			) );

			$all_keys = wp_list_pluck( $field_rows, 'fields_name' );
			
			$option = get_option( 'uacf7_settings' );

			if(isset( $option['uacf7_enable_database_pro'] ) && $option['uacf7_enable_database_pro'] != true || ! is_plugin_active( 'ultimate-addons-for-contact-form-7-pro/ultimate-addons-for-contact-form-7-pro.php' )){

				$all_keys[] = 'Date';

			}

			$list = [];
			$list[] = $all_keys;

			// Step 2: Get all submission rows sorted by data_id
			$rows = $uacf7_db->get_results( $uacf7_db->prepare(
				'SELECT * FROM ' . $uacf7_db->prefix . 'uacf7dp_data_entry 
				WHERE cf7_form_id = %d 
				ORDER BY data_id, id ASC',
				$form_id
			) );

			// Step 3: Group by data_id
			$grouped = [];
			foreach ( $rows as $row ) {
				$grouped[ $row->data_id ][ $row->fields_name ] = $row->value;
				if(isset( $option['uacf7_enable_database_pro'] ) && $option['uacf7_enable_database_pro'] != true || ! is_plugin_active( 'ultimate-addons-for-contact-form-7-pro/ultimate-addons-for-contact-form-7-pro.php' )){
					// Fallback to entry creation time using id or current time
					if ( ! isset( $grouped[ $row->data_id ]['Date'] ) ) {
						$grouped[ $row->data_id ]['Date'] = ! empty( $row->created_at )
							? date_i18n( 'Y-m-d H:i:s', strtotime( $row->created_at ) )
							: date_i18n( 'Y-m-d H:i:s' );
					}
				}
			}

			// Step 4: Generate data rows
			foreach ( $grouped as $entry ) {
				$row = [];
				foreach ( $all_keys as $key ) {
					$value = isset( $entry[ $key ] ) ? $entry[ $key ] : '';

					if ( is_array( $value ) ) {
						$value = implode( ', ', $value );
					}

					if ( strstr( $value, $replace_dir ) ) {
						$value = str_replace( $replace_dir, "", $value );
						$value = $dir . $replace_dir . $value;
					}

					$row[] = $value;
				}
				$list[] = $row;
			}
			
			// Set the headers
			$csv_rows = array();

			foreach ( $list as $fields ) {
				$csv_fields = array();

				foreach ( $fields as $field ) {
					$field = is_scalar( $field ) ? (string) $field : '';

					/*
					* Escape double quotes according to CSV rules.
					*/
					$field = str_replace( '"', '""', $field );

					/*
					* Wrap every value in double quotes.
					*/
					$csv_fields[] = '"' . $field . '"';
				}

				$csv_rows[] = implode( ',', $csv_fields );
			}

			$csv_data = implode( "\r\n", $csv_rows ) . "\r\n";

			$data = array(
				'status'    => true,
				'file_name' => $file_name,
				'csv'       => $csv_data,
			);
		} else {
			$data = [ 
				'status' => false,
				'message' => esc_html( 'Something went wrong! Form ID not found.', 'ultimate-addons-for-contact-form-7' ),
			];
		}


		wp_send_json( $data );
		wp_die();

	}


	/*
	 * Database menu 
	 */

	public function uacf7_add_db_menu() {

		add_submenu_page(
			'uacf7_settings',
			__( 'Database', 'ultimate-addons-for-contact-form-7' ),
			__( 'Database', 'ultimate-addons-for-contact-form-7' ),
			'manage_options',
			'ultimate-addons-db',
			apply_filters( 'uacf7_database_admin_page', array( $this, 'uacf7_create_database_page' ) ),
		);
	}



	public function uacf7_create_database_page() {
		global $wpdb;
		$uacf7_db = $wpdb;
		$list_forms = get_posts(
			array(
				'post_type' => 'wpcf7_contact_form',
				'posts_per_page' => -1
			)
		);

		?>
		<div id="uacf7dp_addons_pages">
			<div id="loading">
				<div class="loading"></div>
			</div>
			<div id="uacf7dp_addons_header" class="uacf7dp-tabcontent">
				<img src="<?php echo esc_url(UACF7_ADDONS) ?>/database/assets/images/ultimate-logo.png" alt="logo" />
				<h4 class="uacf7dp_main-heading">
					<?php echo esc_html__( 'Database', 'ultimate-addons-for-contact-form-7' ); ?>
				</h4>
				<div class="uacf7dp_header-form">
					<h4>
						<?php echo esc_html__( 'Select form', 'ultimate-addons-for-contact-form-7' ); ?>
					</h4>

					<?php
					$selected_form_id = filter_input( INPUT_GET, 'form_id', FILTER_VALIDATE_INT );
					$selected_form_id = false === $selected_form_id || null === $selected_form_id ? 0 : absint( $selected_form_id );
					?>
					<select name="select_from_submit" id="select_from_submit">
						<option value="0" <?php selected( $selected_form_id, 0 ); ?>>
							<?php echo esc_html__( 'Select form', 'ultimate-addons-for-contact-form-7' ); ?>
						</option>
						<?php
						foreach ( $list_forms as $form ) {
							// count number of data
							$count = $uacf7_db->get_var( $uacf7_db->prepare( "SELECT COUNT(*) FROM " . $uacf7_db->prefix . "uacf7dp_data WHERE cf7_form_id = %d", $form->ID ) );

							echo '<option value="' . esc_attr( $form->ID ) . '" ' . selected( $selected_form_id, $form->ID, false ) . '>';
							echo esc_attr( $form->post_title ) . ' ( ' . esc_attr( $count ) . ' )';
							echo '</option>';
						}
						?>
					</select>
				</div>
			</div>

			<div id="uacf7dp_table_container_wrap">

				<div id="uacf7dp_table_container" class="uacf7dp-table-responsive">
					<table id="uacf7dp-database-tablePro"></table>
				</div>
				<div class="uacf7dp_table_empty">
					<img src="<?php echo esc_url(UACF7_ADDONS) ?>/database/assets/images/select.png" alt="thum" />
					<p>
						<span>To view data, please select a form</span>
						Once selected, the data will be displayed on the screen.
						The data can be filtered according to the
						desired parameters. The data can also be exported into a spreadsheet for further analysis.
					</p>
				</div>
			</div>

			<section class="uacf7_popup_preview">
				<div class="uacf7_popup_preview_content">
					<div id="uacf7_popup_wrap">
						<div class="db_popup_view">
							<div class="close" title="Exit Full Screen">╳</div>
							<div id="db_view_wrap">
							</div>
						</div>
					</div>
				</div>
			</section>

		</div>
		<?php
	}

	// PopUp Data view Processing
	public function uacf7dp_view_table_data() {
		global $wpdb;
		$uacf7_db = $wpdb;

		// Capability check
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have permission to perform this action.' );
		}

		// Nonce verify.
		if ( ! check_ajax_referer( 'uacf7dp-nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'mess' => 'Nonce is invalid' ) );
		}

		// Get from Table.
		$form_id = isset( $_POST['cf7_form_id'] ) ? absint( wp_unslash( $_POST['cf7_form_id'] ) ) : 0;
		$all_data = isset( $_POST['all_data'] ) && is_array( $_POST['all_data'] ) ? map_deep( wp_unslash( $_POST['all_data'] ), 'sanitize_text_field' ) : array();

		$encryptionKey = 'AES-256-CBC';

		// Get Form details 
		$ContactForm = WPCF7_ContactForm::get_instance( $form_id );
		$form_fields = $ContactForm->scan_form_tags();

		// Files Paths
		$upload_dir = wp_upload_dir();
		$dir = $upload_dir['baseurl'];
		$signaturepath = $upload_dir['basedir'];
		$replace_dir = '/uacf7-uploads/';

		// filter out signature tag
		$uacf7_signature_tag = [];


		foreach ( $form_fields as $field ) {
			if ( $field->type == 'uacf7_signature*' || $field->type == 'uacf7_signature' ) {
				$uacf7_signature_tag[] = $field->name;
			}
		}

		$html = '<div class="db-view-wrap"> 
					<h3>' . get_the_title( $form_id ) . '</h3>
					<span>' . esc_html( $all_data['submit_time'] ) . '</span>
					<table class="wp-list-table widefat fixed striped table-view-list">';
		$html .= '<tr> <th><strong>Fields</strong></th><th><strong>Values</strong> </th> </tr>';
		foreach ( $all_data as $key => $value ) {

			// Skip these keys
			if ( $key === 'status' || $key === 'id' || $key === 'cf7_form_id' ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$value = implode( ", ", $value );
			}

			if ( in_array( $key, $uacf7_signature_tag ) ) {

				if ( empty( $value ) ) {
					continue;
				}

				$pathInfo = pathinfo( $value );
				$extension = strtolower( $pathInfo['extension'] );
				$fileNameWithoutExtension = pathinfo( $value, PATHINFO_FILENAME );

				// Image Loaded
				$token = md5( uniqid() );
				$decryptedData = $this->decrypt_and_display( $signaturepath . $value, $encryptionKey );
				if ( $decryptedData !== null ) {
					$imageData = 'data:image/jpeg;base64,' . base64_encode( $decryptedData );
				}

				// Check old data
				if ( $extension == 'enc' ) {
					$srcAttribute = $imageData;  // Set to empty or another value if needed
				} else {
					$srcAttribute = $value;
				}

				$html .= '
					<tr> 
						<td>
							<strong>' . esc_attr( $key ) . '</strong>
						</td> 
						<td>
							<button id="signature_view_btn">' . esc_html( 'View' ) . '</button>
							<a class="" href="' . $srcAttribute . '" download="' . $fileNameWithoutExtension . '">
								<button class="signature_download_btn">Download</button>
							</a>
						</td>
					</tr>
					<div class="signature_view_pops">
						<img class="signature_view_pops_img"  src="' . $srcAttribute . '"/>
					</div>
					';
			} else {
				if ( strstr( $value, $replace_dir ) ) {
					$items = array_map( 'trim', explode( ',', $value ) );

					$link_html = '';
					foreach ( $items as $item ) {
						$filename = basename( $item );
						$link_html .= '<a href="' . esc_url( $item ) . '" target="_blank">' . esc_html( $filename ) . '</a><br>';
					}
				
					$html .= '<tr> <td><strong>' . esc_attr( $key ) . '</strong></td> <td>' . $link_html . '</td> </tr>';
				} else {
					$html .= '<tr> <td><strong>' . esc_attr( $key ) . '</strong></td> <td>' . esc_html( $value ) . '</td> </tr>';
				}
			}

		}

		$html .= '</table></div>';

		echo wp_kses_post( $html );
		wp_die();
	}


	public function ajax_get_table_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have permission to perform this action.' );
		}

		if ( ! check_ajax_referer( 'uacf7dp-nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'mess' => 'Nonce is invalid' ) );
		}

		global $wpdb;
		$uacf7_db = $wpdb;
		$cf7d_entry_order_by = '`data_id` DESC';
		$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;

		$get_form_data = $uacf7_db->prepare(
			"SELECT * 
			FROM {$uacf7_db->prefix}uacf7dp_data_entry 
			WHERE `cf7_form_id` = %d 
				AND data_id IN (
					SELECT data_id 
					FROM (
						SELECT data_id 
						FROM {$uacf7_db->prefix}uacf7dp_data_entry 
						WHERE `cf7_form_id` = %d 
						GROUP BY `data_id` 
						ORDER BY %s
					) AS temp_table
				)
			ORDER BY %s",
			$form_id,
			$form_id,
			$cf7d_entry_order_by,
			$cf7d_entry_order_by
		);

		$form_data = $uacf7_db->get_results( $get_form_data );
		$uacf7dp_sortable = $this->uacf7dp_data_sortable( $form_data );
		
		$fields = $this->uacf7dp_get_db_fields( $form_id );
		
		$orgFieldsData = apply_filters( 'uacf7dp_column_default_fields', $uacf7dp_sortable, $fields );

		wp_send_json_success(
			array(
				'fields' => array_map( 'esc_js', $fields ),
				'data_sorted' => $orgFieldsData,
			)
		);
		wp_die();
	}

	public function uacf7dp_data_sortable( $form_data ) {
		$result = [];
		
		foreach ( $form_data as $item ) {
			$dataId = $item->data_id;

			// If the array for this data_id doesn't exist, create it
			if ( ! isset( $result[ $dataId ] ) ) {
				$result[ $dataId ] = [];
			}

			// Add the item data to the array for this data_id
			$result[ $dataId ][] = [ 
				'id' => $item->id,
				'cf7_form_id' => $item->cf7_form_id,
				'data_id' => $item->data_id,
				'fields_name' => $item->fields_name,
				'value' => $item->value,
			];
		}
		return $result;
	}

	public function uacf7dp_get_db_fields( $form_id ) {
		global $wpdb;
		$uacf7_db = $wpdb;
		$data = $uacf7_db->get_results(
				$uacf7_db->prepare(
					'SELECT `fields_name` FROM `' . $uacf7_db->prefix . 'uacf7dp_data_entry` WHERE cf7_form_id = %d GROUP BY `fields_name`',
					$form_id
				)
			);

		$fields = array();
		foreach ( $data as $k => $v ) {
			$sanitized_key = sanitize_text_field( $v->fields_name );
			$fields[ $sanitized_key ] = $sanitized_key;
		}

		if ( $fields ) {
			$fields = apply_filters( 'uacf7dp_adminSide_fields', $fields, $form_id );
		}

		$Finalfields = array_merge( $fields, array( 'id' => 'id', 'cf7_form_id' => 'cf7_form_id' ) );

		return $Finalfields;
	}


	private function uacf7dp_get_wp_filesystem() {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() ) {
			return false;
		}

		global $wp_filesystem;

		return $wp_filesystem;
	}

	private function uacf7dp_get_file_contents( $file_path ) {
		$filesystem = $this->uacf7dp_get_wp_filesystem();

		if ( $filesystem ) {
			return $filesystem->get_contents( $file_path );
		}

		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		return file_get_contents( $file_path );
	}

	private function uacf7dp_put_file_contents( $file_path, $contents ) {
		$filesystem = $this->uacf7dp_get_wp_filesystem();

		if ( $filesystem ) {
			return $filesystem->put_contents( $file_path, $contents, FS_CHMOD_FILE );
		}

		return file_put_contents( $file_path, $contents );
	}

	private function uacf7dp_copy_file( $source, $target ) {
		$filesystem = $this->uacf7dp_get_wp_filesystem();

		if ( $filesystem ) {
			$target_dir = dirname( $target );
			if ( ! $filesystem->is_dir( $target_dir ) ) {
				$filesystem->mkdir( $target_dir, FS_CHMOD_DIR, true );
			}

			return $filesystem->copy( $source, $target, true, FS_CHMOD_FILE );
		}

		if ( ! file_exists( dirname( $target ) ) ) {
			wp_mkdir_p( dirname( $target ) );
		}

		return copy( $source, $target );
	}

	public function encrypt_file( $inputFile, $outputFile, $key ) {
		$inputData = $this->uacf7dp_get_file_contents( $inputFile );

		if ( $inputData === false ) {
			return false;
		}

		// Generate an Initialization Vector (IV)
		$iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );

		// Encrypt the data
		$encryptedData = openssl_encrypt( $inputData, 'aes-256-cbc', $key, 0, $iv );

		// Combine IV and encrypted data
		$encryptedFileContent = $iv . $encryptedData;

		// Save the encrypted content to the output file
		return $this->uacf7dp_put_file_contents( $outputFile, $encryptedFileContent );
	}

	public function decrypt_and_display( $inputFile, $key ) {

		if ( ! file_exists( $inputFile ) ) {
			wp_die( "Error: The file does not exist." );
		}

		// Read the encrypted content
		$encryptedFileContent = $this->uacf7dp_get_file_contents( $inputFile );

		if ( $encryptedFileContent === false ) {
			wp_die( "Error: Unable to read file content." );
		}

		// Extract IV
		$ivSize = openssl_cipher_iv_length( 'aes-256-cbc' );
		$iv = substr( $encryptedFileContent, 0, $ivSize );

		// Extract encrypted data
		$encryptedData = substr( $encryptedFileContent, $ivSize );

		// Decrypt the data
		$decryptedData = openssl_decrypt( $encryptedData, 'aes-256-cbc', $key, 0, $iv );

		// Output the decrypted data directly
		//header( 'Content-Type: image/jpg' ); // Adjust content type based on your file type
		return $decryptedData;
	}

	/*
	 * Ultimate form save into the database
	 */
	public function uacf7_save_to_database( $form ) {
		if ( ! is_plugin_active( 'ultimate-addons-for-contact-form-7-pro/ultimate-addons-for-contact-form-7-pro.php' ) ) {
			if ( defined( 'UACF7_PRO_PATH_ADDONS' ) ) {
				require_once( UACF7_PRO_PATH_ADDONS . '/database-pro/functions.php' );
			} else {
				// Handle the case when the pro plugin is not active and the constant is not defined
				// You can add an error log, a fallback, or any other necessary action here
				// error_log( 'UACF7_PRO_PATH_ADDONS is not defined. Pro plugin might not be installed or active.' );
			}
		}
		global $wpdb;
		$uacf7_db = $wpdb;
		$encryptionKey = 'AES-256-CBC';
		$table_name = $uacf7_db->prefix . 'uacf7_form';

		$submission = WPCF7_Submission::get_instance();
		$ContactForm = WPCF7_ContactForm::get_instance( $form->id() );
		$tags = $ContactForm->scan_form_tags();
		$skip_tag_insert = [];
		$uacf7_signature_tag = [];
		foreach ( $tags as $tag ) {
			if ( $tag->type == 'uacf7_signature*' || $tag->type == 'uacf7_signature' ) {
				$uacf7_signature_tag[] = $tag->name;
			}
			if ( $tag->type == 'uacf7_step_start' || $tag->type == 'uacf7_step_end' || $tag->type == 'uarepeater' || $tag->type == 'conditional' || $tag->type == 'uacf7_conversational_start' || $tag->type == 'uacf7_conversational_end' ) {
				if ( $tag->name != '' ) {
					$skip_tag_insert[] = $tag->name;
				}
			}

		}

		$contact_form_data = $submission->get_posted_data();
		$files = $submission->uploaded_files();
		$upload_dir = wp_upload_dir();
		$dir = $upload_dir['basedir'];
		$uploaded_files = [];
		$time_now = time();
		$data_file = [];
		$uacf7_dirname = $upload_dir['basedir'] . '/uacf7-uploads';
		if ( ! file_exists( $uacf7_dirname ) ) {
			wp_mkdir_p( $uacf7_dirname );
		}

		// CF7 already validates and normalizes uploaded files for the current submission.
		foreach ( $files as $file_key => $file ) {
			if ( ! empty( $file ) ) {
				$uploaded_files[] = $file_key;
			}

			// var_dump( $file_key );

			if ( ! empty( $file ) && in_array( $file_key, $uploaded_files, true ) ) {
					$file = is_array( $file ) ? reset( $file ) : $file;

					// var_dump( $file );

					$dir_link = '/uacf7-uploads/' . $time_now . '-' . $file_key;

					if ( in_array( $file_key, $uacf7_signature_tag ) ) {
						$dir_link = '/uacf7-uploads/' . $time_now . '-' . $file_key . '.enc';
						$this->encrypt_file( $file, $dir . $dir_link, $encryptionKey );
					} else {
						copy( $file, $dir . $dir_link );
					}
				array_push( $data_file, [ $file_key => $dir_link ] );
			}

		}

		$key_count = 0;
		foreach ( $contact_form_data as $key => $value ) {
			if ( in_array( $key, $uploaded_files, true ) ) {
				if ( ! empty( $data_file ) && is_array( $data_file ) ) {
					$contact_form_data[ $key ] = $data_file[ $key_count ][ $key ];
				}
				$key_count++;
			}
		}


		$data = [ 
			'status' => 'unread',
		];

		$form_field_names = wp_list_pluck( $tags, 'name' ); // whitelist of valid fields
		$data             = array_merge( [ 'status' => 'unread' ], $contact_form_data );

		$insert_data = [];
		foreach ( $data as $key => $value ) {
			if ( ! in_array( $key, $skip_tag_insert, true ) && in_array( $key, $form_field_names, true ) ) {
				$safe_key = sanitize_key( $key ); // not strictly needed here but safe
				if ( is_array( $value ) ) {
					$insert_data[ $safe_key ] = array_map( 'esc_html', $value );
				} else {
					$insert_data[ $safe_key ] = esc_html( $value );
				}
			}
		}


		// Initialize the variable to avoid warnings
		$extra_fields_data = [];

		// Now, attempt to call the function if it exists
		if ( function_exists( 'uacf7dp_add_more_fields' ) ) {
			$extra_fields_data = uacf7dp_add_more_fields( $submission );
		}

		apply_filters( 'uacf7dp_send_form_data_before_insert', $insert_data, $extra_fields_data );

		$insert_data = json_encode( $insert_data );

		$uacf7_db->insert(
			$table_name,
			array(
				'form_id' => $form->id(),
				'form_value' => $insert_data,
				'form_date' => current_time( 'Y-m-d H:i:s' ),
			)
		);

		$uacf7_db_insert_id = $uacf7_db->insert_id;

		//  print_r($uacf7_enable_track_order);

		// Order tracking Action
		do_action( 'uacf7_checkout_order_traking', $uacf7_db_insert_id, $form->id() );

		// submission id Action
		do_action( 'uacf7_submission_id_insert', $uacf7_db_insert_id, $form->id(), $contact_form_data, $tags );

	}

	public function uacf7dp_deleted_table_datas() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have permission to perform this action.' );
		}

		if ( ! check_ajax_referer( 'uacf7dp-nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'mess' => 'Nonce is invalid' ) );
		}

		global $wpdb;

		$uacf7_db = $wpdb;

		$form_id = isset( $_POST['cf7_form_id'] ) ? absint( wp_unslash( $_POST['cf7_form_id'] ) ) : 0;
		$data_id = isset( $_POST['data_id'] ) ? absint( wp_unslash( $_POST['data_id'] ) ) : 0;

		// Check if the provided IDs are valid
		if ( $form_id <= 0 || $data_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid cf7_form_id or data_id.' ) );
		}

		$uacf7_db->delete( "{$uacf7_db->prefix}uacf7dp_data", array( 'cf7_form_id' => $form_id, 'data_id' => $data_id ) );

		// Delete from wp_uacf7dp_data_entry
		$uacf7_db->delete( "{$uacf7_db->prefix}uacf7dp_data_entry", array( 'cf7_form_id' => $form_id, 'data_id' => $data_id ) );

		wp_send_json_success( array( 'message' => 'Data processed successfully' ) );
		wp_die();
	}

	function uacf7_ajax_bulk_delete() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have permission to perform this action.' );
		}

		if ( ! check_ajax_referer( 'uacf7dp-nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'mess' => 'Nonce is invalid' ) );
		}

		global $wpdb;
		$uacf7_db = $wpdb;

		$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;
		$ids = isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) ? array_map( 'absint', wp_unslash( $_POST['ids'] ) ) : array();

		if ( $form_id <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid form id.' ) );
		}

		if (empty($ids)) {
			wp_send_json_error(['message' => 'No IDs provided for deletion.']);
		}

		$data_table  = $uacf7_db->prefix . 'uacf7dp_data';
		$entry_table = $uacf7_db->prefix . 'uacf7dp_data_entry';

		// Loop and delete for each ID
		foreach ($ids as $data_id) {
			$uacf7_db->delete($data_table, array(
				'cf7_form_id' => $form_id,
				'data_id'     => $data_id
			));
			$uacf7_db->delete($entry_table, array(
				'cf7_form_id' => $form_id,
				'data_id'     => $data_id
			));
		}

		wp_send_json_success(['message' => 'Selected rows deleted successfully.']);
		wp_die();
		
	}


}

new UACF7_DATABASE();