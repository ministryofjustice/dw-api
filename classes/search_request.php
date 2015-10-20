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
  protected $search_order      = 'ASC';
  protected $search_orderby    = 'relevance';
  protected $post_type         = null;
	protected $date_query_target = 'date_query';

  function rawurldecode($string) {
    $string = str_replace('%252F', '%2F', $string);
    $string = str_replace('%255C', '%5C', $string);
    $string = rawurldecode($string);

    return $string;
  }

	function __construct($param_array = array()) {
		parent::__construct($param_array);

		global $dw_global_orderby;

    $this->data['type'] = $this->post_type===null ? $this->data['type'] : $this->post_type;
    // Check search type - if not page or doc, default to page
    $valid_post_types = array("page","doc","news","document","webchat","event"); // This should be added to as new post types are used
    if(!in_array($this->data['type'],$valid_post_types,true)) {
      if($this->data['type']==='all') {
        $this->data['type'] = $valid_post_types;
			} elseif ($this->data['type']==='content') {
				$this->data['type'] = $valid_post_types;
				$this->data['type'] = array_merge(array_diff($this->data['type'],array("doc","document")));
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
      $this->search_order = 'ASC';
      $this->search_orderby = 'title';
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

		// Build meta_query (and extend orderby)
		if(isset($this->meta_fields)) {
			$meta_query = array();
			foreach ($this->meta_fields as $meta_field=>$meta_sort) {
				if(!in_array($meta_field, $this->date_query_target)) {
					$meta_query[$meta_field] = array(
						'key'     => $meta_field,
						'compare' => 'EXISTS'
					);
				}
			}
			if ($this->search_orderby['meta_fields'] == true) {
				global $dw_global_orderby;
				$keys = array_keys($this->search_orderby);
				$index = array_search('meta_fields', $keys);
				$first_part = array_splice($this->search_orderby,0,$index);
				$second_part = array_splice($this->search_orderby,1);
				$new_orderby = array_merge($first_part,$this->meta_fields,$second_part);
				// Set global var to overide orderby
				end($new_orderby);
				$last = key($new_orderby);
				$mt_count=0;
				foreach ($new_orderby as $field=>$order) {
					if(!in_array($field, $keys)) {
						$mt_count++;
						$dw_global_orderby .= "mt".$mt_count.".meta_value" . " " . $order;
					} else {
						$dw_global_orderby .= "'".$field."'" . " " . $order;
					}
					$dw_global_orderby .= ($field === $last?"":", ");
				}

				$this->search_orderby = $new_orderby;
	    }
		}

		$query_date = $this->data['date']?:($this->fallback_date!='today'?$this->fallback_date:date('Y-m-d'));
		if(!is_array($query_date) && $this->fallback_date!='today') {
			$date_args = $this::parse_date($query_date);
			$date_query = $date_args;
		}

		$meta_query[] = $this->create_date_query($query_date);

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
			's'                 =>  $this->rawurldecode($this->data['keywords']),
			// Restricts posts for first letter
			'post__in'          =>  $postids,
			'meta_query'        =>  $meta_query,
			'date_query'        =>  $date_query
		);

    if (!$api_error) {
      // Get matching results
      $results = new WP_Query($args);
			if($_GET['debug']) {
				Debug::full($results->query);
				Debug::full($results->request);
			}
      if(function_exists(relevanssi_do_query) && $this->data['keywords']!=null) {
        relevanssi_do_query($results);
      }
      $this::generate_json($results);
    }

		$dw_global_orderby='';

    return($this->results_array);
	}

	function create_date_query($query_date) {
		// If date set, work out date range
		if (isset($this->data['date']) || isset($this->fallback_date)) {
			if($this->date_query_target!='date_query') {
				$meta_query_or['relation'] = 'OR';
				$meta_query_and['relation'] = 'AND';
				$compare = $this->data['date']?'LIKE':'>=';
				if(is_array($query_date)) {
					$compare = 'BETWEEN';
					if($query_date[0] == 'today') {
						$compare_value[] = date('Y-m-d');
						$compare_value[] = date('Y-m-t',strtotime("+".$query_date[1]." month"));
					} else {
						$compare_value[] = $query_date[0];
						$compare_value[] = date('Y-m-t',strtotime("+".$query_date[1]." month"));
					}
				}	else {
					$compare_value = $query_date!='today'?$query_date:date('Y-m-d');
				}
				foreach ($this->date_query_target as $meta_field) {
					$meta_query_or[] = array(
						'key'     => $meta_field,
						'value'   => $compare_value,
						'type'    => 'date',
						'compare' => $compare
					);
					$meta_query_and[] = array(
						'key'     => $meta_field
					);
				}
			}
			return array($meta_query_or,$meta_query_and);
		}
	}

	function parse_date($query_date) {
		// Get length of date string
		$date_length = strlen($query_date);
		$date_array = explode("-", $query_date);
		foreach($date_array as $date_component) {
			if (!is_numeric($date_component)) {
				return false;
			}
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
				$date_args = false;

		}
		return $date_args;
	}

  function generate_json($results = array()) {
		global $post;

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
				$thumbnail = wp_get_attachment_image_src($post_thumbnail_id, 'thumbnail');

				$titles = array();
				$page_ancestors = get_post_ancestors($post->ID);
				$num_ancestors = sizeof($page_ancestors);

				if($num_ancestors) {
					if($num_ancestors>1) {
						$titles[] = get_post_meta($page_ancestors[$num_ancestors-1], 'nav_label',true)?:get_the_title($page_ancestors[$num_ancestors-1]);
					}
					$titles[] = get_post_meta($page_ancestors[$num_ancestors], 'nav_label',true)?:get_the_title($page_ancestors[$num_ancestors]);
				} else {
					$titles[] = ucfirst($post->post_type);
				}

        $this->results_array['results'][] = array(
          // Page Title
          'title'             =>  (string) $post->post_title,
          // Page URL
          'url'               =>  (string) get_the_permalink($post->ID),
          // Page Slug
          'slug'              =>  (string) $post->post_name,
          // Page Excerpt
          'excerpt'           =>  (string) $post->post_excerpt,
          // Featured Image
          'thumbnail_url'     =>  (string) $thumbnail[0],
          // Timestamp
          'timestamp'         =>  (string) get_the_time('Y-m-d H:m:s'),
          // File URL
          'file_url'          =>  (string) '',
          // File name
          'file_name'         =>  (string) '',
          // File size
          'file_size'         =>  (int) 0,
          // File pages
          'file_pages'        =>  (int) 0,
					// Result category
					'content_type'      =>  $titles
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
