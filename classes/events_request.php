<?php

/**
 * Processes API requests for events
 *
 * @author ryanajarrett
 * @since 0.12
 */

class events_request extends search_request {

  public static $params = array('date','keywords','page','per_page');
  protected $search_order = null;
  protected $search_orderby = array(
    'meta_fields' => true,
    'title' => 'ASC'
  );
  protected $meta_fields = array(
    '_event-start-date' => 'ASC',
    '_event-end-date'   => 'ASC',
  );
	protected $post_type  = 'event';
  protected $date_query_target = array('_event-start-date', '_event-end-date');
  protected $fallback_date = 'today';

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
        $start_date = get_post_meta( $results->post->ID, '_event-start-date', true );
        $end_date = get_post_meta( $results->post->ID, '_event-end-date', true );
       	$this->results_array['results'][] = array(
          // Event Title
          'title' 			=>  (string) get_the_title(),
          // Event URL
          'url'   			=>  (string) get_the_permalink(),
          // Event Slug
          'slug'  			=>  (string) $results->post->post_name,
          // Event Location
          'location'   	=>  (string) get_post_meta( $results->post->ID, '_event-location', true ),
          // Event Description
          'description' =>  (string) get_the_content(),
          // Event Start Date
          'start_date'  =>  (string) $start_date,
          // Event Start Time
          'start_time'  =>  (string) get_post_meta( $results->post->ID, '_event-start-time', true ),
          // Event End Date
          'end_date'    =>  (string) $end_date,
          // Event End Time
          'end_time'    =>  (string) get_post_meta( $results->post->ID, '_event-end-time', true ),
          // Event All Day Flag
          'allday'      =>  (string) get_post_meta( $results->post->ID, '_event-allday', true )=='allday'?'true':'false',
          // Event Same Day Flag
          'same-day'    =>  (string) $start_date===$end_date?'true':'false',
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
