<?php
/*
Plugin Name: Test plugin
Plugin URI: https://github.com/BamJon/my-private-plugin
Description: A custom plugin with GitHub updates.
Version: 1.0.5
Author: jon
Author URI: https://yourwebsite.com
License: GPL2
*/

add_filter('pre_set_site_transient_update_plugins', 'check_for_github_plugin_update');
add_filter('plugins_api', 'github_plugin_update_info', 10, 3);

/**
 * Check for updates on GitHub.
 *
 * @param object $transient The update data.
 * @return object Modified update data.
 */ 
function check_for_github_plugin_update($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    $repo = 'BamJon/my-private-plugin'; // Your GitHub repo
    $url = "https://api.github.com/repos/$repo/releases/latest";

    // Fetch GitHub release data
    $response = wp_remote_get($url, ['user-agent' => 'WordPress']); // GitHub API requires user-agent
    if (is_wp_error($response)) {
        error_log('GitHub API request failed: ' . $response->get_error_message());
        return $transient;
    }

    $release = json_decode(wp_remote_retrieve_body($response));
    if (empty($release->tag_name)) {
        error_log('Invalid release data from GitHub API.');
        return $transient;
    }

    $plugin_slug = 'my-private-plugin/my-private-plugin.php';
    $plugin_file = WP_PLUGIN_DIR . "/$plugin_slug";

    if (!file_exists($plugin_file)) {
        error_log("Plugin file not found: $plugin_file");
        return $transient;
    }

    // Ensure we can use get_plugin_data()
    if (!function_exists('get_plugin_data')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    // Get the installed plugin version
    $plugin_data = get_plugin_data($plugin_file);
    $current_version = $plugin_data['Version'];
    $new_version = ltrim($release->tag_name, 'v');

    // If a newer version is available, add it to updates
    if (version_compare($current_version, $new_version, '<')) {
        $transient->response[$plugin_slug] = (object) [
            'slug'        => 'my-private-plugin',
            'plugin'      => $plugin_slug,
            'new_version' => $new_version,
            'package'     => $release->assets[0]->browser_download_url ?? '', // Ensure a ZIP file is attached to GitHub release
            'url'         => $release->html_url,
        ];
    }

    return $transient;
}
function github_plugin_update_info($result, $action, $args) {
    if ($action !== 'plugin_information') {
        return $result;
    }

    $plugin_slug = 'my-private-plugin';
    if ($args->slug !== $plugin_slug) {
        return $result;
    }

    $repo = 'BamJon/my-private-plugin';
    $url = "https://api.github.com/repos/$repo/releases/latest";

    // Fetch release data
    $response = wp_remote_get($url, ['user-agent' => 'WordPress']);
    if (is_wp_error($response)) {
        error_log('GitHub API request failed: ' . $response->get_error_message());
        return $result;
    }

    $release = json_decode(wp_remote_retrieve_body($response));
    if (empty($release)) {
        error_log('Invalid release data from GitHub API.');
        return $result;
    }

    return (object) [
        'name'          => 'Test Plugin', // Replace with actual plugin name
        'slug'          => $plugin_slug,
        'version'       => ltrim($release->tag_name, 'v'),
        'author'        => '<a href="https://yourwebsite.com">Jon</a>',
        'homepage'      => $release->html_url,
        'sections'      => [
            'description' => $release->body ?? 'No description available.',
        ],
        'download_link' => $release->assets[0]->browser_download_url ?? '',
    ];
}
