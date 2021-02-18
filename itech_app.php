<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://itech-softsolutions.com/
 * @since             1.0.0
 * @package           Itech_app
 *
 * @wordpress-plugin
 * Plugin Name:       Itech APP
 * Plugin URI:        https://itech-softsolutions.com/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Itech Soft Solutions
 * Author URI:        https://itech-softsolutions.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       itech_app
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'ITECH_APP_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-itech_app-activator.php
 */
function activate_itech_app() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-itech_app-activator.php';
	Itech_app_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-itech_app-deactivator.php
 */
function deactivate_itech_app() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-itech_app-deactivator.php';
	Itech_app_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_itech_app' );
register_deactivation_hook( __FILE__, 'deactivate_itech_app' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-itech_app.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_itech_app() {

	$plugin = new Itech_app();
	$plugin->run();

}
run_itech_app();


// Post
function itech_api_route_for_post( $route, $post ) {
	if ( $post->post_type === 'posts') {
		$route = '/wp/v2/posts/' . $post->ID;
	}
 
	return $route;
}


// Video
function itech_api_route_for_video_post( $route, $post ) {
	if ( $post->post_type === 'video' ) {
		$route = '/wp/v2/video/' . $post->ID;
	}
 
	return $route;
}


// Sticky Post
function itech_api_route_for_sticky_post( $route, $post ) {
	if ( $post->post_type === 'posts' && $post->sticky == 'true' ) {
		$route = '/wp/v2/posts?sticky=true' . $post->ID;
	}
 
	return $route;
}


// Post View Count
function it_get_post_view() {
    $count = get_post_meta( get_the_ID(), 'post_views_count', true );
    return "$count views";
}
function it_set_post_view() {
    $key = 'post_views_count';
    $post_id = get_the_ID();
    $count = (int) get_post_meta( $post_id, $key, true );
    $count++;
    update_post_meta( $post_id, $key, $count );
}
function it_posts_column_views( $columns ) {
    $columns['post_views'] = 'Views';
    return $columns;
}
function it_posts_custom_column_views( $column ) {
    if ( $column === 'post_views') {
        echo it_get_post_view();
    }
}
add_filter( 'manage_posts_columns', 'it_posts_column_views' );
add_action( 'manage_posts_custom_column', 'it_posts_custom_column_views' );

function itech_post_view_count( $content ) {
	if( is_single())  {
		it_set_post_view();
		 {?>
		 	&#128065;
		<?= it_get_post_view(); 
		}
	}
	return $content;
}
add_filter( 'the_content', 'itech_post_view_count' );

// Register
add_action('rest_api_init', 'wp_rest_user_endpoints');

function wp_rest_user_endpoints($request) {
  register_rest_route('wp/v2/users/register/', array(
    'methods' => 'POST',
    'callback' => 'wc_rest_user_endpoint_handler',
  ));
}
function wc_rest_user_endpoint_handler($request = null) {
  $response = array();
  $parameters = $request->get_json_params();
  $username = sanitize_text_field($parameters['username']);
  $email = sanitize_text_field($parameters['email']);
  $password = sanitize_text_field($parameters['password']);
  $role = sanitize_text_field($parameters['role']);
  $error = new WP_Error();
  if (empty($username)) {
    $error->add(400, __("Username field 'username' is required.", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  if (empty($email)) {
    $error->add(401, __("Email field 'email' is required.", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  if (empty($password)) {
    $error->add(402, __("Password field 'password' is required.", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  /*
   if (empty($role)) {
    $role = 'subscriber';
   } else {
       if ($GLOBALS['wp_roles']->is_role($role)) {
         Silence is gold
       } else {
      $error->add(405, __("Role field 'role' is not a valid. Check your User Roles from Dashboard.", 'wp_rest_user'), array('status' => 400));
      return $error;
       }
  }*/
  $user_id = username_exists($username);
  if (!$user_id && email_exists($email) == false) {
    $user_id = wp_create_user($username, $password, $email);
    if (!is_wp_error($user_id)) {
      // Ger User Meta Data (Sensitive, Password included. DO NOT pass to front end.)
      $user = get_user_by('id', $user_id);
      // $user->set_role($role);
      $user->set_role('subscriber');
      // WooCommerce specific code
      if (class_exists('WooCommerce')) {
        $user->set_role('customer');
      }
      // Ger User Data (Non-Sensitive, Pass to front end.)
      $response['code'] = 200;
      $response['message'] = __("User '" . $username . "' Registration was Successful", "wp-rest-user");
    } else {
      return $user_id;
    }
  } else {
    $error->add(406, __("Email already exists, please try 'Reset Password'", 'wp-rest-user'), array('status' => 400));
    return $error;
  }
  return new WP_REST_Response($response, 123);
}

add_action( 'rest_api_init', 'register_api_hooks' );

// Login

function register_api_hooks() {
  register_rest_route('wp/v2/users/login/',
    array(
      'methods'  => 'POST',
      'callback' => 'login',
    )
  );
}

function login($request){
    $creds = array();
    $creds['user_login'] = $request["username"];
    $creds['user_password'] =  $request["password"];
    $creds['remember'] = true;
    $user = wp_signon( $creds, false );

    if ( is_wp_error($user) )
      echo $user->get_error_message();

    return $user;
}

add_action( 'after_setup_theme', 'custom_login' );

// Post Meta

register_rest_field( 'post', 'metadata', array(
    'get_callback' => function ( $data ) {
        return get_post_meta( $data['id'], '', '' );
    }, ));

// Allow Comments
function itech_filter_rest_allow_anonymous_comments() {
    return true;
}
add_filter('rest_allow_anonymous_comments','itech_filter_rest_allow_anonymous_comments');