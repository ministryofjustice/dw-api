<?php

/**
 * Processes API requests for the flexible A-Z/index page
 *
 * @author ryanajarrett
 * @since 0.2
 */
class search_request extends api_request {

	public static $params = array('type','category','keywords','initial','page','per_page');

    // Default search order parameters
    protected $search_order     = 'ASC';
    protected $search_orderby   = 'title';

	function __construct($param_array = array()) {
        // Setup vars from url params
        $this->set_params($param_array);
        // Check search type - if not page or doc, default to page
        if(!in_array($this->data['type'],array("page","doc"),true)) {
            $this->data['type'] = "page";
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

        // Get matching results
        $results = new WP_Query($args);

        $this::generate_json($results);

        return($this->results_array);
	}

    function generate_json() {
        $this->results_array = array(
            "status"    => 401,
            "message"   => "Endpoint not valid",
            "more_info" => "https://github.com/ministryofjustice/dw-pageapi/blob/master/README.md"
        );
    }

}
?>