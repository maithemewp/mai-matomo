<?php

/**
 * Adds settings page.
 *
 * Original code generated by the WordPress Option Page generator:
 * @link http://jeremyhixon.com/wp-tools/option-page/
 */
class Mai_Analytics_Settings {
	private $options;

	/**
	 * Construct the class.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_item' ], 12 );
		add_action( 'admin_init', [ $this, 'init' ] );
		add_filter( 'plugin_action_links_mai-analytics/mai-analytics.php', [ $this, 'add_settings_link' ], 10, 4 );
	}

	/**
	 * Adds menu item for settings page.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function add_menu_item() {
		add_submenu_page(
			class_exists( 'Mai_Engine' ) ? 'mai-theme' : 'options-general.php',
			__( 'Mai Analytics', 'mai-analytics' ), // page_title
			class_exists( 'Mai_Engine' ) ? __( 'Analytics', 'mai-analytics' ) : __( 'Mai Analytics', 'mai-analytics' ), // menu_title
			'manage_options', // capability
			'mai-analytics', // menu_slug
			[ $this, 'add_content' ], // callback
		);
	}

	/**
	 * Adds setting page content.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function add_content() {
		$this->options = mai_analytics_get_options();

		echo '<div class="wrap">';
			printf( '<h2>%s (%s)</h2>', __( 'Mai Analytics', 'mai-analytics' ), MAI_ANALYTICS_VERSION );
			printf( '<p class="description">%s</p>', __( 'Connect your WordPress website to Matomo Analytics.', 'mai-analytics' ) );

			$this->check_connection();

			echo '<form method="post" action="options.php">';
				settings_fields( 'mai_analytics_group' );
				do_settings_sections( 'mai-analytics-section' );
				submit_button();
			echo '</form>';
		echo '</div>';
	}

	/**
	 * Checks if connection is valid.
	 *
	 * @link https://matomo.org/faq/how-to/faq_20278/
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function check_connection() {
		if ( ! ( $this->options['url'] && $this->options['token'] ) ) {
			return;
		}

		$notices = [];

		if ( $this->options['enabled'] ) {

			$connections = [
				'Matomo Version' => $this->options['url'] . sprintf( 'index.php?module=API&method=API.getMatomoVersion&format=json&token_auth=%s', $this->options['token'] ),
				'Matomo Tracker' => $this->options['url'] . 'matomo.php',
			];

			foreach ( $connections as $label => $url ) {
				$response = wp_remote_get( $url );

				if ( is_wp_error( $response ) ) {
					$type    = 'error';
					$message = $response->get_error_message();
				} else {
					$body   = wp_remote_retrieve_body( $response );
					$decode = json_decode( $body );

					if ( json_last_error() === JSON_ERROR_NONE ) {
						$body = $decode;
					}

					// Get response code.
					$code = wp_remote_retrieve_response_code( $response );

					if ( 200 !== $code ) {
						$type    = 'error';
						$message =  $code . ' ' . wp_remote_retrieve_response_message( $response );
					} elseif ( is_string( $body ) ) {
						$type    = 'success';
						$message = __( 'Connected', 'mai-analytics' );
					} elseif ( is_object( $body ) && isset( $body->value ) ) {
						$type    = 'success';
						$message = $body->value;
					} elseif ( is_object( $body ) && isset( $body->result ) && isset( $body->message ) ) {
						$type    = 'error' === $body->result ? 'error' : 'success';
						$message = $body->message;
					}

					$notices[] = [
						'type'    => $type,
						'label'   => $label,
						'message' => $message,
					];
				}

				// Stop checking if we have an error.
				if ( 'error' === $type ) {
					break;
				}
			}
		} else {
			$notices[] = [
				'type'    => 'warning',
				'label'   => 'Matomo',
				'message' => __( 'Tracking is disabled.', 'mai-analytics' ),
			];
		}

		// Display notices.
		foreach ( $notices as $notice ) {
			// WP default colors.
			switch ( $notice['type'] ) {
				case 'success':
					$color = '#00a32a';
				break;
				case 'warning':
					$color = '#dba617';
				break;
				case 'error':
					$color = '#d63638';
				break;
				default:
					$color = 'blue'; // This should never happen.
			}

			printf( '<div style="color:%s;">%s: %s</div>', $color, $notice['label'], wp_kses_post( $notice['message'] ) );
		}
	}

	/**
	 * Initialize the settings.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function init() {
		register_setting(
			'mai_analytics_group', // option_group
			'mai_analytics', // option_name
			[ $this, 'mai_analytics_sanitize' ] // sanitize_callback
		);

		add_settings_section(
			'mai_analytics_settings', // id
			'', // title
			[ $this, 'mai_analytics_section_info' ], // callback
			'mai-analytics-section' // page
		);

		add_settings_field(
			'enabled', // id
			__( 'Enable tracking', 'mai-analytics' ), // title
			[ $this, 'enabled_callback' ], // callback
			'mai-analytics-section', // page
			'mai_analytics_settings' // section
		);

		add_settings_field(
			'enabled_admin', // id
			__( 'Enable back-end tracking', 'mai-analytics' ), // title
			[ $this, 'enabled_admin_callback' ], // callback
			'mai-analytics-section', // page
			'mai_analytics_settings' // section
		);

		add_settings_field(
			'debug', // id
			__( 'Enable debugging', 'mai-analytics' ), // title
			[ $this, 'debug_callback' ], // callback
			'mai-analytics-section', // page
			'mai_analytics_settings' // section
		);

		add_settings_field(
			'site_id', // id
			__( 'Site ID', 'mai-analytics' ), // title
			[ $this, 'site_id_callback' ], // callback
			'mai-analytics-section', // page
			'mai_analytics_settings' // section
		);

		add_settings_field(
			'url', // id
			__( 'Tracker URL', 'mai-analytics' ), // title
			[ $this, 'url_callback' ], // callback
			'mai-analytics-section', // page
			'mai_analytics_settings' // section
		);

		add_settings_field(
			'token', // id
			__( 'Token', 'mai-analytics' ), // title
			[ $this, 'token_callback' ], // callback
			'mai-analytics-section', // page
			'mai_analytics_settings' // section
		);

		add_settings_field(
			'views_days', // id
			__( 'Total Views Days', 'mai-analytics' ), // title
			[ $this, 'views_days_callback' ], // callback
			'mai-analytics-section', // page
			'mai_analytics_settings' // section
		);

		add_settings_field(
			'trending_days', // id
			__( 'Trending Days', 'mai-analytics' ), // title
			[ $this, 'trending_days_callback' ], // callback
			'mai-analytics-section', // page
			'mai_analytics_settings' // section
		);


		add_settings_field(
			'views_interval', // id
			__( 'Trending/Popular Interval', 'mai-analytics' ), // title
			[ $this, 'views_interval_callback' ], // callback
			'mai-analytics-section', // page
			'mai_analytics_settings' // section
		);
	}

	/**
	 * Sanitized saved values.
	 *
	 * @since 0.1.0
	 *
	 * @param array $input
	 *
	 * @return array
	 */
	public function mai_analytics_sanitize( $input ) {
		return mai_analytics_sanitize_options( $input );
	}

