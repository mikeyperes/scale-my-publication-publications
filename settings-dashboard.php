<?php
namespace smp_publications;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function add_wp_admin_settings_page(): void {
    add_options_page(
        Config::$settings_page_name,
        Config::$settings_page_name,
        Config::$settings_page_capability,
        Config::$settings_page_slug,
        __NAMESPACE__ . '\\display_wp_admin_settings_page'
    );
}
add_action( 'admin_menu', __NAMESPACE__ . '\\add_wp_admin_settings_page' );

function display_wp_admin_settings_page(): void {
    if ( ! current_user_can( Config::$settings_page_capability ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'scale-my-publication-publications' ) );
    }

    $runtime = [
        'namespace' => __NAMESPACE__,
        'slug'      => Config::$settings_page_slug,
        'github'    => Config::$github_repo,
        'version'   => Config::$plugin_version,
    ];
    ?>
    <div class="wrap" id="smp-publications-dashboard">
        <h1><?php echo esc_html( Config::$settings_page_display_title ); ?></h1>

        <div class="smp-pub-hero">
            <p class="smp-pub-kicker">Hello World</p>
            <h2>Scale My Publication publications plugin is installed.</h2>
            <p>This is the clean foundation for publication-specific tooling. It currently only confirms bootstrap, admin routing, GitHub updater config, and namespace wiring.</p>
        </div>

        <div class="smp-pub-grid">
            <div class="smp-pub-card">
                <h3>Runtime</h3>
                <table class="widefat striped">
                    <tbody>
                    <?php foreach ( $runtime as $label => $value ) : ?>
                        <tr>
                            <th><?php echo esc_html( ucwords( str_replace( '_', ' ', $label ) ) ); ?></th>
                            <td><code><?php echo esc_html( $value ); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="smp-pub-card">
                <h3>Next Structure</h3>
                <p>Next modules should be added as focused files instead of flattening everything into the bootstrap:</p>
                <ul>
                    <li><code>src/Admin</code> for dashboard screens.</li>
                    <li><code>src/Content</code> for publication post types and metadata.</li>
                    <li><code>src/Integrations</code> for HWS, SFPF, or PR Wire bridge code.</li>
                    <li><code>src/Support</code> for shared helpers.</li>
                </ul>
            </div>
        </div>
    </div>
    <style>
        #smp-publications-dashboard {
            max-width: 1120px;
        }
        .smp-pub-hero {
            margin: 18px 0;
            padding: 28px 30px;
            border: 1px solid #dcdcde;
            border-radius: 14px;
            background:
                radial-gradient(circle at top right, rgba(34, 113, 177, 0.14), transparent 32%),
                linear-gradient(135deg, #ffffff 0%, #f6f7f7 100%);
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.05);
        }
        .smp-pub-kicker {
            margin: 0 0 8px;
            color: #2271b1;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .smp-pub-hero h2 {
            margin: 0 0 10px;
            font-size: 28px;
            line-height: 1.2;
        }
        .smp-pub-hero p:last-child {
            max-width: 720px;
            margin-bottom: 0;
            color: #50575e;
            font-size: 15px;
        }
        .smp-pub-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
        }
        .smp-pub-card {
            padding: 18px;
            border: 1px solid #dcdcde;
            border-radius: 12px;
            background: #fff;
        }
        .smp-pub-card h3 {
            margin-top: 0;
        }
        .smp-pub-card ul {
            margin-left: 18px;
            list-style: disc;
        }
    </style>
    <?php
}

