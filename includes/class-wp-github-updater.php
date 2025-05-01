<?php
/**
 * WP_GitHub_Updater class for handling plugin updates from GitHub.
 *
 * @package EnglishLine_Placement
 * @license GPL-2.0+
 * @author Adapted from Joachim Kudish's WP_GitHub_Updater
 * @link https://github.com/jkudish/WP-GitHub-Updater
 */

// Prevent loading this file directly or if the class is already defined
if (!defined('ABSPATH') || class_exists('WPGitHubUpdater') || class_exists('WP_GitHub_Updater')) {
    return;
}

class WP_GitHub_Updater {

    /**
     * GitHub Updater version
     */
    const VERSION = '1.6.1';

    /**
     * @var array $config The configuration for the updater
     */
    private $config;

    /**
     * @var array $missing_config Any config that is missing from the initialization
     */
    private $missing_config;

    /**
     * @var object $github_data Temporarily stores data fetched from GitHub
     */
    private $github_data;

    /**
     * Class Constructor
     *
     * @param array $config The configuration required for the updater to work
     */
    public function __construct($config = array()) {
        $defaults = array(
            'slug' => plugin_basename(__FILE__),
            'proper_folder_name' => dirname(plugin_basename(__FILE__)),
            'sslverify' => true, // Can be set to false in testing environments if SSL verification fails
            'access_token' => '',
            'transient_expiration' => 60 * 60 * 6, // Default to 6 hours
        );

        $this->config = wp_parse_args($config, $defaults);

        // Validate required configuration
        if (!$this->has_minimum_config()) {
            $message = 'The GitHub Updater was initialized without the minimum required configuration. Missing params: ';
            $message .= implode(',', $this->missing_config);
            _doing_it_wrong(__CLASS__, $message, self::VERSION);
            return;
        }

        $this->set_defaults();

        // Add filters for WordPress update mechanism
        add_filter('pre_set_site_transient_update_plugins', array($this, 'api_check'));
        add_filter('plugins_api', array($this, 'get_plugin_info'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'upgrader_post_install'), 10, 3);
        add_filter('http_request_timeout', array($this, 'http_request_timeout'));
        add_filter('http_request_args', array($this, 'http_request_sslverify'), 10, 2);
    }

    /**
     * Check if the minimum required configuration is set
     *
     * @return bool True if config is complete, false otherwise
     */
    public function has_minimum_config() {
        $this->missing_config = array();

        $required_config_params = array(
            'api_url',
            'raw_url',
            'github_url',
            'zip_url',
            'requires',
            'tested',
            'readme',
        );

        foreach ($required_config_params as $required_param) {
            if (empty($this->config[$required_param])) {
                $this->missing_config[] = $required_param;
            }
        }

        return empty($this->missing_config);
    }

    /**
     * Check if transients should be overruled (force API calls).
     *
     * @return bool True if transients should be overruled, false otherwise.
     */
    public function overrule_transients() {
        // Only return true if the constant is defined and set to true
        return defined('WP_GITHUB_FORCE_UPDATE') ? (bool) WP_GITHUB_FORCE_UPDATE : false;
    }

