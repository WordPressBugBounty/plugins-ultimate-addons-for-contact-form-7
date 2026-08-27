<?php
// don't load directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'UACF7_imageselect' ) ) {
    class UACF7_imageselect extends UACF7_Fields {
        public function __construct( $field, $value = '', $settings_id = '', $parent_field = '', $section_key = '' ) {

            parent::__construct( $field, $value, $settings_id, $parent_field, $section_key );

        }

        public function render() {

            if ( isset( $this->field['options'] ) ) {

                $inline = ( isset( $this->field['inline'] ) && $this->field['inline'] ) ? 'tf-inline' : '';

                echo '<ul class="tf-image-radio-group ' . esc_attr( $inline ) . '">';

                foreach ( $this->field['options'] as $key => $value ) {

                    $checked = $key == $this->value ? ' checked' : '';

                    /*
                     * Default option state.
                     */
                    $state = array(
                        'class'    => '',
                        'disabled' => false,
                        'badges'   => array(),
                    );

                    /*
                     * the image select option state.
                     */
                    $state = apply_filters(
                        'uacf7_imageselect_option_state',
                        $state,
                        $value,
                        $key,
                        $this->field,
                        $this->value,
                        $this->settings_id,
                        $this->parent_field
                    );

                    $class = isset( $state['class'] ) ? $state['class'] : '';

                    /*
                     * Build badges provided by extensions.
                     *
                     * The free plugin does not know what the badge means.
                     * It only renders generic badge data supplied by filters.
                     */
                    $badges = '';

                    if ( ! empty( $state['badges'] ) && is_array( $state['badges'] ) ) {

                        foreach ( $state['badges'] as $badge ) {

                            if ( ! is_array( $badge ) ) {
                                continue;
                            }

                            $badge_label = isset( $badge['label'] ) ? $badge['label'] : '';
                            $badge_class = isset( $badge['class'] ) ? $badge['class'] : '';

                            if ( empty( $badge_label ) ) {
                                continue;
                            }

                            $badges .= '<span class="tf-csf-badge ' . esc_attr( $badge_class ) . '">';
                            $badges .= '<span class="' . esc_attr( $badge_class ) . '">' . esc_html( $badge_label ) . '</span>';
                            $badges .= '</span>';
                        }
                    }

                    ?>

                    <li>

                        <label class="tf-image-checkbox <?php echo esc_attr( $class ); ?>">

                            <?php

                            echo '<input type="radio" id="'
                                . esc_attr( $this->field_name() )
                                . '['
                                . esc_attr( $key )
                                . ']" '
                                . disabled( ! empty( $state['disabled'] ), true, false )
                                . ' name="'
                                . esc_attr( $this->field_name() )
                                . '"'
                                . ' data-depend-id="'
                                . esc_attr( $this->field['id'] )
                                . esc_attr( $this->parent_field )
                                . '"'
                                . ' value="'
                                . esc_attr( $key )
                                . '"'
                                . esc_attr( $checked )
                                . ' '
                                . esc_attr( $this->field_attributes() )
                                . '/>';

                            ?>

                            <img
                                src="<?php echo esc_url( $value['url'] ); ?>"
                                alt="<?php echo esc_attr( $value['title'] ); ?>"
                            >

                            <span class="tf-image-title">

                                <?php echo esc_html( $value['title'] ); ?>

                                <?php echo wp_kses_post( $badges ); ?>

                            </span>

                        </label>

                    </li>

                    <?php
                }

                echo '</ul>';
            }
        }
    }
}
