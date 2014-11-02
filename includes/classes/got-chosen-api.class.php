<?php

class GOT_CHOSEN_API_HANDLER {
  private $api_url = 'http://devapi.gotchosen.com/api/';
  private $feedkey;
  private function __construct() {
    
  }
  public function get_instance() {
    static $instance = null;
    if ($instance === null) {
      $instance = new GOT_CHOSEN_API_HANDLER();
    }
    return $instance;
  }
  public function verifyminifeed() {
    $args = array();
    $response = $this->call_api('GET', 'verifyminifeed', $args);
    $data = json_decode($response['body']);
    return $data->gcid;
  }
  public function minifeed() {
    
  }
  private function call_api($method = 'GET', $endpoint, $args) {
    // Set common headers.
    $args['headers'] = array(
      'Content-Type' => 'application/json',
      'X-GotChosen-Feed-Key' => $this->feedkey,
    );
    if ($method == 'GET') {
      return wp_remote_get($this->api_url . $endpoint, $args);
    }
    elseif ($methos == 'POST') {
      return wp_remote_post($this->api_url . $endpoint, $args);
    }
  }
  public function set_feedkey($feedkey) {
    $this->feedkey = $feedkey;
  }
}