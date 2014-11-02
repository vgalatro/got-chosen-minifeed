<?php

class GOT_CHOSEN_INTG_PLUGIN {
  private $api;
  private $includes_path;
  private $includes_url;
  private $GCID;
  private $options;
  private function __construct($api, $plugin_file) {
    if ($api === null) {
      throw new Exception("Must pass in API instance on instantiation.");
    }
    $this->api = $api;
    $this->includes_path = plugin_dir_path($plugin_file) . 'includes' . DIRECTORY_SEPARATOR;
    $this->includes_url = plugins_url('includes', $plugin_file);
    $this->options = get_option('got_chosen_intg_settings', array('feedkey' => 'bc44017d75d444bcd44e454e06f6f2df226f6a2a'));
    $this->api->set_feedkey($this->options['feedkey']);
    $this->GCID = $this->get_gcid();
    add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
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
      echo '<pre>from cache: '.$gcid.'</pre>';
      return $gcid;
    }
    else {
      $gcid = $this->api->verifyminifeed();
      echo '<pre>from call: '.$gcid.'</pre>';
      set_transient('got_chosen_intg_gcid', $gcid, (24*60*60));
      return $gcid;
    }
  }
  public function enqueue_scripts() {
    wp_register_script('gc_webcurtain', $this->includes_url . '/js/gc-webcurtain.js', array('jquery'));
    wp_localize_script('gc_webcurtain', 'gc_intg_plugin', array('gcid' => $this->GCID));
    wp_enqueue_script('gc_webcurtain');
  }
}
