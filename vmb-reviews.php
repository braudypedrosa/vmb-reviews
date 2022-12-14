<?php
/*
Plugin Name: VMB Reviews
Plugin URI: https://www.buildupbookings.com
Description: Extract reviews from Alchemer API for VMB sites
Author: Braudy Pedrosa
Version: 1.2
Author URI: https://www.buildupbookings.com
*/

// avoid direct access
if ( !function_exists('add_filter') ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

// set constants
if(!defined('VMB_REVIEWS_VERSION')){
	define('VMB_REVIEWS_VERSION', "1.0"); 
}

if(!defined('VMB_REVIEWS_DIR')){
	define('VMB_REVIEWS_DIR', plugin_dir_path( __FILE__ )); 
}

if(!defined('VMB_REVIEWS_URL')){
	define('VMB_REVIEWS_URL', plugin_dir_url( __FILE__ )); 
}

if(!defined('VMB_POST_TYPE')) {
    define('VMB_POST_TYPE', 'vmb_reviews');
}


include_once(VMB_REVIEWS_DIR.'functions.php');

// Get all listings shortcode
if( !function_exists('_get_all_reviews_func') ){
    
    function _get_all_reviews_func($atts){

        ob_start();
        $args = array(
            'post_type' => VMB_POST_TYPE,
            'meta_key' => 'date_submitted',
            'orderby' => 'meta_value',
            'posts_per_page' =>  get_option('reviews_to_display'),
        );

        $rating_avg = 0;
        $output = '';

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post(); 

                $firstName = get_the_title();
                $comment = get_the_content();
                $rating = intval(get_post_meta(get_the_ID(), 'rating', true));

                $rating_avg += $rating;

                $output .= '<div class="review-item">'.
                                '<h3 class="review-author">'.$firstName.'</h3>'.
                                '<div class="review-stars" data-rating="'.$rating.'">'.stars($rating).'</div>'.
                                '<p class="review-comment">"'.$comment.'"</p>'.
                            '</div>';
                
            }
        }

        $classes = '';

        switch(get_option('column_count')) {
            case 1: {
                $classes .= $classes .'colc-1';
                break;
            }

            case 2: {
                $classes .= $classes .'colc-2';
                break;
            }
            
            case 3: {
                $classes .= $classes .'colc-3';
                break;
            }

            default: {
                $classes .= $classes .'colc-4';
            }
        }
    
        wp_reset_postdata();
        
        return '<div class="vmb-reviews '.$classes.'" data-avgRating="'.$rating_avg.'"><div class="reviews-avg"><h3>'.get_option('reviews_to_display').' reviews in total</h3><div class="avgStars">'.$rating_avg / get_option('reviews_to_display').' out of 5<span>average rating</span></div></div><div class="reviews">'.$output.'</div></div>';
    
    }
    add_shortcode( 'display_reviews', '_get_all_reviews_func' );
}

function stars($rating){
    $output = '';

    for($i = 0; $i < 5; $i++){
        if($i < $rating){
            $output .= "<i class='fa-solid fa-star'></i>";
        } else {
            $output .= "<i class='fa-solid fa-star disabled'></i>";
        }
    }

    return $output;
}


// activation hook
register_activation_hook(__FILE__, function(){
    
    add_site_option('_site_id', '');
    add_site_option('_api_token', '');
    add_site_option('_api_secret', '');

    // plugin defaults
    update_option('renewal_date', date("Y-m-d H:i:s"));
    update_option('reviews_to_fetch', 30);
    update_option('ref_ren_interval', 24);
    update_option('rating_to_fetch', 3);
    update_option('reviews_to_display', 20);
    update_option('column_count', 3);
    update_option('total_pages', 1);
    update_option('page', 1);
});

// deactivation hook
register_deactivation_hook(__FILE__, function(){
    
});