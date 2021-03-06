<?php

/*
  Plugin Name: DW API
  Description: An API that allows you to query the WordPress page structure
  Author: Ryan Jarrett
  Version: 0.15
  Author URI: http://sparkdevelopment.co.uk

  Changelog
  ---------
  0.1    - initial release - children request class added
  0.2    - search request class added; api_request class added
  0.3    - added url_params and total_results to search API; handles '-' in query URL
  0.3.1  - corrected issue when api_request class instantiated directly in PHP
           (note that api_request now takes array as argument which mirrors API args)
  0.4    - added az_request and refactored search_request
  0.5    - added news_request
  0.5.1  - fix for news_request returning non-news items
           'news' is now also an allowed 'type' for az_request
  0.6    - added ability to filter by year/month/day on news_request
  0.6.1  - news_request date filter now handles day and month without leading zeroes
           reports error if date components are non-numeric
  0.7    - extended search_request so it can be called on its own
  0.7.1  - added file_name to returned json for search_request
  0.8    - extended children_request to return child_count and is_external
  0.9    - added crawl_request to provide url mapping for content crawler/importer
  0.9.1  - fixed issue which was preventing news appearing with Relevanssi enabled
  0.10   - children_request now returns top level items with is_top_level set to 1 if
           no parent id is given (or it is set to 0)
  0.10.1 - removed CORS header
  0.11   - rebranded to DW API & added cache control header
  0.12   - modify children_request to ignore text before colon in title
  0.13   - added event_request class to handle event search requests
  0.14   - added months_class to return count of posts by month (up to 12 months from current date)
  0.15   - added post_request class to return blog posts
  0.16   - added incrementor template and likes_request endpoint
 */

  if (!defined('ABSPATH')) {
    exit; // disable direct access
  }

  class api_error {

  }

  if (!class_exists('DWAPI')) {

    class DWAPI {

        /**
         * @var string
         */
        public $version = '0.15';

        /**
         * Define DW API constants
         *
         * @since 1.0
         */
        private function define_constants() {

          define('DWAPI_VERSION', $this->version);
          define('DWAPI_BASE_URL', trailingslashit(plugins_url('dwapi')));
          define('DWAPI_PATH', plugin_dir_path(__FILE__));
          define('DWAPI_ROOT', 'service');
        }

        /**
         * All DW API classes
         *
         * @since 1.0
         */
        private function plugin_classes() {
          $api_classes = array('search','children','az','news','crawl','events','months','likes','post');

          foreach ($api_classes as $api_class) {
            $class_definitions[$api_class.'_request'] = DWAPI_PATH . 'classes/'.$api_class.'_request.php';
          }
          return $class_definitions;
        }

        public function __construct() {
          $this->define_constants();
          $this->includes();

            // Setup permalinks
          add_action('init', array(&$this, 'setup_api_rewrites'), 10);
          add_action('wp_loaded', array(&$this, 'flush_api_permalinks'));
          add_action('wp', array(&$this, 'process_api_request'), 5);
        }

        /**
        * Set up rewrite rules in WordPress
        *
        * @since 1.0
        */
        public function setup_api_rewrites() {
          $total_params=0;
          foreach ($this->plugin_classes() as $id => $path) {
            if (class_exists($id)) {
              // $temp_class = new $id(true);
              if($total_params<count($id::$params)) {
                $total_params = count($id::$params);
              }
            }
          }
          $rewrite_string = 'index.php?api_action=$matches[1]';
          $rewrite_pattern = '/([^/]+)';
          for($i=1;$i<=$total_params;$i++) {
            $rewrite_string .= '&param' . ($i) . '=$matches[' . ($i+1) . ']';
            $rewrite_pattern .= '/?([^/]*)?';
            add_rewrite_tag('%param' . ($i) . '%', '([^&]+)');
          }

          add_rewrite_rule(DWAPI_ROOT . $rewrite_pattern . '/?', $rewrite_string, 'top');
          add_rewrite_tag('%api_action%', '([^&]+)');

          // global $wp_rewrite;var_dump($wp_rewrite);
        }

        /**
        * Reset permalinks in WordPress
        *
        * @since 1.0
        */
        public function flush_api_permalinks() {
          global $wp_query;

          $rules = get_option('rewrite_rules');

          if (!isset($rules['(' . DWAPI_ROOT . ')/(.+)$'])) {
            global $wp_rewrite;
            $wp_rewrite->flush_rules();
          }
        }

        /**
        * Parses endpoint and processes API request
        *
        * @since 1.0
        */
        public function process_api_request() {
          global $wp_query;

            // Get custom URL parameters
          $api_action = get_query_var('api_action');

          if ($api_action !== '') {
            $request_class = $api_action . "_request";
            if (class_exists($request_class)) {
              $results = new $request_class(array());
            } else {
              $results = new api_error(array());
              $results->results_array = array (
                "status"    => 401,
                "message"   => "Endpoint not valid",
                "more_info" => "https://github.com/ministryofjustice/dw-api/blob/master/README.md"
              );
            }
            $this->output_json($results);
            wp_reset_query();
            exit;
          }
        }

        /**
         * Load required classes
         *
         * @since 1.0
         */
        private function includes() {
          require_once DWAPI_PATH . 'classes/api_request.php';

          foreach (glob(DWAPI_PATH.'templates/*.php') as $filename) {
            require_once $filename;
          }

          foreach ($this->plugin_classes() as $id => $path) {
            if (is_readable($path) && !class_exists($id)) {
              require_once $path;
            }
          }

        }

        /**
         * Outputs JSON from results array
         *
         * @since 1.0
         */
        function output_json($json_array) {
          $suppress_results_summary = $json_array->results_array['controls']['suppress_results_summary'];
          $timeout = isset($json_array->results_array['controls']['timeout'])?$json_array->results_array['controls']['timeout']:30;
          unset($json_array->results_array['controls']);
          $status = $json_array->results_array['status'];
          if (!is_null($status)) {
            http_response_code($status);
          } elseif (empty($json_array->results_array['results']) && !$suppress_results_summary) {
            $json_array->results_array['total_results'] = 0;
            $json_array->results_array['results'] = array();
          }
          if(!$_GET['debug']) {
            $request_method = $_SERVER['REQUEST_METHOD'];
            switch ($request_method) {
              case 'GET':
                http_response_code(200);
                break;
              case 'POST':
                http_response_code(201);
                break;
            }
            header('Content-Type: application/json');

            if($timeout) {
              header('Cache-Control: public, max-age=' . $timeout);
              header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + ($cache_timeout?:60)));
              header_remove("Pragma");
            } else {
              header('Cache-Control: private, max-age=0, no-cache');
              header("Pragma: no-cache");
              header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() - 60));
            }
            echo json_encode($json_array->results_array);
          } else {
            Debug::full($json_array->results_array);
          }
        }

      }

      new DWAPI;
    }

function dw_posts_orderby($orderby) {
  global $dw_global_orderby;
  if ( $dw_global_orderby && $orderby ) {
	   $orderby = $dw_global_orderby;
	}
  return ( $orderby ) ? $orderby : '';
}

add_filter( 'posts_orderby', 'dw_posts_orderby' );
