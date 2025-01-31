<?php
/**
 * Class for the Customizer Import/Export and Reset.
 * This is based on the Beaver Builders Import Export plugin.
 *
 * Used in the Customizer importer.
 *
 * @since 1.0.4
 * @package Noor Starter Templates
 */

namespace Noor_Starter_Templates;

use WP_Customize_Control;
use WP_Filesystem;
use stdClass;
use function add_action;
use function add_filter;
use function wp_enqueue_style;
use function get_template_directory;
use function wp_style_add_data;
use function get_theme_file_uri;
use function get_theme_file_path;
use function wp_styles;
use function esc_attr;
use function esc_url;
use function wp_style_is;
use function _doing_it_wrong;
use function wp_print_styles;
use function get_option;
use function wp_get_attachment_thumb_url;
use function apply_filters;
use function wp_get_attachment_url;
use function wp_get_attachment_metadata;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for Customizer Import Export
 *
 * @category class
 */
class Customizer_Import_Export {

	/**
	 * An array of core options that shouldn't be imported.
	 *
	 * @access private
	 * @var array $core_options
	 */
	private static $core_options = array(
		'blogname',
		'blogdescription',
		'show_on_front',
		'page_on_front',
		'page_for_posts',
	);

	/**
	 * @var null
	 */
	private static $instance = null;
	/**
	 * Instance Control
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	/**
	 * Class constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'customize_register', array( $this, 'import_export_requests' ), 999999 );
		add_action( 'customize_register', array( $this, 'register_controls' ) );
		add_action( 'customize_register', array( $this, 'import_export_setup' ) );
		add_action( 'customize_controls_print_scripts', array( $this, 'controls_print_scripts' ) );
		add_filter( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_customizer_scripts' ) );
		// Ajax handler for reset.
		add_action( 'wp_ajax_noor_starter_reset', array( $this, 'ajax_reset' ) );
	}
	/**
	 * Enqueue Customizer scripts
	 *
	 * @access public
	 * @return void
	 */
	public function enqueue_customizer_scripts() {
		wp_enqueue_style( 'noor-starter-import-export', NOOR_STARTER_TEMPLATES_URL . 'assets/css/starter-import-export.css', array( 'wp-components' ), NOOR_STARTER_TEMPLATES_VERSION );
		wp_enqueue_script( 'noor-starter-import-export', NOOR_STARTER_TEMPLATES_URL . 'assets/export/starter-import-export.min.js', array( 'jquery' ), NOOR_STARTER_TEMPLATES_VERSION, true );
		wp_localize_script(
			'noor-starter-import-export',
			'noorStarterImport',
			array(
				'resetConfirm'  => __( "Attention! This will remove all customizations to this theme!\n\nThis action is irreversible!", 'noor-starter-templates' ),
				'emptyImport'   => __( 'Please choose a file to import.', 'noor-starter-templates' ),
				'customizerURL' => admin_url( 'customize.php' ),
				'nonce'         => array(
					'reset'  => wp_create_nonce( 'noor-starter-reseting' ),
					'export' => wp_create_nonce( 'noor-starter-exporting' ),
				),
			)
		);
	}
	/**
	 * Reset to default values via Ajax request
	 *
	 * @access public
	 * @return void
	 */
	public function ajax_reset() {
		// Check request.
		if ( ! check_ajax_referer( 'noor-starter-reseting', 'nonce', false ) ) {
			wp_send_json_error( 'invalid_nonce' );
		}

		// Check if user is allowed to reset values.
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_send_json_error( 'invalid_permissions' );
		}

