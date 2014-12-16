<?php

/**
 * Processes API requests for the flexible A-Z/index page
 *
 * @author ryanajarrett
 * @since 0.2
 */
class search_request extends api_request {

	public static $params = array('type','category','keywords','page','per_page');

    // Default search order parameters
    protected $search_order     = 'ASC';
    protected $search_orderby   = 'title';
    protected $post_type        = null;

	function __construct($param_array = array()) {
        // Setup vars from url params
        $this->set_params($param_array);
        $this->data['type'] = $this->post_type===null ? $this->data['type'] : $this->post_type;
        // Check search type - if not page or doc, default to page
        $valid_post_types = array("page","doc","news"); // This should be added to as new post types are used
        if(!in_array($this->data['type'],$valid_post_types,true)) {
            if($this->data['type']==='all') {
                $this->data['type'] = $valid_post_types;
            } else {
                $this->data['type'] = "page";
            }
        }

        // If initial set, limit WP_Query args to matching post IDs
        if (strlen($this->data['initial'])===1) {
            global $wpdb;
            $postids=$wpdb->get_col($wpdb->prepare("
                SELECT      ID
                FROM        $wpdb->posts
                WHERE       SUBSTR($wpdb->posts.post_title,1,1) = %s
                ORDER BY    $wpdb->posts.post_title",$this->data['initial'])
            );
        } else {
            $postids = null;
        }

        // Set paging options
        $nopaging = true;
        if(is_numeric($this->data['page'])) {
            $paged = $this->data['page'];
            $nopaging = false;
        } else {
            $paged = null;
        }
        if(is_numeric($this->data['per_page'])) {
            $per_page =  $this->data['per_page'];
            $nopaging = false;
        } else {
            $per_page = 10;
        }

        // Set up WP_Query params
        $args = array(
            // Paging
            'nopaging'          =>  $nopaging,
            'paged'             =>  $paged,
            'posts_per_page'    =>  $per_page,
            // Sorting
            'order'             =>  $this->search_order,
            'orderby'           =>  $this->search_orderby,
            // Filters
            'post_type'         =>  $this->data['type'],
            'category_name'     =>  $this->data['category'],
            's'                 =>  $this->data['keywords'],
            // Restricts posts for first letter
            'post__in'          =>  $postids
        );

        // If date set, work out date range
        if (isset($this->data['date'])) {
            $query_date = $this->data['date'];
            // Get length of date string
            $date_length = strlen($query_date);
            $date_array = explode("-", $query_date);
            foreach($date_array as $date_component) {
                $api_error = !is_numeric($date_component) ?: false;
            }
            // Act depending on length of date
            switch($date_length) {
                case 4: // Year
                    $date_args = array(
                        'year'  =>  $date_array[0]
                    );
                    break;
                case 6:
                case 7: // Year/Month
                    $date_args = array(
                        'year'  =>  $date_array[0],
                        'monthnum' =>  $date_array[1]
                    );
                break;
                case 8:
                case 9:
                case 10: // Year/Month/Day
                    $date_args = array(
                        'year'  =>  $date_array[0],
                        'monthnum' =>  $date_array[1],
                        'day'   =>  $date_array[2]
                    );
                break;
                default: // Invalid dates
                    $api_error = true;

            }
            if (!$api_error) {
                $args = array_merge($args,$date_args);
            } else {
                $this->results_array = array(
                    "status"    => 401,
                    "message"   => "Invalid date",
                    "more_info" => "https://github.com/ministryofjustice/dw-pageapi/blob/master/README.md"
                );
            }
        }

        if (!$api_error) {
            // Get matching results
            $results = new WP_Query($args);

            $this::generate_json($results); 
        }

        return($this->results_array);
	}


    function generate_json($results = array()) {

        // Start JSON
        // URL parameters
        foreach ($this::$params as $param) {
            $this->results_array['urlParams'][$param] = $this->data[$param];
        }

        if($results->have_posts()) {

            // Total posts
            $this->results_array['totalResults'] = $results->found_posts;

            $last_post = false;
            while ($results->have_posts()) {
                $results->the_post();
                $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'thumbnail');

                $this->results_array['results'][] = array(
                    // Page Title
                    'title'             =>  get_the_title(),
                    // Page URL
                    'url'               =>  get_the_permalink(),
                    // Page Slug
                    'slug'              =>  $post->post_name,
                    // Page Excerpt
                    'excerpt'           =>  get_the_excerpt(),
                    // Featured Image
                    'thumbnail_url'     =>  $thumbnail[0],
                    // Timestamp
                    'timestamp'         =>  get_the_time('Y-m-d H:m:s'),
                    // File URL
                    'file_url'          =>  '',
                    // File size
                    'file_size'         =>  0,
                    // File pages
                    'file_pages'        =>  0
                );
            }
        }
        // Prevent protected variables being returned
        unset($this->search_order);
        unset($this->search_orderby);
        unset($this->post_type);
        unset($this->data);
    }

}
?>