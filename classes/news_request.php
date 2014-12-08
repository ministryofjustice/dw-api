<?php

/**
 * Processes API requests for news results
 *
 * @author ryanajarrett
 * @since 0.4
 */

class news_request extends search_request {

	public static $params = array('category','keywords','page','per_page');

	protected $search_order 	= 'DESC';
	protected $search_orderby	= 'date';
	protected $post_type        = 'news';

	function generate_json($results = array()) {

		if($results->have_posts()) {

			// Start JSON
            // URL parameters
            // $this->results_array[]['url_params'] = array();
            foreach ($this::$params as $param) {
                $this->results_array['urlParams'][$param] = $this->data[$param];
            }

            // Total posts
            $this->results_array['totalResults'] = $results->found_posts;

            $last_post = false;
            while ($results->have_posts()) {
            	$results->the_post();
             	$this->results_array['results'][] = array(
                    // Page Title
                    'title' 			=>  get_the_title(),
                    // Page URL
                    'url'   			=>  get_the_permalink(),
                    // Page Slug
                    'slug'  			=>  $post->post_name,
                    // Page Excerpt
                    'excerpt'   		=>  get_the_excerpt(),
                    // Featured Image
                    'thumbnail_url' => wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'thumbnail')[0],
                    // Timestamp
                    'timestamp'			=>	get_the_time('Y-m-d H:m:s'),
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
