<?php

class GOT_CHOSEN_API_HANDLER {
  private $api_url = 'http://devapi.gotchosen.com/api/';
  private $feedkey;
  private $notices;
  private function __construct() {
    add_action('admin_notices', array(&$this, 'admin_notices'));
    if (!$this->notices = get_transient('got_chosen_api_notices')) {
      $this->notices = array(); 
    }
  }
  public function get_instance() {
    static $instance = null;
    if ($instance === null) {
      $instance = new GOT_CHOSEN_API_HANDLER();
    }
    return $instance;
  }
  public function verifyminifeed($args = array()) {
    return $this->call_api('GET', 'verifyminifeed', $args);
  }
  public function minifeed($args = array()) {
    return $this->call_api('POST', 'minifeed', $args);
  }
  private function call_api($method, $endpoint, $args) {
    // Set common headers.
    $args['headers'] = array(
      'Content-Type' => 'application/json',
      'X-GotChosen-Feed-Key' => $this->feedkey,
    );
    $response = array();
    if ($method == 'GET') {
      $response = wp_remote_get($this->api_url . $endpoint, $args);
    }
    elseif ($method == 'POST') {
      $response = wp_remote_post($this->api_url . $endpoint, $args);
    }
    // Handle request errors.
    if (is_wp_error($response)) {
      $this->update_notices('A WordPress API error occured: ' . $response->get_error_message());
    }
    elseif ($response['response']['code'] == '403') {
      $this->update_notices('There was an error authenticating, please check your Feed Key.');
    }
    elseif ($response['response']['code'] != '200') {
      $this->update_notices('There was an error contacting the API server.');
    }
    elseif ($response['response']['code'] == '200') {
      return json_decode($response['body']);
    }
    // If request was not successful, return false.
    return false;
  }
  public function set_feedkey($feedkey) {
    $this->feedkey = $feedkey;
  }
  private function update_notices($message) {
    $this->notices[] = $message;
    set_transient('got_chosen_api_notices', $this->notices);
  }
  public function admin_notices() {
    if (!empty($this->notices)) {
      echo '<div class="error">';
      foreach ($this->notices as $notice) {
        echo '<p>' . $notice . '</p>';
      }
      echo '<div>';
    } 
  }
}
