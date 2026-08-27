<?php
// don't load directly
defined( 'ABSPATH' ) || exit;

/**
 * Get all the meals from glabal settings
 * @author AbuHena
 * @since 1.7.0
 */

$uacf7_requested_post_id = filter_input( INPUT_GET, 'post', FILTER_VALIDATE_INT );
$uacf7_requested_page = filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW );
$uacf7_requested_page = is_string( $uacf7_requested_page ) ? sanitize_key( $uacf7_requested_page ) : '';

if ( $uacf7_requested_post_id && $uacf7_requested_post_id > 0 && 'wpcf7' === $uacf7_requested_page ) {
	$uacf7_post_id = absint( $uacf7_requested_post_id );
} else {
	$uacf7_post_id = 0;
}
UACF7_Metabox::metabox( 'uacf7_form_opt', array(
	'title' => __( 'Addons for CF7 Options', 'ultimate-addons-for-contact-form-7' ),
	'post_type' => 'uacf7',

	'sections' => apply_filters( 'uacf7_post_meta_options', array(), $uacf7_post_id ),

) );
