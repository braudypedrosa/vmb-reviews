<?php ob_start();

// register sub menu
if( !function_exists('_vmb_reviews_register_submenu_page') ){
  function _vmb_reviews_register_submenu_page(){

      add_submenu_page(
            'edit.php?post_type='.VMB_POST_TYPE,
            'Settings',
            'Settings',
            'manage_options',
            'settings',
            '_vmb_reviews_load_template'
      );
  }

  add_action( 'admin_menu', '_vmb_reviews_register_submenu_page' );
}

// submenu callback
if( !function_exists('_vmb_reviews_load_template') ){
  function _vmb_reviews_load_template(){

    $page = isset($_GET['page']) ? $_GET['page'] : "";

    include_once(VMB_REVIEWS_DIR.'/'.$page.'.php');

  }
}

// register post type
if( !function_exists('_vmbreviews_post_types') ){

  function _vmbreviews_post_types() {

    
    register_post_type(VMB_POST_TYPE,
    
      array('labels' => array(
          'name' => __('VMB Reviews', 'vmb_sites'), /* This is the Title of the Group */
          'singular_name' => __('Review', 'vmb_sites'), /* This is the individual type */
          'all_items' => __('All Reviews', 'vmb_sites'), /* the all items menu item */
          'add_new' => __('Add New Review', 'vmb_sites'), /* The add new menu item */
          'add_new_item' => __('Add New Review', 'vmb_sites'), /* Add New Display Title */
          'edit' => __( 'Edit', 'vmb_sites' ), /* Edit Dialog */
          'edit_item' => __('Edit', 'vmb_sites'), /* Edit Display Title */
          'new_item' => __('New Review', 'vmb_sites'), /* New Display Title */
          'view_item' => __('View', 'vmb_sites'), /* View Display Title */
          'search_items' => __('Search', 'vmb_sites'), /* Search Custom Type Title */
          'not_found' =>  __('Nothing found in the Database.', 'vmb_sites'), /* This displays if there are no entries yet */
          'not_found_in_trash' => __('Nothing found in Trash', 'vmb_sites'), /* This displays if there is nothing in the trash */
          'parent_item_colon' => ''
        ), /* end of arrays */
        'public' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'show_ui' => true,
        'query_var' => true,
        'menu_position' => 8, /* this is what order you want it to appear in on the left hand side menu */
        'menu_icon' => 'dashicons-format-quote', /* the icon for the custom post type menu. uses built-in dashicons (CSS class name) */
        'rewrite' => array( 'slug' => 'vmb_reviews', 'with_front' => false ), /* you can specify its url slug */
        'has_archive' => 'false', /* you can rename the slug here */
        'capability_type' => 'post',
        'hierarchical' => false,
        /* the next one is important, it tells what's enabled in the post editor */
        'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'revisions'),
        // This is where we add taxonomies to our CPT
            //'taxonomies'          => array( 'category' )

      ) /* end of options */

    ); /* end of register post type */

  }
  add_action( 'init', '_vmbreviews_post_types');
}


// get total number of reviews
if( !function_exists('_vmbreviews_total_count') ){
    function _vmbreviews_total_count(){
        
        $response = wp_remote_get(REQUEST_URL, [ 'timeout' => 45 ]);
        $responseBody = wp_remote_retrieve_body( $response );
        $results = json_decode( $responseBody );

        update_option('total_pages', $results->total_pages);

    }
}


if( !function_exists('_vmbreviews_get_all_reviews') ){

    function _vmbreviews_get_all_reviews($page = 1, $reviews = 0){
        
        $response = wp_remote_get(REQUEST_URL.'&page='.$page.'&resultsperpage='.(get_option('reviews_to_fetch') * 2), [ 'timeout' => 45 ]);
        $responseBody = wp_remote_retrieve_body( $response );

        $results = json_decode( $responseBody );

        update_option('total_pages', $results->total_pages);

        if($page <= $results->total_pages) {

          foreach($results->data as $result) {

            // skip if rating is above minimum rating to fetch or if comment is empty
            if((($result->survey_data->{92}->answer) <= get_option('rating_to_fetch')) || ($result->survey_data->{92}->comments) == '') {
  
              continue;
  
            } else {
              
              if($reviews < get_option('reviews_to_fetch')) {
  
                $data['id'] = $result->id;
                $data['firstName'] = $result->url_variables->firstname->value;
                $data['comment'] = $result->survey_data->{92}->comments;
                $data['rating'] = $result->survey_data->{92}->answer;
                $data['date_submitted'] = $result->date_submitted;
  
                _vmbreviews_add_new_review($data);
  
                $reviews++;
              }
            }
          }
        } else {
          // avoid infinite loop
          return;
        }


        if($reviews < get_option('reviews_to_fetch')) {      
          _vmbreviews_get_all_reviews($page+1, $reviews);
        } else {
          update_option('page', $page);
        }

    }
}

