<?php

// Do not access directly

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UACF7_SIGNATURE_PANEL{

  public $uacf7_signature_enable;
  public $uacf7_signature_bg_color;
  public $uacf7_signature_pen_color;
  public $uacf7_signature_pad_width;
  public $uacf7_signature_pad_height;

  public function __construct(){
    add_action( 'wpcf7_editor_panels', [$this, 'uacf7_signature_panel_add'] );
    add_action( 'wpcf7_after_save', [$this, 'uacf7_signature_save_form'] );
    
  }



  /** Signature Panel Adding */

  public function uacf7_signature_panel_add($panels){
    $panels['uacf7-signature-panel'] = array(
      'title'    => __( 'UACF7 Signature', 'ultimate-addons-for-contact-form-7' ),
      'callback' => [ $this, 'uacf7_create_uacf7_signature_panel_fields' ],
      );
      return $panels;
  }

  public function uacf7_create_uacf7_signature_panel_fields( $form){

    $uacf7_signature_settings = get_post_meta( $form->id(), 'uacf7_signature_settings', true );



    if(!empty($uacf7_signature_settings)){
      $this->uacf7_signature_enable = $uacf7_signature_settings['uacf7_signature_enable'] ?? '';
      $this->uacf7_signature_bg_color = $uacf7_signature_settings['uacf7_signature_bg_color'] ?? '#dddddd';
      $this->uacf7_signature_pen_color = $uacf7_signature_settings['uacf7_signature_pen_color'] ?? '#000000';
      $this->uacf7_signature_pad_width = $uacf7_signature_settings['uacf7_signature_pad_width'] ?? '300';
      $this->uacf7_signature_pad_height = $uacf7_signature_settings['uacf7_signature_pad_height'] ?? '100';
      
    } 

    ?> 

      <h2><?php echo esc_html__( 'Signature Settings', 'ultimate-addons-for-contact-form-7' ); ?></h2>  
      <p><?php echo esc_html__('This feature will help you to add the signature in form .','ultimate-addons-for-contact-form-7'); ?>  </p>
      <div class="uacf7-doc-notice"> 
            <?php echo wp_kses_post( sprintf( 
                /* translators: %1s: demo link */
                __( 'Confused? Check our Documentation on  %1$s.', 'ultimate-addons-for-contact-form-7' ),
                '<a href="https://themefic.com/docs/uacf7/free-addons/contact-form-7-signature-addon/" target="_blank" rel="noopener">' . esc_html__( 'Digital Signature', 'ultimate-addons-for-contact-form-7' ) . '</a>'
            ) ); ?> 
        </div>

      <label for="uacf7_signature_enable"> 
      <input class="uacf7_signature_enable" id="uacf7_signature_enable" name="uacf7_signature_enable" type="checkbox" <?php checked( 'on',  $this->uacf7_signature_enable, true ); ?>> <?php esc_html_e( 'Enable Signature for Form', 'ultimate-addons-for-contact-form-7' ); ?>
      </label>

      <div class="uacf7_signature_wrapper">
        <fieldset>
        <h3><?php esc_html_e('Signature Pad Background Color', 'ultimate-addons-for-contact-form-7' ) ?></h3>
            <input type="text" id="uacf7_signature_bg_color" name="uacf7_signature_bg_color" class="uacf7-color-picker" value="<?php echo esc_attr($this->uacf7_signature_bg_color? $this->uacf7_signature_bg_color : '#dddddd'); ?>" placeholder="<?php echo esc_attr__( 'Background Color', 'ultimate-addons-for-contact-form-7' ); ?>"><br>
 
            <small><?php esc_html_e(' E.g. Default is #dddddd', 'ultimate-addons-for-contact-form-7' ) ?></small>
           
            <h3><?php esc_html_e('Signature Pen Color', 'ultimate-addons-for-contact-form-7' ) ?></h3>
            <input type="text" id="uacf7_signature_pen_color" name="uacf7_signature_pen_color" class="uacf7-color-picker" value="<?php echo esc_attr($this->uacf7_signature_pen_color? $this->uacf7_signature_pen_color : '#000000'); ?>" placeholder="<?php echo esc_attr__( 'Pen Color', 'ultimate-addons-for-contact-form-7' ); ?>"><br>
    
            <small><?php esc_html_e(' E.g. Default is #000000', 'ultimate-addons-for-contact-form-7' ) ?></small> 

            <h3><?php esc_html_e('Signature Pad Width', 'ultimate-addons-for-contact-form-7' ) ?></h3>
            <input type="text" id="uacf7_signature_pad_width" name="uacf7_signature_pad_width" value="<?php echo esc_attr($this->uacf7_signature_pad_width? $this->uacf7_signature_pad_width : '300'); ?>" placeholder="<?php echo esc_attr__( 'Pad Width', 'ultimate-addons-for-contact-form-7' ); ?>"><br>
    
            <small><?php esc_html_e(' E.g. There is no need to include units such as "px" or "rem".', 'ultimate-addons-for-contact-form-7' ) ?></small> 

            <h3><?php esc_html_e('Signature Pad Height', 'ultimate-addons-for-contact-form-7' ) ?></h3>
            <input type="text" id="uacf7_signature_pad_height" name="uacf7_signature_pad_height" value="<?php echo esc_attr($this->uacf7_signature_pad_height? $this->uacf7_signature_pad_height : '100'); ?>" placeholder="<?php echo esc_attr__( 'Pad Height', 'ultimate-addons-for-contact-form-7' ); ?>"><br>
    
            <small><?php esc_html_e(' E.g. There is no need to include units such as "px" or "rem".', 'ultimate-addons-for-contact-form-7' ) ?></small> 
        </fieldset> 
      </div>
     
   <?php 

    wp_nonce_field( 'uacf7_signature_nonce_action', 'uacf7_signature_nonce' );

  }

  /** Form Save */

  public function uacf7_signature_save_form($form){

    if ( ! isset( $_POST ) || empty( $_POST ) ) {
        return;
    }

    $nonce = isset( $_POST['uacf7_signature_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['uacf7_signature_nonce'] ) ) : '';
    if ( !wp_verify_nonce( $nonce, 'uacf7_signature_nonce_action' ) ) {
        return;
    }

    $uacf7_signature_settings = [
      'uacf7_signature_enable' =>  isset($_POST['uacf7_signature_enable']) ? sanitize_text_field( wp_unslash( $_POST['uacf7_signature_enable'] ) ) : '',
      'uacf7_signature_bg_color' =>  isset($_POST['uacf7_signature_bg_color']) ? sanitize_text_field( wp_unslash( $_POST['uacf7_signature_bg_color'] ) ) : '',
      'uacf7_signature_pen_color' =>  isset($_POST['uacf7_signature_pen_color']) ? sanitize_text_field( wp_unslash( $_POST['uacf7_signature_pen_color'] ) ) : '',
      'uacf7_signature_pad_width' =>  isset($_POST['uacf7_signature_pad_width']) ? sanitize_text_field( wp_unslash( $_POST['uacf7_signature_pad_width'] ) ) : '',
      'uacf7_signature_pad_height' =>  isset($_POST['uacf7_signature_pad_height']) ? sanitize_text_field( wp_unslash( $_POST['uacf7_signature_pad_height'] ) ) : '',
    ];

    update_post_meta( $form->id(), 'uacf7_signature_settings', $uacf7_signature_settings);

  }


}

new UACF7_SIGNATURE_PANEL;