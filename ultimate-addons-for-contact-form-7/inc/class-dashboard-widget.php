<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Uacf7DashboardWidget {

    private static $instance = null;

    public function __construct() {
        add_action( 'wp_dashboard_setup', array( $this, 'uacf7_register_dashboard_widget' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'uacf7_widget_enqueue_assets' ) );
    }

    public static function instance() {
        if ( ! self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function uacf7_register_dashboard_widget() {
        wp_add_dashboard_widget( 'uacf7_widget', __( 'Ultra Addons for Contact Form 7', 'ultimate-addons-cf7' ), array( $this, 'uacf7_display_dashboard_widget' ) , null, null, 'normal', 'high' );
    }

    public function uacf7_widget_enqueue_assets( $screen ) {

        /**
		 * Admin Dashboard CSS
		 */
		if ( $screen == 'index.php' ) {
			wp_enqueue_style( 'uacf7-admin-dashboard', UACF7_URL . 'assets/css/uacf7-admin-dashboard.css', '', UACF7_VERSION );
		}

    }

    public function uacf7_display_dashboard_widget() {
        $enableDatabase = uacf7_settings('uacf7_enable_database_field');
        ?>
        <div class="uacf7-widget">

            <!-- Stats Row -->
            <div class="uacf7-stats">

                <?php if ( $enableDatabase ) { ?>
                    <div class="uacf7-stat">
                        <?php
                            global $wpdb;

                            // Get last 30 days date
                            $date_30_days_ago = date( 'Y-m-d H:i:s', strtotime( '-30 days' ) );

                            // Get all active WPForms
                            $forms = get_posts( [
                                'post_type'      => 'wpcf7_contact_form',
                                'post_status'    => 'publish',
                                'posts_per_page' => -1,
                                'fields'         => 'ids',
                            ] );
                            
                            $total_submissions = 0;

                            if ( ! empty( $forms ) ) {
                                foreach ( $forms as $form_id ) {

                                    $count = (int) $wpdb->get_var(
                                        $wpdb->prepare(
                                            "SELECT COUNT(*) 
                                            FROM {$wpdb->prefix}uacf7_form 
                                            WHERE form_id = %d",
                                            $form_id
                                        )
                                    );

                                    $total_submissions += $count;
                                }
                            }

                        ?>
                        <strong><?php echo esc_html( $total_submissions ); ?></strong>
                        <span><?php esc_html_e( 'Total Submissions', 'ultimate-addons-cf7' ); ?></span>
                    </div>
                    <div class="uacf7-stat">
                        <strong>
                            <?php 
                            $count = wp_count_posts( 'wpcf7_contact_form' );
                            echo isset( $count->publish ) ? (int) $count->publish : 0;
                            ?>
                        </strong>
                        <span><?php esc_html_e( 'Total Active Forms', 'ultimate-addons-cf7' ); ?></span>
                    </div>
                <?php }else{ ?>
                    <div class="uacf7-stat">
                        <strong>
                            <?php 
                            $count = wp_count_posts( 'wpcf7_contact_form' );
                            echo isset( $count->publish ) ? (int) $count->publish : 0;
                            ?>
                        </strong>
                        <span><?php esc_html_e( 'Total Active Forms', 'ultimate-addons-cf7' ); ?></span>
                        </br></br>
                        <div class="uacf7-actions">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=uacf7_addons' ) ); ?>" class="button">
                                <?php esc_html_e( 'Enable Database Addon To Save Entries', 'ultimate-addons-cf7' ); ?>
                            </a>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <!-- Quick Actions -->
            <div class="uacf7-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpcf7-new' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Create Form', 'ultimate-addons-cf7' ); ?>
                </a>
                <?php if($enableDatabase):?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ultimate-addons-db' ) ); ?>" class="button">
                    <?php esc_html_e( 'View Entries', 'ultimate-addons-cf7' ); ?>
                </a>
                <?php endif;?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=uacf7_settings#tab=mailchimp' ) ); ?>" class="button">
                    <?php esc_html_e( 'Settings', 'ultimate-addons-cf7' ); ?>
                </a>
            </div>

            <!-- Popular Integrations -->
            <div class="uacf7-section uacf7-integrations">
                <h4><?php esc_html_e( 'Popular Integrations', 'ultimate-addons-cf7' ); ?></h4>

                <div class="uacf7-integration-grid">

                    <div class="uacf7-integration-item">
                        <svg fill="#2271b1" height="800px" width="800px" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 204.376 204.376" xml:space="preserve" stroke="#2271b1">

                        <g id="SVGRepo_bgCarrier" stroke-width="0"/>

                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"/>

                        <g id="SVGRepo_iconCarrier"> <path d="M171.247,204.376c2.485,0,4.5-2.015,4.5-4.5V61.35h-51.744c-7.502,0-13.605-6.107-13.605-13.614V0H33.13 c-2.485,0-4.5,2.015-4.5,4.5v195.376c0,2.485,2.015,4.5,4.5,4.5H171.247z M52.891,87.627h99.717v80H52.891V87.627z M106.749,143.96 h37.858v15.667h-37.858V143.96z M60.891,119.294h37.858v16.666H60.891V119.294z M60.891,143.96h37.858v15.667H60.891V143.96z M106.749,95.627h37.858v15.667h-37.858V95.627z M106.749,119.294h37.858v16.666h-37.858V119.294z M60.891,95.627h37.858v15.667 H60.891V95.627z M120.397,47.736v-37.34L164.2,51.35h-40.197C122.014,51.35,120.397,49.729,120.397,47.736z"/> </g>

                        </svg>
                        <span><?php esc_html_e( 'Google Sheets', 'ultimate-addons-cf7' ); ?></span>
                    </div>

                    <div class="uacf7-integration-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M331 243.5c3.1-.4 6.2-.4 9.3 0 1.7-3.8 2-10.4 .5-17.6-2.2-10.7-5.3-17.1-11.5-16.1s-6.5 8.7-4.2 19.4c1.3 6 3.5 11.1 6 14.3l0 0zM277.4 252c4.5 2 7.2 3.3 8.3 2.1 1.9-1.9-3.5-9.4-12.1-13.1-5-2.1-10.4-2.8-15.8-2.2s-10.5 2.7-14.8 5.8c-3 2.2-5.8 5.2-5.4 7.1 .9 3.7 10-2.7 22.6-3.5 7-.4 12.8 1.8 17.3 3.7l0 0zm-9 5.1c-9.1 1.4-15 6.5-13.5 10.1 .9 .3 1.2 .8 5.2-.8 6-2.3 12.4-2.9 18.7-1.9 2.9 .3 4.3 .5 4.9-.5 1.5-2.2-5.7-8-15.4-6.9l0 0zm54.2 17.1c3.4-6.9-10.9-13.9-14.3-7s10.9 13.9 14.3 7l0 0zm15.7-20.5c-7.7-.1-8 15.8-.3 15.9s8-15.8 .3-16l0 0zM119.5 332.7c-1.3 .3-6 1.5-8.5-2.3-5.2-8 11.1-20.4 3-35.8-9.1-17.5-27.8-13.5-35-5.5-8.7 9.6-8.7 23.5-5 24.1 4.3 .6 4.1-6.5 7.4-11.6 .9-1.4 2.1-2.6 3.5-3.6s3-1.6 4.6-2 3.4-.4 5 0 3.3 1 4.7 1.9c11.6 7.6 1.4 17.8 2.3 28.6 1.4 16.7 18.4 16.4 21.6 9 .2-.4 .3-.8 .3-1.2s-.2-.8-.5-1.1c0 .9 .7-1.3-3.4-.4l0 0zm299.7-17.1c-3.3-11.7-2.6-9.2-6.8-20.5 2.4-3.7 15.3-24-3.1-43.3-10.4-10.9-33.9-16.5-41.1-18.5-1.5-11.4 4.6-58.7-21.5-83 20.8-21.6 33.8-45.3 33.7-65.7-.1-39.2-48.2-51-107.4-26.5l-12.5 5.3c-.1 0-22.7-22.3-23.1-22.6-67.5-58.9-278.8 175.9-211.3 232.9l14.8 12.5c-4 10.7-5.4 22.2-4.1 33.5 3.4 33.4 36 60.4 67.5 60.4 57.7 133.1 267.9 133.3 322.3 3 1.7-4.5 9.1-24.6 9.1-42.4s-10.1-25.3-16.5-25.3l0 0zm-316 48.2c-22.8-.6-47.5-21.1-49.9-45.5-6.2-61.3 74.3-75.3 84-12.3 4.5 29.6-4.7 58.5-34.1 57.8l0 0zM84.7 249.6c-15.2 3-28.5 11.5-36.7 23.5-4.9-4.1-14-12-15.6-15-13-24.8 14.2-73 33.3-100.2 47.1-67.2 120.9-118.1 155-108.9 5.5 1.6 23.9 22.9 23.9 22.9s-34.1 18.9-65.8 45.3C136.2 150 104 197.7 84.7 249.6zM323.6 350.7s-35.7 5.3-69.5-7.1c6.2-20.2 27 6.1 96.4-13.8 15.3-4.4 35.4-13 51-25.4 3.4 7.8 5.8 15.9 7.1 24.3 3.7-.7 14.2-.5 11.4 18.1-3.3 19.9-11.7 36-25.9 50.8-8.9 9.6-19.4 17.5-31.2 23.3-6.5 3.4-13.3 6.3-20.3 8.6-53.5 17.5-108.3-1.7-126-43-1.4-3.1-2.6-6.4-3.6-9.7-7.5-27.2-1.1-59.8 18.8-80.4 1.2-1.3 2.5-2.9 2.5-4.8-.2-1.7-.8-3.3-1.9-4.5-7-10.1-31.2-27.4-26.3-60.8 3.5-24 24.5-40.9 44.1-39.9l5 .3c8.5 .5 15.9 1.6 22.9 1.9 11.7 .5 22.2-1.2 34.6-11.6 4.2-3.5 7.6-6.5 13.3-7.5 2.3-.6 4.7-.7 7-.3s4.6 1.2 6.6 2.5c10 6.6 11.4 22.7 11.9 34.5 .3 6.7 1.1 23 1.4 27.6 .6 10.7 3.4 12.2 9.1 14 3.2 1 6.2 1.8 10.5 3.1 13.2 3.7 21 7.5 26 12.3 2.5 2.5 4.2 5.8 4.7 9.3 1.6 11.4-8.8 25.4-36.3 38.2-46.7 21.7-93.7 14.4-100.5 13.7-20.2-2.7-31.6 23.3-19.5 41.1 22.6 33.4 122.4 20 151.4-21.4 .7-1 .1-1.6-.7-1-41.8 28.6-97.1 38.2-128.5 26-4.8-1.8-14.7-6.4-15.9-16.7 43.6 13.5 71 .7 71 .7s2-2.8-.6-2.5zM171.7 157.5c16.7-19.4 37.4-36.2 55.8-45.6 .1-.1 .3-.1 .5-.1s.3 .1 .4 .2 .2 .3 .2 .4 0 .3-.1 .5c-1.5 2.7-4.3 8.3-5.2 12.7 0 .1 0 .3 0 .4s.2 .3 .3 .4 .3 .1 .4 .1 .3 0 .4-.1c11.5-7.8 31.5-16.2 49-17.3 .2 0 .3 0 .5 .1s.2 .2 .3 .4 .1 .3 0 .5-.1 .3-.3 .4c-2.9 2.2-5.5 4.8-7.7 7.7-.1 .1-.1 .2-.1 .4s0 .3 .1 .4 .2 .2 .3 .3 .2 .1 .4 .1c12.3 .1 29.7 4.4 41 10.7 .8 .4 .2 1.9-.6 1.7-69.5-15.9-123.1 18.5-134.5 26.8-.2 .1-.3 .1-.5 .1s-.3-.1-.5-.2-.2-.3-.2-.5 .1-.4 .2-.5l-.1 0z"/></svg>
                        <span><?php esc_html_e( 'Mailchimp', 'ultimate-addons-cf7' ); ?></span>
                    </div>

                    <div class="uacf7-integration-item">
                        <svg fill="#2271b1" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" stroke="#2271b1"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M16 0c-8.823-0.010-15.99 7.135-16 15.964v0.036c0 8.854 7.146 16 16 16s16-7.146 16-16c0-8.854-7.146-16-16-16zM16 27.734c-6.464 0.021-11.714-5.203-11.734-11.667v-0.068c-0.021-6.464 5.203-11.714 11.667-11.734h0.068c6.464-0.021 11.714 5.203 11.734 11.667v0.068c0.021 6.464-5.203 11.714-11.667 11.734zM23.255 12.052c0 1.813-1.495 3.307-3.307 3.307-1.823-0.010-3.297-1.484-3.307-3.307 0-1.813 1.495-3.307 3.307-3.307s3.307 1.495 3.307 3.307zM23.255 19.948c0 1.813-1.495 3.307-3.307 3.307-1.823-0.010-3.302-1.49-3.307-3.307 0-1.813 1.495-3.307 3.307-3.307s3.307 1.495 3.307 3.307zM15.359 19.948c0 1.813-1.49 3.307-3.302 3.307-1.823-0.010-3.302-1.484-3.307-3.307 0-1.813 1.49-3.307 3.307-3.307 1.807 0 3.302 1.495 3.302 3.307zM15.359 12.052c0 1.813-1.49 3.307-3.302 3.307-1.823-0.010-3.302-1.484-3.307-3.307 0-1.813 1.49-3.307 3.307-3.307 1.807 0 3.302 1.495 3.302 3.307z"></path> </g></svg>
                        <span><?php esc_html_e( 'Twilio Integration', 'ultimate-addons-cf7' ); ?></span>
                    </div>

                    <div class="uacf7-integration-item">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M94.1 315.1c0 25.9-21.2 47.1-47.1 47.1S0 341 0 315.1 21.2 268 47.1 268l47.1 0 0 47.1zm23.7 0c0-25.9 21.2-47.1 47.1-47.1S212 289.2 212 315.1l0 117.8c0 25.9-21.2 47.1-47.1 47.1s-47.1-21.2-47.1-47.1l0-117.8zm47.1-189c-25.9 0-47.1-21.2-47.1-47.1S139 32 164.9 32 212 53.2 212 79.1l0 47.1-47.1 0zm0 23.7c25.9 0 47.1 21.2 47.1 47.1S190.8 244 164.9 244L47.1 244C21.2 244 0 222.8 0 196.9s21.2-47.1 47.1-47.1l117.8 0zm189 47.1c0-25.9 21.2-47.1 47.1-47.1S448 171 448 196.9 426.8 244 400.9 244l-47.1 0 0-47.1zm-23.7 0c0 25.9-21.2 47.1-47.1 47.1S236 222.8 236 196.9l0-117.8C236 53.2 257.2 32 283.1 32s47.1 21.2 47.1 47.1l0 117.8zm-47.1 189c25.9 0 47.1 21.2 47.1 47.1S309 480 283.1 480 236 458.8 236 432.9l0-47.1 47.1 0zm0-23.7c-25.9 0-47.1-21.2-47.1-47.1S257.2 268 283.1 268l117.8 0c25.9 0 47.1 21.2 47.1 47.1s-21.2 47.1-47.1 47.1l-117.8 0z"/></svg>
                        <span><?php esc_html_e( 'Slack Integration', 'ultimate-addons-cf7' ); ?></span>
                    </div>

                </div>
            </div>

            <!-- Button for more integrations -->
            <div class="uacf7-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=uacf7_addons' ) ); ?>" class="button">
                    <?php esc_html_e( 'Check More Integrations', 'ultimate-addons-cf7' ); ?>
                </a>
            </div>
            
            <?php if(!class_exists('Ultimate_Addons_CF7_PRO')){  ?>
            <!-- Upsell -->
            <div class="uacf7-upsell">
                <h4><?php esc_html_e( 'Unlock Pro Features', 'ultimate-addons-cf7' ); ?></h4>
                <ul>
                    <li><?php esc_html_e( '✔ Conditional Logic', 'ultimate-addons-cf7' ); ?></li>
                    <li><?php esc_html_e( '✔ Custom Column Width', 'ultimate-addons-cf7' ); ?></li>
                    <li><?php esc_html_e( '✔ Frontend Post Submission', 'ultimate-addons-cf7' ); ?></li>
                    <li><?php esc_html_e( '✔ Drag & Drop File Upload & Signature', 'ultimate-addons-cf7' ); ?></li>
                </ul>
                <a href="<?php echo esc_url( 'https://cf7addons.com/pricing' ); ?>" target="_blank" class="button button-primary go-pro">
                    <?php esc_html_e( 'Upgrade Now', 'ultimate-addons-cf7' ); ?>
                </a>
            </div>
            <?php } ?>
            <!-- Blog Section -->
			<div class="uacf7-section-title"><?php esc_html_e( 'Latest posts from Ultra Addons for Contact Form 7', 'ultimate-addons-cf7' ); ?></div>
			<ul class="uacf7-blog-list">
				<li>
					<span class="uacf7-badge"><?php esc_html_e( 'NEW', 'ultimate-addons-cf7' ); ?></span>
					<a href="<?php echo esc_url( 'https://cf7addons.com/twilio-slack-integration-prevent-duplicate-entries-and-google-recaptcha-v3/' ); ?>" target="_blank"><?php esc_html_e( 'v3.5.25: Twilio, Slack integration & Security Improvements', 'ultimate-addons-cf7' ); ?></a>
				</li>
				<li>
					<a href="<?php echo esc_url( 'https://cf7addons.com/google-recaptcha-integration-with-contact-form-7/' ); ?>" target="_blank"><?php esc_html_e( 'v3.5.21: Added Google reCAPTCHA Support', 'ultimate-addons-cf7' ); ?></a>
				</li>
				<li>
					<a href="<?php echo esc_url( 'https://cf7addons.com/google-sheets-integration-with-contact-form-7/' ); ?>" target="_blank"><?php esc_html_e( 'v3.5.23: Google Sheets Integration with Contact form 7', 'ultimate-addons-cf7' ); ?></a>
				</li>
			</ul>

            <!-- Footer -->
            <div class="uacf7-footer">
                <a href="<?php echo esc_url( 'https://cf7addons.com/docs/' ); ?>" target="_blank">
                    <?php esc_html_e( 'Docs', 'ultimate-addons-cf7' ); ?>
                    <span aria-hidden="true" class="dashicons dashicons-external"></span>
                </a>
                <a href="<?php echo esc_url( 'https://portal.themefic.com/support/' ); ?>" target="_blank">
                    <?php esc_html_e( 'Support', 'ultimate-addons-cf7' ); ?>
                    <span aria-hidden="true" class="dashicons dashicons-external"></span>
                </a>
                <a href="<?php echo esc_url( 'https://cf7addons.com/blog/' ); ?>" target="_blank">
                    <?php esc_html_e( 'Blog', 'ultimate-addons-cf7' ); ?>
                    <span aria-hidden="true" class="dashicons dashicons-external"></span>
                </a>
                <a href="<?php echo esc_url( 'https://cf7addons.com/pricing' ); ?>" target="_blank" class="go-pro">
                    <?php esc_html_e( 'Buy Now', 'ultimate-addons-cf7' ); ?>
                    <span aria-hidden="true" class="dashicons dashicons-external"></span>
                </a>
            </div>

        </div>
        <?php
    }



}

Uacf7DashboardWidget::instance();
