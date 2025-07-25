<?php
// don't load directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'UACF7_select2' ) ) {
	class UACF7_select2 extends UACF7_Fields {

		public function __construct( $field, $value = '', $settings_id = '', $parent_field = '',  $section_key = ''  ) {
			parent::__construct( $field, $value, $settings_id, $parent_field , $section_key );
		}

		public function render() {

			if(empty($this->field['options']) && empty($this->field['options_callback'])) {
				return;
			}

			$args = wp_parse_args( $this->field, array(
				'placeholder' => '',
				'multiple'    => false,
			) );

			if(isset($this->field['options_callback']) && is_callable($this->field['options_callback'])) {
				$this->field['options'] = call_user_func($this->field['options_callback']);
			}

			$placeholder = ( ! empty( $args['placeholder'] ) ) ? $args['placeholder'] : '';
			$multiple    = ( ! empty( $args['multiple'] ) ) ? 'multiple' : '';

			if(!empty($args['query_args']) && $args['options'] == 'posts'){
				$posts = get_posts($args['query_args']);
				$args['options'] = array();
				foreach($posts as $post){
					$args['options'][$post->ID] = (empty($post->post_title)) ? 'No title ('.$post->ID.')' : $post->post_title;
				}
			}

			if(!empty($args['query_args']) && $args['options'] == 'terms'){
				$terms = get_terms($args['query_args']);
				$args['options'] = array();
				foreach($terms as $term){
					$args['options'][$term->term_id] = $term->name;
				}
			}

			if ( ! empty( $args['query_args'] ) && $args['options'] == 'uacf7' ) { 
				$post_id  = isset( $args['query_args']['post_id'] ) ? (int) $args['query_args']['post_id'] : 0; 
				$args['options'] = [];

				if ( $post_id > 0 ) {
					$specific = isset( $args['query_args']['specific'] ) ? $args['query_args']['specific'] : '';  
					$ContactForm = WPCF7_ContactForm::get_instance( $post_id ); 

					$tags = ( $specific != '' )
						? $ContactForm->scan_form_tags( array( 'basetype'=> $specific ) )
						: $ContactForm->scan_form_tags();

					$exclude = isset( $args['query_args']['exclude'] ) ? $args['query_args']['exclude'] : array();

					foreach ( $tags as $tag ) { 
						if ( $tag['type'] == '' || in_array( $tag['basetype'], $exclude ) ) continue; 

						if ( $tag['type'] == 'checkbox' ) {   
							$tag_name = ( is_array( $tag['options'] ) && !in_array( 'exclusive', $tag['options'] ) )
								? $tag['name'].'[]'
								: $tag['name'];
						} elseif ( $tag['type'] == 'select' ) {    
							$tag_name = ( is_array( $tag['options'] ) && in_array( 'multiple', $tag['options'] ) )
								? $tag['name'].'[]'
								: $tag['name'];
						} else { 
							$tag_name = $tag['name'];
						}

						if ( $tag['name'] == '' && $tag['type'] == 'uarepeater' ) {
							$attrs = explode( ' ', $tag['attr'] );  
							if ( $attrs == '' ) {
								$attrs = $tag['options'];
							} 
							$args['options'][ $attrs[0] ] = esc_html( $attrs[0] );  
						} else {
							$args['options'][ $tag_name ] = esc_html( $tag['name'] ); 
						}
					}
				}
			}


			$field_name = !empty($this->field['multiple']) ? $this->field_name() . '[]' : $this->field_name();
			$tf_select2_unique_id = str_replace( array("[","]"),"_",esc_attr( $this->field_name() ) );
			$parent_class = ( ! empty( $this->parent_field ) ) ? 'tf-select2-parent' : 'tf-select2';
			$parent_class = ( isset( $this->field['select2'] ) ) ? 'tf-select2' : $parent_class ;

			echo '<select name="' . $field_name . '" id="' . $tf_select2_unique_id . '" class=" tf-select-two '.$parent_class.' " data-placeholder="' . esc_attr( $placeholder ) . '" ' . $multiple . ' '. $this->field_attributes() .'>';
			foreach ( $args['options'] as $key => $value ) {
				$selected = '';

				if ( ! empty( $this->field['multiple'] ) ) {
					if ( is_array( $this->value ) && in_array( $key, $this->value ) ) {
						$selected = 'selected';
					}
				} else {
					$selected = selected( $this->value, $key, false );
				}

				echo '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . esc_html( $value ) . '</option>';
			}

			echo '</select>';
		}

		//sanitize
		public function sanitize() {
			$value = $this->value;
			if ( ! empty( $this->field['multiple'] ) && is_array( $this->value ) ) {
				$value = array_map( 'sanitize_text_field', $value );
			} else {
				$value = sanitize_text_field( $value );
			}

			return $value;
		}

	}
}