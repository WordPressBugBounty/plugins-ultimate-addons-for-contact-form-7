<?php
// don't load directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'UACF7_select' ) ) {
	class UACF7_select extends UACF7_Fields {

		public function __construct( $field, $value = '', $settings_id = '', $parent_field = '', $section_key = '' ) {
			parent::__construct( $field, $value, $settings_id, $parent_field, $section_key  );
		}

		public function render() {

			if ( empty( $this->field['options'] ) && empty( $this->field['options_callback'] ) ) {
				return;
			}

			if ( isset( $this->field['options_callback'] ) && is_callable( $this->field['options_callback'] ) ) {
				$this->field['options'] = call_user_func( $this->field['options_callback'] );
			}

			if ( ! empty( $this->field['query_args'] ) && $this->field['options'] == 'posts' ) {
				$posts                  = get_posts( $this->field['query_args'] );
				$this->field['options'] = array(
					'0' => 'Select Field'
				);
				foreach ( $posts as $post ) {
					$this->field['options'][ $post->ID ] = ( empty( $post->post_title ) ) ? 'No title (' . $post->ID . ')' : $post->post_title;
				}
			}

			if ( ! empty( $this->field['query_args'] ) && $this->field['options'] == 'uacf7' ) { 
				$post_id  = $this->field['query_args']['post_id']; 
				$this->field['options'] = array(
					'0' => 'Select Field'
				);
				if($post_id > 0){
					$specific = isset($this->field['query_args']['specific']) ? $this->field['query_args']['specific'] : '';  
					$ContactForm = WPCF7_ContactForm::get_instance($post_id); 
					if($specific != ''){
						$tags = $ContactForm->scan_form_tags(array('basetype'=> $specific));
					}else{
						$tags = $ContactForm->scan_form_tags(); 
					} 
					// uacf7_print_r($tags);
					
					$exclude = isset($this->field['query_args']['exclude']) ? $this->field['query_args']['exclude'] : array();
					
					foreach ( $tags as $tag ) { 

						if ($tag['type'] == '' || in_array($tag['basetype'], $exclude) ) continue; 

						if( $tag['type'] == 'checkbox'  ) {   
							$tag_name =  is_array($tag['options']) && !in_array('exclusive', $tag['options']) ? $tag['name'].'[]'  : $tag['name'];
						}elseif( $tag['type'] == 'select'  ) {    
							$tag_name =  is_array($tag['options']) && in_array('multiple', $tag['options']) ? $tag['name'].'[]'  : $tag['name'];
						}else { 
							$tag_name = $tag['name'];
						}
						if($tag['name'] == '' && $tag['type'] == 'uarepeater'){
							$attrs = explode(' ', $tag['attr']);  
							if(  $attrs == '' ){
								$attrs = $tag['options'];
							} 
							$this->field['options'][ $attrs[0] ] =  esc_html($attrs[0]);  
						}else{

							$this->field['options'][ $tag_name ] =  esc_html($tag['name']); 
						}
					}
				}  
				
			}

			if ( ! empty( $this->field['query_args'] ) && $this->field['options'] == 'salesforce' ) {

				// Not connected → stop early
				if ( ! UACF7_Salesforce_Client::is_connected() ) {
					$this->field['options'] = [
						'' => 'Connect Salesforce first'
					];
					return;
				}

				$this->field['options'] = array(
					'' => 'Select Salesforce Field'
				);

				$object = '';

				if (!empty($this->field['query_args']['post_id'])) {
					$post_id = $this->field['query_args']['post_id'];

					$settings = uacf7_get_form_option($post_id, 'salesforce');
					
					if (!empty($settings['uacf7_salesforce_object'])) {
						$object = ucfirst($settings['uacf7_salesforce_object']);
					}
				}
				
				try {

					$cache_key = 'uacf7_sf_fields_' . strtolower($object);
					$cached = get_transient($cache_key);

					if ($cached !== false) {

						$this->field['options'] = apply_filters(
							'uacf7_salesforce_field_options',
							$cached,
							$object
						);

					} else {

						$client = new UACF7_Salesforce_Client();
						$response = $client->get_fields($object);

						if ( is_wp_error($response) ) {
							$this->field['options'] = [
								'' => $response->get_error_message()
							];
							
							return;
						}

						$options = ['' => 'Select Salesforce Field'];

						if (!empty($response['fields'])) {
							foreach ($response['fields'] as $field) {

								if (empty($field['createable'])) continue;

								$options[$field['name']] = sprintf(
									'%s',
									$field['label'],
								);
							}
						}

						set_transient($cache_key, $options, 1 * HOUR_IN_SECONDS);

						$this->field['options'] = apply_filters(
							'uacf7_salesforce_field_options',
							$options,
							$object
						);
					}

				} catch (Exception $e) {

					$this->field['options'] = [
						'' => 'Error loading fields'
					];
				}
			}

			if($this->field['options'] == 'post_types'){
				$post_types = get_post_types();
				$this->field['options'] = array();
				foreach ( $post_types as $post ) {
					$this->field['options'][ $post ] = $post;
				}
			}
			if ( ! empty( $this->field['query_args'] ) && $this->field['options'] == 'terms' ) {
				$terms                  = get_terms( $this->field['query_args'] );
				$this->field['options'] = array();
				foreach ( $terms as $term ) {
					$this->field['options'][ $term->term_id ] = $term->name;
				}
			}

			echo '<select name="' . $this->field_name() . '" id="' . esc_attr( $this->field_name() ) . '" data-depend-id="' . esc_attr( $this->field['id'] ) . '' . $this->parent_field . '" class="tf-select"  '. $this->field_attributes() .'>';
			if ( ! empty( $this->field['placeholder'] ) ) {
				echo '<option value="">' . esc_html( $this->field['placeholder'] ) . '</option>';
			}
			foreach ( $this->field['options'] as $key => $value ) {
				if($key !== '') {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $this->value, $key, false ) . '>' . esc_html( $value ) . '</option>';
				} else {
					//disable empty value
					echo '<option value="" disabled>' . esc_html( $value ) . '</option>';
				}
			}
			echo '</select>';
		}

	}
}