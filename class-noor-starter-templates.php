<?php

/**
 * Importer class.
 *
 * @package Noor Starter Templates
 */

namespace Noor_Starter_Templates;

use function activate_plugin;
use function plugins_api;
use function wp_send_json_error;
use function wp_json_file_decode;
/**
 * Block direct access to the main plugin file.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Main plugin class with initialization tasks.
 */
class Starter_Templates {




	/**
	 * Instance of this class
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * The instance of the Importer class.
	 *
	 * @var object
	 */
	public $importer;

	/**
	 * The fonts list.
	 *
	 * @var object
	 */
	public $fonts;

	/**
	 * The resulting page's hook_suffix, or false if the user does not have the capability required.
	 *
	 * @var boolean or string
	 */
	private $plugin_page;

	/**
	 * Holds the verified import files.
	 *
	 * @var array
	 */
	public $import_files;

	/**
	 * The path of the log file.
	 *
	 * @var string
	 */
	public $log_file_path;

	/**
	 * The index of the `import_files` array (which import files was selected).
	 *
	 * @var int
	 */
	private $selected_index;

	/**
	 * The palette for the import.
	 *
	 * @var string
	 */
	private $selected_palette;

	/**
	 * The font for the import.
	 *
	 * @var string
	 */
	private $selected_font;

	/**
	 * The page for the import.
	 *
	 * @var string
	 */
	private $selected_page;

	/**
	 * The selected builder for import.
	 *
	 * @var string
	 */
	private $selected_builder;

	/**
	 * Import Single Override colors
	 *
	 * @var boolean
	 */
	private $override_colors;

	/**
	 * Import Single Override fonts
	 *
	 * @var boolean
	 */
	private $override_fonts;

	/**
	 * Global palette Presets
	 *
	 * @var array
	 */
	private $palette_presets;
	/**
	 * $default palette Presets
	 *
	 * @var array
	 */
	private $default;



	/**
	 * The paths of the actual import files to be used in the import.
	 *
	 * @var array
	 */
	private $selected_import_files;

	/**
	 * Holds any error messages, that should be printed out at the end of the import.
	 *
	 * @var string
	 */
	public $frontend_error_messages = array();

	/**
	 * Was the before content import already triggered?
	 *
	 * @var boolean
	 */
	private $before_import_executed = false;

	/**
	 * Make plugin page options available to other methods.
	 *
	 * @var array
	 */
	private $plugin_page_setup = array();
	/**
	 * plugin list.
	 *
	 * @var array
	 */
	private $importer_plugins = array();

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
	 * Construct function
	 */
	public function __construct() {
		 // Set plugin constants.
		$this->set_plugin_constants();
		$this->include_plugin_files();
		add_filter( 'noor_starter_templates_save_log_files', array( $this, 'active_log' ) );

		// SER: 1: rep / 2:thirdparty / 3: bundle
		$this->importer_plugins = array(
			'noor_assistant'                      => array(
				'title' => 'Noor Assistant',
				'key'   => 'plugins/js_composer.zip',
				'base'  => 'noor_assistant',
				'slug'  => 'noor_assistant',
				'path'  => 'noor_assistant/noor-assistant.php',
				'state' => Plugin_Check::active_check( 'noor_assistant/noor-assistant.php' ),
				'src'   => 'bundle',
			),
			'revslider'                           => array(
				'title' => 'Slider Revolution',
				'base'  => 'revslider',
				'key'   => 'plugins/revslider.zip',
				'slug'  => 'revslider',
				'path'  => 'revslider/revslider.php',
				'state' => Plugin_Check::active_check( 'revslider/revslider.php' ),
				'src'   => 'bundle',
			),
			'js_composer'                         => array(
				'title' => 'WPBakery Page Builder',
				'key'   => 'plugins/js_composer.zip',
				'base'  => 'js_composer',
				'slug'  => 'js_composer',
				'path'  => 'js_composer/js_composer.php',
				'state' => Plugin_Check::active_check( 'js_composer/js_composer.php' ),
				'src'   => 'bundle',
			),
			'woocommerce'                         => array(
				'title' => 'Woocommerce',
				'base'  => 'woocommerce',
				'slug'  => 'woocommerce',
				'path'  => 'woocommerce/woocommerce.php',
				'state' => Plugin_Check::active_check( 'woocommerce/woocommerce.php' ),
				'src'   => 'repo',
			),
			'yith-woocommerce-wishlist'           => array(
				'title' => 'YITH WooCommerce Wishlist',
				'base'  => 'yith-woocommerce-wishlist',
				'slug'  => 'yith-woocommerce-wishlist',
				'path'  => 'yith-woocommerce-wishlist/init.php',
				'state' => Plugin_Check::active_check( 'yith-woocommerce-wishlist/init.php' ),
				'src'   => 'repo',
			),
			'elementor'                           => array(
				'title' => 'Elementor',
				'base'  => 'elementor',
				'slug'  => 'elementor',
				'path'  => 'elementor/elementor.php',
				'state' => Plugin_Check::active_check( 'elementor/elementor.php' ),
				'src'   => 'repo',
			),
			'stackable-ultimate-gutenberg-blocks' => array(
				'title' => 'Stackable',
				'base'  => 'stackable-ultimate-gutenberg-blocks',
				'slug'  => 'stackable-ultimate-gutenberg-blocks',
				'path'  => 'stackable-ultimate-gutenberg-blocks/plugin.php',
				'state' => Plugin_Check::active_check( 'stackable-ultimate-gutenberg-blocks/plugin.php' ),
				'src'   => 'repo',
			),
			'easy-digital-downloads'              => array(
				'title' => 'Easy Digital Downloads',
				'base'  => 'easy-digital-downloads',
				'slug'  => 'easy-digital-downloads',
				'path'  => 'easy-digital-downloads/easy-digital-downloads.php',
				'state' => Plugin_Check::active_check( 'easy-digital-downloads/easy-digital-downloads.php' ),
				'src'   => 'repo',
			),
			'bbpress'                             => array(
				'title' => 'bbPress',
				'base'  => 'bbpress',
				'slug'  => 'bbpress',
				'path'  => 'bbpress/bbpress.php',
				'state' => Plugin_Check::active_check( 'bbpress/bbpress.php' ),
				'src'   => 'repo',
			),
			'fluentform'                          => array(
				'title' => 'Fluent Forms',
				'base'  => 'fluentform',
				'slug'  => 'fluentform',
				'path'  => 'fluentform/fluentform.php',
				'state' => Plugin_Check::active_check( 'fluentform/fluentform.php' ),
				'src'   => 'repo',
			),
			'give'                                => array(
				'title' => 'GiveWP',
				'base'  => 'give',
				'slug'  => 'give',
				'path'  => 'give/give.php',
				'state' => Plugin_Check::active_check( 'give/give.php' ),
				'src'   => 'repo',
			),
			'the-events-calendar'                 => array(
				'title' => 'The Events Calendar',
				'base'  => 'the-events-calendar',
				'slug'  => 'the-events-calendar',
				'path'  => 'the-events-calendar/the-events-calendar.php',
				'state' => Plugin_Check::active_check( 'the-events-calendar/the-events-calendar.php' ),
				'src'   => 'repo',
			),
			'events-calendar-pro'                 => array(
				'title' => 'The Events Calendar Pro',
				'base'  => 'events-calendar-pro',
				'slug'  => 'events-calendar-pro',
				'path'  => 'events-calendar-pro/events-calendar-pro.php',
				'state' => Plugin_Check::active_check( 'events-calendar-pro/events-calendar-pro.php' ),
				'src'   => 'thirdparty',
			),
			'contact-form-7'                      => array(
				'title' => 'Contact Form 7',
				'base'  => 'contact-form-7',
				'slug'  => 'contact-form-7',
				'path'  => 'contact-form-7/wp-contact-form-7.php',
				'state' => Plugin_Check::active_check( 'contact-form-7/wp-contact-form-7.php' ),
				'src'   => 'repo',
			),
			'echo-knowledge-base'                 => array(
				'title' => 'Knowledge Base for Documents and FAQs',
				'base'  => 'echo-knowledge-base',
				'slug'  => 'echo-knowledge-base',
				'path'  => 'echo-knowledge-base/echo-knowledge-base.php',
				'state' => Plugin_Check::active_check( 'echo-knowledge-base/echo-knowledge-base.php' ),
				'src'   => 'repo',
			),
			'jetformbuilder'                      => array(
				'title' => 'JetFormBuilder',
				'base'  => 'jetformbuilder',
				'slug'  => 'jetformbuilder',
				'path'  => 'jetformbuilder/jet-form-builder.php',
				'state' => Plugin_Check::active_check( 'jetformbuilder/jet-form-builder.php' ),
				'src'   => 'repo',
			),
		);

		add_action( 'init', array( $this, 'set_global_palette_fonts' ) );
		add_action( 'init', array( $this, 'init_config' ) );
		add_action( 'init', array( $this, 'load_api_settings' ) );
		if ( is_admin() ) {
			// Ajax Calls.
			add_action( 'wp_ajax_noor_import_demo_data', array( $this, 'import_demo_data_ajax_callback' ) );
			add_action( 'wp_ajax_noor_import_install_plugins', array( $this, 'install_plugins_ajax_callback' ) );
			add_action( 'wp_ajax_noor_import_customizer_data', array( $this, 'import_customizer_data_ajax_callback' ) );
			add_action( 'wp_ajax_noor_after_import_data', array( $this, 'after_all_import_data_ajax_callback' ) );
			add_action( 'wp_ajax_noor_import_single_data', array( $this, 'import_demo_single_data_ajax_callback' ) );
			add_action( 'wp_ajax_noor_remove_past_import_data', array( $this, 'remove_past_data_ajax_callback' ) );
			add_action( 'wp_ajax_noor_import_subscribe', array( $this, 'subscribe_ajax_callback' ) );
			add_action( 'wp_ajax_noor_check_plugin_data', array( $this, 'check_plugin_data_ajax_callback' ) );
			add_action( 'wp_ajax_noor_starter_dismiss_notice', array( $this, 'ajax_dismiss_starter_notice' ) );
		}
		add_action( 'init', array( $this, 'setup_plugin_with_filter_data' ) );
		// Text Domain.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		// Filters durning Import.
		add_action( 'noor-starter-templates/after_import', array( $this, 'noor_theme_after_import' ), 10, 3 );

		add_action( 'noor-starter-templates/after_import', array( $this, 'noor_elementor_after_import' ), 20, 3 );

		add_filter( 'plugin_action_links_noor-starter-templates/noor-starter-templates.php', array( $this, 'add_settings_link' ) );

		add_filter( 'update_post_metadata', array( $this, 'forcibly_fix_issue_with_metadata' ), 15, 5 );

		add_action( 'elementor/experiments/default-features-registered', array( $this, 'adjust_default_experiments' ) );
	}
	public function active_log() {
		return false;
	}

