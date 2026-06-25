<?php
/**
 * Plugin.
 *
 * @package Akwaaba\WordPress\SharePost
 */

namespace Akwaaba\WordPress\SharePost;

/**
 * Plugin class.
 */
class Plugin {
	/**
	 * Plugin.
	 *
	 * @var Plugin
	 */
	protected static $instance;

	/**
	 * Instance.
	 *
	 * @param string|array|object $args The plugin arguments.
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		\add_action( 'admin_menu', [ $this, 'admin_menu' ] );

		new Brevo();
	}

	public function admin_menu() {
		\add_options_page(
			__( 'Share Post', 'akwaaba-share-post' ),
			__( 'Share Post', 'akwaaba-share-post' ),
			'manage_options',
			'akwaaba_share_post',
			[ $this, 'settings_page' ]
		);
	}

	/**
	 * Settings page.
	 *
	 * @return void
	 */
	public function settings_page() {
		?>

		<div class="wrap">
			<h1><?php esc_html_e( 'Share Post Settings', 'akwaaba-share-post' ); ?></h1>

			<form method="post" action="options.php">
				<?php

				settings_fields( 'akwaaba_share_post_settings' );

				do_settings_sections( 'akwaaba_share_post_settings' );

				submit_button();

				?>
			</form>
		</div>

		<?php
	}
}
