<?php
/**
 * Plugin Name: BlockMap Lite
 * Plugin URI: https://github.com/desenvolverempreender/blockmap
 * Description: Turn vector graphics fully interactive custom maps.
 * Version: 1.0
 * Author: Desenvolver Empreender
 * Author URI: https://www.desenvolverempreender.com/
 */

if (!class_exists('BlockMap')) :

class BlockMap {
	public static $version = '1.0';
	public $admin;

	public function __construct() {
		// Actions
		add_action('init', array($this, 'localize'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts_styles'));

		// Filters
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_link'));

		// Create shortcode
		add_shortcode('blockmap', array($this, 'shortcode'));

		// Admin
		if (is_admin()) {
			include('admin/admin.php');
			$this->admin = new BlockMapAdmin();
			register_activation_hook(__FILE__, array('BlockMapAdmin', 'activation')); // activation
		}
	}

	public function add_action_link($links) {
		$newlink = array('<a href="' . admin_url('edit.php?post_type=blockmap_map' ) . '">' . __('Map List', 'blockmap') . '</a>');
		return array_merge($links, $newlink);
	}

	public function enqueue_scripts_styles() {
		// Styles
		wp_register_style('blockmap-style', plugins_url('core/blockmap.css', __FILE__), array(), BlockMap::$version);

		// Scripts
		wp_register_script('mousewheel', plugins_url('js/jquery.mousewheel.js', __FILE__), false, null);
		wp_register_script('blockmap-script', plugins_url('core/blockmap.js', __FILE__), array('jquery', 'mousewheel'), BlockMap::$version);
		$blockmap_localization = array(
			'more' => __('More', 'blockmap'),
			'search' => __('Search', 'blockmap'),
			'notfound' => __('Nothing found. Please try a different search.', 'blockmap'),
			'iconfile' => plugins_url('core/images/icons.svg', __FILE__)
		);
		wp_localize_script('blockmap-script', 'blockmap_localization', $blockmap_localization);
	}

	public function localize() {
		load_plugin_textdomain('blockmap', false, dirname(plugin_basename(__FILE__)) . '/languages');
	}

	public function shortcode($atts) {
		extract(shortcode_atts(array(
			'id' => false,
			'h' => false,
			'class' => false, 
			'landmark' => false
		), $atts, 'blockmap'));

		$post = get_post($id);
		if (!$post || !$id) return __('Error: map with the specified ID doesn\'t exist!', 'blockmap');

		$data = $post->post_content;
		
		$output = '<div id="blockmap-id' . $id . '" data-mapdata="' . htmlentities($data, ENT_QUOTES, 'UTF-8') . '"';
		if ($class) $output .= ' class="' . $class . '"';
		if ($landmark) $output .= ' data-landmark="' . $landmark . '"';
		if ($h) $output .= ' data-height="' . $h . '"';
		$output .= '></div>';

		wp_enqueue_style('blockmap-style');
		wp_enqueue_script('blockmap-script');
		do_action('blockmap_enqueue');

		return $output;
	}
}

endif;

function blockmap() {
	global $blockmap;
	if (!isset($blockmap)) $blockmap = new BlockMap();
	return $blockmap;
}
blockmap();

?>