<?php
/**
 * BlockMap Lite Admin
 * Version: 1.0
 */

if (!class_exists('BlockMapAdmin')) :

class BlockMapAdmin {

	public function __construct() {
		// Actions
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_styles'));
		add_action('init', array($this, 'create_post_type'));
		add_action('manage_blockmap_map_posts_custom_column' , array($this, 'column_shortcode'), 10, 2);
		add_action('edit_form_after_editor', array($this, 'backend_map'));
		add_action('add_meta_boxes_blockmap_map', array($this, 'metaboxes'));
		add_action('in_admin_footer', array($this, 'blockmap_logo'));
		add_action('admin_menu', array($this, 'add_upgrade_page'));

		// Filters
		add_filter('upload_mimes', array($this, 'mime_types'));
		add_filter('manage_edit-blockmap_map_columns', array($this, 'add_column_shortcode'));
		add_filter('wp_insert_post_data', array($this, 'save_map'), 99, 2);

		// Includes
		include('maps.php');
		include('metaboxes.php');
	}

	public function blockmap_logo() {
		if (get_post_type() == 'blockmap_map') {
			echo '<a class="blockmap-logo" href="https://blockmap.com/?utm_source=mlite" target="_blank"><img src="' . plugins_url('../images/logo.svg', __FILE__) . '"></a><br>';
		}
	}

	public function enqueue_scripts_styles() {
		if (get_post_type() == 'blockmap_map') {
			// Disable autosave
			wp_dequeue_script('autosave');

			// Media uploader
			wp_enqueue_media();

			// Admin style
			wp_register_style('blockmap-style', plugins_url('css/blockmap-admin.css', __FILE__), false, BlockMap::$version);
			wp_enqueue_style('blockmap-admin-style', plugin_dir_url(__FILE__) . 'css/admin-style.css', array('blockmap-style'), null);
			wp_enqueue_style('alpha-color-picker', plugin_dir_url(__FILE__) . 'css/alpha-color-picker.css', array('wp-color-picker'), null);

			// Admin scripts
			wp_register_script('mousewheel', plugins_url('../js/jquery.mousewheel.js', __FILE__), false, null);
			wp_enqueue_script('blockmap-admin', plugins_url('js/blockmap-admin.js', __FILE__), array('jquery', 'mousewheel'), null, true);
			wp_enqueue_script('alpha-color-picker', plugins_url('js/alpha-color-picker.js', __FILE__), array('wp-color-picker'));
			wp_enqueue_script('blockmap-admin-script', plugins_url('js/admin-script.js', __FILE__), array('jquery', 'alpha-color-picker'), null);
			$blockmap_localization = array(
				'add' => __('Add', 'blockmap'),
				'save' => __('Save', 'blockmap'),
				'search' => __('Search', 'blockmap'),
				'not_found' => __('Nothing found. Please try a different search.', 'blockmap'),
				'map' => __('Map', 'blockmap'),
				'raw' => __('Raw', 'blockmap'),
				'missing_id' => __('Landmark ID is required and must be unique!', 'blockmap'),
				'iconfile' => plugins_url('../core/images/icons.svg', __FILE__)
			);
			wp_localize_script('blockmap-admin-script', 'blockmap_localization', $blockmap_localization);
		}
	}

	public function mime_types($mimes) {
		$mimes['svg'] = 'image/svg+xml';
		$mimes['csv'] = 'text/csv';
		return $mimes;
	}

	public function create_post_type() {
		$labels = array(
			'name' => __('Maps Lite', 'blockmap'),
			'all_items' => __('All Maps', 'blockmap'),
			'singular_name' => __('Map', 'blockmap'),
			'add_new_item' => __('Add New Map', 'blockmap'),
			'new_item' => __('New Map', 'blockmap'),
			'edit_item' => __('Edit Map', 'blockmap')
		);

		register_post_type('blockmap_map',
			array(
				'labels' => $labels,
				'show_in_menu' => true,
				'show_ui' => true,
				'hierarchical' => false,
				'menu_position' => 25,
				'menu_icon' => 'dashicons-location-alt',
				'public' => false,
				'exclude_from_search' => true,
				'show_in_nav_menus' => false,
				'has_archive' => false,
				'rewrite' => array('slug' => 'map'),
				'supports' => array ('title')
			)
		);
	}

