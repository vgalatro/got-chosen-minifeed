<?php
/**
 * Plugin Name: Got Chosen Integration
 * Plugin URI: http://gotchosen.com
 * Description: Enables support for Got Chosen's web curtain and minifeed publishing.
 * Version: 1.0
 * License: GPL2
 */

require_once(plugin_dir_path(__FILE__) . 'includes' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'got-chosen-api.class.php');
require_once(plugin_dir_path(__FILE__) . 'includes' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'got-chosen-plugin.class.php');
 
$got_chosen_api_handler = GOT_CHOSEN_API_HANDLER::get_instance();
GOT_CHOSEN_INTG_PLUGIN::get_instance($got_chosen_api_handler, __FILE__);
unset($got_chosen_api_handler);
