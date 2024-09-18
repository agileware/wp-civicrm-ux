<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if (!class_exists('WP_Upgrader')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
}

/**
 * Handles the update process for the plugin using WP_Upgrader.
 */
class Civicrm_Ux_Upgrader extends Plugin_Upgrader {

    private $plugin_uri           = '';
    private $plugin_update_uri    = '';
    private $plugin               = ''; // folder/filename.php
	private $name                 = '';
	private $slug                 = '';
	private $version              = '';
    private $author               = '';
	private $author_uri           = '';

    /**
     * Constructor.
     *
     * @param string $plugin_file The main plugin file.
     */
    public function __construct( $plugin_file ) {
        // Get plugin information
        $plugin_data = get_file_data( $plugin_file, [
            'PluginName'    => 'Plugin Name',
            'PluginURI'     => 'Plugin URI',
            'Version'       => 'Version',
            'Author'        => 'Author',
            'AuthorURI'     => 'Author URI'
        ] );

        $this->plugin_uri               = $plugin_data['PluginURI'];
        $this->plugin_update_uri        = 'https://api.github.com/repos/' . WP_CIVICRM_UX_PLUGIN_GITHUB_REPO . '/releases/latest';
        $this->plugin                   = plugin_basename( $plugin_file );
        $this->name                     = $plugin_data['PluginName'];
		$this->slug                     = basename( dirname( $plugin_file ) );
		$this->version                  = $plugin_data['Version'];
        $this->author                   = $plugin_data['Author'];
        $this->author_uri               = $plugin_data['AuthorURI'];
    }

    /**
     * Initialize the updater by hooking into WordPress.
     */
    public function init() {
        add_filter( 'pre_set_site_transient_update_plugins', [$this, 'check_for_update'] );
        add_filter( 'plugins_api', [$this, 'plugins_api_filter'], 10, 3 );
        add_filter( 'upgrader_source_selection', [$this, 'fix_plugin_directory_name'], 10, 4 );
    }

    /**
     * Check for plugin updates.
     *
     * @param object $transient The current update transient.
     * @return object Modified update transient with potential update data.
     */
    public function check_for_update( $transient ) {
        if ( ! is_object( $transient ) ) {
			$transient = new \stdClass();
		}

        if ( ! empty( $transient->response ) && ! empty( $transient->response[ $this->name ] ) ) {
			return $transient;
		}

        // Get the current version of the plugin
        $current_version = $this->version;

        // Get the latest version information from GitHub
        $update_info = $this->get_update_info();

        if ( !$update_info ) {
            return $transient;
        }

        $latest_version = $update_info->tag_name;

        // Compare versions and add the update info if a newer version is available
        if ( version_compare( $current_version, $latest_version, '<' ) ) {
            $plugin = [
                'slug'        => $this->slug,
                'plugin'      => $this->plugin,
                'name'        => $this->name,
                'new_version' => $latest_version,
                'url'         => $update_info->html_url,
                'package'     => $update_info->zipball_url
            ];

            $transient->response[$this->plugin] = (object) $plugin;
        }

        return $transient;
    }

    /**
     * Updates information on the "View version x.x details" page with custom data.
	 *
	 *
	 * @param mixed   $result
	 * @param string  $action
	 * @param object  $args
	 */
	public function plugins_api_filter( $result, $action = '', $args = null ) {
		if ( 'plugin_information' !== $action ) {
			return $result;
		}

		if ( ! isset( $args->slug ) || ( $args->slug !== $this->slug ) ) {
			return $result;
		}

        // Get the latest version information from GitHub
        $update_info = $this->get_update_info();

        if (!$update_info) {
            return $result;
        }

        $plugin_info = new stdClass();
        $plugin_info->name = $this->name . ' version ' . $update_info->tag_name;
        $plugin_info->slug = $this->plugin;
        $plugin_info->version = ltrim($update_info->tag_name, 'v');
        $plugin_info->author = '<a href="' . $this->author_uri . '">' . $this->author . '</a>';
        $plugin_info->homepage = $this->plugin_uri;
        $plugin_info->download_link = $update_info->zipball_url;
        $plugin_info->sections = [
            'Release Notes' => $update_info->html_body,
        ];

		return $plugin_info;
	}

    /**
     * Convert the Markdown body to HTML using GitHub's Markdown API
     * 
     * @param string    $markdown_body
	 * @return string
     */
    private function convert_markdown_to_html( $markdown_body ) {
        // GitHub API URL for converting Markdown to HTML
        $markdown_url = 'https://api.github.com/markdown';

        // Convert the markdown to HTML
        $response = wp_remote_post( $markdown_url, [
            'headers' => [
                'Accept'        => 'application/vnd.github.v3+json',
                'User-Agent'    => $this->name . ' Plugin Updater',
            ],
            'body' => json_encode([
                'text'   => $markdown_body,
                'mode'   => 'gfm',
                'context' => WP_CIVICRM_UX_PLUGIN_GITHUB_REPO
            ]),
        ] );

        if ( is_wp_error( $response ) ) {
            error_log( 'Error converting Markdown to HTML via GitHub API : ' . $response->get_error_message() );
            return false;
        }

        $html_body = wp_remote_retrieve_body( $response );

        return $html_body;
    }

