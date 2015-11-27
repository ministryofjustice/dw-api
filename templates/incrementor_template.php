<?php

/**
 * Endpoint template for creating incrementors
 *
 * @author ryanajarrett
 * @since 1.5
 */

class incrementor extends api_request {

  public static $params = array('id');
  public static $namespace = 'dw_inc_';
  public static $suppress_results_summary = true;

  function __construct($param_array) {
    parent::__construct($param_array);

    $request_method = $_SERVER['REQUEST_METHOD'];

    if($this->endpoint==null) {
      $this->results_array = array(
        "status"    => 401,
        "message"   => "Incrementor endpoint not defined",
        "more_info" => "https://github.com/ministryofjustice/dw-api/blob/master/README.md"
      );
    }

    switch ($request_method) {
      case 'GET':
        $this::generate_json();
        break;
      case 'POST':
        $this::process_request();
        break;
    }

    return($this->results_array);
  }

  function generate_json() {
    $meta_key = self::$namespace . $this->endpoint;
    $count = get_post_meta( $this->data['id'], $meta_key, true )?:0;
    $this->results_array = array(
      "count" => (int) $count
    );
  }

  function process_request() {
    // Process POST data
    $meta_key = self::$namespace . $this->endpoint;
    $nonce = $_POST['nonce'];
    if(wp_verify_nonce( $nonce, $meta_key )) {
      $count = get_post_meta( $this->data['id'], $meta_key, true )?:0;
      $update_status = update_post_meta( $this->data['id'], $meta_key, $count+1, $count );
      $count = get_post_meta( $this->data['id'], $meta_key, true )?:0;
      $this->results_array = array(
        "count" => (int) $count,
        "nonce" => $nonce
      );
    } else {
      $this->results_array = array(
        "status"    => 401,
        "message"   => "Invalid nonce",
        "more_info" => "https://github.com/ministryofjustice/dw-api/blob/master/README.md"
      );
    }
  }

}

?>
