<?php
/*
Plugin Name: Inverse Paradox Theme Framework
Description:
Version: 0.1
Author: Inverse Paradox
*/

class IP_Framework
{

	public function init() {

		IP_Framework_Roles::init();

		add_action('admin_menu', array($this,'add_to_menu'));
		add_action('admin_init', array($this,'register_all'));
		add_action('admin_enqueue_scripts', array($this,'enqueue_all'));
		add_action('init', array($this,'register_posts'));
		add_action('init', array($this,'register_taxonomies'));
		add_action('init', array($this,'register_menus'));

	}

	public function add_to_menu() {
		add_menu_page('IP Framework', 'IP Framework', 'manage_ipframework', 'ip_framework', array($this, 'route'), self::plugin_url() . 'assets/images/menu-icon.png', '61.1');
		//add_submenu_page('ip_framework', 'Manage Theme Options', 'Theme Options', 'manage_options', 'ip_framework_setup_theme_options', array($this, 'route'));
		add_submenu_page('ip_framework', 'Manage Nav Menus', 'Nav Menus', 'manage_ipframework', 'ip_framework_nav_menus', array($this, 'route'));
		add_submenu_page('ip_framework', 'Manage Custom Post Types', 'Custom Post Types', 'manage_ipframework', 'ip_framework_custom_post_types', array($this, 'route'));
		add_submenu_page('ip_framework', 'Manage Custom Taxonomies', 'Custom Taxonomies', 'manage_ipframework', 'ip_framework_custom_taxonomies', array($this, 'route'));

		//add_theme_page('Manage Theme Options', 'Theme Options', 'edit_themes', 'ip_framework_theme_options', array($this, 'route'));
	}

	public function register_styles() {
		wp_register_style('ip_framework', self::plugin_url() . 'assets/css/ip_framework.css');
	}
	public function register_scripts() {
		wp_register_script('ip_framework', self::plugin_url() . 'assets/js/ip_framework.js', array('jquery'));
	}
	public function register_all() {
		$this->register_styles();
		$this->register_scripts();
	}

	public function enqueue_styles() {
		wp_enqueue_style('ip_framework');
	}
	public function enqueue_scripts() {
		wp_enqueue_script('ip_framework');
	}
	public function enqueue_all($hook) {
		$hook_parts = explode('_page_', $hook);
		if (
			count($hook_parts) >= 2 &&
			($hook_parts[0] == 'ip-framework' ||
			($hook_parts[0] == 'toplevel' && $hook_parts[1] == 'ip_framework') ||
			$hook_parts[1] == 'ip_framework_theme_options')
		) {

			$this->enqueue_scripts();
			$this->enqueue_styles();
		}
	}

	public function register_posts() {
		$post_types = get_option('ip_framework_post_types');
		if (is_array($post_types)) {
			foreach ($post_types as $post_type) {
				register_post_type($post_type['post_type'], $post_type['args']);
			}
		}
	}

	public function register_taxonomies() {
		$taxonomies = get_option('ip_framework_taxonomies');
		if (is_array($taxonomies)) {
			foreach ($taxonomies as $taxonomy) {
				register_taxonomy($taxonomy['taxonomy'], $taxonomy['object_type'], $taxonomy['args']);
			}
		}
	}

	public function register_menus() {
		$menus = get_option('ip_framework_nav_menus');
		if (is_array($menus)) {
			register_nav_menus($menus);
		}
	}

	public function route() {

		$page = sanitize_key($_GET['page']);

		$page = str_replace(array('ip_framework'), '', $page);

		if (isset($_GET['action'])) {
			$action = sanitize_key($_GET['action']);
		} else if (isset($_POST['action'])) {
			$action = sanitize_key($_POST['action']);
		}
		if (!isset($action) || $action == '') {
			$action = 'default_action';
		}

		$controller_name = str_replace('_', ' ', $page);
		$controller_name = ucwords($controller_name);
		$controller_name = str_replace(' ', '_', $controller_name);
		$controller_name = 'IP_Framework_Controller' . $controller_name;

		$file_name = 'controller'.$page.'.php';

		try {
			if (file_exists(self::plugin_path() . '/controllers/' . $file_name)) {
				include self::plugin_path() . '/controllers/' . $file_name;
				if (class_exists($controller_name)) {
					$controller = new $controller_name;
					if (method_exists($controller, $action)) {
						$controller->$action();
					} else if ($action != 'default_action' && method_exists($controller, 'default_action')) {
						$controller->default_action();
					} else {
						throw new Exception('Cannot find method ' . $action . ' in ' . $controller_name);
					}
				} else {
					throw new Exception('Cannot find class ' . $controller_name);
				}
			} else {
				throw new Exception('Cannot find file ' . $file_name);
			}
		} catch (Exception $e) {
			echo $e->getMessage();
		}

	}

	public static function plugin_path() {
		return plugin_dir_path(__FILE__);
	}

	public static function plugin_url() {
		return plugin_dir_url(__FILE__);
	}

}

include 'controllers/controller_abstract.php';
include 'models/model_abstract.php';
include 'views/view_abstract.php';
include 'classes/roles.php';

$ip_framework = new IP_Framework();
$ip_framework->init();