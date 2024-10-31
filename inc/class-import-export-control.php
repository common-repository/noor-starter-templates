<?php
/**
 * The Import Export customize control extends the WP_Customize_Control class.
 *
 * @package Noor Starter Templates
 */

namespace Noor_Starter_Templates;

use WP_Customize_Control;

if ( ! class_exists( 'WP_Customize_Control' ) ) {
	return;
}

/**
 * Class Noor_Starter_Control_Import_Export
 *
 * @access public
 */
class Noor_Starter_Control_Import_Export extends WP_Customize_Control {
	/**
	 * Control type
	 *
	 * @var string
	 */
	public $type = 'noor_starter_import_export_control';
	/**
	 * Empty Render Function to prevent errors.
	 */
	public function render_content() {
		?>
			<span class="customize-control-title">
				<?php esc_html_e( 'Export', 'noor-starter-templates' ); ?>
			</span>
			<span class="description customize-control-description">
				<?php esc_html_e( 'Click the button below to export the customization settings for this theme.', 'noor-starter-templates' ); ?>
			</span>
			<input type="button" class="button noor-starter-export noor-starter-button" name="noor-starter-export-button" value="<?php esc_attr_e( 'Export', 'noor-starter-templates' ); ?>" />

			<hr class="dima-theme-hr" />

			<span class="customize-control-title">
				<?php esc_html_e( 'Import', 'noor-starter-templates' ); ?>
			</span>
			<span class="description customize-control-description">
				<?php esc_html_e( 'Upload a file to import customization settings for this theme.', 'noor-starter-templates' ); ?>
			</span>
			<div class="noor-starter-import-controls">
				<input type="file" name="noor-starter-import-file" class="noor-starter-import-file" />
				<?php wp_nonce_field( 'noor-starter-importing', 'noor-starter-import' ); ?>
			</div>
			<div class="noor-starter-uploading"><?php esc_html_e( 'Uploading...', 'noor-starter-templates' ); ?></div>
			<input type="button" class="button noor-starter-import noor-starter-button" name="noor-starter-import-button" value="<?php esc_attr_e( 'Import', 'noor-starter-templates' ); ?>" />

			<hr class="dima-theme-hr" />
			<span class="customize-control-title">
				<?php esc_html_e( 'Reset', 'noor-starter-templates' ); ?>
			</span>
			<span class="description customize-control-description">
				<?php esc_html_e( 'Click the button to reset all theme settings.', 'noor-starter-templates' ); ?>
			</span>
			<input type="button" class="components-button is-destructive noor-starter-reset noor-starter-button" name="noor-starter-reset-button" value="<?php esc_attr_e( 'Reset', 'noor-starter-templates' ); ?>" />
			<?php
	}
}