		// Reset to default values.
		delete_option( 'theme_mods_' . get_option( 'stylesheet' ) );
		delete_option( 'dima_global_palette' );
		wp_send_json_success();
	}
	/**
	 * Add Control.
	 *
	 * @access public
	 * @param object $wp_customize the customizer object.
	 * @return void
	 */
	public function register_controls( $wp_customize ) {
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-import-export-control.php'; // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound
	}
	/**
	 * Add Customizer Setup
	 *
	 * @access public
	 * @param object $wp_customize the object.
	 * @return void
	 */
	public static function import_export_setup( $wp_customize ) {
		$section_config = array(
			'title'    => __( 'Import/Export', 'noor-starter-templates' ),
			'priority' => 999,
		);
		$wp_customize->add_section( 'noor_starter_import_export', $section_config );
		$control_config = array(
			'settings' => array(),
			'priority' => 2,
			'section'  => 'noor_starter_import_export',
			'label'    => esc_html__( 'Import/Export', 'noor-starter-templates' ),
		);
		$wp_customize->add_control( new Noor_Starter_Control_Import_Export( $wp_customize, 'noor_starter_import_export', $control_config ) );

	}
	/**
	 * Check to see if we need to do an export or import.
	 *
	 * @param object $wp_customize An instance of WP_Customize_Manager.
	 * @return void
	 */
	public static function import_export_requests( $wp_customize ) {
		// Check if user is allowed to change values.
		if ( current_user_can( 'edit_theme_options' ) ) {
			if ( isset( $_REQUEST['noor-starter-export'] ) ) {
				self::export_data( $wp_customize );
			}
			if ( isset( $_REQUEST['noor-starter-import'] ) && isset( $_FILES['noor-starter-import-file'] ) ) {
				self::import_data( $wp_customize );
			}
		}
	}

	/**
	 * Export Theme settings.
	 *
	 * @access private
	 * @param object $wp_customize An instance of WP_Customize_Manager.
	 * @return void
	 */
	private static function export_data( $wp_customize ) {
		if ( ! wp_verify_nonce( $_REQUEST['noor-starter-export'], 'noor-starter-exporting' ) ) {
			return;
		}
		$template = 'noor';
		$charset  = get_option( 'blog_charset' );
		$mods     = get_theme_mods();
		$data     = array(
			'template' => $template,
			'mods'     => $mods ? $mods : array(),
			'options'  => array(),
		);

		// Get options from the Customizer API.
		$settings = $wp_customize->settings();
		foreach ( $settings as $key => $setting ) {

			if ( 'option' == $setting->type ) {

				// Don't save widget data.
				if ( 'widget_' === substr( strtolower( $key ), 0, 7 ) ) {
					continue;
				}

				// Don't save sidebar data.
				if ( 'sidebars_' === substr( strtolower( $key ), 0, 9 ) ) {
					continue;
				}

				// Don't save core options.
				if ( in_array( $key, self::$core_options ) ) {
					continue;
				}

				$data['options'][ $key ] = $setting->value();
			}
		}
		if ( function_exists( 'wp_get_custom_css_post' ) ) {
			$data['wp_css'] = wp_get_custom_css();
		}

		// Set the download headers.
		header( 'Content-disposition: attachment; filename=noor-theme-export.dat' );
		header( 'Content-Type: application/octet-stream; charset=' . $charset );

		// Serialize the export data.
		echo serialize( $data );

		// Start the download.
		die();
	}
	/**
	 * Imports uploaded noor woo email settings
	 *
	 * @access private
	 * @param object $wp_customize An instance of WP_Customize_Manager.
	 * @return void
	 */
	private static function import_data( $wp_customize ) {
		// Make sure we have a valid nonce.
		if ( ! wp_verify_nonce( $_REQUEST['noor-starter-import'], 'noor-starter-importing' ) ) {
			return;
		}
		// Make sure WordPress upload support is loaded.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		// Load the export/import option class.
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-import-customizer-option.php'; // phpcs:ignore WPThemeReview.CoreFunctionality.FileInclude.FileIncludeFound

		// Setup global vars.
		global $wp_customize;
		global $noor_starter_import_error;
		global $wp_filesystem;

		// Setup internal vars.
		$noor_starter_import_error = false;
		$template                  = 'noor';
		$overrides                 = array(
			'test_form' => false,
			'test_type' => false,
			'mimes'     => array( 'dat' => 'text/plain' ),
		);
		$file                      = wp_handle_upload( $_FILES['noor-starter-import-file'], $overrides );

		// Make sure we have an uploaded file.
		if ( isset( $file['error'] ) ) {
			$noor_starter_import_error = $file['error'];
			return;
		}
		if ( ! file_exists( $file['file'] ) ) {
			$noor_starter_import_error = __( 'Error importing settings! Please try again.', 'noor-starter-templates' );
			return;
		}
		if ( ! is_object( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		// Get the upload data.
		$data = '';
		if ( $wp_filesystem->exists( $file['file'] ) ) {
			$raw  = $wp_filesystem->get_contents( $file['file'] );
			$data = @unserialize( $raw );
		}

		// Remove the uploaded file.
		unlink( $file['file'] );

		// Data checks.
		if ( 'array' != gettype( $data ) ) {
			$noor_starter_import_error = __( 'Error importing settings! Please check that you uploaded a customizer export file.', 'noor-starter-templates' );
			return;
		}
		if ( ! isset( $data['template'] ) ) {
			$noor_starter_import_error = __( 'Error importing settings! Please check that you uploaded a customizer export file.', 'noor-starter-templates' );
			return;
		}
		if ( $data['template'] != $template ) {
			$noor_starter_import_error = __( 'Error importing settings! The settings you uploaded are not for the Noor Theme.', 'noor-starter-templates' );
			return;
		}
		// Import images.
		$data['mods'] = self::import_images( $data['mods'] );

		// Import custom options.
		if ( isset( $data['options'] ) ) {
			foreach ( $data['options'] as $option_key => $option_value ) {
				$option = new Import_Option(
					$wp_customize,
					$option_key,
					array(
						'default'    => '',
						'type'       => 'option',
						'capability' => 'edit_theme_options',
					)
				);
				$option->import( $option_value );
			}
		}
		// If wp_css is set then import it.
		if ( function_exists( 'wp_update_custom_css_post' ) && isset( $data['wp_css'] ) && '' !== $data['wp_css'] ) {
			wp_update_custom_css_post( $data['wp_css'] );
		}
		// Call the customize_save action.
		do_action( 'customize_save', $wp_customize );

		// Loop through the mods.
		foreach ( $data['mods'] as $key => $val ) {

			// Call the customize_save_ dynamic action.
			do_action( 'customize_save_' . $key, $wp_customize );

			// Save the mod.
			set_theme_mod( $key, $val );
		}

		// Call the customize_save_after action.
		do_action( 'customize_save_after', $wp_customize );
	}

	/**
	 * Imports images for settings saved as mods.
	 *
	 * @since 0.1
	 * @access private
	 * @param array $mods An array of customizer mods.
	 * @return array The mods array with any new import data.
	 */
	private static function import_images( $mods ) {
		foreach ( $mods as $key => $val ) {

			if ( self::is_image_url( $val ) ) {

				$data = self::sideload_image( $val );

				if ( ! is_wp_error( $data ) ) {

					$mods[ $key ] = $data->url;

					// Handle header image controls.
					if ( isset( $mods[ $key . '_data' ] ) ) {
						$mods[ $key . '_data' ] = $data;
						update_post_meta( $data->attachment_id, '_wp_attachment_is_custom_header', get_stylesheet() );
					}
				}
			}
		}

		return $mods;
	}
	/**
	 * Taken from the core media_sideload_image function and
	 * modified to return an array of data instead of html.
	 *
	 * @since 0.1
	 * @access private
	 * @param string $file The image file path.
	 * @return array An array of image data.
	 */
	private static function sideload_image( $file ) {
		$data = new stdClass();

		if ( ! function_exists( 'media_handle_sideload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		if ( ! empty( $file ) ) {

			// Set variables for storage, fix file filename for query strings.
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
			$file_array         = array();
			$file_array['name'] = basename( $matches[0] );

			// Download file to temp location.
			$file_array['tmp_name'] = download_url( $file );

			// If error storing temporarily, return the error.
			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				return $file_array['tmp_name'];
			}

			// Do the validation and storage stuff.
			$id = media_handle_sideload( $file_array, 0 );

			// If error storing permanently, unlink.
			if ( is_wp_error( $id ) ) {
				@unlink( $file_array['tmp_name'] );
				return $id;
			}

			// Build the object to return.
			$meta                = wp_get_attachment_metadata( $id );
			$data->attachment_id = $id;
			$data->url           = wp_get_attachment_url( $id );
			$data->thumbnail_url = wp_get_attachment_thumb_url( $id );
			$data->height        = $meta['height'];
			$data->width         = $meta['width'];
		}

		return $data;
	}

	/**
	 * Checks to see whether a string is an image url or not.
	 *
	 * @since 0.1
	 * @access private
	 * @param string $string The string to check.
	 * @return bool Whether the string is an image url or not.
	 */
	private static function is_image_url( $string = '' ) {
		if ( is_string( $string ) ) {
			if ( preg_match( '/\.(jpg|jpeg|png|gif)/i', $string ) ) {
				return true;
			}
		}

		return false;
	}
	/**
	 * Prints error scripts for the control.
	 *
	 * @since 0.1
	 * @return void
	 */
	public static function controls_print_scripts() {
		global $noor_starter_import_error;

		if ( $noor_starter_import_error ) {
			echo '<script> alert("' . $noor_starter_import_error . '"); </script>';
		}
	}
}
Customizer_Import_Export::get_instance();
