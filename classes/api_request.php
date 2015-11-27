<?php

/**
 * Processes API requests
 *
 * @author ryanajarrett
 * @since 0.2
 */
class api_request {
    public $results_array = array();
    public static $params;
    public static $suppress_results_summary = false;
    protected $data;

    function __construct($param_array) {
      // Setup vars from url params
      $this->set_params($param_array);
      $this->results_array['controls']['suppress_results_summary'] = self::$suppress_results_summary;
    }

    /**
     *
     * Sets variables based on $params and query_vars
     *
     */
    function set_params($param_array = array()) {
        $i = 1;
        foreach ($this::$params as $param) {
            if(!empty($param_array)) {
                $url_param = $param_array[$i-1];
            } else {
                $url_param = get_query_var('param' . $i);
            }
            $this->data[$param] = ($url_param && $url_param!=="-") ? $url_param : null;
            $i++;
        }
    }

    function generate_json() {
        $this->results_array = array(
            "status"    => 401,
            "message"   => "Endpoint not valid",
            "more_info" => "https://github.com/ministryofjustice/dw-api/blob/master/README.md"
        );
    }

}
