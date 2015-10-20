<?php

/**
 * Processes API requests for item counts by month (up to 12 months from current date)
 *
 * @author ryanajarrett
 * @since 0.13
 */

class months_request extends search_request {

  public static $params = array('type');
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
  // If array, first arg is start date, second arg is months later


  function __construct($param_array) {
    $this->fallback_date = array(date('Y-m-01'),'11');

    parent::__construct($param_array);
  }

  function generate_json($results = array()) {

    // Start JSON
    // URL parameters
    foreach ($this::$params as $param) {
        $this->results_array['url_params'][$param] = $this->data[$param];
    }

    // Set default total_results
    $this->results_array['total_results'] = 0;

    $months_array = array();
    for ($x=0;$x<12;$x++) {
      $this->results_array['results'][date('Y-m-01',strtotime("+$x month"))]=0;
    }

		if($results->have_posts()) {
      // Total posts
      $this->results_array['total_results'] = (int) $results->found_posts;

      while ($results->have_posts()) {
      	$results->the_post();
        $start_date = get_post_meta($results->post->ID,'_event-start-date',true);
        $this->results_array['results'][date('Y-m-01',strtotime($start_date))]++;
      }
		}

		// Prevent protected variables being returned
		unset($this->search_order);
		unset($this->search_orderby);
		unset($this->post_type);
		unset($this->data);
	}

}
