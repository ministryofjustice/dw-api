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
    protected $data;

    /**
     *
     * Sets variables based on $params and query_vars
     *
     */
    function set_params($param_array) {
        $i = 1;
        foreach ($this::$params as $param) {
            if(isset($param_array)) {
                $url_param = $param_array[$i-1];
            } else {
                $url_param = get_query_var('param' . $i);
            }
            $this->data[$param] = ($url_param && $url_param!=="-") ? $url_param : null;
            $i++;
        }
    }

}