	public function set_global_palette_fonts() {
		$this->palette_presets = wp_json_file_decode( NOOR_STARTER_TEMPLATES_PATH . '/assets/colors.json', true );
		$this->default         = wp_json_file_decode( NOOR_STARTER_TEMPLATES_PATH . '/assets/colors-default.json', true );

		if ( is_rtl() ) {
			$this->fonts = array(
				'droid_kufi_naskh'     => array(
					'name'  => 'Droid Arabic Kufi & Droid Arabic Naskh',
					'hfont' => 'Droid Arabic Kufi',
					'bfont' => 'Droid Arabic Naskh',
					'font'  => 'droid_kufi_naskh',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/droid-arabic-kufi.png',
				),
				'cairo_noto_sans'      => array(
					'name'  => 'Cairo & Noto Sans Arabic',
					'hfont' => 'Cairo',
					'bfont' => 'Noto Sans Arabic',
					'font'  => 'cairo_noto_sans',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/Cairo.png',
				),
				'rakkas_lateef'        => array(
					'name'  => 'Rakkas & Lateef',
					'hfont' => 'Rakkas',
					'bfont' => 'Lateef',
					'font'  => 'rakkas_lateef',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/Rakkas.png',
				),
				'katibeh_scheherazade' => array(
					'name'  => 'Katibeh & Scheherazade',
					'hfont' => 'Katibeh',
					'bfont' => 'Scheherazade',
					'font'  => 'katibeh_scheherazade',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/Katibeh.png',
				),
				'lalezar_baloo'        => array(
					'name'  => 'Lalezar & Baloo Bhaijaan 2',
					'hfont' => 'Lalezar',
					'bfont' => 'Baloo Bhaijaan 2',
					'font'  => 'lalezar_baloo',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/Lalezar.png',
				),
				'kufam_harmattan'      => array(
					'name'  => 'Kufam & Harmattan',
					'hfont' => 'Kufam',
					'bfont' => 'Harmattan',
					'font'  => 'kufam_harmattan',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/Kufam.png',
				),
			);
		} else {
			$this->fonts = array(
				'poppins'          => array(
					'name'  => 'Poppins',
					'hfont' => 'Poppins',
					'bfont' => 'Poppins',
					'font'  => 'poppins',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/poppins.png',
				),
				'lora'             => array(
					'name'  => 'Lora',
					'hfont' => 'Lora',
					'bfont' => 'Lora',
					'font'  => 'lora',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/lora.png',
				),
				'kaushan_poppins'  => array(
					'name'  => 'Kaushan Scrip & Poppins',
					'hfont' => 'Kaushan Scrip',
					'bfont' => 'poppins',
					'font'  => 'kaushan_poppins',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/kaushan.png',
				),
				'raleway_lato'     => array(
					'name'  => 'Raleway & Lato',
					'hfont' => 'Raleway',
					'bfont' => 'Lato',
					'font'  => 'raleway_lato',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/raleway.png',
				),
				'roboto_nunito'    => array(
					'name'  => 'Roboto & Nunito',
					'hfont' => 'Roboto',
					'bfont' => 'Nunito',
					'font'  => 'roboto_nunito',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/nunito.png',
				),
				'montserrat_karla' => array(
					'name'  => 'Montserrat & Karla',
					'hfont' => 'Montserrat',
					'bfont' => 'Karla',
					'font'  => 'montserrat_karla',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/montserrat.png',
				),
				'hind_glegoo'      => array(
					'name'  => 'Glegoo & Hind',
					'hfont' => 'Glegoo',
					'bfont' => 'Hind',
					'font'  => 'hind_glegoo',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/glegoo.png',
				),
				'dancing_josefin'  => array(
					'name'  => 'Dancing Script & Josefin Sans',
					'hfont' => 'Dancing Script',
					'bfont' => 'Josefin Sans',
					'font'  => 'dancing_josefin',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/dancing-script.png',
				),
				'abril_roboto'     => array(
					'name'  => 'Abril Fatface & Roboto',
					'hfont' => 'Abril Fatface',
					'bfont' => 'Roboto',
					'font'  => 'abril_roboto',
					'hv'    => 'regular',
					'img'   => NOOR_STARTER_TEMPLATES_URL . 'assets/images/fonts/abril-fatface.png',
				),
			);
		}
	}


	/**
	 * Adjust default Elementor Experiments.
	 *
	 * @sine 10.4.3
	 *
	 * @param \Elementor\Core\Experiments\Manager $experiments
	 */
	public function adjust_default_experiments( $experiments ) {
		// Turn on Flexbox Container.
		// $experiments->set_feature_default_state('container', 'active');
		update_option( $experiments->get_feature_option_key( 'container' ), 'active' );
	}

