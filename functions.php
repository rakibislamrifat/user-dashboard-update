<?php
ob_start();
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0' );

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles() {

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20 );


function create_userinformation_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'userinformation';
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        dob DATE NOT NULL,
        address TEXT NOT NULL,
        region VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
    ) $charset_collate;";
    dbDelta($sql);
}
add_action('after_switch_theme', 'create_userinformation_table');

function velvetreel_signup_form_shortcode() {
    ob_start();

    // Include the signup processing script
    include_once get_template_directory() . '/sign-up.php'; 

    return ob_get_clean();
}
add_shortcode('velvetreel_signup', 'velvetreel_signup_form_shortcode');

add_action('after_setup_theme', function() {
  if (!current_user_can('administrator') && !is_admin()) {
    show_admin_bar(false);
  }
});



add_action('wp_ajax_upload_profile_image', 'handle_profile_image_ajax');
function handle_profile_image_ajax() {
    // Kill any prior output (safely)
    if (ob_get_length()) ob_end_clean();

    header('Content-Type: application/json; charset=utf-8');

    // Authenticate
    if (!is_user_logged_in()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        wp_die();
    }

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'profile_image_nonce')) {
        echo json_encode(['success' => false, 'message' => 'Invalid nonce.']);
        wp_die();
    }

    // Required WordPress includes
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Check file exists
    if (empty($_FILES['profile_image'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
        wp_die();
    }

    $upload_id = media_handle_upload('profile_image', 0);

    if (is_wp_error($upload_id)) {
        echo json_encode(['success' => false, 'message' => $upload_id->get_error_message()]);
        wp_die();
    }

    $url = wp_get_attachment_url($upload_id);

    if (!$url) {
        echo json_encode(['success' => false, 'message' => 'Failed to get image URL.']);
        wp_die();
    }

    update_user_meta(get_current_user_id(), 'profile_picture', esc_url_raw($url));

    echo json_encode(['success' => true, 'url' => esc_url($url)]);
    wp_die();
}