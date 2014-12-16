<?php

/**
 * Processes API requests for the flexible A-Z/index page
 *
 * @author ryanajarrett
 * @since 0.4
 */

class az_request extends search_request {

    public static $params = array('type','category','keywords','initial','page','per_page');

	protected $search_order 	= 'ASC';
	protected $search_orderby	= 'title';

	function generate_json($results = array()) {


        // Start JSON
        // URL parameters
        // $this->results_array[]['url_params'] = array();
        foreach ($this::$params as $param) {
            $this->results_array['urlParams'][$param] = $this->data[$param];
        }
            
        if($results->have_posts()) {

            // Total posts
            $this->results_array['totalResults'] = $results->found_posts;

            // Loop through alphabet
            $result_letters = range('A', 'Z');
            global $post;

            // Get first letter
            $result_letter = current($result_letters);
            $letter_array = array();
            $letter_array['initial'] = $result_letter;
            $letter_array['results'] = array();
            // Get first post
            $results->the_post();
            $last_post = false;
            do {
                if($result_letter==strtoupper(substr(get_the_title(),0,1)) && !$last_post) {
                    $letter_array['results'][] = array(
                        // Page Title
                        'title' =>  get_the_title(),
                        // Page URL 
                        'url'   =>  get_the_permalink(),
                        // Page Slug
                        'slug'  =>  $post->post_name,
                        // Page Excerpt
                        'excerpt'   =>  get_the_excerpt()
                    );
                    if($results->current_post+1 != $results->post_count) {
                        $results->the_post();
                    } else {
                        $last_post = true;
                    }
                } else {
                    // Store current result
                    $this->results_array['data'][] = $letter_array;
                    // Get next letter
                    $result_letter = next($result_letters);
                    // Set up new result array
                    $letter_array = array(
                        'initial' => $result_letter,
                        'results' => array()
                    );
                }
            } while ($result_letter!=="Z" || ($results->current_post+1 != $results->post_count));
            // Store final result
            $this->results_array['data'][] = $letter_array;
        }
    }

}