    /**
     * Set default values for the configuration
     */
    public function set_defaults() {
        // Sanitize URLs
        $this->config['api_url'] = esc_url_raw($this->config['api_url']);
        $this->config['raw_url'] = esc_url_raw($this->config['raw_url']);
        $this->config['github_url'] = esc_url_raw($this->config['github_url']);
        $this->config['zip_url'] = esc_url_raw($this->config['zip_url']);

        // Add access token to zip URL if provided
        if (!empty($this->config['access_token'])) {
            $this->config['zip_url'] = add_query_arg(array('access_token' => sanitize_text_field($this->config['access_token'])), $this->config['zip_url']);
        }

        // Set dynamic defaults
        if (!isset($this->config['new_version'])) {
            $this->config['new_version'] = $this->get_new_version();
        }

        if (!isset($this->config['last_updated'])) {
            $this->config['last_updated'] = $this->get_date();
        }

        if (!isset($this->config['description'])) {
            $this->config['description'] = $this->get_description();
        }

        $plugin_data = $this->get_plugin_data();
        if (!isset($this->config['plugin_name'])) {
            $this->config['plugin_name'] = $plugin_data['Name'];
        }

        if (!isset($this->config['version'])) {
            $this->config['version'] = $plugin_data['Version'];
        }

        if (!isset($this->config['author'])) {
            $this->config['author'] = $plugin_data['Author'];
        }

        if (!isset($this->config['homepage'])) {
            $this->config['homepage'] = $plugin_data['PluginURI'];
        }

        if (!isset($this->config['readme'])) {
            $this->config['readme'] = 'README.md';
        }
    }

    /**
     * Set HTTP request timeout
     *
     * @return int Timeout value in seconds
     */
    public function http_request_timeout() {
        return 10; // Increased to 10 seconds for better reliability
    }

    /**
     * Set sslverify for HTTP requests
     *
     * @param array $args HTTP request arguments
     * @param string $url The URL being requested
     * @return array Modified arguments
     */
    public function http_request_sslverify($args, $url) {
        if ($this->config['zip_url'] === $url) {
            $args['sslverify'] = $this->config['sslverify'];
        }
        return $args;
    }

    /**
     * Get the new version from GitHub
     *
     * @return string|bool The version number or false on failure
     */
    public function get_new_version() {
        $transient_key = md5($this->config['slug']) . '_new_version';
        $version = get_site_transient($transient_key);

        if ($this->overrule_transients() || !isset($version) || !$version || '' === $version) {
            $raw_response = $this->remote_get(trailingslashit($this->config['raw_url']) . basename($this->config['slug']));

            if (is_wp_error($raw_response)) {
                return false;
            }

            if (!isset($raw_response['body'])) {
                return false;
            }

            preg_match('/.*Version:\s*(.*)$/mi', $raw_response['body'], $matches);
            $version = !empty($matches[1]) ? $matches[1] : false;

            // Cache the version for the configured expiration time
            if (false !== $version) {
                set_site_transient($transient_key, $version, $this->config['transient_expiration']);
            }
        }

        return $version;
    }

    /**
     * Make a remote GET request to GitHub
     *
     * @param string $query The URL to query
     * @return array|WP_Error The response
     */
    public function remote_get($query) {
        $query = esc_url_raw($query);

        if (!empty($this->config['access_token'])) {
            $query = add_query_arg(array('access_token' => sanitize_text_field($this->config['access_token'])), $query);
        }

        $args = array(
            'sslverify' => $this->config['sslverify'],
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
                'Accept' => 'application/vnd.github.v3+json',
            ),
        );

        $response = wp_remote_get($query, $args);

