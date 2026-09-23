<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UACF7_Admin_Menu {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'uacf7_add_plugin_page' ], 9999 );
	}

	/*
	 * Admin menu
	 */
	public function uacf7_add_plugin_page() {
		add_submenu_page(
			'uacf7_settings', //parent slug
			__( 'Setup Wizard', 'ultimate-addons-for-contact-form-7' ), // page_title
			__( 'Setup Wizard', 'ultimate-addons-for-contact-form-7' ), // menu_title
			'manage_options', // capability 
			'admin.php?page=uacf7-setup-wizard', // menu_slug
		);
		
		if ( ! class_exists( 'Ultimate_Addons_CF7_PRO' ) ) {

			add_submenu_page(
				'uacf7_settings',
				'Upgrade to Pro',
				'<span class="uacf7-pro-link">★ Upgrade to Pro</span>',
				'manage_options',
				'https://cf7addons.com/',
				'',
				999
			);

			add_action( 'admin_footer', 'uacf7_upgrade_to_pro_new_tab' );
		}

		/**
		 * Open the Upgrade to Pro menu item in a new tab.
		 */
		function uacf7_upgrade_to_pro_new_tab() {
			?>
			<script>
				document.addEventListener('DOMContentLoaded', function () {
					const links = document.querySelectorAll('#adminmenu a');

					links.forEach(function (link) {
						if (link.href.indexOf('cf7addons.com') !== -1) {
							link.target = '_blank';
							link.rel = 'noopener noreferrer';
						}
					});
				});
			</script>
			<style>
				.uacf7-pro-link {
					color: #fff;
					font-weight: bold;
					background: #382673;
					padding: 5px 7px;
					border-radius: 5px;
				}
			</style>
			<?php
		}
	}


}

new UACF7_Admin_Menu();
