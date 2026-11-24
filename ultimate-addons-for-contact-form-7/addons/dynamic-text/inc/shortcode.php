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
        $attr = is_array( $val ) && ! empty( $val['attr'] ) ? sanitize_key( $val['attr'] ) : 'name';
        $allowed_bloginfo = array( 'name', 'description', 'url', 'wpurl', 'charset', 'html_type', 'version', 'language', 'text_direction' );
        if ( ! in_array( $attr, $allowed_bloginfo, true ) ) {
            $attr = 'name';
        }
        $data = get_bloginfo( $attr );
        return esc_html( (string) $data );
    }
    add_shortcode('UACF7_BLOGINFO', 'UACF7_BLOGINFO');

}

// POST iNFO Info Shortcode
if(!function_exists('UACF7_POSTINFO')){
    function UACF7_POSTINFO($val){ 
        global $post;
        $data = '';

        if ( ! $post instanceof WP_Post ) {
            return '';
        }

        // Password check
        if ( post_password_required( $post ) ) {
            return '';
        }

        // If post is not published, verify user has permission to read it
        if ( 'publish' !== $post->post_status && ! current_user_can( 'read_post', $post->ID ) ) {
            return '';
        }

        $attr = is_array( $val ) && ! empty( $val['attr'] ) ? sanitize_key( $val['attr'] ) : '';

        // Safe allowed attributes (strictly disallow sensitive attributes like post_password)
        $allowed_attrs = array(
            'post_title',
            'post_name',
            'post_date',
            'post_date_gmt',
            'post_excerpt',
            'post_status',
            'post_type',
            'id',
            'post_author',
            'comment_count',
            'post_permalink',
        );

        if ( 'post_permalink' === $attr ) {
            $data = get_permalink( $post->ID );
            return esc_url( $data );
        }

        if ( ! empty( $attr ) && in_array( strtolower( $attr ), $allowed_attrs, true ) ) {
            if ( 'id' === strtolower( $attr ) ) {
                $data = $post->ID;
            } elseif ( isset( $post->$attr ) ) {
                $data = $post->$attr;
            }
        } elseif ( empty( $attr ) ) {
            $data = $post->post_title;
        }

        return esc_html( (string) $data );
    }
    add_shortcode('UACF7_POSTINFO', 'UACF7_POSTINFO');

}

// User Info Info Shortcode
if(!function_exists('UACF7_USERINFO')){
    function UACF7_USERINFO($val){  
        $data = '';
        if( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            $attr = is_array( $val ) && ! empty( $val['attr'] ) ? sanitize_key( $val['attr'] ) : '';

            // Safe user attributes allowed to be retrieved (never allow user_pass, user_activation_key, session_tokens)
            $allowed_user_attrs = array(
                'display_name',
                'user_nicename',
                'user_login',
                'user_email',
                'user_url',
                'first_name',
                'last_name',
                'nickname',
                'description',
                'id',
            );

            if ( ! empty( $attr ) && in_array( strtolower( $attr ), $allowed_user_attrs, true ) ) {
                if ( 'id' === strtolower( $attr ) ) {
                    $data = $current_user->ID;
                } else {
                    $data = $current_user->$attr;
                }
            } else {
                $data = $current_user->user_nicename;
            } 
        }
        return esc_html( (string) $data );
    }
    add_shortcode('UACF7_USERINFO', 'UACF7_USERINFO');

}

// Post Custom Fields Shortcode
if(!function_exists('UACF7_CUSTOM_FIELDS')){
    function UACF7_CUSTOM_FIELDS($val){    
        $data ='';

        if ( empty( $val['attr'] ) || ! is_string( $val['attr'] ) ) {
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
        
        if ( empty( $id ) || empty( $custom_field ) ) {
            return $data;
        }

        $post = get_post( $id );
        if ( ! $post instanceof WP_Post ) {
            return $data;
        }

        // 1. Password-protected post check
        if ( post_password_required( $post ) ) {
            return $data;
        }

        // 2. Authorization check: if post is not published (draft, private, pending, etc.), require 'read_post' capability
        if ( 'publish' !== $post->post_status && ! current_user_can( 'read_post', $id ) ) {
            return $data;
        }

        // 3. Post type check: if post type is not public (e.g. shop_order, form submissions, internal CPTs), require 'read_post' capability
        $post_type_obj = get_post_type_object( $post->post_type );
        if ( ! $post_type_obj || ! $post_type_obj->public ) {
            if ( ! current_user_can( 'read_post', $id ) ) {
                return $data;
            }
        }

        // 4. Protected / private meta keys check:
        // In WordPress, any meta key starting with '_' is a private/internal meta field (e.g. _billing_email, _secret_meta).
        // Accessing protected meta must require 'edit_post' permission for the post.
        if ( ( function_exists( 'is_protected_meta' ) && is_protected_meta( $custom_field, 'post' ) ) || 0 === strpos( $custom_field, '_' ) ) {
            if ( ! current_user_can( 'edit_post', $id ) ) {
                return $data;
            }
        }

        // 5. Extensibility hook allowing sites/developers to filter allowed keys or authorization
        if ( ! apply_filters( 'uacf7_custom_fields_can_read_meta', true, $custom_field, $id, $post ) ) {
            return $data;
        }

        $raw = get_post_meta( $id, $custom_field, true );
        if ( is_array( $raw ) || is_object( $raw ) ) {
            $raw = wp_json_encode( $raw );
        }

        $allowed = array(
            'a'      => array( 'href' => true, 'title' => true ),
            'strong' => array(),
            'em'     => array(),
            'br'     => array(),
        );
        $data = wp_kses( (string) $raw, $allowed );

        return $data;
    }
    add_shortcode('UACF7_CUSTOM_FIELDS', 'UACF7_CUSTOM_FIELDS');
}


?>