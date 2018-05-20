<?php

namespace AdvancedGutenbergBlocks;

defined('ABSPATH') or die('Cheatin&#8217; uh?');

use AdvancedGutenbergBlocks\Helpers\Consts;
use AdvancedGutenbergBlocks\Helpers\Dashicons;

use AdvancedGutenbergBlocks\Services\Blocks;

use AdvancedGutenbergBlocks\WP\Installer;
use AdvancedGutenbergBlocks\WP\Admin;
use AdvancedGutenbergBlocks\WP\Front;
use AdvancedGutenbergBlocks\WP\Gutenberg;
use AdvancedGutenbergBlocks\WP\Settings;

use AdvancedGutenbergBlocks\Blocks\Notice;
use AdvancedGutenbergBlocks\Blocks\Plugin;
use AdvancedGutenbergBlocks\Blocks\Ad;
use AdvancedGutenbergBlocks\Blocks\AdText;
use AdvancedGutenbergBlocks\Blocks\Product;
use AdvancedGutenbergBlocks\Blocks\Card;
use AdvancedGutenbergBlocks\Blocks\AddToCart;
use AdvancedGutenbergBlocks\Blocks\Post;
use AdvancedGutenbergBlocks\Blocks\Testimonial;
use AdvancedGutenbergBlocks\Blocks\Gmap;


/**
 * Initialize all the needed plugin classes
 *
 * @author Maximebj
 * @version 1.0.0
 * @since 1.0.0
 */

class AdvancedGutenbergBlocks {

	// Will contain the blocks service class
	public $blocks;

	// List the registered blocks in WP Admin Gutenberg Blocks settings page
	public $registered_blocks = array();

	// List the registered blocks in WP Admin Gutenberg Blocks settings page
	public $registered_settings = array();

	public function run() {

		// Check compatibility (WP 5.1 or gutenberg plugin is activated)
		add_action( 'admin_init', array( $this, 'check_compatibility' ), 1 );

		// Load text domain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Plugin path
		$path = plugin_dir_path( __DIR__ );

		// Load Classes
		require_once $path . 'classes/Helpers/Consts.php';
		require_once $path . 'classes/Helpers/Dashicons.php';

		require_once $path . 'classes/Services/Blocks.php';

		require_once $path . 'classes/WP/Installer.php';
		require_once $path . 'classes/WP/Admin.php';
		require_once $path . 'classes/WP/Front.php';
		require_once $path . 'classes/WP/Gutenberg.php';
		require_once $path . 'classes/WP/Settings.php';

		require_once $path . 'classes/Blocks/Notice.php';
		require_once $path . 'classes/Blocks/Plugin.php';
		require_once $path . 'classes/Blocks/Ad.php';
		require_once $path . 'classes/Blocks/AdText.php';
		require_once $path . 'classes/Blocks/Product.php';
		require_once $path . 'classes/Blocks/Card.php';
		require_once $path . 'classes/Blocks/AddToCart.php';
		require_once $path . 'classes/Blocks/Post.php';
		require_once $path . 'classes/Blocks/Testimonial.php';
		require_once $path . 'classes/Blocks/Gmap.php';

		// Hack to get JS strings translatable by wp.org
		require_once $path . 'js-strings.php';


		// Blocks service
		$this->blocks = new Blocks( $this );

		// Activation / Deactivation hooks
		(new Installer)->register_hooks();

		// Init Classes and Hooks
    (new Admin)->register_hooks();
    (new Front)->register_hooks();
		(new Gutenberg)->register_hooks( $this->blocks );
		(new Settings)->register_hooks( $this->blocks );

		// Blocks
		(new Plugin)->run( $this->blocks );
		// (new Notice)->run( $this );
		// (new Ad)->run( $this );
		// (new AdText)->run( $this );
		// (new Product)->run( $this );
		// (new Card)->run( $this );
		// (new AddToCart)->run( $this );
		// (new Post)->run( $this );
		// (new Testimonial)->run( $this );
		// (new Gmap)->run( $this );
	}


	/**
	 * AGB needs WP 5.0+ or Gutenberg Plugin to work
	 */

	public function check_compatibility() {
		global $wp_version;

		if ( ! version_compare( $wp_version, '5.0', '>=' ) and ! is_plugin_active( 'gutenberg/gutenberg.php' ) ) {

			deactivate_plugins( '/advanced-gutenberg-blocks/plugin.php' );
			add_action( 'admin_notices', array( $this , 'compatibility_notice') );
		}
	}

	public function compatibility_notice() {
		?>
		<div class="error notice is-dismissible">
			<p><?php _e( 'Advanced Gutenberg Blocks requires WordPress 5.0 or Gutenberg plugin to be activated', 'advanced-gutenberg-blocks' ); ?></p>
		</div>
		<?php
	}


	/**
	 * Load text domain
	 */

	public function load_textdomain() {
	  load_plugin_textdomain( 'advanced-gutenberg-blocks', false, plugin_dir_path( __DIR__ ) . '/languages' );
	}
}
