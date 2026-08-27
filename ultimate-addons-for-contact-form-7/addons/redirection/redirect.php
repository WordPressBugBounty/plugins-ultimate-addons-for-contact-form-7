<?php

use phpDocumentor\Reflection\Types\This;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UACF7_Redirection {
    
	/**
	 * Store redirection fields.
	 *
	 * @var array
	 */
	public $fields = [];

	/**
	 * Store the resolved redirect URL.
	 *
	 * @var string
	 */
	public $redirect_url = '';

	/**
	 * Store whether a new-tab redirect script should be enqueued.
	 *
	 * @var bool
	 */
	public $enqueue_new_tab_script = false;

    /*
    * Construct function
    */
    public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_redirect_script' ) );  
		// add_action( 'wpcf7_after_save', array( $this, 'uacf7_save_meta' ) );
		add_action( 'wpcf7_submit', array( $this, 'uacf7_non_ajax_redirection' ) );
		add_filter( 'uacf7_post_meta_options', array( $this, 'uacf7_post_meta_options_redirection' ), 10, 2 );
    }
    
    public function enqueue_redirect_script() {
		
        wp_enqueue_script( 'uacf7-redirect-script', UACF7_URL . 'addons/redirection/js/redirect.js', array('jquery'), UACF7_VERSION, true );
		wp_localize_script( 'uacf7-redirect-script', 'uacf7_redirect_object', $this->get_forms() );
        wp_localize_script( 'uacf7-redirect-script', 'uacf7_redirect_enable', $this->uacf7_redirect_enable() );
        
		if ( isset( $this->enqueue_new_tab_script ) && $this->enqueue_new_tab_script ) {
			wp_add_inline_script( 'wpcf7-redirect-script', 'window.open("' . $this->redirect_url . '");' );
		}
    }
 
	
    public function uacf7_post_meta_options_redirection($value, $post_id) {
		
		$fields = array( 
			'redirection_heading' => array(
				'id'    => 'redirection_heading',
				'type'  => 'heading', 
				'label' => __( 'Redirection Settings', 'ultimate-addons-for-contact-form-7' ),
				'subtitle' => sprintf(
					/* translators: %1$s: demo link */
					__( 'Redirect users to a Thank You or external page based on form submission, with an option to open in a new tab. See Demo %1$s.', 'ultimate-addons-for-contact-form-7' ),
						'<a href="https://cf7addons.com/preview/redirection-for-contact-form-7/" target="_blank">'.esc_html__( 'Demo', 'ultimate-addons-for-contact-form-7' ).'</a>'
				)
			),
			'redirection_docs' => array(
				'id'      => 'redirection_docs',
				'type'    => 'notice',
				'style'   => 'success',
				'content' => sprintf( 
					/* translators: %1$s: redirect to a page or external URL, %2$s: conditional redirect, %3$s: tag support */
					__( 'Confused? Check our Documentation on  %1$s, %2$s and %3$s .', 'ultimate-addons-for-contact-form-7' ),
					'<a href="https://themefic.com/docs/uacf7/free-addons/redirection-for-contact-form-7/" target="_blank">'.esc_html__( 'Redirect to a Page or External URL', 'ultimate-addons-for-contact-form-7' ).'</a>',
					'<a href="https://themefic.com/docs/uacf7/pro-addons/conditional-redirect-for-contact-form-7/" target="_blank">'.esc_html__( 'Conditional Redirect', 'ultimate-addons-for-contact-form-7' ).'</a>',
					'<a href="https://themefic.com/docs/uacf7/pro-addons/contact-form-7-whatsapp-integration-and-tag-support/" target="_blank">'.esc_html__( 'Tag Support', 'ultimate-addons-for-contact-form-7' ).'</a>'
				)
			),
			'uacf7_redirect_enable' => array(
				'id'        => 'uacf7_redirect_enable',
				'type'      => 'switch',
				'label'     => __( ' Enable Redirection', 'ultimate-addons-for-contact-form-7' ),
				'label_on'  => __( 'Yes', 'ultimate-addons-for-contact-form-7' ),
				'label_off' => __( 'No', 'ultimate-addons-for-contact-form-7' ),
				'default'   => false
			),
			'uacf7_redirect_form_options_heading' => array(
				'id'        => 'uacf7_redirect_form_options_heading',
				'type'      => 'heading',
				'label'     => __( 'Redirection Option ', 'ultimate-addons-for-contact-form-7' ),
			),
			'uacf7_redirect_to_type' => array(
				'id'        => 'uacf7_redirect_to_type',
				'type'      => 'radio',
				'label'     => __( 'Redirect to', 'ultimate-addons-for-contact-form-7' ),
				'options' => array(
					'to_page' => __( 'Redirect to Internal Page', 'ultimate-addons-for-contact-form-7' ),
					'to_url' => 'Redirect to External URL ',
					),
					'default' => 'to_page',
					'inline' => true,
			),
			'page_id' => array(
				'id'        => 'page_id',
				'type'      => 'select',
				'label'     => __( 'Select the Redirection Page ', 'ultimate-addons-for-contact-form-7' ),  
				'options'     => 'posts', 
				'query_args'  => array(
					'post_type'      => 'page',
					'posts_per_page' => - 1,
				),
				'multiple' => true,
				'dependency' => array(array( 'uacf7_redirect_to_type', '==', 'to_page' )),
			),
			'external_url' => array(
				'id'        => 'external_url',
				'type'      => 'text',
				'label'     => __( 'Insert Any URL', 'ultimate-addons-for-contact-form-7' ),   
				'dependency' => array(array( 'uacf7_redirect_to_type', '==', 'to_url' )),
			),
			'target' => array(
				'id'        => 'target',
				'type'      => 'switch',
				'label'     => __( 'Open Page in a New Tab', 'ultimate-addons-for-contact-form-7' ),
				'subtitle' => __( 'Enable this to open the redirection page in a new tab.', 'ultimate-addons-for-contact-form-7' ),
				'label_on'  => __( 'Yes', 'ultimate-addons-for-contact-form-7' ),
				'label_off' => __( 'No', 'ultimate-addons-for-contact-form-7' ),
				'default'   => false,
				'field_width' => 50,
			),
			
		);

		/**
		 * Allow extensions to add/modify Conditional Redirect fields.
		 */
		$fields = apply_filters(
			'uacf7_conditional_redirect_fields',
			$fields,
			$post_id
		);

		$redirection = apply_filters('uacf7_post_meta_options_redirection_pro', $data = array(
			'title'  => __( 'Redirection', 'ultimate-addons-for-contact-form-7' ),
			'icon'   => 'fa-solid fa-diamond-turn-right',
            'checked_field'   => 'uacf7_redirect_enable',
			'fields' => $fields
		), $post_id);
		$value['redirection'] = $redirection;  
		return $value;
	}
   
 

    public function get_forms() {
		$args  = array(
			'post_type'        => 'wpcf7_contact_form',
			'posts_per_page'   => -1,
		);
		$query = new WP_Query( $args );

		$forms = array();

		if ( $query->have_posts() ) :

			
			$fields = $this->fields(); 

			while ( $query->have_posts() ) :
				$query->the_post();

				$post_id = get_the_ID(); 
				$post_meta = uacf7_get_form_option($post_id, 'redirection');
				if($post_meta != false){
					foreach ( $fields as $field ) {
						// $forms[ $post_id ][ $field['name'] ] = get_post_meta( $post_id, 'uacf7_redirect_' . $field['name'], true );
						$forms[ $post_id ][ $field['name'] ] = $post_meta[$field['name']];
					}
	
					$forms[ $post_id ]['thankyou_page_url'] = $forms[ $post_id ]['page_id'] ? get_permalink( $forms[ $post_id ]['page_id'] ) : '';
				}
				
			endwhile;
			wp_reset_postdata();
		endif;

		return $forms;
	}
    
    public function uacf7_get_options( $post_id ) {
		$fields = $this->fields();
		$post_meta = uacf7_get_form_option($post_id, 'redirection');
		foreach ( $fields as $field ) {
			$values[ $field['name'] ] = $post_meta[$field['name']];
		}
		return $values;
	}
    
 
    
    public function uacf7_non_ajax_redirection( $contact_form ) {
		$this->fields = $this->uacf7_get_options( $contact_form->id() );

		if ( isset( $this->fields ) && ! WPCF7_Submission::is_restful() ) {
			$submission = WPCF7_Submission::get_instance();

			if ( $submission->get_status() === 'mail_sent' ) {

				if ( 'to_url' === $this->fields['uacf7_redirect_to_type'] && $this->fields['external_url'] ) {
					$this->redirect_url = $this->fields['external_url'];
				}
				if( 'to_page' === $this->fields['uacf7_redirect_to_type'] && $this->fields['page_id'] ){
					$this->redirect_url = get_permalink( $this->fields['page_id'] );
				}

				// Open link in a new tab
				if ( isset( $this->redirect_url ) && $this->redirect_url ) {
					if ( 'on' === $this->fields['open_in_new_tab'] ) {
						$this->enqueue_new_tab_script = true;
					} else {
						wp_safe_redirect( $this->redirect_url );
						exit;
					}
				}
			}
		}
	}
    

    /*
    * Fields array
    */
    public function fields() {
        $fields = array(
            array(
                'name'  => 'uacf7_redirect_to_type',
                'type'  => 'radio',
            ),
			array(
                'name'  => 'page_id',
                'type'  => 'number',
            ),
            array(
                'name'  => 'external_url',
                'type'  => 'url',
            ),
            array(
                'name'  => 'target',
                'type'  => 'checkbox',
            ),
        );
        return $fields;
    }
 
    /*
    Enable conditional redirect
    */
    public function uacf7_redirect_enable() {
    	$args  = array(
    		'post_type'        => 'wpcf7_contact_form',
    		'posts_per_page'   => -1,
    	);
    	$query = new WP_Query( $args );
    
    	$forms = array();
    
    	if ( $query->have_posts() ) :
    
    		while ( $query->have_posts() ) :
    			$query->the_post();
    
                $post_id = get_the_ID();
                
                // $uacf7_redirect = get_post_meta( get_the_ID(), 'uacf7_redirect_enable', true );
				$post_meta = uacf7_get_form_option(get_the_ID(), 'redirection');
				// beaf_print_r($post_meta);
				if($post_meta != false){
					$uacf7_redirect = $post_meta['uacf7_redirect_enable']; 
					
					if( !empty($uacf7_redirect) && $uacf7_redirect == true ) {
						
						$forms[ $post_id ] = $uacf7_redirect;
					
					}
				} 
    		endwhile;
    		wp_reset_postdata();
    	endif;
		// beaf_print_r($forms);
    	return $forms;
    }
}
new UACF7_Redirection();