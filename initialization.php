<?php
/**
 * Plugin Name: Scale My Publication - Publications
 * Description: Publication management foundation for Scale My Publication systems.
 * Author: Michael Peres
 * Plugin URI: https://github.com/mikeyperes/scale-my-publication-publications
 * Version: 0.1.1
 * Text Domain: scale-my-publication-publications
 * Domain Path: /languages
 * Author URI: https://michaelperes.com
 * GitHub Plugin URI: https://github.com/mikeyperes/scale-my-publication-publications/
 * GitHub Branch: main
 */

namespace smp_publications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Config {
    public static $plugin_name        = 'Scale My Publication - Publications';
    public static $plugin_version     = '0.1.1';
    public static $plugin_slug        = 'smp-publications';
    public static $plugin_folder_name = 'scale-my-publication-publications';
    public static $plugin_file        = 'initialization.php';

    public static $settings_page_name          = 'SMP Publications';
    public static $settings_page_capability    = 'manage_options';
    public static $settings_page_slug          = 'smp-publications';
    public static $settings_page_display_title = 'Scale My Publication - Publications';

    public static $github_repo   = 'mikeyperes/scale-my-publication-publications';
    public static $github_branch = 'main';

    public static function get_plugin_basename(): string {
        return plugin_basename( __FILE__ );
    }

    public static function get_canonical_plugin_basename(): string {
        return self::$plugin_folder_name . '/' . self::$plugin_file;
    }

    public static function get_github_config(): array {
        return [
            'plugin_file'        => __FILE__,
            'github_repo'        => self::$github_repo,
            'github_branch'      => self::$github_branch,
            'proper_folder_name' => self::$plugin_folder_name,
            'requires'           => '5.0',
            'tested'             => '7.0',
        ];
    }
}

function should_boot_github_updater(): bool {
    if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
        return true;
    }

    return defined( 'WP_CLI' ) && WP_CLI;
}

add_action( 'plugins_loaded', function(): void {
    if ( ! should_boot_github_updater() ) {
        return;
    }

    require_once __DIR__ . '/GitHub_Updater.php';
    init_github_updater( Config::get_github_config() );
}, 20 );

if ( is_admin() ) {
    require_once __DIR__ . '/settings-dashboard.php';
}
