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
    $this -> api = $api;
    $this -> includes_path = plugin_dir_path($plugin_file) . 'includes' . DIRECTORY_SEPARATOR;
    $this -> includes_url = plugins_url('includes', $plugin_file);
    $this -> options = get_option('got_chosen_intg_settings', array());
    $this -> api -> set_feedkey($this -> options['feedkey']);
    $this -> gcid = $this -> get_gcid();
    if (!$this -> pub_queue = get_transient('got_chosen_pub_queue')) {
      $this -> pub_queue = array();
    }
    add_action('wp_enqueue_scripts', array(&$this, 'enqueue_scripts'));
    add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
    add_action('save_post', array(&$this, 'save_post'));
    add_action('wp', array(&$this, 'schedule_pub'));
    add_action('admin_menu', array(&$this, 'admin_menu'));
    add_action('add_meta_boxes_post', array(&$this, 'add_meta_boxes'));
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
    } else {
      $response = $this -> api -> verifyminifeed();
      if ($response) {
        set_transient('got_chosen_intg_gcid', $response -> gcid, (24 * 60 * 60));
        return $response -> gcid;
      }
    }
    return false;
  }

  public function enqueue_scripts() {
    if ($this -> gcid && $this -> options['webcurtain']) {
      wp_register_script('gc_intg_webcurtain', $this -> includes_url . '/js/gc-webcurtain.js', array('jquery'));
      wp_localize_script('gc_intg_webcurtain', 'gc_intg_plugin', array('gcid' => $this -> gcid, 'compat' => $this -> options['webcurtain_compat'], ));
      wp_enqueue_script('gc_intg_webcurtain');
    }
  }

  public function admin_enqueue_scripts() {
    if (isset($_GET['page']) && $_GET['page'] == 'got_chosen') {
      wp_enqueue_style('gc_intg_admin_css', $this -> includes_url . '/css/admin.css');
    }
  }

  public function save_post($post_id) {
    // Don't do anything on the autosaving of posts.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }
    // Return if user is not allowed to edit posts.
    if (!current_user_can('edit_post', $post_id)) {
      return;
    }
    // Save our publish option.
    if (isset($_POST['gc_meta_wpnonce']) && wp_verify_nonce($_POST['gc_meta_wpnonce'], 'got chosen save meta')) {
      $publish = isset($_POST['gc_minifeed_publish']) ? 1 : 0;
      update_post_meta($post_id, 'gc_minifeed_publish', $publish);
    }
    // Check if post has been sent to the minifeed API.
    $minifeed_id = get_post_meta($post_id, 'gc_minifeed_id', true);
    if (empty($minifeed_id) && isset($publish) && $publish) {
      $post = get_post($post_id);
      if ($post -> post_type = 'post') {
        $args = array();
        $args['title'] = $post -> post_title;
        $args['body'] = wp_trim_words($post -> post_content, 150, '') . ' <a href="' . get_permalink($post_id) . '">' . $this -> options['read_more'] . '</a>';
        $args['shareable'] = (bool)$this -> options['shareable'];
        $args['commentable'] = (bool)$this -> options['commentable'];
        $this -> pub_queue[$post_id] = $args;
        $this -> process_pub_queue();
      }
    }
  }

  public function process_pub_queue() {
    if (!empty($this -> pub_queue)) {
      foreach ($this->pub_queue as $post_id => $args) {
        $response = $this -> api -> minifeed($args);
        if ($response) {
          update_post_meta($post_id, 'gc_minifeed_id', $response -> post_id);
          unset($this -> pub_queue[$post_id]);
        }
      }
      set_transient('got_chosen_pub_queue', $this -> pub_queue);
    }
  }

  public function admin_menu() {
    add_menu_page('Got Chosen Integration', 'Got Chosen', 'manage_options', 'got_chosen', array(&$this, 'build_menu'), $this -> includes_url . '/images/got_chosen_logo.png');
  }

  public function build_menu() {
    // Process submission.
    if ($_POST && isset($_POST['_wpnonce'])) {
      // Verify submission was made on the site.
      if (wp_verify_nonce($_POST['_wpnonce'], 'got chosen save options') !== false) {
        // Rebuild options array and update.
        $this -> options['feedkey'] = isset($_POST['feedkey']) ? $_POST['feedkey'] : $this -> options['feedkey'];
        $this -> options['webcurtain'] = isset($_POST['webcurtain']) ? 1 : 0;
        $this -> options['webcurtain_compat'] = isset($_POST['webcurtain_compat']) ? 1 : 0;
        $this -> options['pub_minifeed_default'] = isset($_POST['pub_minifeed_default']) ? 1 : 0;
        $this -> options['shareable'] = isset($_POST['shareable']) ? 1 : 0;
        $this -> options['commentable'] = isset($_POST['commentable']) ? 1 : 0;
        $this -> options['read_more'] = isset($_POST['read_more']) ? $_POST['read_more'] : $this -> options['read_more'];
        update_option('got_chosen_intg_settings', $this -> options);
      }
    }
    // Include admin template.
    require_once $this -> includes_path . 'templates' . DIRECTORY_SEPARATOR . 'admin.php';
  }

  public function add_meta_boxes() {
    add_meta_box('gc_intg_minifeed_pub', 'Publish to Got Chosen Minifeed', array(&$this, 'build_meta_box'), 'post', 'side');
  }

  public function build_meta_box($post) {
    wp_nonce_field('got chosen save meta', 'gc_meta_wpnonce');
    $publish = get_post_meta($post -> ID, 'gc_minifeed_publish', true);
    // If get_post_meta returns an empty string, the option was not found.
    if ($publish === '') {
      $publish = $this -> options['pub_minifeed_default'];
    }
    $checked = '';
    if ($publish) {
      $checked = 'checked="checked"';
    }
    echo '<label for="gc_minifeed_publish">Publish to Got Chosen minifeed: </label>';
    echo '<input type="checkbox" name="gc_minifeed_publish" id="gc_minifeed_publish" ' . $checked . '/>';
  }

}
