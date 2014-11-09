<?php

class GOT_CHOSEN_INTG_PLUGIN {
  private $api;
  private $includes_path;
  private $includes_url;
  private $gcid;
  private $options;
  private $pub_queue;
  private function __construct($api, $plugin_file) {
    if ($api === null) {
      throw new Exception("Must pass in API instance on instantiation.");
    }
    $this->api = $api;
    $this->includes_path = plugin_dir_path($plugin_file) . 'includes' . DIRECTORY_SEPARATOR;
    $this->includes_url = plugins_url('includes', $plugin_file);
    $this->options = get_option('got_chosen_intg_settings', array('feedkey' => 'bc44017d75d444bcd44e454e06f6f2df226f6a2a', 'shareable' => true, 'commentable' => true, 'read_more' => 'Read full article...'));
    $this->api->set_feedkey($this->options['feedkey']);
    $this->gcid = $this->get_gcid();
    if(!$this->pub_queue = get_transient('got_chosen_pub_queue')){
      $this->pub_queue = array();
    }
    add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
    add_action('save_post', array(&$this, 'save_post'));
    add_action('wp', array(&$this, 'schedule_pub'));
    add_action('pub_queue_hourly', array(&$this, 'process_pub_queue'));
  }
  public function get_instance($api = null, $plugin_file = null) {
    static $instance = null;
    if ($instance === null) {
      $instance = new GOT_CHOSEN_INTG_PLUGIN($api, $plugin_file);
    }
    return $instance;
  }
  private function get_gcid() {
    if ($gcid = get_transient('got_chosen_intg_gcid')) {
      return $gcid;
    }
    else {
      $response = $this->api->verifyminifeed();
      if ($response) {
        set_transient('got_chosen_intg_gcid', $response->gcid, (24*60*60));
        return $response->gcid;
      }
    }
    return false;
  }
  public function enqueue_scripts() {
    if ($this->gcid) {
      wp_register_script('gc_webcurtain', $this->includes_url . '/js/gc-webcurtain.js', array('jquery'));
      wp_localize_script('gc_webcurtain', 'gc_intg_plugin', array(
        'gcid' => $this->gcid,
        'compat' => false,
      ));
      wp_enqueue_script('gc_webcurtain');
    }
  }
  public function save_post($post_id) {
    // Check if post has been sent to the minifeed API.
    $minifeed_id = get_post_meta('gc_minifeed_id', $post_id, true);
    if (empty($minifeed_id)) {
      $post = get_post($post_id);
      $args = array();
      $args['title'] = $post->post_title;
      $args['body'] = wp_trim_words($post->post_content, 150, '') . ' <a href="' . get_permalink($post_id) . '">' . $this->options['read_more'] . '</a>';
      $args['shareable'] = $this->options['shareable'];
      $args['commentable'] = $this->options['commentable'];
      $this->pub_queue[$post_id] = $args;
      $this->process_pub_queue();
    }
  }
  public function process_pub_queue() {
    if (!empty($this->pub_queue)) {
      foreach ($this->pub_queue as $post_id => $args) {
        $response = $this->api->minifeed($args);
        if ($response) {
          update_post_meta('gc_minifeed_id', $post_id, $response->post_id);
          unset($this->pub_queue[$post_id]);
        }
      }
      set_transient('got_chosen_pub_queue', $this->pub_queue);
    }
  }
  public function schedule_pub() {
    if ( ! wp_next_scheduled( 'pub_queue_hourly' ) ) {
      wp_schedule_event( time(), 'hourly', 'pub_queue_hourly');
    }
  }
}
