<?php

/**
 * Tracks likes for a certain post
 *
 * @author ryanajarrett
 * @since 1.5
 */

class likes_request extends incrementor {
  public $endpoint = 'likes';
  public static $timeout = 0;

  function __construct($param_array) {
    parent::__construct($param_array);

    $this->results_array['controls']['timeout'] = self::$timeout;
  }
}

?>
