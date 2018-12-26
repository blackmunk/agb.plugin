<?php

namespace AdvancedGutenbergBlocks\Blocks;

use AdvancedGutenbergBlocks\Helpers\Consts;
use AdvancedGutenbergBlocks\Services\Blocks;

class Code {

  public function run() {

		// Register Hooks
		add_action( 'init', array( $this, 'register_render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'front_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_assets' ) );

		// Register Block in the plugin settings page
		$args = array(
			'icon' => 'dashicons-editor-code',
			'category' => 'specific',
			'preview_image' => Consts::get_url() . 'admin/img/blocks/code.jpg',
			'description' => __( "Syntax highlighting with custom themes for every languages.", 'advanced-gutenberg-blocks' ),
			'options_callback' => array( $this, 'settings' ),
			'credits_callback' => array( $this, 'credits' ),
		);

		Blocks::register_block( 'advanced-gutenberg-blocks/code', __( 'Code', 'advanced-gutenberg-blocks' ), $args );

		// Register settings
		Blocks::register_setting( 'advanced-gutenberg-blocks-code-theme' );
  }

	public function settings() {

		$selected_theme = $this->get_selected_theme();
		$select_html = '';

		foreach( $this->get_theme_list() as $theme ) {
			$value = $theme['value'];
			$label = $theme['label'];
			$selected = ($theme['value'] == $selected_theme) ? ' selected ' : '';
			$select_html .= "<option value='$value'$selected>$label</option>";
		}

		echo '
			<div class="AGB-form__setting">
				<div class="AGB-form__label">
					<label for="advanced-gutenberg-blocks-bloc-theme"> ' . __( 'Theme', 'advanced-gutenberg-blocks' ) . '</label>
				</div>

				<div class="AGB-form__field">
					<select name="advanced-gutenberg-blocks-code-theme">
						' . $select_html . '
					</select>
				</div>
			</div>

			<p class="AGB-form__help">' . __( 'what does it look like? <a href="https://codemirror.net/demo/theme.html" target="_blank">Find it out here</a>. ', 'advanced-gutenberg-blocks' ) . '</p>
		';
	}

	public function credits() {
		echo '	
			<p>' . __('This block uses Code Mirror and React Code Mirror from Marijn Haverbeke and Jed Watson.', 'advanced-gutenberg-blocks' ) . '</p>
		';
	}

	public function editor_assets() {

		wp_localize_script(
			Consts::BLOCKS_SCRIPT,
			'advancedGutenbergBlocksCode',
			array(
				'themes' => $this->get_theme_list(),
				'selectedTheme' => $this->get_selected_theme(),
				'languages' => $this->get_language_list(),
			)
		);
	}

	public function front_assets() {
		if ( has_block('advanced-gutenberg-blocks/code') ) {
			
			wp_enqueue_style(
				Consts::PLUGIN_NAME . '-code-mirror',
				Consts::get_url() . 'vendor/codemirror/codemirror.css',
				array(),
				Consts::VERSION
			);

			// Enqueue Theme
			$theme = $this->get_selected_theme();
			
			wp_enqueue_style(
				Consts::PLUGIN_NAME . '-code-mirror-theme',
				Consts::get_url() . "vendor/codemirror/themes/$theme.css",
				[ Consts::PLUGIN_NAME . '-code-mirror' ],
				Consts::VERSION
			);

			wp_enqueue_script(
				Consts::PLUGIN_NAME . '-code-mirror',
				Consts::get_url() . 'vendor/codemirror/codemirror.js',
				array(),
        Consts::VERSION
			);

			wp_enqueue_script(
				Consts::PLUGIN_NAME . '-code-mirror-matchbrackets',
				Consts::get_url() . 'vendor/codemirror/addons/edit/matchbrackets.js',
				array(),
        Consts::VERSION
			);

			// TODO Send languages array to JS (back and front)

			// Enqueue languages

			// TODO get Content 

			wp_enqueue_script(
				Consts::PLUGIN_NAME . '-code-mirror-xml',
				Consts::get_url() . 'vendor/codemirror/modes/xml/xml.js',
				[ Consts::PLUGIN_NAME . '-code-mirror' ],
				Consts::VERSION
			);


			wp_enqueue_script(
				Consts::PLUGIN_NAME . '-code-mirror-php',
				Consts::get_url() . 'vendor/codemirror/modes/php/php.js',
				[ Consts::PLUGIN_NAME . '-code-mirror' ],
				Consts::VERSION
			);

			// PHP / JAVA / C / C++ / Objective C
			wp_enqueue_script(
				Consts::PLUGIN_NAME . '-code-mirror-clike',
				Consts::get_url() . 'vendor/codemirror/modes/clike/clike.js',
				[ Consts::PLUGIN_NAME . '-code-mirror' ],
				Consts::VERSION
			);

			// HTML / PHP
			wp_enqueue_script(
				Consts::PLUGIN_NAME . '-code-mirror-htmlmixed',
				Consts::get_url() . 'vendor/codemirror/modes/htmlmixed/htmlmixed.js',
				[ Consts::PLUGIN_NAME . '-code-mirror' ],
				Consts::VERSION
			);

			wp_enqueue_script(
				Consts::PLUGIN_NAME . '-code-mirror-css',
				Consts::get_url() . 'vendor/codemirror/modes/css/css.js',
				[ Consts::PLUGIN_NAME . '-code-mirror' ],
				Consts::VERSION
			);

		}
	}

	public function register_render() {

		if ( ! function_exists( 'register_block_type' ) or is_admin() ) {
			return;
		}

		register_block_type(
      'advanced-gutenberg-blocks/code',
      [ 'render_callback' => array( $this, 'render_block' ) ]
    );

	}

	public function render_block( $attributes ) {
		
		if( ! isset( $attributes['source'] ) ) {
			return;
		}

		// Default values
		if( ! isset( $attributes['language'] ) ) { $attributes['language'] = 'xml'; }
		if( ! isset( $attributes['startLine'] ) ) { $attributes['startLine'] = 1; }
		if( ! isset( $attributes['showLines'] ) ) { $attributes['showLines'] = true; }

		// Define Align Class
		$align_class = ( isset($attributes['alignment']) ) ? ' align' . $attributes['alignment'] : '';

		// Random ID for this code
		// Allows multiple instances of CodeMirror
		$rand = rand();

		// Get theme
		$theme = $this->get_selected_theme();

		// Get language Label
		$languages = $this->get_language_list();
		$key = array_search( $attributes['language'], array_column($languages, 'value') );
		$lang_label = $languages[$key]['label'];

		// Start cached output
		$output = "";
		ob_start();

		// Get template
		include apply_filters( 'advanced_gutenberg_blocks_template', Consts::get_path() . 'public/templates/code.php', 'code' );

		// End cached output
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	public function get_selected_theme() {
		$selected_theme = get_option( 'advanced-gutenberg-blocks-code-theme' );
		
		return ( ! $selected_theme ) ? 'hopscotch' : $selected_theme;
	}

	public function get_language_list() {

		return array(
			array( 'value' => 'xml', 'label' => 'HTML' ),
			array( 'value' => 'css', 'label' => 'CSS' ),
			array( 'value' => 'php', 'label' => 'PHP' ),
			array( 'value' => 'javascript', 'label' => 'JS' ),
			array( 'value' => 'jsx', 'label' => 'JSX' ),
			array( 'value' => 'xml', 'label' => 'XML' ),
			array( 'value' => 'less', 'label' => 'Less' ),
			array( 'value' => 'sass', 'label' => 'sass' ),
			array( 'value' => 'styl', 'label' => 'Stylus' ),
		);
	}

	public function get_theme_list() {

		return array(
			array( 'value' => '3024-day' , 'label' => '3024 Day' ),
			array( 'value' => '3024-night' , 'label' => '3024 Night' ),
			array( 'value' => 'abcdef' , 'label' => 'ABCDEF' ),
			array( 'value' => 'ambiance' , 'label' => 'Ambiance' ),
			array( 'value' => 'ambiance-mobile' , 'label' => 'Ambiance-mobile' ),
			array( 'value' => 'base16-dark' , 'label' => 'Base16 Dark' ),
			array( 'value' => 'base16-light' , 'label' => 'Base16 Light' ),
			array( 'value' => 'bespin' , 'label' => 'Bespin' ),
			array( 'value' => 'blackboard' , 'label' => 'Blackboard' ),
			array( 'value' => 'cobalt' , 'label' => 'Cobalt' ),
			array( 'value' => 'colorforth' , 'label' => 'Colorforth' ),
			array( 'value' => 'darcula' , 'label' => 'Darcula' ),
			array( 'value' => 'dracula' , 'label' => 'Dracula' ),
			array( 'value' => 'duotone-dark' , 'label' => 'Duotone Dark' ),
			array( 'value' => 'duotone-light' , 'label' => 'Duotone Light' ),
			array( 'value' => 'eclipse' , 'label' => 'Eclipse' ),
			array( 'value' => 'elegant' , 'label' => 'Elegant' ),
			array( 'value' => 'erlang-dark' , 'label' => 'Erlang Dark' ),
			array( 'value' => 'gruvbox-dark' , 'label' => 'Gruvbox Dark' ),
			array( 'value' => 'hopscotch' , 'label' => 'Hopscotch' ),
			array( 'value' => 'icecoder' , 'label' => 'Icecoder' ),
			array( 'value' => 'idea' , 'label' => 'Idea' ),
			array( 'value' => 'isotope' , 'label' => 'Isotope' ),
			array( 'value' => 'lesser-dark' , 'label' => 'Lesser Dark' ),
			array( 'value' => 'liquibyte' , 'label' => 'Liquibyte' ),
			array( 'value' => 'lucario' , 'label' => 'Lucario' ),
			array( 'value' => 'material' , 'label' => 'Material' ),
			array( 'value' => 'mbo' , 'label' => 'MBO' ),
			array( 'value' => 'mdn-like' , 'label' => 'MDN like' ),
			array( 'value' => 'midnight' , 'label' => 'Midnight' ),
			array( 'value' => 'monokai' , 'label' => 'Monokai' ),
			array( 'value' => 'neat' , 'label' => 'Neat' ),
			array( 'value' => 'neo' , 'label' => 'Neo' ),
			array( 'value' => 'night' , 'label' => 'Night' ),
			array( 'value' => 'oceanic-next' , 'label' => 'Oceanic Next' ),
			array( 'value' => 'panda-syntax' , 'label' => 'Panda Syntax' ),
			array( 'value' => 'paraiso-dark' , 'label' => 'Paraiso Dark' ),
			array( 'value' => 'paraiso-light' , 'label' => 'Paraison Light' ),
			array( 'value' => 'pastel-on-dark' , 'label' => 'Pastel On Dark' ),
			array( 'value' => 'railscasts' , 'label' => 'Railscasts' ),
			array( 'value' => 'rubyblue' , 'label' => 'Rubyblue' ),
			array( 'value' => 'seti' , 'label' => 'Seti' ),
			array( 'value' => 'shadowfox' , 'label' => 'Shadowfox' ),
			array( 'value' => 'solarized' , 'label' => 'Solarized' ),
			array( 'value' => 'ssms' , 'label' => 'SSMS' ),
			array( 'value' => 'the-matrix' , 'label' => 'The Matrix' ),
			array( 'value' => 'tomorrow-night-bright' , 'label' => 'Tomorrow Night Bright' ),
			array( 'value' => 'tomorrow-night-eighties' , 'label' => 'Tomorrow Night Eighties' ),
			array( 'value' => 'ttcn' , 'label' => 'TTCN' ),
			array( 'value' => 'twilight' , 'label' => 'Twilight' ),
			array( 'value' => 'vibrant-ink' , 'label' => 'Vibrant Ink' ),
			array( 'value' => 'xq-dark' , 'label' => 'XQ Dark' ),
			array( 'value' => 'xq-light' , 'label' => 'XQ Light' ),
			array( 'value' => 'yeti' , 'label' => 'Yeti' ),
			array( 'value' => 'zenburn' , 'label' => 'Zenburn' ),
		);
	}
}