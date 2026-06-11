<?php
namespace smp_publications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function init_github_updater( array $config ) {
    if ( empty( $config['plugin_file'] ) || empty( $config['github_repo'] ) ) {
        return false;
    }

    return new GitHub_Updater( $config );
}

final class GitHub_Updater {
    private array $config;
    private ?array $remote_plugin_data = null;

    public function __construct( array $config ) {
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugin_data   = get_plugin_data( $config['plugin_file'] );
        $github_repo   = trim( (string) $config['github_repo'], '/' );
        $github_branch = $config['github_branch'] ?? 'main';
        $runtime_slug  = plugin_basename( $config['plugin_file'] );
        $starter_file  = basename( $config['plugin_file'] );
        $folder_name   = ! empty( $config['proper_folder_name'] )
            ? trim( (string) $config['proper_folder_name'], '/' )
            : dirname( $runtime_slug );

        $this->config = [
            'slug'                => $runtime_slug,
            'canonical_slug'      => $folder_name . '/' . $starter_file,
            'proper_folder_name'  => $folder_name,
            'plugin_starter_file' => $starter_file,
            'github_repo'         => $github_repo,
            'github_branch'       => $github_branch,
            'github_url'          => 'https://github.com/' . $github_repo,
            'api_url'             => 'https://api.github.com/repos/' . $github_repo,
            'raw_plugin_url'      => 'https://raw.githubusercontent.com/' . $github_repo . '/' . $github_branch . '/' . $starter_file,
            'zip_url'             => 'https://github.com/' . $github_repo . '/archive/' . $github_branch . '.zip',
            'version'             => $plugin_data['Version'] ?? '0.0.0',
            'plugin_name'         => $plugin_data['Name'] ?? $folder_name,
            'description'         => $plugin_data['Description'] ?? '',
            'author'              => $plugin_data['Author'] ?? '',
            'requires'            => $config['requires'] ?? '5.0',
            'tested'              => $config['tested'] ?? '7.0',
        ];

        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
        add_filter( 'plugins_api', [ $this, 'plugin_info' ], 10, 3 );
        add_filter( 'upgrader_source_selection', [ $this, 'source_selection' ], 10, 4 );
        add_filter( 'http_request_timeout', [ $this, 'http_timeout' ] );
        add_filter( 'http_request_args', [ $this, 'http_args' ], 10, 2 );
    }

    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote = $this->get_remote_plugin_data();
        if ( empty( $remote['Version'] ) || ! version_compare( $remote['Version'], $this->config['version'], '>' ) ) {
            return $transient;
        }

        $update = (object) [
            'id'          => $this->config['github_url'],
            'slug'        => dirname( $this->config['canonical_slug'] ),
            'plugin'      => $this->config['slug'],
            'new_version' => $remote['Version'],
            'url'         => $this->config['github_url'],
            'package'     => $this->config['zip_url'],
            'tested'      => $this->config['tested'],
            'requires'    => $this->config['requires'],
        ];

        $transient->response[ $this->config['slug'] ] = $update;
        if ( $this->config['slug'] !== $this->config['canonical_slug'] ) {
            $transient->response[ $this->config['canonical_slug'] ] = $update;
        }

        return $transient;
    }

    public function plugin_info( $result, string $action, $args ) {
        if ( 'plugin_information' !== $action || empty( $args->slug ) || dirname( $this->config['canonical_slug'] ) !== $args->slug ) {
            return $result;
        }

        $remote = $this->get_remote_plugin_data();

        return (object) [
            'name'          => $this->config['plugin_name'],
            'slug'          => dirname( $this->config['canonical_slug'] ),
            'version'       => $remote['Version'] ?? $this->config['version'],
            'author'        => $this->config['author'],
            'homepage'      => $this->config['github_url'],
            'requires'      => $this->config['requires'],
            'tested'        => $this->config['tested'],
            'download_link' => $this->config['zip_url'],
            'sections'      => [
                'description' => $this->config['description'],
                'changelog'   => 'See the GitHub repository for release notes.',
            ],
        ];
    }

    public function source_selection( $source, $remote_source, $upgrader, $hook_extra ) {
        if ( empty( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->config['slug'] ) {
            return $source;
        }

        global $wp_filesystem;
        $target = trailingslashit( $remote_source ) . $this->config['proper_folder_name'];

        if ( trailingslashit( $source ) === trailingslashit( $target ) || ! $wp_filesystem ) {
            return $source;
        }

        if ( $wp_filesystem->exists( $target ) ) {
            $wp_filesystem->delete( $target, true );
        }

        if ( $wp_filesystem->move( $source, $target ) ) {
            return $target;
        }

        return $source;
    }

    public function http_timeout( int $timeout ): int {
        return max( $timeout, 15 );
    }

    public function http_args( array $args, string $url ): array {
        if ( str_contains( $url, 'github.com' ) || str_contains( $url, 'api.github.com' ) || str_contains( $url, 'raw.githubusercontent.com' ) ) {
            $args['sslverify'] = true;
            $args['headers']['Accept'] = $args['headers']['Accept'] ?? 'application/vnd.github+json';
            $args['headers']['User-Agent'] = $args['headers']['User-Agent'] ?? 'ScaleMyPublicationPublicationsUpdater/0.1';
        }

        return $args;
    }

    private function get_remote_plugin_data(): array {
        if ( null !== $this->remote_plugin_data ) {
            return $this->remote_plugin_data;
        }

        $response = wp_remote_get(
            $this->config['raw_plugin_url'],
            [
                'timeout' => 15,
                'headers' => [
                    'Accept'     => 'text/plain',
                    'User-Agent' => 'ScaleMyPublicationPublicationsUpdater/0.1',
                ],
            ]
        );

        if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            $this->remote_plugin_data = [];
            return $this->remote_plugin_data;
        }

        $headers = (string) wp_remote_retrieve_body( $response );
        $this->remote_plugin_data = [
            'Version' => $this->parse_header( $headers, 'Version' ),
        ];

        return $this->remote_plugin_data;
    }

    private function parse_header( string $contents, string $header ): string {
        if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $header, '/' ) . ':(.*)$/mi', $contents, $matches ) ) {
            return trim( $matches[1] );
        }

        return '';
    }
}