    /**
     * Get the latest release information from cached data or from remote repository.
     *
     * @return object|false Latest release information or false on failure.
     */
    private function get_update_info() {
        // Allow cache to be skipped
		$version_info = $this->allowCached() ? $this->get_cached_version_info() : false;

		if ( false === $version_info ) {
			$version_info = $this->get_update_info_from_remote();

			if ( ! $version_info ) {
				return false;
			}

			// This is required to support auto-updates since WordPress 5.5.
			$version_info->plugin = $this->name;
			$version_info->id     = $this->name;
			$version_info->version = $version_info->new_version;
			$version_info->author = sprintf( '<a href="%s">%s</a>', esc_url($this->author_uri), esc_html($this->author) );

            // Cache the body after converting from Markdown to HTML
            $version_info->html_body = $this->convert_markdown_to_html( $version_info->body );

			$this->set_version_info_cache( $version_info );
		}

		return $version_info;
    }

    /**
     * Get the latest release information from the GitHub repository.
     *
     * @return object|false Latest release information or false on failure.
     */
    private function get_update_info_from_remote() {
        // Set up the request headers, including a User-Agent as GitHub requires this.
        $args = [
            'headers' => [
                'User-Agent' => $this->name . ' Plugin Request', // GitHub API requires a User-Agent header.
                'Accept' => 'application/vnd.github+json',
            ],
        ];
        $response = wp_remote_get( $this->plugin_update_uri, $args );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        if ( empty( $data ) || isset( $data->message ) ) {
            return false;
        }

        return $data;
    }

    /**
	 * Get the version info from the cache, if it exists.
	 *
	 * @param string $cache_key
	 * @return boolean|string
	 */
	public function get_cached_version_info( $cache_key = '' ) {

		if ( empty( $cache_key ) ) {
			$cache_key = $this->get_cache_key();
		}

        $cache = get_transient( $cache_key );

		return $cache;
	}

    /**
	 * Adds the plugin version information to the database.
	 *
	 * @param string $value
	 * @param string $cache_key
	 */
	public function set_version_info_cache( $value = '', $cache_key = '' ) {

		if ( empty( $cache_key ) ) {
			$cache_key = $this->get_cache_key();
		}

        // Set the transient to expire in 12 hours (12 * 60 * 60 seconds)
        set_transient( $cache_key, $value, 12 * 60 * 60 );
	}

    /**
	 * Gets the unique key (option name) for a plugin.
	 *
	 * @return string
	 */
	private function get_cache_key() {
		$string = $this->slug;

		return 'wpciviux_vi_' . md5( $string );
	}

	private function allowCached() : bool {
		return empty($_GET['force-check']);
	}

    /**
     * Fix the directory name of the plugin during the update process.
     * 
     * Releases pulled from GitHub ZIP downloads use directory names that can include the GitHub username,
     * repository name, branch, and version. This results in incorrect folder structure when WordPress 
     * runs updates, because WordPress expects the ZIP file to contain exactly one directory with the 
     * same name as the directory where the plugin is currently installed.
     * 
     * We need to change the name of the folder downloaded from GitHub to the actual plugin folder name. 
     *
     * @param string $source        The source path of the update.
     * @param string $remote_source The remote source path of the update.
     * @param WP_Upgrader $upgrader The WP_Upgrader instance performing the update.
     * @param array $hook_extra     Additional arguments passed to the filter.
     * @return string Modified source path.
     */
    function fix_plugin_directory_name( $source, $remote_source, $upgrader, $hook_extra ) {
        global $wp_filesystem;

        //Basic sanity checks.
        if ( !isset( $source, $remote_source, $upgrader, $wp_filesystem ) ) {
            return $source;
        }

        // Check if we're updating this plugin
        if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === $this->plugin ) {
            // Get the current directory name
            $currentDirectory = basename( WP_CIVICRM_UX_PLUGIN_PATH );

            // Define the expected directory structure
            $correctedSource = trailingslashit( $remote_source ) . $currentDirectory . '/';
            
            // Check if the extracted directory matches the expected name
            if ( $source !== $correctedSource ) {
                if ( $wp_filesystem->move( $source, $correctedSource, true ) ) {
					// error_log( 'Successfully renamed the directory.' );
					return $correctedSource;
				} else {
					// error_log( 'Unable to rename the update to match the existing directory.' );
                    return new WP_Error( 'rename_failed', __('Failed to rename plugin directory to match the existing directory.') );
				}
            }
        }

        // Return the original source if no changes are needed
        return $source;
    }
}