        if (is_wp_error($response)) {
            // Log the error for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('WP_GitHub_Updater Error: ' . $response->get_error_message());
            }
            return $response;
        }

        // Check for GitHub rate limit
        if (isset($response['response']['code']) && 403 === $response['response']['code']) {
            return new WP_Error('github_rate_limit', __('GitHub API rate limit exceeded.', 'englishline-test'));
        }

        return $response;
    }

    /**
     * Get GitHub data for the repository
     *
     * @return object|bool The GitHub data or false on failure
     */
    public function get_github_data() {
        if (isset($this->github_data) && !empty($this->github_data)) {
            return $this->github_data;
        }

        $transient_key = md5($this->config['slug']) . '_github_data';
        $github_data = get_site_transient($transient_key);

        if ($this->overrule_transients() || !isset($github_data) || !$github_data || '' === $github_data) {
            $response = $this->remote_get($this->config['api_url']);

            if (is_wp_error($response)) {
                return false;
            }

            $github_data = json_decode($response['body']);

            if (empty($github_data)) {
                return false;
            }

            // Cache the data for the configured expiration time
            set_site_transient($transient_key, $github_data, $this->config['transient_expiration']);
        }

        $this->github_data = $github_data;
        return $github_data;
    }

    /**
     * Get the last updated date
     *
     * @return string|bool The date or false on failure
     */
    public function get_date() {
        $data = $this->get_github_data();
        return (!empty($data->updated_at)) ? date('Y-m-d', strtotime($data->updated_at)) : false;
    }

    /**
     * Get the plugin description
     *
     * @return string|bool The description or false on failure
     */
    public function get_description() {
        $data = $this->get_github_data();
        return (!empty($data->description)) ? sanitize_text_field($data->description) : false;
    }

    /**
     * Get plugin data from the plugin file
     *
     * @return array The plugin data
     */
    public function get_plugin_data() {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        return get_plugin_data(WP_PLUGIN_DIR . '/' . $this->config['slug']);
    }

    /**
     * Hook into WordPress update check
     *
     * @param object $transient The update transient
     * @return object The modified transient
     */
    public function api_check($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $new_version = $this->config['new_version'];
        $current_version = $this->config['version'];

        if (!$new_version || !$current_version) {
            return $transient;
        }

        $update = version_compare($new_version, $current_version, '>');

        if ($update) {
            $response = new stdClass;
            $response->new_version = $new_version;
            $response->slug = $this->config['proper_folder_name'];
            $response->url = esc_url_raw($this->config['github_url']);
            $response->package = esc_url_raw($this->config['zip_url']);

            $transient->response[$this->config['slug']] = $response;
        }

        return $transient;
    }

    /**
     * Provide plugin info for the WordPress plugin API
     *
     * @param bool $false Always false
     * @param string $action The API action
     * @param object $response The response object
     * @return object|bool The plugin info or false
     */
    public function get_plugin_info($false, $action, $response) {
        if (!isset($response->slug) || $response->slug !== $this->config['proper_folder_name']) {
            return false;
        }

        $response->slug = $this->config['proper_folder_name'];
        $response->plugin_name = $this->config['plugin_name'];
        $response->version = $this->config['new_version'];
        $response->author = $this->config['author'];
        $response->homepage = $this->config['homepage'];
        $response->requires = $this->config['requires'];
        $response->tested = $this->config['tested'];
        $response->downloaded = 0;
        $response->last_updated = $this->config['last_updated'];
        $response->sections = array('description' => $this->config['description']);
        $response->download_link = esc_url_raw($this->config['zip_url']);

        return $response;
    }

    /**
     * Handle post-installation tasks for the updater
     *
     * @param bool $true Always true
     * @param mixed $hook_extra Not used
     * @param array $result The result of the installation
     * @return array The modified result
     */
    public function upgrader_post_install($true, $hook_extra, $result) {
        global $wp_filesystem;

        if (!$wp_filesystem) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
            require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
            $wp_filesystem = new WP_Filesystem_Direct(array());
        }

        $proper_destination = WP_PLUGIN_DIR . '/' . $this->config['proper_folder_name'];
        $wp_filesystem->move($result['destination'], $proper_destination);
        $result['destination'] = $proper_destination;

        $activate = activate_plugin(WP_PLUGIN_DIR . '/' . $this->config['slug']);

        // Use WordPress admin notices instead of echo
        if (is_wp_error($activate)) {
            add_action('admin_notices', function() {
                $message = sprintf(
                    /* translators: %s: Plugin name */
                    __('The plugin %s has been updated, but could not be reactivated. Please reactivate it manually.', 'englishline-test'),
                    esc_html($this->config['plugin_name'])
                );
                echo '<div class="notice notice-error is-dismissible"><p>' . $message . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                $message = sprintf(
                    /* translators: %s: Plugin name */
                    __('The plugin %s has been updated and reactivated successfully.', 'englishline-test'),
                    esc_html($this->config['plugin_name'])
                );
                echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
            });
        }

        return $result;
    }
}