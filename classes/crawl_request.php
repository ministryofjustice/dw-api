<?php

/**
 * Processes API requests for content crawler
 *
 * @author ryanajarrett
 * @since 0.9
 */

class crawl_request extends search_request {

  protected $search_order   = 'DESC';
  protected $search_orderby = 'date';
  protected $post_type      = 'page';

  function generate_json($results = array()) {

    // Start JSON
    // URL parameters
    // foreach ($this::$params as $param) {
    //     $this->results_array['urlParams'][$param] = $this->data[$param];
    // }

    if($results->have_posts()) {

            // Total posts
            // $this->results_array['totalResults'] = $results->found_posts;

            $last_post = false;
            while ($results->have_posts()) {
              $results->the_post();
              $thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'thumbnail');

              $post_id = get_the_id();

              $redirect_url = get_post_meta( $post_id, 'redirect_url', true );

              if($redirect_url) {
                $this->results_array['results'][] = array(
                      // Redirect URL
                      'redirect_url'  => $redirect_url,
                      // Page URL
                      'wp_url'        =>  get_the_permalink(),
                      // Post ID
                      'id'            => $post_id
                  );
              }
            }
    }
    // Prevent protected variables being returned
    unset($this->search_order);
    unset($this->search_orderby);
    unset($this->post_type);
    unset($this->data);
  }
}
