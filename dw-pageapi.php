<?php

/*
  Plugin Name: dw-PageAPI
  Description: An API that allows you to query the WordPress page structure
  Author: Ryan Jarrett
  Version: 0.9
  Author URI: http://sparkdevelopment.co.uk

  Changelog
  ---------
  0.1   - initial release - children request class added
  0.2   - search request class added; api_request class added
  0.3   - added urlParams and totalResults to search API; handles '-' in query URL
  0.3.1 - corrected issue when api_request class instantiated directly in PHP
          (note that api_request now takes array as argument which mirrors API args)
  0.4   - added az_request and refactored search_request
  0.5   - added news_request
  0.5.1 - fix for news_request returning non-news items
          'news' is now also an allowed 'type' for az_request
  0.6   - added ability to filter by year/month/day on news_request
  0.6.1 - news_request date filter now handles day and month without leading zeroes
          reports error if date components are non-numeric
  0.7   - extended search_request so it can be called on its own
  0.7.1 - added file_name to returned json for search_request
  0.8   - extended children_request to return child_count and is_external
  0.9   - added crawl_request to provide url mapping for content crawler/importer
  0.9.1 - fixed issue which was preventing news appearing with Relevanssi enabled
  0.10  - children_request now returns top level items with is_top_level set to 1 if
          no parent id is given (or it is set to 0)
 */

  if (!defined('ABSPATH')) {
    exit; // disable direct access
  }

  if (!class_exists('PageAPI')) {

    class PageAPI {

        /**
         * @var string
         */
        public $version = '0.9';

        /**
         * Define PageAPI constants
         *
         * @since 1.0
         */
        private function define_constants() {

          define('PAGEAPI_VERSION', $this->version);
          define('PAGEAPI_BASE_URL', trailingslashit(plugins_url('pageapi')));
          define('PAGEAPI_PATH', plugin_dir_path(__FILE__));
          define('PAGEAPI_ROOT', 'service');
        }

        /**
         * All PageAPI classes
         *
         * @since 1.0
         */
        private function plugin_classes() {
          return array(
            'api_request' => PAGEAPI_PATH . 'classes/api_request.php',
            'search_request' => PAGEAPI_PATH . 'classes/search_request.php',
            'children_request' => PAGEAPI_PATH . 'classes/children_request.php',
            'az_request' => PAGEAPI_PATH . 'classes/az_request.php',
            'news_request' => PAGEAPI_PATH . 'classes/news_request.php',
            'crawl_request' => PAGEAPI_PATH . 'classes/crawl_request.php'
            );
        }

        public function __construct() {
          $this->define_constants();
          $this->includes();

            // Setup permalinks
          add_action('wp_loaded', array(&$this, 'flush_api_permalinks'));
          add_action('init', array(&$this, 'setup_api_rewrites'), 10);
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

          add_rewrite_rule(PAGEAPI_ROOT . $rewrite_pattern . '/?', $rewrite_string, 'top');
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

          if (!isset($rules['(' . PAGEAPI_ROOT . ')/(.+)$'])) {
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
                  // $results = array();
              $results->results_array = array (
                "status"    => 401,
                "message"   => "Endpoint not valid",
                "more_info" => "https://github.com/ministryofjustice/dw-pageapi/blob/master/README.md"
                );
            }
            $this->output_json($results);
            exit;
          }
        }

        /**
         * Load required classes
         *
         * @since 1.0
         */
        private function includes() {

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
          // print_r($json_array);§
          $status = $json_array->results_array['status'];
          if (!is_null($status)) {
            http_response_code($status);
          } elseif (empty($json_array->results_array['results'])) {
            $json_array->results_array['totalResults'] = 0;
            $json_array->results_array['results'] = array();
          }
          header('Content-Type: application/json');
          echo json_encode($json_array->results_array);
        }

      }

      new PageAPI;
    }
