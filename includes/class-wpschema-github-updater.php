<?php
/**
 * GitHub-based plugin update checker.
 *
 * Checks the GitHub Releases API for new versions and integrates
 * with the WordPress plugin update system so updates can be
 * installed from the Plugins screen.
 *
 * @package WPSchemaManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles self-hosted plugin updates via GitHub Releases.
 */
class WPSchema_GitHub_Updater {

	/**
	 * GitHub username / organisation.
	 *
	 * @var string
	 */
	private string $github_username = 'patrice-hue';

	/**
	 * GitHub repository name.
	 *
	 * @var string
	 */
	private string $github_repo = 'wp-schema-manager';

	/**
	 * Plugin basename (e.g. wp-schema-manager/wp-schema-manager.php).
	 *
	 * @var string
	 */
	private string $plugin_basename;

	/**
	 * Plugin slug (directory name).
	 *
	 * @var string
	 */
	private string $plugin_slug;

	/**
	 * Current plugin version.
	 *
	 * @var string
	 */
	private string $current_version;

	/**
	 * Transient key used to cache the GitHub API response.
	 *
	 * @var string
	 */
	private string $transient_key = 'wpschema_github_update';

	/**
	 * How long (in seconds) to cache the GitHub API response.
	 *
	 * @var int
	 */
	private int $cache_duration = 12 * HOUR_IN_SECONDS;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->plugin_basename = WPSCHEMA_PLUGIN_BASENAME;
		$this->plugin_slug     = dirname( $this->plugin_basename );
		$this->current_version = WPSCHEMA_VERSION;
	}

	/**
	 * Register WordPress hooks.
	 */
	public function init(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );
	}

	/**
	 * Query the GitHub Releases API and return the latest release data.
	 *
	 * Results are cached in a transient to avoid hitting the API on
	 * every admin page load.
	 *
	 * @return array|null Release data or null on failure.
	 */
	private function get_latest_release(): ?array {
		$cached = get_transient( $this->transient_key );

		if ( false !== $cached ) {
			return $cached ?: null;
		}

		$url = sprintf(
			'https://api.github.com/repos/%s/%s/releases/latest',
			$this->github_username,
			$this->github_repo
		);

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array(
					'Accept' => 'application/vnd.github.v3+json',
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			// Cache an empty value so we don't retry immediately.
			set_transient( $this->transient_key, '', $this->cache_duration );
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['tag_name'] ) ) {
			set_transient( $this->transient_key, '', $this->cache_duration );
			return null;
		}

		set_transient( $this->transient_key, $body, $this->cache_duration );

		return $body;
	}

	/**
	 * Strip a leading "v" from a version tag (e.g. "v1.2.0" → "1.2.0").
	 *
	 * @param string $tag The tag name.
	 * @return string Normalised version string.
	 */
	private function normalise_tag( string $tag ): string {
		return ltrim( $tag, 'vV' );
	}

	/**
	 * Inject update information into the WordPress update transient.
	 *
	 * Hooked to `pre_set_site_transient_update_plugins`.
	 *
	 * @param object $transient The update_plugins transient object.
	 * @return object Modified transient.
	 */
	public function check_for_update( object $transient ): object {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();

		if ( null === $release ) {
			return $transient;
		}

		$remote_version = $this->normalise_tag( $release['tag_name'] );

		if ( ! version_compare( $remote_version, $this->current_version, '>' ) ) {
			return $transient;
		}

		$download_url = $release['zipball_url'] ?? '';

		if ( empty( $download_url ) ) {
			return $transient;
		}

		$transient->response[ $this->plugin_basename ] = (object) array(
			'slug'        => $this->plugin_slug,
			'plugin'      => $this->plugin_basename,
			'new_version' => $remote_version,
			'url'         => $release['html_url'] ?? '',
			'package'     => $download_url,
			'icons'       => array(),
			'banners'     => array(),
			'tested'      => '',
			'requires'    => '6.0',
			'requires_php' => '8.0',
		);

		return $transient;
	}

	/**
	 * Provide plugin information for the "View Details" modal.
	 *
	 * Hooked to `plugins_api`.
	 *
	 * @param false|object|array $result The result object or array. Default false.
	 * @param string             $action The API action being performed.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object
	 */
	public function plugin_info( $result, string $action, object $args ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || $args->slug !== $this->plugin_slug ) {
			return $result;
		}

		$release = $this->get_latest_release();

		if ( null === $release ) {
			return $result;
		}

		$remote_version = $this->normalise_tag( $release['tag_name'] );

		return (object) array(
			'name'          => 'WP Schema Manager',
			'slug'          => $this->plugin_slug,
			'version'       => $remote_version,
			'author'        => '<a href="https://www.digitalhitmen.com.au/">Patrice Cognard</a>',
			'homepage'      => 'https://github.com/' . $this->github_username . '/' . $this->github_repo,
			'requires'      => '6.0',
			'requires_php'  => '8.0',
			'tested'        => '',
			'download_link' => $release['zipball_url'] ?? '',
			'sections'      => array(
				'description' => 'A lightweight, developer-friendly WordPress plugin for managing JSON-LD structured data.',
				'changelog'   => nl2br( esc_html( $release['body'] ?? '' ) ),
			),
		);
	}

	/**
	 * After the update ZIP is extracted, rename the directory so
	 * WordPress can find the plugin at its expected path.
	 *
	 * GitHub ZIP archives are extracted into a folder like
	 * "username-repo-hash", which does not match the plugin slug.
	 *
	 * Hooked to `upgrader_post_install`.
	 *
	 * @param bool  $response   Installation response.
	 * @param array $hook_extra Extra arguments passed to hooked filters.
	 * @param array $result     Installation result data.
	 * @return bool|WP_Error
	 */
	public function post_install( bool $response, array $hook_extra, array $result ) {
		// Only act on this plugin's updates.
		if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
			return $response;
		}

		global $wp_filesystem;

		$proper_destination = WP_PLUGIN_DIR . '/' . $this->plugin_slug;

		// Move the extracted folder to the correct location.
		$wp_filesystem->move( $result['destination'], $proper_destination );
		$result['destination'] = $proper_destination;

		// Re-activate the plugin if it was active before the update.
		if ( is_plugin_active( $this->plugin_basename ) ) {
			activate_plugin( $this->plugin_basename );
		}

		return $response;
	}
}