if(!function_exists('_vmbreviews_add_new_review')) {

  function _vmbreviews_add_new_review($data){

    global $wpdb;

    $sql = "SELECT post_id FROM ".$wpdb->prefix."postmeta WHERE meta_key = 'review_id' AND meta_value='".$data['id']."'";
            
    $result = $wpdb->get_results($sql,ARRAY_A);
    $post_id = $result[0]['post_id'];

    // check if review exists, otherwise add to reviews
    if(!$post_id) {

      $post_id = wp_insert_post(array(
          'post_title'=> $data['firstName'], 
          'post_type'=> VMB_POST_TYPE,
          'post_content'=> !empty($data['comment']) ? $data['comment'] : $data['firstName'],
          'post_status'=> 'publish'
      ));

      update_post_meta($post_id, 'review_id', $data['id']);
      update_post_meta($post_id, 'rating', $data['rating']);
      update_post_meta($post_id, 'date_submitted', $data['date_submitted']);
    }

  }
}

if(!function_exists('_save')) {
  function _save(){

    $last_page = get_option('page');
    $total_pages = get_option('total_pages');
    
    $current_year = date("Y");
    $current_month = date("m");;
    
    $past_three_month = (($current_month - 3) > 1) ? ($current_month -3) : 1;
    
    $api_base_request_URL = 'https://api.alchemer.com/v5/survey/1853973/surveyresponse?';
    
    // show only completed surveys
    $fixed_filter = 'filter[field][0]=status&filter[operator][0]==&filter[value][0]=Complete';
    // show only reviews from current year
    $date_filter = 'filter[field][1]=date_submitted&filter[operator][1]=>=&filter[value][1]='.$current_year.'-'.$past_three_month.'-01+00:00:00';

    $site_id = isset($_POST['site_id']) ? $_POST['site_id'] : get_option('site_id');
    $api_token = isset($_POST['api_token']) ? $_POST['api_token'] : get_option('api_token');
    $api_secret = isset($_POST['api_secret']) ? $_POST['api_secret'] : get_option('api_secret');

    $reviews_to_fetch = isset($_POST['reviews_to_fetch']) ? $_POST['reviews_to_fetch'] : get_option('reviews_to_fetch');
    $rating_to_fetch = isset($_POST['rating_to_fetch']) ? $_POST['rating_to_fetch'] : get_option('rating_to_fetch');
    $ref_ren_interval = isset($_POST['ref_ren_interval']) ? $_POST['ref_ren_interval'] : get_option('ref_ren_interval');

    $column_count = isset($_POST['column_count']) ? $_POST['column_count'] : get_option('column_count');
    $reviews_to_display = isset($_POST['reviews_to_display']) ? $_POST['reviews_to_display'] : get_option('reviews_to_display');

    define('REQUEST_URL', $api_base_request_URL.'api_token='.$api_token.'&api_token_secret='.$api_secret.'&'.$fixed_filter.'&'.$date_filter);

    update_option('site_id', $site_id);
    update_option('api_token', $api_token);
    update_option('api_secret', $api_secret);

    update_option('reviews_to_fetch', $reviews_to_fetch);
    update_option('rating_to_fetch', $rating_to_fetch);
    update_option('ref_ren_interval', $ref_ren_interval);

    update_option('column_count', $column_count);
    update_option('reviews_to_display', $reviews_to_display);
  }
}

if(!function_exists('_renew_reviews')) {
  function _renew_reviews(){

    $args = array(
      'numberposts' => -1,
      'post_type'   => VMB_POST_TYPE,
    );
    
    $reviews = get_posts( $args );


    foreach($reviews as $review) {
      $id = $review->ID;
      $published_data = get_post_meta($id, 'date_submitted', true);

      if(strtotime($published_data) < strtotime('-3 Months')) {
        wp_update_post(array(
          'ID' => $id,
          'post_status'=> 'draft'
        ));

        update_post_meta($id, 'status', 'expired_post');
      }
    }
  }
}


function _auto_refresh_reviews(){
    $interval = get_option('ref_ren_interval');
    $renewal_date = get_option('renewal_date') != '' ? date("Y-m-d H:i:s", strtotime('+'.$interval.' hours', strtotime(get_option('renewal_date')))) : get_option('renewal_date');
    $now = date("Y-m-d H:i:s");

    if(strtotime($renewal_date) < strtotime($now)) {
      _renew_reviews();
      _vmbreviews_get_all_reviews(get_option('page'), 0);
    }
}
add_action('admin_init', '_auto_refresh_reviews');

function enqueue_required_assets() { 
  wp_enqueue_style( 'font-awesome-vmbreviews', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css');
  wp_enqueue_style( 'vmbreviews-style', VMB_REVIEWS_URL. 'assets/style.css' );
}
add_action( 'wp_enqueue_scripts', 'enqueue_required_assets' );