<?php

/*
  Plugin Name: PageAPI
  Description: An API that allows you to query the WordPress page structure
  Author: Ryan Jarrett
  Version: 0.1
  Author URI: http://sparkdevelopment.co.uk
 */

if (!defined('ABSPATH')) {
    exit; // disable direct access
}

if (!class_exists('PageAPI')) {

    class PageAPI {

        /**
         * @var string
         */
        public $version = '0.1';

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
                'pageapi_children_request' => PAGEAPI_PATH . 'classes/children_request.php',
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

        public function setup_api_rewrites() {
            add_rewrite_rule(PAGEAPI_ROOT . '/(.+)/(.+)/*', 'index.php?api_action=$matches[1]&pageid=$matches[2]', 'top');
            add_rewrite_tag('%api_action%', '([^&]+)');
            add_rewrite_tag('%pageid%', '([^&]+)');
        }

        public function flush_api_permalinks() {
            global $wp_query;

            $rules = get_option('rewrite_rules');

            if (!isset($rules['(' . PAGEAPI_ROOT . ')/(.+)$'])) {
                global $wp_rewrite;
                $wp_rewrite->flush_rules();
            }
        }

        public function process_api_request() {
            global $wp_query;

            // Get custom URL parameters
            $api_action = get_query_var('api_action');
            $pageid = get_query_var('pageid');

            if ($api_action !== '' && $pageid !== '') {
                $request_class = $api_action . "_request";
                $results = new $request_class($pageid);
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
         */
        function output_json($json_array) {
            echo json_encode($json_array->results_array);
        }

    }

    new PageAPI;
}