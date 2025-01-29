<?php
/*
Plugin Name: My Private Plugin
Plugin URI: https://github.com/BamJon/my-private-plugin
Description: A custom plugin with GitHub updates.
Version: 1.0.8
Author: BamJon
Author URI: https://yourwebsite.com
License: GPL2
*/

// GitHub repository details
define('GITHUB_REPO', 'BamJon/my-private-plugin'); // Replace with your GitHub repo
define('PLUGIN_SLUG', plugin_basename(__FILE__));
define('CURRENT_VERSION', '1.0.8');

/**
 * Check for plugin updates from GitHub.
 *
 * @param object $transient The update transient.
 * @return object Modified transient.
 */
add_filter('pre_set_site_transient_update_plugins', function ($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    // GitHub API URL for the latest release
    $url = "https://api.github.com/repos/" . GITHUB_REPO . "/releases/latest";

    // Fetch the release data
    $response = wp_remote_get($url, [
        'headers' => ['User-Agent' => 'WordPress/' . get_bloginfo('version')],
    ]);

    if (is_wp_error($response)) {
        error_log('Error fetching GitHub release data: ' . $response->get_error_message());
        return $transient;
    }

    $release = json_decode(wp_remote_retrieve_body($response));
    if (empty($release->tag_name) || empty($release->assets[0]->browser_download_url)) {
        error_log('Invalid GitHub release data: ' . print_r($release, true));
        return $transient;
    }

    // Check if the release version is newer than the current version
    $new_version = ltrim($release->tag_name, 'v');
    if (version_compare(CURRENT_VERSION, $new_version, '<')) {
        $plugin_data = [
            'slug'        => PLUGIN_SLUG,
            'new_version' => $new_version,
            'package'     => $release->assets[0]->browser_download_url,
            'url'         => $release->html_url,
        ];
        $transient->response[PLUGIN_SLUG] = (object)$plugin_data;
    }

    return $transient;
});

/**
 * Add "View Details" link for the plugin.
 *
 * @param object $result The plugin info.
 * @param string $action The action being performed.
 * @param object $args   Plugin arguments.
 * @return object Plugin info.
 */
add_filter('plugins_api', function ($result, $action, $args) {
    if ($action !== 'plugin_information' || $args->slug !== PLUGIN_SLUG) {
        return $result;
    }

    // GitHub API URL for the latest release
    $url = "https://api.github.com/repos/" . GITHUB_REPO . "/releases/latest";

    // Fetch the release data
    $response = wp_remote_get($url, [
        'headers' => ['User-Agent' => 'WordPress/' . get_bloginfo('version')],
    ]);

    if (is_wp_error($response)) {
        return $result;
    }

    $release = json_decode(wp_remote_retrieve_body($response));
    if (empty($release->tag_name) || empty($release->assets[0]->browser_download_url)) {
        return $result;
    }

    $result = (object)[
        'name'        => 'My Private Plugin',
        'slug'        => PLUGIN_SLUG,
        'version'     => ltrim($release->tag_name, 'v'),
        'author'      => '<a href="https://yourwebsite.com">BamJon</a>',
        'homepage'    => $release->html_url,
        'download_link' => $release->assets[0]->browser_download_url,
        'sections'    => [
            'description' => 'This is a private plugin with automatic GitHub updates.',
            'changelog'   => isset($release->body) ? nl2br($release->body) : '',
        ],
    ];

    return $result;
}, 10, 3);