	/**
	 * Displays HTML before settings.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function mai_analytics_section_info() {}

	/**
	 * Setting callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enabled_callback() {
		$constant = defined( 'MAI_ANALYTICS' );
		$value    = $constant ? rest_sanitize_boolean( MAI_ANALYTICS ) : $this->options['enabled'];

		printf(
			'<input type="checkbox" name="mai_analytics[enabled]" id="enabled" value="enabled"%s%s> <label for="enabled">%s%s</label>',
			$value ? ' checked' : '',
			$constant ? ' disabled' : '',
			__( 'Enable tracking for this website.', 'mai-analytics' ),
			$constant ? ' ' . $this->config_notice() : ''
		);
	}

	/**
	 * Setting callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function enabled_admin_callback() {
		$constant = defined( 'MAI_ANALYTICS_ADMIN' );
		$value    = $constant ? rest_sanitize_boolean( MAI_ANALYTICS_ADMIN ) : $this->options['enabled_admin'];

		printf(
			'<input type="checkbox" name="mai_analytics[enabled_admin]" id="enabled_admin" value="enabled_admin"%s%s> <label for="enabled_admin">%s%s</label>',
			$value ? ' checked' : '',
			$constant ? ' disabled' : '',
			__( 'Enabling tracking in the WordPress Dashboard.', 'mai-analytics' ),
			$constant ? ' ' . $this->config_notice() : ''
		);
	}

	/**
	 * Setting callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function debug_callback() {
		$constant = defined( 'MAI_ANALYTICS_DEBUG' );
		$value    = $constant ? rest_sanitize_boolean( MAI_ANALYTICS_DEBUG ) : $this->options['debug'];

		printf(
			'<input type="checkbox" name="mai_analytics[debug]" id="debug" value="debug"%s%s> <label for="debug">%s%s</label>',
			$value ? ' checked' : '',
			$constant ? ' disabled' : '',
			__( 'Enable debugging to print data to the Console and Spatie Ray.', 'mai-analytics' ),
			$constant ? ' ' . $this->config_notice() : ''
		);
	}

	/**
	 * Setting callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function site_id_callback() {
		$constant = defined( 'MAI_ANALYTICS_SITE_ID' );
		$value    = $constant ? absint( MAI_ANALYTICS_SITE_ID ) : $this->options['site_id'];

		printf(
			'<input class="regular-text" type="number" name="mai_analytics[site_id]" id="site_id" value="%s"%s>%s',
			$value,
			$constant ? ' disabled' : '',
			$constant ? ' ' . $this->config_notice() : ''
		);
	}

	/**
	 * Setting callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function url_callback() {
		$constant = defined( 'MAI_ANALYTICS_URL' );
		$value    = $constant ? trailingslashit( esc_url( MAI_ANALYTICS_URL ) ) : $this->options['url'];

		printf(
			'<input class="regular-text" type="text" name="mai_analytics[url]" id="url" value="%s"%s>%s',
			$value,
			$constant ? ' disabled' : '',
			$constant ? ' ' . $this->config_notice() : ''
		);
	}

	/**
	 * Setting callback.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function token_callback() {
		$constant = defined( 'MAI_ANALYTICS_TOKEN' );
		$value    = $constant ? sanitize_key( MAI_ANALYTICS_TOKEN ) : $this->options['token'];

		printf(
			'<input class="regular-text" type="password" name="mai_analytics[token]" id="token" value="%s"%s>%s',
			$value,
			$constant ? ' disabled' : '',
			$constant ? ' ' . $this->config_notice() : ''
		);
	}

	/**
	 * Setting callback.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function views_days_callback() {
		$constant = defined( 'MAI_ANALYTICS_VIEWS_DAYS' );
		$value    = $constant ? absint( MAI_ANALYTICS_VIEWS_DAYS ) : $this->options['views_days'];

		printf(
			'<input class="small-text" type="number" name="mai_analytics[views_days]" id="views_days" value="%s"%s> %s',
			$value,
			$constant ? ' disabled' : '',
			__( 'days', 'mai-analytics' ),
			$constant ? ' ' . $this->config_notice() : ''
		);

		printf( '<p class="description">%s %s %s</p>',
			__( 'Retrieve total post views going back this many days.', 'mai-analytics' ),
			__( 'Use 0 to disable fetching total views.', 'mai-analytics' ),
			__( 'Values are stored in the <code>mai_views</code> meta key.', 'mai-analytics' )
		);
	}

	/**
	 * Setting callback.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function trending_days_callback() {
		$constant = defined( 'MAI_ANALYTICS_TRENDING_DAYS' );
		$value    = $constant ? absint( MAI_ANALYTICS_TRENDING_DAYS ) : $this->options['trending_days'];

		printf(
			'<input class="small-text" type="number" name="mai_analytics[trending_days]" id="trending_days" value="%s"%s> %s%s',
			$value,
			$constant ? ' disabled' : '',
			__( 'days', 'mai-analytics' ),
			$constant ? ' ' . $this->config_notice() : ''
		);

		printf( '<p class="description">%s %s %s</p>',
			__( 'Retrieve trending post views going back this many days.', 'mai-analytics' ),
			__( 'Use 0 to disable fetching trending post views.', 'mai-analytics' ),
			__( 'Values are stored in the <code>mai_trending</code> meta key.', 'mai-analytics' )
		);
	}

	/**
	 * Setting callback.
	 *
	 * @since 0.4.0
	 *
	 * @return void
	 */
	public function views_interval_callback() {
		$constant = defined( 'MAI_ANALYTICS_VIEWS_INTERVAL' );
		$value    = $constant ? absint( MAI_ANALYTICS_VIEWS_INTERVAL ) : $this->options['views_interval'];

		printf(
			'<input class="small-text" type="number" name="mai_analytics[views_interval]" id="views_interval" value="%s"%s> %s',
			$value,
			$constant ? ' disabled' : '',
			__( 'minutes', 'mai-analytics' ),
			$constant ? ' ' . $this->config_notice() : ''
		);

		printf( '<p class="description">%s %s</p>',
			__( 'Wait this long between fetching the view counts for a given post.', 'mai-analytics' ),
			__( 'Views are only fetched when a post is visited on the front end of the site.', 'mai-analytics' )
		);
	}

	/**
	 * Gets notice for when config values are used.
	 *
	 * @return string
	 */
	public function config_notice() {
		return sprintf( '<span style="color:green;">%s</span>', __( 'Overridden in wp-config.php', 'mai-analytics' ) );
	}

	/**
	 * Return the plugin action links.  This will only be called if the plugin is active.
	 *
	 * @since 0.2.2
	 *
	 * @param array  $actions     Associative array of action names to anchor tags
	 * @param string $plugin_file Plugin file name, ie my-plugin/my-plugin.php
	 * @param array  $plugin_data Associative array of plugin data from the plugin file headers
	 * @param string $context     Plugin status context, ie 'all', 'active', 'inactive', 'recently_active'
	 *
	 * @return array associative array of plugin action links
	 */
	public function add_settings_link( $actions, $plugin_file, $plugin_data, $context ) {
		$url                 = esc_url( admin_url( sprintf( '%s.php?page=mai-analytics', class_exists( 'Mai_Engine' ) ? 'admin' : 'options-general' ) ) );
		$link                = sprintf( '<a href="%s">%s</a>', $url, __( 'Settings', 'mai-analytics' ) );
		$actions['settings'] = $link;

		return $actions;
	}
}