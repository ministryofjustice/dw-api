<?php

/**
 * Processes API requests for (blog) posts
 *
 * @author ryanajarrett
 * @since 0.4
 */

class post_request extends search_request {

	public static $params = array('category','date','keywords','page','per_page');

	protected $search_order 	= 'DESC';
	protected $search_orderby	= 'date';
	protected $post_type      = 'post';

	function generate_json($results = array()) {

	  // Start JSON
	  // URL parameters
	  foreach ($this::$params as $param) {
	    $this->results_array['url_params'][$param] = $this->data[$param];
	  }

		if($results->have_posts()) {

	    // Total posts
	    $this->results_array['total_results'] = (int) $results->found_posts;

	    $last_post = false;
	    while ($results->have_posts()) {
	    	$results->the_post();
				$post_id = $results->post->ID;
	      $thumbnail_id = get_post_thumbnail_id($post_id);
	      $thumbnail = wp_get_attachment_image_src($thumbnail_id, 'thumbnail');
	      $alt_text = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);

				if (function_exists('dw_get_author_info')) {
					$authors = dw_get_author_info($post_id);
				} else {
					$authors = array();
				}

	     	$this->results_array['results'][] = array(
	        // Page Title
	        'title' 		         => (string) get_the_title(),
	        // Page URL
	        'url'   			       => (string) get_the_permalink(),
	        // Page Slug
	        'slug'  			       => (string) $post->post_name,
	        // Page Excerpt
	        'excerpt'   		     => (string) get_the_excerpt(),
	        // Featured Image
	        'thumbnail_url'      => (string) $thumbnail[0],
	        // Thumbnail Alt Text
	        'thumbnail_alt_text' => (string) $alt_text,
	        // Timestamp
	        'timestamp'			     =>	(string) get_the_time('Y-m-d H:m:s'),
					// Author(s)
					'authors'            => $authors
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
