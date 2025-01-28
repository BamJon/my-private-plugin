<?php
/*
Plugin Name: My Plugin
Plugin URI: https://github.com/BamJon/my-private-plugin
Description: A custom plugin with GitHub updates.
Version: 1.0.0
Author: Your Name
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

    // GitHub API URL for latest release
    $repo = 'BamJon/my-private-plugin'; // Replace with your repository
    $url = "https://api.github.com/repos/$repo/releases/latest";

    // Fetch release data from GitHub
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return $transient; // Exit on error
    }

    $release = json_decode(wp_remote_retrieve_body($response));
    if (empty($release->tag_name)) {
        return $transient; // Exit if no version found
    }

    // Plugin data
    $plugin_slug = 'my-private-plugin/my-private-plugin.php'; // Adjust to match your plugin file structure
    $current_version = get_plugin_data(WP_PLUGIN_DIR . "/$plugin_slug")['Version'];

    // Check if an update is needed
    if (version_compare($current_version, ltrim($release->tag_name, 'v'), '<')) {
        $transient->response[$plugin_slug] = (object) [
            'slug'        => $plugin_slug,
            'new_version' => ltrim($release->tag_name, 'v'),
            'package'     => $release->assets[0]->browser_download_url ?? '', // ZIP file download URL
            'url'         => $release->html_url,
        ];
    }

    return $transient;
}

/**
 * Provide additional plugin information.
 *
 * @param mixed  $result The result object or array.
 * @param string $action The requested action.
 * @param object $args   Plugin API arguments.
 * @return mixed Modified plugin information.
 */
function github_plugin_update_info($result, $action, $args) {
    if ($action !== 'plugin_information') {
        return $result;
    }

    $plugin_slug = 'my-private-plugin'; // Replace with your plugin slug
    if ($args->slug !== $plugin_slug) {
        return $result;
    }

    // GitHub API URL for latest release
    $repo = 'BamJon/my-private-plugin'; // Replace with your repository
    $url = "https://api.github.com/repos/$repo/releases/latest";

    // Fetch release data
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return $result; // Exit on error
    }

    $release = json_decode(wp_remote_retrieve_body($response));
    if (empty($release)) {
        return $result; // Exit if no release data
    }

    // Provide plugin information for the "View details" link
    return (object) [
        'name'          => 'Test plugin', // Replace with your plugin name
        'slug'          => $plugin_slug,
        'version'       => ltrim($release->tag_name, 'v'),
        'author'        => '<a href="https://yourwebsite.com">Your Name</a>', // Replace with your name and link
        'homepage'      => $release->html_url,
        'sections'      => [
            'description' => $release->body ?? 'No description available.',
        ],
        'download_link' => $release->assets[0]->browser_download_url ?? '', // ZIP file download URL
    ];
}
