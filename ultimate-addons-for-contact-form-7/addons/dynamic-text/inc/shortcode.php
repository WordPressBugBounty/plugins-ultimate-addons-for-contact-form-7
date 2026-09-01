<?php 
if ( ! defined( 'ABSPATH' ) ) exit;

// // Current url Shortcode
// if(!function_exists('UACF7_URL')){
//     function UACF7_URL($val){ 
//         beaf_print_r($val);
//         $data = get_permalink();
//         return $data;
//     }

//     add_shortcode('UACF7_URL', 'UACF7_URL'); 
// }

if (!function_exists('UACF7_URL')) {
    function UACF7_URL($val) {
        $http_host = isset( $_SERVER['HTTP_HOST'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) )
            : '';
        $request_uri = isset( $_SERVER['REQUEST_URI'] )
            ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )
            : '';

        $current_url = 'https://' . $http_host . $request_uri;
        $parsed_url = wp_parse_url( $current_url );
        $part = isset( $val['part'] ) ? sanitize_key( trim( wp_unslash( $val['part'] ), "'" ) ) : '';
        $key = isset( $val['key'] ) ? sanitize_text_field( wp_unslash( trim( $val['key'], "'" ) ) ) : '';

        $query_array = array();
        if ( ! empty( $parsed_url['query'] ?? '' ) ) {
            wp_parse_str( $parsed_url['query'], $query_array );
        }

        switch ( $part ) {
            case 'host':
                return esc_html(
                    sanitize_text_field( $parsed_url['host'] ?? '' )
                );
            case 'path':
                return esc_html(
                    sanitize_text_field( $parsed_url['path'] ?? '' )
                );
            case 'query':
                // If a key is provided, return its value
                if ( ! empty( $key ) && isset( $query_array[ $key ] ) ) {
                    return esc_html( sanitize_text_field( $query_array[ $key ] ) );
                }
                // Otherwise, return full query string
                $query = http_build_query(
                    array_map( 'sanitize_text_field', $query_array ),
                    '',
                    '&',
                    PHP_QUERY_RFC3986
                );

                return esc_html( $query );

            default:
                // Return only base URL (no query string)
                $scheme = is_ssl() ? 'https' : 'http';
                $host = $parsed_url['host'] ?? $http_host;
                $path = $parsed_url['path'] ?? '';
                return esc_url( $scheme . '://' . $host . $path );
        }
    }

    add_shortcode('UACF7_URL', 'UACF7_URL');
}


// Current url with Perameters Shortcode
if(!function_exists('UACF7_URL_WITH_PERAMETERS')){
  
    function UACF7_URL_WITH_PERAMETERS($val){ 
        $request_uri = isset( $_SERVER['REQUEST_URI'] )
            ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )
            : '';
        $current = home_url( $request_uri );

        return esc_url( $current );
    }

    add_shortcode('UACF7_URL_WITH_PERAMETERS', 'UACF7_URL_WITH_PERAMETERS'); 
}



// Blog Info Shortcode
if(!function_exists('UACF7_BLOGINFO')){
    function UACF7_BLOGINFO($val){ 
        $attr = is_array( $val ) && ! empty( $val['attr'] ) ? $val['attr'] : 'name';
        $data = get_bloginfo( $attr );
        return esc_html( $data );
    }
    add_shortcode('UACF7_BLOGINFO', 'UACF7_BLOGINFO');

}

// POST iNFO Info Shortcode
if(!function_exists('UACF7_POSTINFO')){
    function UACF7_POSTINFO($val){ 
        global $post;
        $data = '';

        if ( ! $post ) {
            return '';
        }

        $attr = is_array( $val ) && ! empty( $val['attr'] ) ? $val['attr'] : '';

        if ( $attr === 'post_permalink' ) {
            $data = get_permalink( $post->ID );
        } elseif ( ! empty( $attr ) ) {
            $post_attr = $attr;
            $data = $post->$post_attr;
        } else {
            $data = $post->post_title;
        }

        if ( $attr === 'post_permalink' ) {
            return esc_url( $data );
        }

        return esc_html( $data );
    }
    add_shortcode('UACF7_POSTINFO', 'UACF7_POSTINFO');

}

// User Info Info Shortcode
if(!function_exists('UACF7_USERINFO')){
    function UACF7_USERINFO($val){  
        $data = '';
        if( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            if(!empty($val['attr'])){
                $user_attr = $val['attr'];
                $data = $current_user->$user_attr;
            }else{
                $data = $current_user->user_nicename;
            } 
        }
        return esc_html( $data );
    }
    add_shortcode('UACF7_USERINFO', 'UACF7_USERINFO');

}

// Post Custom Fields Shortcode
if(!function_exists('UACF7_CUSTOM_FIELDS')){
    function UACF7_CUSTOM_FIELDS($val){    
        $data ='';

        if ( empty( $val['attr'] ) ) {
            return $data;
        }

        $value = explode("/",$val['attr']); 

        if ( count( $value ) === 2 ) {
            // Passed both ID and custom field
            $id = absint( $value[0] );
            $custom_field = sanitize_key( $value[1] );
        } else {
            // Only field name passed, get current post ID
            $id = get_the_ID();
            $custom_field = sanitize_key( $value[0] );
        }
        
        if ( $id > 0 && ! empty( $custom_field ) ) {
            $raw = get_post_meta( $id, $custom_field, true );
            $allowed = array(
                'a'      => array( 'href' => true, 'title' => true ),
                'strong' => array(),
                'em'     => array(),
                'br'     => array(),
                );
            $data = wp_kses( $raw, $allowed );
        }

        return $data;
    }
    add_shortcode('UACF7_CUSTOM_FIELDS', 'UACF7_CUSTOM_FIELDS');
}


?>