	/**
	 * Set plugin constants.
	 *
	 * Path/URL to root of this plugin, with trailing slash and plugin version.
	 */
	private function set_plugin_constants() {
		// Path/URL to root of this plugin, with trailing slash.
		if ( ! defined( 'NOOR_STARTER_TEMPLATES_PATH' ) ) {
			define( 'NOOR_STARTER_TEMPLATES_PATH', plugin_dir_path( __FILE__ ) );
		}
		if ( ! defined( 'NOOR_STARTER_TEMPLATES_URL' ) ) {
			define( 'NOOR_STARTER_TEMPLATES_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
		}
		if ( ! defined( 'NOOR_STARTER_TEMPLATES_VERSION' ) ) {
			define( 'NOOR_STARTER_TEMPLATES_VERSION', '1.0.5' );
		}
	}

	/**
	 * Add a little css for submenu items.
	 */
	public function basic_css_menu_support() {
		wp_register_style( 'noor-import-admin', false );
		wp_enqueue_style( 'noor-import-admin' );
		$css = '#menu-appearance .wp-submenu a[href^="themes.php?page=noor-"]:before {content: "\21B3";margin-right: 0.5em;opacity: 0.5;}';
		wp_add_inline_style( 'noor-import-admin', $css );
	}
	/**
	 * Noor Import
	 */
	public function init_config() {
		if ( class_exists( 'Dima\Theme' ) && defined( 'DIMA_VERSION' ) && version_compare( DIMA_VERSION, '5.0.0', '>=' ) ) {
			add_action( 'noor_theme_admin_menu', array( $this, 'create_admin_page_in_noor_submenu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'basic_css_menu_support' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'create_admin_page' ) );
		}
	}
	/**
	 * Noor After Import functions.
	 *
	 * @param array $selected_import the selected import.
	 */
	public function noor_theme_after_import( $selected_import, $selected_palette, $selected_font ) {
		// error_log( print_r( $selected_import, true ) );
		if ( class_exists( 'woocommerce' ) && isset( $selected_import['ecommerce'] ) && $selected_import['ecommerce'] ) {
			$this->import_demo_woocommerce();
		}
		if ( class_exists( 'Restrict_Content_Pro' ) && isset( $selected_import['plugins'] ) && is_array( $selected_import['plugins'] ) && in_array( 'restrict-content', $selected_import['plugins'] ) ) {
			$this->import_demo_restrict_content();
		}
		if ( function_exists( 'tribe_update_option' ) ) {
			tribe_update_option( 'toggle_blocks_editor', true );
		}

		if ( isset( $selected_import['menus'] ) && is_array( $selected_import['menus'] ) ) {
			$locations = array();
			foreach ( $selected_import['menus'] as $key => $value ) {

				$menu = get_term_by( 'name', $value['title'], 'nav_menu' );
				// error_log( print_r( $menu, true ) );

				if ( $menu ) {
					$locations[ $value['menu'] ] = $menu->term_id;
				}
			}
			set_theme_mod( 'nav_menu_locations', $locations );
		}
		if ( isset( $selected_import['homepage'] ) && ! empty( $selected_import['homepage'] ) ) {
			$homepage = get_page_by_title( $selected_import['homepage'] );
			if ( isset( $homepage ) && $homepage->ID ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', $homepage->ID ); // Front Page.
			}
		}
		if ( isset( $selected_import['blogpage'] ) && ! empty( $selected_import['blogpage'] ) ) {
			$blogpage = get_page_by_title( $selected_import['blogpage'] );
			if ( isset( $blogpage ) && $blogpage->ID ) {
				update_option( 'page_for_posts', $blogpage->ID );
			}
		}
		if ( $selected_palette && ! empty( $selected_palette ) ) {
			// error_log( print_r( $selected_palette, true ) );
			$this->palette_presets = json_decode( json_encode( $this->palette_presets ), true );
			$this->default         = json_decode( json_encode( $this->default ), true );

			if ( \is_array( $this->palette_presets ) ) {
				if ( isset( $this->palette_presets[ $selected_palette ] ) ) {
					$this->default['palette'][0]['color'] = $this->palette_presets[ $selected_palette ][0]['color'];
					$this->default['palette'][1]['color'] = $this->palette_presets[ $selected_palette ][1]['color'];
					$this->default['palette'][2]['color'] = $this->palette_presets[ $selected_palette ][2]['color'];
					$this->default['palette'][3]['color'] = $this->palette_presets[ $selected_palette ][3]['color'];
					$this->default['palette'][4]['color'] = $this->palette_presets[ $selected_palette ][4]['color'];
					$this->default['palette'][5]['color'] = $this->palette_presets[ $selected_palette ][5]['color'];
					$this->default['palette'][6]['color'] = $this->palette_presets[ $selected_palette ][6]['color'];
					$this->default['palette'][7]['color'] = $this->palette_presets[ $selected_palette ][7]['color'];
					$this->default['palette'][8]['color'] = $this->palette_presets[ $selected_palette ][8]['color'];
					update_option( 'dima_global_palette', json_encode( $this->default ) );
				}
			}
		}
		$this->update_font( $selected_font );
	}


	/**
	 * Update font on the database depending on the font selected.
	 */
	public function update_font( $selected_font ) {
		if ( class_exists( 'Dima\Theme' ) ) {
			if ( $selected_font && ! empty( $selected_font ) ) {
				if ( isset( $this->fonts[ $selected_font ] ) ) {
					// Headline typography.
					$the_font           = $this->fonts[ $selected_font ];
					$heading            = \Dima\dima()->option( 'heading_font' );
					$heading['family']  = $the_font['hfont'];
					$heading['google']  = true;
					$heading['variant'] = $the_font['hv'];
					\Dima\dima()->update_option( 'heading_font', $heading );

					// Body font
					$body            = \Dima\dima()->option( 'base_font' );
					$body['family']  = $the_font['bfont'];
					$body['google']  = true;
					$body['variant'] = $the_font['hv'];
					\Dima\dima()->update_option( 'base_font', $body );
				}
			}
		}
	}

	/**
	 * Noor Import function.
	 */
	public function import_demo_restrict_content() {
		$rcp_options = get_option( 'rcp_settings' );
		$rcppages    = array(
			'registration_page' => 'Register',
			'redirect'          => 'Welcome',
			'account_page'      => 'Your Membership',
			'edit_profile'      => 'Edit Your Profile',
			'update_card'       => 'Update Billing Card',
		);
		foreach ( $rcppages as $rcp_page_name => $rcp_page_title ) {
			$rcppage = get_page_by_title( $rcp_page_title );
			if ( isset( $rcppage ) && $rcppage->ID ) {
				$rcp_options[ $rcp_page_name ] = $rcppage->ID;
			}
		}

		update_option( 'rcp_settings', $rcp_options );
	}
	/**
	 * Noor Import function.
	 */
	public function import_demo_woocommerce( $shop = 'Shop', $cart = 'Cart', $checkout = 'Checkout', $myaccount = 'My Account' ) {
		$woopages = array(
			'woocommerce_shop_page_id'      => $shop,
			'woocommerce_cart_page_id'      => $cart,
			'woocommerce_checkout_page_id'  => $checkout,
			'woocommerce_myaccount_page_id' => $myaccount,
		);
		foreach ( $woopages as $woo_page_name => $woo_page_title ) {
			$woopage = get_page_by_title( $woo_page_title );
			if ( isset( $woopage ) && $woopage->ID ) {
				update_option( $woo_page_name, $woopage->ID );
			}
		}

		// We no longer need to install pages.
		delete_option( '_wc_needs_pages' );
		delete_transient( '_wc_activation_redirect' );

		// Flush rules after install.
		flush_rewrite_rules();
	}
	/**
	 * Throw error on object clone.
	 *
	 * @return void
	 */
	public function __clone() {
		 // Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cloning instances of the class is forbidden.', 'noor-starter-templates' ), '1.0' );
	}


	/**
	 * Disable un-serializing of the class.
	 *
	 * @return void
	 */
	public function __wakeup() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of the class is forbidden.', 'noor-starter-templates' ), '1.0' );
	}
	/**
	 * Include all plugin files.
	 */
	private function include_plugin_files() {
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-template-database-importer.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-author-meta.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-import-export-option.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-plugin-check.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-helpers.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-import-actions.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-widget-importer.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-import-give.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-logger.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-logger-cli.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-importer.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-downloader.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-customizer-importer.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-import-elementor.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-import-fluent.php';
		require_once NOOR_STARTER_TEMPLATES_PATH . 'inc/class-import-revolution-sliders.php';
	}

	/**
	 * Add settings link
	 *
	 * @param array $links holds plugin links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'themes.php?page=noor-starter-templates' ) . '">' . __( 'View Template Library', 'noor-starter-templates' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Creates the plugin page and a submenu item in WP Appearance menu.
	 */
	public function create_admin_page() {
		$page = add_theme_page(
			esc_html__( 'Starter Templates by PixelDima', 'noor-starter-templates' ),
			esc_html__( 'Starter Templates', 'noor-starter-templates' ),
			'import',
			'noor-starter-templates',
			array( $this, 'render_admin_page' )
		);
		add_action( 'admin_print_styles-' . $page, array( $this, 'scripts' ) );
	}

	/**
	 * Creates the plugin page and a submenu item in WP Appearance menu.
	 */
	public function create_admin_page_in_noor_submenu() {
		$page = add_submenu_page(
			'pixeldima-dashboard',
			'Noor Starter Templates',
			esc_html__( 'Starter Templates', 'noor-assistant' ),
			'manage_options',
			'noor-starter-templates',
			array( $this, 'render_admin_page' )
		);
		add_action( 'admin_print_styles-' . $page, array( $this, 'scripts' ) );
	}

	/**
	 * Plugin page display.
	 * Output (HTML) is in another file.
	 */
	public function render_admin_page() {                ?>
		<div class="wrap noor_theme_starter_dash">
			<div class="noor_theme_starter_dashboard">
				<h2 class="notices" style="display:none;"></h2>
				<?php settings_errors(); ?>
				<div class="page-grid">
					<div class="noor_starter_dashboard_main">
					</div>
				</div>
			</div>
		<?php
	}

	/**
	 * Loads admin style sheets and scripts
	 */
	public function scripts() {
		$old_data     = get_option( '_noor_starter_templates_last_import_data', array() );
		$has_content  = false;
		$has_previous = false;
		if ( ! empty( $old_data ) ) {
			$has_content  = true;
			$has_previous = true;
		}
		// Check for multiple posts.
		if ( false === $has_content ) {
			$has_content = ( 1 < wp_count_posts()->publish ? true : false );
		}
		if ( false === $has_content ) {
			// Check for multiple pages.
			$has_content = ( 1 < wp_count_posts( 'page' )->publish ? true : false );
		}
		if ( false === $has_content ) {
			// Check for multiple images.
			$has_content = ( 0 < wp_count_posts( 'attachment' )->inherit ? true : false );
		}
		$show_builder_choice = ( 'active' === $this->importer_plugins['elementor']['state'] ? true : false );

		$defaultBuilder = 'blocks';
		if ( 'active' === $this->importer_plugins['elementor']['state'] ) {
			$defaultBuilder = 'elementor';
		} elseif ( 'active' === $this->importer_plugins['js_composer']['state'] ) {
			$defaultBuilder = 'wpbakery';
		}
		$current_user = wp_get_current_user();
		$user_email   = $current_user->user_email;
		$subscribed   = ( ! empty( apply_filters( 'noor_starter_templates_custom_array', array() ) ) ? true : get_option( 'noor_starter_templates_subscribe' ) );
		wp_enqueue_style( 'noor-starter-templates', NOOR_STARTER_TEMPLATES_URL . 'dist/css/starter-templates.css', array( 'wp-components' ), NOOR_STARTER_TEMPLATES_VERSION );
		wp_enqueue_script( 'noor-starter-templates', NOOR_STARTER_TEMPLATES_URL . 'dist/js/starter-templates.js', array( 'jquery', 'wp-i18n', 'wp-element', 'wp-plugins', 'wp-components', 'wp-api', 'wp-hooks', 'wp-edit-post', 'lodash', 'wp-block-library', 'wp-block-editor', 'wp-editor' ), NOOR_STARTER_TEMPLATES_VERSION, true );
		$php_to_js = array(
			'ajax_url'                => admin_url( 'admin-ajax.php' ),
			'ajax_nonce'              => wp_create_nonce( 'noor-ajax-verification' ),
			'isNoor'                  => class_exists( 'Dima\Theme' ),
			'ctemplates'              => apply_filters( 'noor_custom_child_starter_templates_enable', false ),
			'custom_icon'             => apply_filters( 'noor_custom_child_starter_templates_logo', '' ),
			'custom_name'             => apply_filters( 'noor_custom_child_starter_templates_name', '' ),
			'plugins'                 => apply_filters( 'noor_starter_templates_plugins_array', $this->importer_plugins ),
			'fonts'                   => apply_filters( 'noor_starter_templates_fonts_array', $this->fonts ),
			'logo'                    => esc_attr( NOOR_STARTER_TEMPLATES_URL . 'assets/images/noor_logo_c.svg' ),
			'has_content'             => $has_content,
			'has_previous'            => $has_previous,
			'starterSettings'         => get_option( 'noor_starter_templates_config' ),
			'notice'                  => esc_html__( 'Important: Full site importing is intended for use on new or empty sites that do not have any existing content. Keep in mind that using this feature will override your current site customizer settings, widgets, and menus. ', 'noor-starter-templates' ),
			'notice_previous'         => esc_html( 'Important: Full site importing is intended for use on new or empty sites that do not have any existing content. Keep in mind that using this feature will override your current site customizer settings, widgets, and menus. It is recommended that you enable the option to "Delete Previously Imported Posts and Images" if you are trying out different starter templates on your site.' ),
			'remove_progress'         => esc_html__( 'Removing Past Imported Content', 'noor-starter-templates' ),
			'subscribe_progress'      => esc_html__( 'Getting Started', 'noor-starter-templates' ),
			'plugin_progress'         => esc_html__( 'Checking/Installing/Activating Required Plugins', 'noor-starter-templates' ),
			'content_progress'        => esc_html__( 'Importing Content...', 'noor-starter-templates' ),
			'content_new_progress'    => esc_html__( 'Importing Content... Creating pages.', 'noor-starter-templates' ),
			'content_newer_progress'  => esc_html__( 'Importing Content... Downloading images.', 'noor-starter-templates' ),
			'content_newest_progress' => esc_html__( 'Importing Content... Still Importing.', 'noor-starter-templates' ),
			'widgets_progress'        => esc_html__( 'Importing Widgets...', 'noor-starter-templates' ),
			'customizer_progress'     => esc_html__( 'Importing Customizer Settings...', 'noor-starter-templates' ),
			'user_email'              => $user_email,
			'subscribed'              => $subscribed,
			'openBuilder'             => $show_builder_choice,
			'defaultBuilder'          => $defaultBuilder, // wpbakery, elementor
		);
		// Test if Noor theme is active.
		if ( class_exists( 'Dima\Theme' ) ) {
			$php_to_js['isMraigal'] = \Dima\dima()->dima_is_theme_enabled();
		} else {
			$php_to_js['isMraigal'] = false;
		}
		wp_localize_script(
			'noor-starter-templates',
			'noorStarterParams',
			$php_to_js
		);
	}
	/**
	 * Register settings
	 */
	public function load_api_settings() {
		register_setting(
			'noor_starter_templates_config',
			'noor_starter_templates_config',
			array(
				'type'              => 'string',
				'description'       => __( 'Config Noor Starter Templates', 'noor-starter-templates' ),
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
			)
		);
	}
	/**
	 * AJAX callback to install a plugin.
	 */
	public function check_plugin_data_ajax_callback() {
		 Helpers::verify_ajax_call();
		if ( ! isset( $_POST['selected'] ) || ! isset( $_POST['builder'] ) ) {
			wp_send_json_error( 'Missing Parameters' );
		}
		$selected_index   = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
		$selected_builder = empty( $_POST['builder'] ) ? '' : sanitize_text_field( $_POST['builder'] );
		if ( empty( $selected_index ) || empty( $selected_builder ) ) {
			wp_send_json_error( 'Missing Parameters' );
		}

		if ( empty( $this->import_files ) || ( is_array( $this->import_files ) && ! isset( $this->import_files[ $selected_index ] ) ) ) {
			$template_database  = Template_Database_Importer::get_instance();
			$this->import_files = $template_database->get_importer_files( $selected_index, $selected_builder );
		}

		if ( ! isset( $this->import_files[ $selected_index ] ) ) {
			wp_send_json_error( 'Missing Template ' . $selected_index . ' ' . $selected_builder );
		}
		$plugins_info = $this->import_files[ $selected_index ];

		if ( isset( $plugins_info['plugins'] ) && ! empty( $plugins_info['plugins'] ) ) {

			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}

			$plugin_information = array();
			foreach ( $plugins_info['plugins'] as $plugin ) {
				$path = false;
				if ( strpos( $plugin, '/' ) !== false ) {
					$path = $plugin;
					$arr  = explode( '/', $plugin, 2 );
					$base = $arr[0];
					if ( isset( $this->importer_plugins[ $base ] ) && isset( $this->importer_plugins[ $base ]['src'] ) ) {
						$src = $this->importer_plugins[ $base ]['src'];
					} else {
						$src = 'unknown';
					}
					if ( isset( $this->importer_plugins[ $base ] ) && isset( $this->importer_plugins[ $base ]['title'] ) ) {
						$title = $this->importer_plugins[ $base ]['title'];
					} else {
						$title = $base;
					}
				} elseif ( isset( $this->importer_plugins[ $plugin ] ) ) {
					$path  = $this->importer_plugins[ $plugin ]['path'];
					$base  = $this->importer_plugins[ $plugin ]['base'];
					$src   = $this->importer_plugins[ $plugin ]['src'];
					$title = $this->importer_plugins[ $plugin ]['title'];
				}
				if ( $path ) {
					$state = Plugin_Check::active_check( $path );
					if ( 'unknown' === $src ) {
						$check_api = $this->get_plugins_api( $base );
						if ( false !== $check_api ) {
							$title = $check_api->name;
							$src   = 'repo';
						}
					}
					$plugin_information[ $plugin ] = array(
						'state' => $state,
						'src'   => $src,
						'title' => $title,
					);
				} else {
					$plugin_information[ $plugin ] = array(
						'state' => 'unknown',
						'src'   => 'unknown',
						'title' => $plugin,
					);
				}
			}
			wp_send_json( $plugin_information );
		} else {
			wp_send_json_error( 'Missing Plugins' );
		}
	}

	/**
	 * Retrieve the download URL for a WP repo package.
	 *
	 * @since 2.5.0
	 *
	 * @param string $slug Plugin slug.
	 * @return string Plugin download URL.
	 */
	protected function get_wp_repo_download_url( $slug ) {
		$source = '';
		$api    = $this->get_plugins_api( $slug );

		if ( false !== $api && isset( $api->download_link ) ) {
			$source = $api->download_link;
		}

		return $source;
	}
	/**
	 * AJAX callback to install a plugin.
	 */
	public function install_plugins_ajax_callback() {
		Helpers::verify_ajax_call();

		if ( ! isset( $_POST['selected'] ) || ! isset( $_POST['builder'] ) ) {
			wp_send_json_error( 'Missing Information' );
		}
		// Get selected file index or set it to 0.
		$selected_index   = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
		$selected_builder = empty( $_POST['builder'] ) ? '' : sanitize_text_field( $_POST['builder'] );
		if ( empty( $selected_index ) || empty( $selected_builder ) ) {
			wp_send_json_error( 'Missing Parameters' );
		}
		if ( empty( $this->import_files ) || ( is_array( $this->import_files ) && ! isset( $this->import_files[ $selected_index ] ) ) ) {
			$template_database  = Template_Database_Importer::get_instance();
			$this->import_files = $template_database->get_importer_files( $selected_index, $selected_builder );
		}
		if ( ! isset( $this->import_files[ $selected_index ] ) ) {
			wp_send_json_error( 'Missing Template*' );
		}
		$plugins_info = $this->import_files[ $selected_index ];
		$install      = true;
		if ( isset( $plugins_info['plugins'] ) && ! empty( $plugins_info['plugins'] ) ) {

			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}
			if ( ! class_exists( 'WP_Upgrader' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}

			foreach ( $plugins_info['plugins'] as $plugin ) {
				$path = false;
				if ( strpos( $plugin, '/' ) !== false ) {
					$path = $plugin;
					$arr  = explode( '/', $plugin, 2 );
					$base = $arr[0];
					if ( isset( $this->importer_plugins[ $base ] ) && isset( $this->importer_plugins[ $base ]['src'] ) ) {
						$src = $this->importer_plugins[ $base ]['src'];
					} else {
						$src = 'unknown';
					}
				} elseif ( isset( $this->importer_plugins[ $plugin ] ) ) {
					$path = $this->importer_plugins[ $plugin ]['path'];
					$base = $this->importer_plugins[ $plugin ]['base'];
					$src  = $this->importer_plugins[ $plugin ]['src'];
				}

				if ( $path ) {
					$state = Plugin_Check::active_check( $path );
					// If src is messing and the base is existe.
					if ( 'unknown' === $src ) {
						$check_plugin_existe = $this->get_plugins_api( $base );
						if ( false !== $check_plugin_existe ) {
							$src = 'repo';
						}
					}

					if ( 'notactive' === $state && 'repo' === $src ) {
						if ( ! current_user_can( 'install_plugins' ) ) {
							wp_send_json_error( 'Permissions Issue' );
						}
						$download_link = $this->get_wp_repo_download_url( $base );
						if ( ! empty( $download_link ) ) {
							// Use AJAX upgrader skin instead of plugin installer skin.
							// ref: function wp_ajax_install_plugin().
							$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );

							$installed = $upgrader->install( $download_link );
							if ( $installed ) {
								$silent = ( 'give' === $base || 'elementor' === $base ? false : true );
								if ( 'give' === $base ) {
									add_option( 'give_install_pages_created', 1, '', false );
								}
								if ( 'restrict-content' === $base ) {
									update_option( 'rcp_install_pages_created', current_time( 'mysql' ) );
								}
								$activate = activate_plugin( $path, '', false, $silent );
								if ( is_wp_error( $activate ) ) {
									$install = false;
								}
							} else {
								$install = false;
							}
						} else {
							$install = false;
						}
					} elseif ( 'notactive' === $state && 'bundle' === $src ) {
						if ( ! current_user_can( 'install_plugins' ) ) {
							wp_send_json_error( 'Permissions Issue' );
						}
						global $dima_library;
						$download_link = $dima_library->remote_install->get_package( $this->importer_plugins[ $base ]['key'], 'noor' );

						if ( ! empty( $download_link ) ) {
							$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );

							$installed = $upgrader->install( $download_link );
							if ( $installed ) {
								$activate = activate_plugin( $path, '', false, true );
								if ( is_wp_error( $activate ) ) {
									$install = false;
								}
							} else {
								$install = false;
							}
						}
					} elseif ( 'installed' === $state ) {
						if ( ! current_user_can( 'install_plugins' ) ) {
							wp_send_json_error( 'Permissions Issue' );
						}
						// $silent = false;
						$silent = ( 'give' === $base || 'elementor' === $base ? false : true );
						if ( 'give' === $base ) {
							// Make sure give doesn't add it's pages, prevents having two sets.
							update_option( 'give_install_pages_created', 1, '', false );
						}
						if ( 'restrict-content' === $base ) {
							update_option( 'rcp_install_pages_created', current_time( 'mysql' ) );
						}
						$activate = activate_plugin( $path, '', false, $silent );
						if ( is_wp_error( $activate ) ) {
							$install = false;
						}
					}

					if ( 'give' === $base ) {
						update_option( 'give_version_upgraded_from', '2.13.2' );
					}
				}
			}
		}