	public function add_upgrade_page() {
		add_submenu_page(
			'edit.php?post_type=blockmap_map',
			'Upgrade BlockMap',
			'<span style="color:#F18401;">Upgrade</span>',
			'manage_options',
			'upgrade_blockmap',
			array($this, 'upgrade_page')
		);
	}

	public function upgrade_page() {
		include 'upgrade.php';
	}

	public static function activation() {
		if (!get_option('blockmap-lite-version')) {
			// First Activation
			blockmap_add_example_maps();
			add_option('blockmap-lite-version', BlockMap::$version);
		}
	}

	// Column Shortcode
	public function column_shortcode($column, $post_id) {
		if ($column == 'shortcode') echo '[blockmap id="' . $post_id . '"]';
	}

	public function add_column_shortcode($columns) {
		$new_columns = array();
		foreach ($columns as $key => $title) {
			if ($key == 'date') $new_columns['shortcode'] = __('Shortcode', 'blockmap');
			$new_columns[$key] = $title;
		}
		return $new_columns;
	}

	// Map edit
	public function backend_map($post) {
		if ($post->post_type == 'blockmap_map') {
			$mapdata = htmlentities($post->post_content, ENT_QUOTES, 'UTF-8');
			echo '<div class="blockmap-rawedit"><label class="right"><input type="checkbox" id="blockmap-indent"></input>' . __('Indent', 'blockmap') . '</label>';
			echo '<textarea name="blockmap-mapdata" id="blockmap-mapdata" rows="20" spellcheck="false">' . $mapdata . '</textarea></div>';
			$screen = get_current_screen();
			if ($screen->action != 'add') {
				// tests 

				$json = json_decode($post->post_content, true);
				//print_r($json);
				/*
				foreach ($json['levels'] as &$level) {
					foreach ($level['locations'] as &$location) {
						echo $location['id'];
					}
				}*/

				//$res = wp_json_encode($json, JSON_UNESCAPED_SLASHES);
				//echo htmlentities($res, ENT_QUOTES, 'UTF-8');
				//echo $mapdata;

				echo '<div id="blockmap-admin-map" data-mapdata="' . $mapdata . '"></div>';
				submit_button();
				echo '<input type="button" id="blockmap-editmode" class="button" value="' . __('Raw', 'blockmap') .'">';
				echo '<a href="edit.php?post_type=blockmap_map&page=upgrade_blockmap" class="button" style="margin-left: 10px;">' . __('Upgrade', 'blockmap') .'</a>';
			}
			else blockmap_new_map_type();
		}
	}

	public function metaboxes($post) {
		$screen = get_current_screen();
		if ($screen->action != 'add') {
			add_meta_box('landmark', __('Location', 'blockmap'), 'blockmap_landmark_box', 'blockmap_map', 'side', 'core');
			add_meta_box('floors', __('Floors', 'blockmap'), 'blockmap_floors_box', 'blockmap_map', 'side', 'core');
			add_meta_box('style', __('Styles', 'blockmap'), 'blockmap_styles_box', 'blockmap_map', 'side', 'core');
			add_meta_box('categories', __('Groups', 'blockmap'), 'blockmap_categories_box', 'blockmap_map', 'side', 'core');
			add_meta_box('geoposition', __('Geoposition', 'blockmap'), 'blockmap_geoposition_box', 'blockmap_map', 'side', 'core');
			add_meta_box('settings', __('Settings', 'blockmap'), 'blockmap_settings_box', 'blockmap_map', 'normal', 'core');
			remove_meta_box('submitdiv', 'blockmap_map', 'side');
		}
	}

	public function save_map($data, $postarr) {
		if (!isset($postarr['ID']) || !$postarr['ID']) return $data;
		if (($data['post_type'] == 'blockmap_map') && ($data['post_status'] != 'trash')) {
			$type = $_POST['new-map-type'];

			$json = $_POST['blockmap-mapdata'];
			$json = str_replace(chr(194) . chr(160), ' ', $json);
			$json = stripslashes($json);
			$json = json_decode($json, true);
			foreach ($json['levels'] as &$level) {
				foreach ($level['locations'] as &$location) {
					$location['description'] = addcslashes(wp_kses_post($location['description']), '"\\');
				}
			}
			$cont = wp_json_encode($json);

			if (isset($type)) $data['post_content'] = blockmap_map_type(sanitize_text_field($type)); // New
			else if (isset($_POST['blockmap-mapdata'])) $data['post_content'] = $cont;
		}
		return $data;
	}
}

endif;
?>