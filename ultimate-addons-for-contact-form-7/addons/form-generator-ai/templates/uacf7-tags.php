<?php
defined( 'ABSPATH' ) || exit;

/**
 * Template for the Form Generator AI tags.
 *
 * @package   UACF7
 * @subpackage Form Generator AI
 * @since     1.0.0
 * @Author:  Sydur Rahman
 * @variable :  $uacf7_default, $form_step, $form_field, $uacf7_form_label
 *
 */ 
$uacf7_tag_manager = WPCF7_FormTagsManager::get_instance();

// $reflector = new ReflectionClass('WPCF7_TagGenerator');
// $property = $reflector->getProperty('panels');
// $property->setAccessible(true);

// $panels = $property->getValue($tag_generator); 

ob_start();
    $uacf7_field = '';
    if(isset($uacf7_default[1]) && !empty($uacf7_default[1])){
        $uacf7_form_label = isset($uacf7_default[2]) ? $uacf7_default[2] : 0;
        $uacf7_required = isset($uacf7_default[3]) ? $uacf7_default[3] : '';
        $uacf7_number = wp_rand(100, 999);
        
        switch($uacf7_default[1]){
            case 'text':
                $tag = '[text'.esc_attr($uacf7_required).' text-'.$uacf7_number.' placeholder "Your text here"]';
                break;
            case 'email':
                $tag = '[email'.esc_attr($uacf7_required).' email-'.$uacf7_number.' placeholder "you@example.com"]';
                break;
            case 'url':
                $tag = '[url'.esc_attr($uacf7_required).' url-'.$uacf7_number.' placeholder "https://example.com"]';
                break;
            case 'tel':
                $tag = '[tel'.esc_attr($uacf7_required).' tel-'.$uacf7_number.' placeholder "+1 (555) 123-4567"]';
                break;
            case 'number':
                $tag = '[number'.esc_attr($uacf7_required).' number-'.$uacf7_number.' min:0 max:100 step:1 placeholder "Enter a number"]';
                break;
            case 'number':
                $tag = '[date'.esc_attr($uacf7_required).' date-'.$uacf7_number.' min:2023-09-20 max:2024-09-20 step:1]';
                break;
            case 'number':
                $tag = '[textarea'.esc_attr($uacf7_required).' textarea-'.$uacf7_number.' placeholder "Your message here..."]';
                break;
            case 'menu':
                $tag = '[select'.esc_attr($uacf7_required).' menu-'.$uacf7_number.' "Option 1" "Option 2" "Option 3"]';
                break;
            case 'checkbox':
                $tag = '[checkbox'.esc_attr($uacf7_required).' checkbox-'.$uacf7_number.' "Option 1" "Option 2" "Option 3"]';
                break;
            case 'radio':
                $tag = '[radio radio-'.$uacf7_number.' default:1 "Option 1" "Option 2" "Option 3"]';
                break;
            case 'acceptance':
                $tag = '[acceptance acceptance-'.$uacf7_number.'] I agree to the terms and conditions. [/acceptance]';
                break;
            case 'quiz':
                $tag = '[quiz'.esc_attr($uacf7_required).' quiz-'.$uacf7_number.' "What is the capital of France?|Paris"]';
                break;
            case 'file':
                $tag = '[file'.esc_attr($uacf7_required).' file-'.$uacf7_number.' limit:2mb filetypes:jpg|jpeg|png|pdf]';
                break;
            case 'submit':
                $tag = '[submit "Send"]';
                break;
            case 'uacf7_city':
                $tag = '[uacf7_city'.esc_attr($uacf7_required).' uacf7_city-'.$uacf7_number.' placeholder:City]';
                break;
            case 'uacf7_state':
                $tag = '[uacf7_state'.esc_attr($uacf7_required).' uacf7_state-'.$uacf7_number.' placeholder:State]';
                break;
            case 'uacf7_zip':
                $tag = '[uacf7_zip'.esc_attr($uacf7_required).' uacf7_zip-'.$uacf7_number.' placeholder:Zip Code]';
                break;
            case 'uacf7_product_dropdown':
                $tag = '[uacf7_product_dropdown'.esc_attr($uacf7_required).' uacf7_product_dropdown-'.$uacf7_number.']';
                break;
            case 'uacf7_star_rating':
                $tag = '[uacf7_star_rating'.esc_attr($uacf7_required).' rating-'.$uacf7_number.' selected:5 star1:1 star2:2 star3:3 star4:4 star5:5 icon:star1 "default"]';
                break;
            case 'uacf7_range_slider':
                $tag = '[uacf7_range_slider'.esc_attr($uacf7_required).' uacf7_range_slider-'.$uacf7_number.' min:15 max:100 default:50 step:1 show_value:on handle:1 "default"]';
                break;
            case 'uacf7_country_dropdown':
                $tag = '[uacf7_country_dropdown'.esc_attr($uacf7_required).' uacf7_country_dropdown-'.$uacf7_number.']';
                break; 
            case 'uacf7_submission_id':
                $tag = '[uacf7_submission_id uacf7_submission_id-'.$uacf7_number.']';
                break; 
            default : 
                $tag = '['.$uacf7_default[1].' '.$uacf7_default[1].'-'.$uacf7_number.']';
                break; 
        } 

        if($uacf7_form_label == 'label'){ 
            $uacf7_field =  '<label> '.$uacf7_default[1].' '.PHP_EOL. $tag.' </label>' . PHP_EOL;
        }else{
            $uacf7_field = $tag.PHP_EOL;
        }

        if($uacf7_default[1] == 'uacf7-col' && isset($uacf7_default[2])  ){
            switch ($uacf7_default[2]) { 
                case "col-2":
                    $uacf7_field = '[uacf7-row] '.PHP_EOL.' [uacf7-col col:6] --your code-- [/uacf7-col]'.PHP_EOL.' [uacf7-col col:6] --your code-- [/uacf7-col]'.PHP_EOL.' [/uacf7-row]'.PHP_EOL;
                    break;
                case "col-3";
                    $uacf7_field = '[uacf7-row]'.PHP_EOL.' [uacf7-col col:4] --your code-- [/uacf7-col]'.PHP_EOL.' [uacf7-col col:4] --your code-- [/uacf7-col] '.PHP_EOL.'   [uacf7-col col:4] --your code-- [/uacf7-col]'.PHP_EOL.'[/uacf7-row]'.PHP_EOL;
                    break;
                case "col-4":
                    $uacf7_field = '[uacf7-row]'.PHP_EOL.' [uacf7-col col:3] --your code-- [/uacf7-col]'.PHP_EOL.' [uacf7-col col:3] --your code-- [/uacf7-col]'.PHP_EOL.'   [uacf7-col col:3] --your code-- [/uacf7-col]'.PHP_EOL.' [uacf7-col col:3] --your code-- [/uacf7-col] '.PHP_EOL.'[/uacf7-row]'.PHP_EOL;
                    break;
                default:
                    $uacf7_field = '[uacf7-row] '.PHP_EOL.'  [uacf7-col col:12] --your code-- [/uacf7-col] '.PHP_EOL.'  [/uacf7-row]'.PHP_EOL;
                    break;
            }
        }
    }
    echo wp_kses_post( $uacf7_field );
    


 return ob_get_clean();