		if ( false === $install ) {
			wp_send_json_error();
		} else {
			wp_send_json( array( 'status' => 'pluginSuccess' ) );
		}
	}

	/**
	 * Try to grab information from WordPress API.
	 *
	 * @since 2.5.0
	 *
	 * @param string $slug Plugin slug.
	 * @return object Plugins_api response object on success, WP_Error on failure.
	 */
	protected function get_plugins_api( $slug ) {
		static $api = array(); // Cache received responses.

		if ( ! isset( $api[ $slug ] ) ) {
			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}

			$response = plugins_api(
				'plugin_information',
				array(
					'slug'   => $slug,
					'fields' => array(
						'short_description' => false,
						'sections'          => false,
						'requires'          => false,
						'rating'            => false,
						'ratings'           => false,
						'downloaded'        => false,
						'last_updated'      => false,
						'added'             => false,
						'tags'              => false,
						'compatibility'     => false,
						'homepage'          => false,
						'donate_link'       => false,
					),
				)
			);

			$api[ $slug ] = false;

			if ( is_wp_error( $response ) ) {
				wp_die( esc_html( $this->strings['oops'] ) );
			} else {
				$api[ $slug ] = $response;
			}
		}

		return $api[ $slug ];
	}

	/**
	 * AJAX callback to subscribe..
	 */
	public function subscribe_ajax_callback() {
		 Helpers::verify_ajax_call();
		$email          = empty( $_POST['email'] ) ? '' : sanitize_text_field( $_POST['email'] );
		$selected_index = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
		// Do you have the data?
		if ( $email && is_email( $email ) && filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			list($user, $domain)            = explode( '@', $email );
			list($pre_domain, $post_domain) = explode( '.', $domain );
			$spell_issue_domain_ends        = array( 'local', 'comm', 'orgg', 'cmm' );
			if ( in_array( $pre_domain, $spell_issue_domain_ends, true ) ) {
				return wp_send_json( 'emailDomainPreError' );
			}
			if ( in_array( $post_domain, $spell_issue_domain_ends, true ) ) {
				return wp_send_json( 'emailDomainPostError' );
			}
			$args = array(
				'method'    => 'POST',
				'sslverify' => false,
				'timeout'   => 45,
				'body'      => array(
					'email'   => $email,
					'starter' => $selected_index,
				),
			);
			// Get the response.
			$__endpoint = 'https://acumbamail.com/webhook/incoming/1cJIb2h09SIdPWBbnPGXYV1fiDgvrrhOt1KI/j9d7m25i-AIMaYrfweOEYw==/';
			$response   = wp_remote_post( $__endpoint, $args );
			// Early exit if there was an error.
			if ( is_wp_error( $response ) ) {
				return wp_send_json( array( 'status' => 'subscribeSuccess' ) );
			}
			// Get the CSS from our response.
			$contents = wp_remote_retrieve_body( $response );
			// Early exit if there was an error.
			if ( is_wp_error( $contents ) ) {
				return wp_send_json( array( 'status' => 'subscribeSuccess' ) );
			}
			if ( ! $contents ) {
				// Send JSON Error response to the AJAX call.
				wp_send_json( array( 'status' => 'subscribeSuccess' ) );
			} else {
				update_option( 'noor_starter_templates_subscribe', true );
				wp_send_json( array( 'status' => 'subscribeSuccess' ) );
			}
		}
		// Send JSON Error response to the AJAX call.
		wp_send_json( 'emailDomainPreError' );
		die;
	}
	/**
	 * AJAX callback to remove past content..
	 */
	public function remove_past_data_ajax_callback() {
		Helpers::verify_ajax_call();

		if ( ! current_user_can( 'customize' ) ) {
			wp_send_json_error();
		}
		global $wpdb;
		// Prevents elementor from pushing out an confrimation and breaking the import.
		$_GET['force_delete_kit'] = true;
		$removed_content          = true;

		$post_ids = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_noor_starter_templates_imported_post'" );
		$term_ids = $wpdb->get_col( "SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key='_noor_starter_templates_imported_term'" );
		if ( isset( $post_ids ) && is_array( $post_ids ) ) {
			foreach ( $post_ids as $post_id ) {
				$worked = wp_delete_post( $post_id, true );
				if ( false === $worked ) {
					$removed_content = false;
				}
			}
		}
		if ( isset( $term_ids ) && is_array( $term_ids ) ) {
			foreach ( $term_ids as $term_id ) {
				$term = get_term( $term_id );
				if ( ! is_wp_error( $term ) ) {
					wp_delete_term( $term_id, $term->taxonomy );
				}
			}
		}

		if ( false === $removed_content ) {
			wp_send_json_error();
		} else {
			wp_send_json( array( 'status' => 'removeSuccess' ) );
		}
	}
	/**
	 * Main AJAX callback function for:
	 * 1). prepare import files (uploaded or predefined via filters)
	 * 2). execute 'before content import' actions (before import WP action)
	 * 3). import content
	 * 4). execute 'after content import' actions (before widget import WP action, widget import, customizer import, after import WP action)
	 */
	public function import_demo_single_data_ajax_callback() {
		// Try to update PHP memory limit (so that it does not run out of it).
		ini_set( 'memory_limit', apply_filters( 'noor-starter-templates/import_memory_limit', '350M' ) );

		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();
		// Is this a new AJAX call to continue the previous import?
		$use_existing_importer_data = $this->use_existing_importer_data();

		if ( ! $use_existing_importer_data ) {
			// Create a date and time string to use for demo and log file names.
			Helpers::set_demo_import_start_time();

			if ( apply_filters( 'noor_starter_templates_save_log_files', false ) ) {
				// Define log file path.
				$this->log_file_path = Helpers::get_log_path();
			} else {
				$this->log_file_path = '';
			}
			// Get selected file index or set it to 0.
			$this->selected_index   = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
			$this->selected_builder = empty( $_POST['builder'] ) ? 'blocks' : sanitize_text_field( $_POST['builder'] );
			$this->selected_page    = empty( $_POST['page_id'] ) ? '' : sanitize_text_field( $_POST['page_id'] );
			$this->override_colors  = 'true' === $_POST['override_colors'] ? true : false;
			$this->override_fonts   = 'true' === $_POST['override_fonts'] ? true : false;

			$this->selected_palette = empty( $_POST['palette'] ) ? '' : sanitize_text_field( $_POST['palette'] );
			$this->selected_font    = empty( $_POST['font'] ) ? '' : sanitize_text_field( $_POST['font'] );

			if ( empty( $this->import_files ) || ( is_array( $this->import_files ) && ! isset( $this->import_files[ $this->selected_index ] ) ) ) {
				$template_database  = Template_Database_Importer::get_instance();
				$this->import_files = $template_database->get_importer_files( $this->selected_index, $this->selected_builder );
			}
			if ( ! isset( $this->import_files[ $this->selected_index ] ) ) {
				wp_send_json_error();
			}
			/**
			 * 1). Prepare import files.
			 * Predefined import files via filter: noor-starter-templates/import_files
			 */
			if ( ! empty( $this->import_files[ $this->selected_index ] ) && ! empty( $this->selected_page ) && isset( $this->import_files[ $this->selected_index ]['pages'] ) && isset( $this->import_files[ $this->selected_index ]['pages'][ $this->selected_page ] ) ) { // Use predefined import files from wp filter: noor-starter-templates/import_files.

				// Download the import files (content, widgets and customizer files).
				$this->selected_import_files = Helpers::download_import_file( $this->import_files[ $this->selected_index ], $this->selected_page );

				// Check Errors.
				if ( is_wp_error( $this->selected_import_files ) ) {
					// Write error to log file and send an AJAX response with the error.
					Helpers::log_error_and_send_ajax_response(
						$this->selected_import_files->get_error_message(),
						$this->log_file_path,
						esc_html__( 'Downloaded files', 'noor-starter-templates' )
					);
				}
				if ( apply_filters( 'noor_starter_templates_save_log_files', false ) ) {
					// Add this message to log file.
					$log_added = Helpers::append_to_file(
						sprintf(
							__( 'The import files for: %s were successfully downloaded!', 'noor-starter-templates' ),
							$this->import_files[ $this->selected_index ]['_ID']
						) . Helpers::import_file_info( $this->selected_import_files ),
						$this->log_file_path,
						esc_html__( 'Downloaded files', 'noor-starter-templates' )
					);
				}
			} else {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'noor-starter-templates' ) );
			}
		}

		// Save the initial import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_import_data_transient( $this->get_current_importer_data() );

		// If elementor make sure the defaults are off.
		$elementor = false;
		if ( isset( $this->import_files[ $this->selected_index ]['type'] ) && 'elementor' === $this->import_files[ $this->selected_index ]['type'] ) {
			update_option( 'elementor_disable_color_schemes', 'yes' );
			update_option( 'elementor_disable_typography_schemes', 'yes' );
			$elementor = true;
			if ( class_exists( 'Dima\Theme' ) ) {
				$component = \Dima\Theme::instance()->components['elementor'];
				if ( $component ) {
					$component->elementor_add_theme_colors();
				}
			}
		}

		/**
		 * 3). Import content (if the content XML file is set for this import).
		 * Returns any errors greater then the "warning" logger level, that will be displayed on front page.
		 */
		$new_post = '';
		if ( ! empty( $this->selected_import_files['content'] ) ) {
			$meta   = ( ! empty( $this->import_files[ $this->selected_index ] ) && ! empty( $this->selected_page ) && isset( $this->import_files[ $this->selected_index ]['pages'] ) && isset( $this->import_files[ $this->selected_index ]['pages'][ $this->selected_page ] ) && isset( $this->import_files[ $this->selected_index ]['pages'][ $this->selected_page ]['meta'] ) ? $this->import_files[ $this->selected_index ]['pages'][ $this->selected_page ]['meta'] : 'inherit' );
			$logger = $this->importer->import_content( $this->selected_import_files['content'], true, $meta, $elementor );
			if ( is_object( $logger ) && property_exists( $logger, 'error_output' ) && $logger->error_output ) {
				$this->append_to_frontend_error_messages( $logger->error_output );
			} elseif ( is_object( $logger ) && $logger->messages ) {
				$messages = $logger->messages;
				if ( isset( $messages[1] ) && isset( $messages[1]['level'] ) && 'debug' == $messages[1]['level'] && isset( $messages[1]['message'] ) && ! empty( $messages[1]['message'] ) ) {
					$pieces   = explode( ' ', $messages[1]['message'] );
					$new_post = array_pop( $pieces );
				}
			}
		}

		if ( $this->override_colors ) {
			if ( $this->selected_palette && ! empty( $this->selected_palette ) ) {
				$this->palette_presets = json_decode( json_encode( $this->palette_presets ), true );
				$this->default         = json_decode( json_encode( $this->default ), true );

				if ( isset( $this->palette_presets[ $this->selected_palette ] ) ) {
					$this->default['palette'][0]['color'] = $this->palette_presets[ $this->selected_palette ][0]['color'];
					$this->default['palette'][1]['color'] = $this->palette_presets[ $this->selected_palette ][1]['color'];
					$this->default['palette'][2]['color'] = $this->palette_presets[ $this->selected_palette ][2]['color'];
					$this->default['palette'][3]['color'] = $this->palette_presets[ $this->selected_palette ][3]['color'];
					$this->default['palette'][4]['color'] = $this->palette_presets[ $this->selected_palette ][4]['color'];
					$this->default['palette'][5]['color'] = $this->palette_presets[ $this->selected_palette ][5]['color'];
					$this->default['palette'][6]['color'] = $this->palette_presets[ $this->selected_palette ][6]['color'];
					$this->default['palette'][7]['color'] = $this->palette_presets[ $this->selected_palette ][7]['color'];
					$this->default['palette'][8]['color'] = $this->palette_presets[ $this->selected_palette ][8]['color'];
					update_option( 'dima_global_palette', json_encode( $this->default ) );
				}
			} else {
				/**
				 * Execute the customizer import actions.
				 */
				do_action( 'noor-starter-templates/customizer_import_color_only_execution', $this->selected_import_files );
			}
		}
		if ( $this->override_fonts ) {
			$this->update_font( $this->selected_font );
		}

		// If elementor make sure the defaults are off.
		if ( isset( $this->import_files[ $this->selected_index ]['type'] ) && 'elementor' === $this->import_files[ $this->selected_index ]['type'] ) {
			if ( class_exists( 'Elementor\Plugin' ) ) {
				\Elementor\Plugin::instance()->files_manager->clear_cache();
			}
		}

		// Send a JSON response with final report.
		$this->final_response( $new_post );
	}
	/**
	 * Main AJAX callback function for:
	 * 1). prepare import files (uploaded or predefined via filters)
	 * 2). execute 'before content import' actions (before import WP action)
	 * 3). import content
	 * 4). execute 'after content import' actions (before widget import WP action, widget import, customizer import, after import WP action)
	 */
	public function import_demo_data_ajax_callback() {
		// Try to update PHP memory limit (so that it does not run out of it).
		ini_set( 'memory_limit', apply_filters( 'noor-starter-templates/import_memory_limit', '350M' ) );

		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();

		// Is this a new AJAX call to continue the previous import?
		$use_existing_importer_data = $this->use_existing_importer_data();

		if ( ! $use_existing_importer_data ) {
			// Create a date and time string to use for demo and log file names.
			Helpers::set_demo_import_start_time();

			if ( apply_filters( 'noor_starter_templates_save_log_files', false ) ) {
				// Define log file path.
				$this->log_file_path = Helpers::get_log_path();
			} else {
				$this->log_file_path = '';
			}

			// Get selected file index or set it to 0.
			$this->selected_index   = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
			$this->selected_palette = empty( $_POST['palette'] ) ? '' : sanitize_text_field( $_POST['palette'] );
			$this->selected_font    = empty( $_POST['font'] ) ? '' : sanitize_text_field( $_POST['font'] );
			$this->selected_builder = empty( $_POST['builder'] ) ? 'blocks' : sanitize_text_field( $_POST['builder'] );

			if ( empty( $this->import_files ) || ( is_array( $this->import_files ) && ! isset( $this->import_files[ $this->selected_index ] ) ) ) {
				$template_database  = Template_Database_Importer::get_instance();
				$this->import_files = $template_database->get_importer_files( $this->selected_index, $this->selected_builder );
			}
			if ( ! isset( $this->import_files[ $this->selected_index ] ) ) {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'noor-starter-templates' ) );
			}
			/**
			 * 1). Prepare import files.
			 * Predefined import files via filter: noor-starter-templates/import_files
			 */
			if ( ! empty( $this->import_files[ $this->selected_index ] ) ) { // Use predefined import files from wp filter: noor-starter-templates/import_files.

				// Download the import files (content, widgets and customizer files).
				$this->selected_import_files = Helpers::download_import_files( $this->import_files[ $this->selected_index ] );

				// Check Errors.
				if ( is_wp_error( $this->selected_import_files ) ) {
					// Write error to log file and send an AJAX response with the error.
					Helpers::log_error_and_send_ajax_response(
						$this->selected_import_files->get_error_message(),
						$this->log_file_path,
						esc_html__( 'Downloaded files', 'noor-starter-templates' )
					);
				}
				if ( apply_filters( 'noor_starter_templates_save_log_files', false ) ) {
					// Add this message to log file.
					$log_added = Helpers::append_to_file(
						sprintf(
							__( 'The import files for: %s were successfully downloaded!', 'noor-starter-templates' ),
							$this->import_files[ $this->selected_index ]['_ID']
						) . Helpers::import_file_info( $this->selected_import_files ),
						$this->log_file_path,
						esc_html__( 'Downloaded files', 'noor-starter-templates' )
					);
				}
			} else {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'noor-starter-templates' ) );
			}
		}

		// If elementor make sure the defaults are off.
		if ( isset( $this->import_files[ $this->selected_index ]['type'] ) && 'elementor' === $this->import_files[ $this->selected_index ]['type'] ) {
			update_option( 'elementor_disable_color_schemes', 'yes' );
			update_option( 'elementor_disable_typography_schemes', 'yes' );
		}
		// Save the initial import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_import_data_transient( $this->get_current_importer_data() );
		if ( ! $this->before_import_executed ) {
			$this->before_import_executed = true;

			/**
			 * Save Current Theme mods for a potential undo.
			 */
			update_option( '_noor_starter_templates_old_customizer', get_option( 'theme_mods_' . get_option( 'stylesheet' ) ) );
			// Save Import data for use if we need to reset it.
			update_option( '_noor_starter_templates_last_import_data', $this->import_files[ $this->selected_index ], 'no' );
			/**
			 * 2). Execute the actions hooked to the 'noor-starter-templates/before_content_import_execution' action:
			 *
			 * Default actions:
			 * 1 - Before content import WP action (with priority 10).
			 */
			/**
			 * Clean up default contents.
			 */
			$hello_world = get_page_by_title( 'Hello World', OBJECT, 'post' );
			if ( $hello_world ) {
				wp_delete_post( $hello_world->ID, true ); // Hello World.
			}
			$sample_page = get_page_by_title( 'Sample Page' );
			if ( $sample_page ) {
				wp_delete_post( $sample_page->ID, true ); // Sample Page.
			}
			wp_delete_comment( 1, true ); // WordPress comment.
			/**
			 * Clean up default woocommerce.
			 */
			$woopages = array(
				'woocommerce_shop_page_id'      => 'shop',
				'woocommerce_cart_page_id'      => 'cart',
				'woocommerce_checkout_page_id'  => 'checkout',
				'woocommerce_myaccount_page_id' => 'my-account',
			);
			foreach ( $woopages as $woo_page_option => $woo_page_slug ) {
				if ( get_option( $woo_page_option ) ) {
					wp_delete_post( get_option( $woo_page_option ), true );
				}
			}
			// Move All active widgets into inactive.
			$sidebars = wp_get_sidebars_widgets();
			if ( is_array( $sidebars ) ) {
				foreach ( $sidebars as $sidebar_id => $sidebar_widgets ) {
					if ( 'wp_inactive_widgets' === $sidebar_id ) {
						continue;
					}
					if ( is_array( $sidebar_widgets ) && ! empty( $sidebar_widgets ) ) {
						foreach ( $sidebar_widgets as $i => $single_widget ) {
							$sidebars['wp_inactive_widgets'][] = $single_widget;
							unset( $sidebars[ $sidebar_id ][ $i ] );
						}
					}
				}
			}
			wp_set_sidebars_widgets( $sidebars );
			// Reset to default settings values.
			delete_option( 'theme_mods_' . get_option( 'stylesheet' ) );
			// Reset Global Palette
			update_option( 'dima_global_palette', json_encode( $this->default ) );
			do_action( 'noor-starter-templates/before_content_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index, $this->selected_palette, $this->selected_font );
		}

		/**
		 * 3). Import content (if the content XML file is set for this import).
		 * Returns any errors greater then the "warning" logger level, that will be displayed on front page.
		 */
		if ( ! empty( $this->selected_import_files['content'] ) ) {
			$this->append_to_frontend_error_messages( $this->importer->import_content( $this->selected_import_files['content'] ) );
		}

		/**
		 * 4). Execute the actions hooked to the 'noor-starter-templates/after_content_import_execution' action:
		 *
		 * Default actions:
		 * 1 - Before widgets import setup (with priority 10).
		 * 2 - Import widgets (with priority 20).
		 * 3 - Import Redux data (with priority 30).
		 */
		do_action( 'noor-starter-templates/after_content_import_execution', $this->selected_import_files, $this->import_files, $this->selected_index, $this->selected_palette, $this->selected_font );
		// Save the import data as a transient, so other import parts (in new AJAX calls) can use that data.
		Helpers::set_import_data_transient( $this->get_current_importer_data() );
		// Request the customizer import AJAX call.
		if ( ! empty( $this->selected_import_files['customizer'] ) ) {
			wp_send_json( array( 'status' => 'customizerAJAX' ) );
		}

		// Request the after all import AJAX call.
		if ( false !== has_action( 'noor-starter-templates/after_all_import_execution' ) ) {
			wp_send_json( array( 'status' => 'afterAllImportAJAX' ) );
		}

		// Send a JSON response with final report.
		$this->final_response();
	}

	/**
	 * After import run elementor stuff.
	 */
	public function noor_elementor_after_import( $selected_import, $selected_palette, $selected_font ) {
		// If elementor make sure we set things up and clear cache.
		if ( isset( $selected_import['type'] ) && 'elementor' === $selected_import['type'] ) {
			if ( class_exists( 'Elementor\Plugin' ) ) {
				if ( class_exists( 'Dima\Theme' ) ) {
					$component = \Dima\Theme::instance()->components['elementor'];
					if ( $component ) {
						$component->elementor_add_theme_colors();
					}
				}
				if ( isset( $selected_import['content_width'] ) && 'large' === $selected_import['content_width'] ) {
					$container_width        = array(
						'unit'  => 'px',
						'size'  => 1242,
						'sizes' => array(),
					);
					$container_width_tablet = array(
						'unit'  => 'px',
						'size'  => 700,
						'sizes' => array(),
					);
					if ( method_exists( \Elementor\Plugin::$instance->kits_manager, 'update_kit_settings_based_on_option' ) ) {
						\Elementor\Plugin::$instance->kits_manager->update_kit_settings_based_on_option( 'container_width', $container_width );
						\Elementor\Plugin::$instance->kits_manager->update_kit_settings_based_on_option( 'container_width_tablet', $container_width_tablet );
					}
				} else {
					$container_width        = array(
						'unit'  => 'px',
						'size'  => 1140,
						'sizes' => array(),
					);
					$container_width_tablet = array(
						'unit'  => 'px',
						'size'  => 700,
						'sizes' => array(),
					);
					if ( method_exists( \Elementor\Plugin::$instance->kits_manager, 'update_kit_settings_based_on_option' ) ) {
						\Elementor\Plugin::$instance->kits_manager->update_kit_settings_based_on_option( 'container_width', $container_width );
						\Elementor\Plugin::$instance->kits_manager->update_kit_settings_based_on_option( 'container_width_tablet', $container_width_tablet );
					}
				}

				\Elementor\Plugin::instance()->files_manager->clear_cache();
			}
		}
	}


	/**
	 * AJAX callback for importing the customizer data.
	 * This request has the wp_customize set to 'on', so that the customizer hooks can be called
	 * (they can only be called with the $wp_customize instance). But if the $wp_customize is defined,
	 * then the widgets do not import correctly, that's why the customizer import has its own AJAX call.
	 */
	public function import_customizer_data_ajax_callback() {
		// Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();
		$use_existing_importer_data = $this->use_existing_importer_data();

		if ( ! $use_existing_importer_data ) {
			// Create a date and time string to use for demo and log file names.
			Helpers::set_demo_import_start_time();

			if ( apply_filters( 'noor_starter_templates_save_log_files', false ) ) {
				// Define log file path.
				$this->log_file_path = Helpers::get_log_path();
			} else {
				$this->log_file_path = '';
			}

			// Get selected file index or set it to 0.
			$this->selected_index   = empty( $_POST['selected'] ) ? '' : sanitize_text_field( $_POST['selected'] );
			$this->selected_palette = empty( $_POST['palette'] ) ? '' : sanitize_text_field( $_POST['palette'] );
			$this->selected_font    = empty( $_POST['font'] ) ? '' : sanitize_text_field( $_POST['font'] );
			$this->selected_builder = empty( $_POST['builder'] ) ? 'blocks' : sanitize_text_field( $_POST['builder'] );

			if ( empty( $this->import_files ) || ( is_array( $this->import_files ) && ! isset( $this->import_files[ $this->selected_index ] ) ) ) {
				$template_database  = Template_Database_Importer::get_instance();
				$this->import_files = $template_database->get_importer_files( $this->selected_index, $this->selected_builder );
			}
			if ( ! isset( $this->import_files[ $this->selected_index ] ) ) {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'noor-starter-templates' ) );
			}
			/**
			 * 1). Prepare import files.
			 * Predefined import files via filter: noor-starter-templates/import_files
			 */
			if ( ! empty( $this->import_files[ $this->selected_index ] ) ) { // Use predefined import files from wp filter: noor-starter-templates/import_files.

				// Download the import files (content, widgets and customizer files).
				$this->selected_import_files = Helpers::download_import_files( $this->import_files[ $this->selected_index ] );
				// Check Errors.
				if ( is_wp_error( $this->selected_import_files ) ) {
					// Write error to log file and send an AJAX response with the error.
					Helpers::log_error_and_send_ajax_response(
						$this->selected_import_files->get_error_message(),
						$this->log_file_path,
						esc_html__( 'Downloaded files', 'noor-starter-templates' )
					);
				}
				if ( apply_filters( 'noor_starter_templates_save_log_files', false ) ) {
					// Add this message to log file.
					$log_added = Helpers::append_to_file(
						sprintf(
							__( 'The import files for: %s were successfully downloaded!', 'noor-starter-templates' ),
							$this->import_files[ $this->selected_index ]['_ID']
						) . Helpers::import_file_info( $this->selected_import_files ),
						$this->log_file_path,
						esc_html__( 'Downloaded files', 'noor-starter-templates' )
					);
				}
			} else {
				// Send JSON Error response to the AJAX call.
				wp_send_json( esc_html__( 'No import files specified!', 'noor-starter-templates' ) );
			}
			// If elementor make sure the defaults are off.
			if ( isset( $this->import_files[ $this->selected_index ]['type'] ) && 'elementor' === $this->import_files[ $this->selected_index ]['type'] ) {
				update_option( 'elementor_disable_color_schemes', 'yes' );
				update_option( 'elementor_disable_typography_schemes', 'yes' );
			}
			// Save the initial import data as a transient, so other import parts (in new AJAX calls) can use that data.
			Helpers::set_import_data_transient( $this->get_current_importer_data() );
			if ( ! $this->before_import_executed ) {
				$this->before_import_executed = true;

				/**
				 * Save Current Theme mods for a potential undo.
				 */
				update_option( '_noor_starter_templates_old_customizer', get_option( 'theme_mods_' . get_option( 'stylesheet' ) ) );
				// Save Import data for use if we need to reset it.
				update_option( '_noor_starter_templates_last_import_data', $this->import_files[ $this->selected_index ], 'no' );
				// Reset to default settings values.
				delete_option( 'theme_mods_' . get_option( 'stylesheet' ) );
				// Reset Global Palette
				if ( get_option( 'dima_global_palette' ) !== false ) {
					// The option already exists, so update it.
					update_option( 'dima_global_palette', $this->default );
				}
			}
		}

		/**
		 * Execute the customizer import actions.
		 *
		 * Default actions:
		 * 1 - Customizer import (with priority 10).
		 */
		do_action( 'noor-starter-templates/customizer_import_execution', $this->selected_import_files );

		// Request the after all import AJAX call.
		if ( false !== has_action( 'noor-starter-templates/after_all_import_execution' ) ) {
			wp_send_json( array( 'status' => 'afterAllImportAJAX' ) );
		}

		// Send a JSON response with final report.
		$this->final_response();
	}

	/**
	 * AJAX callback for the after all import action.
	 */
	public function after_all_import_data_ajax_callback() {
		 // Verify if the AJAX call is valid (checks nonce and current_user_can).
		Helpers::verify_ajax_call();

		// Get existing import data.
		if ( $this->use_existing_importer_data() ) {
			/**
			 * Execute the after all import actions.
			 *
			 * Default actions:
			 * 1 - after_import action (with priority 10).
			 */
			do_action(
				'noor-starter-templates/after_all_import_execution',
				$this->selected_import_files,
				$this->import_files,
				$this->selected_index,
				$this->selected_palette,
				$this->selected_font
			);
		}

		// Send a JSON response with final report.
		$this->final_response();
	}

	/**
	 * Send a JSON response with final report.
	 */
	private function final_response( $extra = '' ) {
		// Delete importer data transient for current import.
		delete_transient( 'noor_importer_data' );

		// Display final messages (success or error messages).
		if ( empty( $this->frontend_error_messages ) && ! empty( $extra ) ) {
			$response['message'] = '';

			$response['message'] .= sprintf(
				__( '%1$sFinished! View your page →%2$s', 'noor-starter-templates' ),
				'<div class="finshed-notice-success"><p><a href="' . esc_url( get_permalink( $extra ) ) . '" class="button-primary button noor-starter-templates-finish-button">',
				'</a></p></div>'
			);
		} elseif ( empty( $this->frontend_error_messages ) ) {
			$response['message'] = '';

			$response['message'] .= sprintf(
				__( '%1$sFinished! View your site →%2$s', 'noor-starter-templates' ),
				'<div class="finshed-notice-success"><p><a href="' . esc_url( home_url( '/' ) ) . '" class="button-primary button noor-starter-templates-finish-button">',
				'</a></p></div>'
			);
		} else {
			$response['message'] = $this->frontend_error_messages_display() . '<br>';
			if ( apply_filters( 'noor_starter_templates_save_log_files', false ) ) {
				$response['message'] .= sprintf(
					__( '%1$sThe demo import has finished, but there were some import errors.%2$sMore details about the errors can be found in this %3$s%5$slog file%6$s%4$s%7$s', 'noor-starter-templates' ),
					'<div class="notice  notice-warning"><p>',
					'<br>',
					'<strong>',
					'</strong>',
					'<a href="' . Helpers::get_log_url( $this->log_file_path ) . '" target="_blank">',
					'</a>',
					'</p></div>'
				);
			} else {
				$response['message'] .= sprintf(
					__( '%1$sThe demo import has finished, but there were some import errors.%2$sPlease check your php error logs if site is incomplete.%3$s', 'noor-starter-templates' ),
					'<div class="notice  notice-warning"><p>',
					'<br>',
					'</p></div>'
				);
			}
		}

		wp_send_json( $response );
	}

	/**
	 * Get content importer data, so we can continue the import with this new AJAX request.
	 *
	 * @return boolean
	 */
	private function use_existing_importer_data() {
		if ( $data = get_transient( 'noor_importer_data' ) ) {
			$this->frontend_error_messages = empty( $data['frontend_error_messages'] ) ? array() : $data['frontend_error_messages'];
			$this->log_file_path           = empty( $data['log_file_path'] ) ? '' : $data['log_file_path'];
			$this->selected_index          = empty( $data['selected_index'] ) ? 0 : $data['selected_index'];
			$this->selected_palette        = empty( $data['selected_palette'] ) ? '' : $data['selected_palette'];
			$this->selected_font           = empty( $data['selected_font'] ) ? '' : $data['selected_font'];
			$this->selected_import_files   = empty( $data['selected_import_files'] ) ? array() : $data['selected_import_files'];
			$this->import_files            = empty( $data['import_files'] ) ? array() : $data['import_files'];
			$this->before_import_executed  = empty( $data['before_import_executed'] ) ? false : $data['before_import_executed'];
			$this->importer->set_importer_data( $data );

			return true;
		}
		return false;
	}

	/**
	 * Get the current state of selected data.
	 *
	 * @return array
	 */
	public function get_current_importer_data() {
		return array(
			'frontend_error_messages' => $this->frontend_error_messages,
			'log_file_path'           => $this->log_file_path,
			'selected_index'          => $this->selected_index,
			'selected_palette'        => $this->selected_palette,
			'selected_font'           => $this->selected_font,
			'selected_import_files'   => $this->selected_import_files,
			'import_files'            => $this->import_files,
			'before_import_executed'  => $this->before_import_executed,
		);
	}

	/**
	 * Getter function to retrieve the private log_file_path value.
	 *
	 * @return string The log_file_path value.
	 */
	public function get_log_file_path() {
		return $this->log_file_path;
	}

	/**
	 * Setter function to append additional value to the private frontend_error_messages value.
	 *
	 * @param string $additional_value The additional value that will be appended to the existing frontend_error_messages.
	 */
	public function append_to_frontend_error_messages( $text ) {
		$lines = array();

		if ( ! empty( $text ) ) {
			$text  = str_replace( '<br>', PHP_EOL, $text );
			$lines = explode( PHP_EOL, $text );
		}

		foreach ( $lines as $line ) {
			if ( ! empty( $line ) && ! in_array( $line, $this->frontend_error_messages ) ) {
				$this->frontend_error_messages[] = $line;
			}
		}
	}

	/**
	 * Display the frontend error messages.
	 *
	 * @return string Text with HTML markup.
	 */
	public function frontend_error_messages_display() {
		 $output = '';

		if ( ! empty( $this->frontend_error_messages ) ) {
			foreach ( $this->frontend_error_messages as $line ) {
				$output .= esc_html( $line );
				$output .= '<br>';
			}
		}

		return $output;
	}

	/**
	 * Load the plugin textdomain, so that translations can be made.
	 */
	public function load_textdomain() {
		 load_plugin_textdomain( 'noor-starter-templates', false, plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/languages' );
	}

	/**
	 * Get data from filters, after the theme has loaded and instantiate the importer.
	 */
	public function setup_plugin_with_filter_data() {
		if ( ! ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) ) {
			return;
		}
		// Get info of import data files and filter it.
		// $this->import_files = apply_filters( 'noor-starter-templates/import_files', array() );
		$this->import_files = '';
		// $this->import_files = Helpers::validate_import_file_info( apply_filters( 'noor-starter-templates/import_files', array() ) );
		/**
		 * Register all default actions (before content import, widget, customizer import and other actions)
		 * to the 'before_content_import_execution' and the 'noor-starter-templates/after_content_import_execution' action hook.
		 */
		$import_actions = new ImportActions();
		$import_actions->register_hooks();

		// Importer options array.
		$importer_options = apply_filters(
			'noor-starter-templates/importer_options',
			array(
				'fetch_attachments'     => true,
				'aggressive_url_search' => true,
			)
		);

		// Logger options for the logger used in the importer.
		$logger_options = apply_filters(
			'noor-starter-templates/logger_options',
			array(
				'logger_min_level' => 'warning',
			)
		);

		// Configure logger instance and set it to the importer.
		$logger            = new Logger();
		$logger->min_level = $logger_options['logger_min_level'];

		// Create importer instance with proper parameters.
		$this->importer = new Importer( $importer_options, $logger );
	}
	/**
	 * Run check to see if we need to dismiss the notice.
	 * If all tests are successful then call the dismiss_notice() method.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function ajax_dismiss_starter_notice() {
		 // Sanity check: Early exit if we're not on a wptrt_dismiss_notice action.
		if ( ! isset( $_POST['action'] ) || 'noor_starter_dismiss_notice' !== $_POST['action'] ) {
			return;
		}
		// Security check: Make sure nonce is OK.
		check_ajax_referer( 'noor-starter-ajax-verification', 'security', true );

		// If we got this far, we need to dismiss the notice.
		update_option( 'noor_starter_templates_dismiss_upsell', true, false );
	}
	/**
	 * Add a little css for submenu items.
	 *
	 * @param string $forward null, unless we should overide.
	 * @param int    $object_id  ID of the object metadata is for.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param mixed  $prev_value Optional. Previous value to check before updating.
	 */
	public function forcibly_fix_issue_with_metadata( $forward, $object_id, $meta_key, $meta_value, $prev_value ) {
		$meta_keys_to_allow = array(
			'kt_blocks_editor_width'     => true,
			'_kad_post_transparent'      => true,
			'_kad_post_title'            => true,
			'_kad_post_layout'           => true,
			'_kad_post_content_style'    => true,
			'_kad_post_vertical_padding' => true,
			'_kad_post_sidebar_id'       => true,
			'_kad_post_feature'          => true,
			'_kad_post_feature_position' => true,
			'_kad_post_header'           => true,
			'_kad_post_footer'           => true,
		);
		if ( isset( $meta_keys_to_allow[ $meta_key ] ) ) {
			$old_value = get_metadata( 'post', $object_id, $meta_key );
			if ( is_array( $old_value ) && 1 < count( $old_value ) ) {
				// Data is an array which shouldn't be the case so we need to clean that up.
				delete_metadata( 'post', $object_id, $meta_key );
				add_metadata( 'post', $object_id, $meta_key, $meta_value );
				return true;
			}
		}
		return $forward;
	}
}
Starter_Templates::get_instance();
