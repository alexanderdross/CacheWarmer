<?php
/**
 * Admin-Menü und Page Registration.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLM_Admin {

    /**
     * Admin-Hooks registrieren.
     */
    public function init(): void {
        add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // WordPress Dashboard Widget
        add_action( 'wp_dashboard_setup', [ $this, 'register_dashboard_widget' ] );

        // AJAX-Handler registrieren
        add_action( 'wp_ajax_cwlm_export_licenses', [ $this, 'ajax_export_licenses' ] );
        add_action( 'wp_ajax_cwlm_dashboard_stats', [ $this, 'ajax_dashboard_stats' ] );
    }

    /**
     * Admin-Menü erstellen.
     */
    public function add_menu_pages(): void {
        add_menu_page(
            __( 'CacheWarmer LM', 'cwlm' ),
            __( 'CacheWarmer LM', 'cwlm' ),
            'manage_options',
            'cwlm-dashboard',
            [ $this, 'render_dashboard' ],
            'dashicons-shield',
            30
        );

        add_submenu_page(
            'cwlm-dashboard',
            __( 'Dashboard', 'cwlm' ),
            __( 'Dashboard', 'cwlm' ),
            'manage_options',
            'cwlm-dashboard',
            [ $this, 'render_dashboard' ]
        );

        add_submenu_page(
            'cwlm-dashboard',
            __( 'Lizenzen', 'cwlm' ),
            __( 'Lizenzen', 'cwlm' ),
            'manage_options',
            'cwlm-licenses',
            [ $this, 'render_licenses' ]
        );

        add_submenu_page(
            'cwlm-dashboard',
            __( 'Installationen', 'cwlm' ),
            __( 'Installationen', 'cwlm' ),
            'manage_options',
            'cwlm-installations',
            [ $this, 'render_installations' ]
        );

        add_submenu_page(
            'cwlm-dashboard',
            __( 'Audit Log', 'cwlm' ),
            __( 'Audit Log', 'cwlm' ),
            'manage_options',
            'cwlm-audit',
            [ $this, 'render_audit' ]
        );

        add_submenu_page(
            'cwlm-dashboard',
            __( 'Stripe Events', 'cwlm' ),
            __( 'Stripe Events', 'cwlm' ),
            'manage_options',
            'cwlm-stripe',
            [ $this, 'render_stripe' ]
        );

        add_submenu_page(
            'cwlm-dashboard',
            __( 'Produkte', 'cwlm' ),
            __( 'Produkte', 'cwlm' ),
            'manage_options',
            'cwlm-products',
            [ $this, 'render_products' ]
        );

        add_submenu_page(
            'cwlm-dashboard',
            __( 'Einstellungen', 'cwlm' ),
            __( 'Einstellungen', 'cwlm' ),
            'manage_options',
            'cwlm-settings',
            [ $this, 'render_settings' ]
        );
    }

    /**
     * Admin-Assets laden.
     */
    public function enqueue_assets( string $hook ): void {
        if ( ! str_starts_with( $hook, 'toplevel_page_cwlm' ) && ! str_contains( $hook, 'cwlm-' ) ) {
            return;
        }

        wp_enqueue_style(
            'cwlm-admin',
            CWLM_PLUGIN_URL . 'admin/css/cwlm-admin.css',
            [],
            CWLM_VERSION
        );

        wp_enqueue_script(
            'cwlm-admin',
            CWLM_PLUGIN_URL . 'admin/js/cwlm-admin.js',
            [ 'jquery' ],
            CWLM_VERSION,
            true
        );

        // Chart.js für Dashboard (lokal gebundled statt CDN)
        if ( str_contains( $hook, 'cwlm-dashboard' ) || str_starts_with( $hook, 'toplevel_page_cwlm' ) ) {
            wp_enqueue_script(
                'chartjs',
                CWLM_PLUGIN_URL . 'admin/js/chart.min.js',
                [],
                '4.4.0',
                true
            );

            wp_enqueue_script(
                'cwlm-dashboard',
                CWLM_PLUGIN_URL . 'admin/js/cwlm-dashboard.js',
                [ 'jquery', 'chartjs' ],
                CWLM_VERSION,
                true
            );
        }

        wp_localize_script( 'cwlm-admin', 'cwlm', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'cwlm_admin' ),
            'rest_url' => rest_url( 'cwlm/v1/' ),
        ] );
    }

    public function render_dashboard(): void {
        if ( file_exists( CWLM_PLUGIN_DIR . 'admin/views/dashboard.php' ) ) {
            include CWLM_PLUGIN_DIR . 'admin/views/dashboard.php';
        } else {
            echo '<div class="wrap"><h1>CacheWarmer License Manager – Dashboard</h1><p>Dashboard-View wird geladen...</p></div>';
        }
    }

    public function render_licenses(): void {
        if ( file_exists( CWLM_PLUGIN_DIR . 'admin/views/licenses.php' ) ) {
            include CWLM_PLUGIN_DIR . 'admin/views/licenses.php';
        } else {
            echo '<div class="wrap"><h1>Lizenzen</h1><p>Lizenzverwaltung wird geladen...</p></div>';
        }
    }

    public function render_installations(): void {
        if ( file_exists( CWLM_PLUGIN_DIR . 'admin/views/installations.php' ) ) {
            include CWLM_PLUGIN_DIR . 'admin/views/installations.php';
        } else {
            echo '<div class="wrap"><h1>Installationen</h1><p>Installationsübersicht wird geladen...</p></div>';
        }
    }

    public function render_audit(): void {
        if ( file_exists( CWLM_PLUGIN_DIR . 'admin/views/audit-log.php' ) ) {
            include CWLM_PLUGIN_DIR . 'admin/views/audit-log.php';
        } else {
            echo '<div class="wrap"><h1>Audit Log</h1><p>Audit-Log wird geladen...</p></div>';
        }
    }

    public function render_stripe(): void {
        if ( file_exists( CWLM_PLUGIN_DIR . 'admin/views/stripe-events.php' ) ) {
            include CWLM_PLUGIN_DIR . 'admin/views/stripe-events.php';
        } else {
            echo '<div class="wrap"><h1>Stripe Events</h1><p>Stripe Events werden geladen...</p></div>';
        }
    }

    public function render_products(): void {
        if ( file_exists( CWLM_PLUGIN_DIR . 'admin/views/products.php' ) ) {
            include CWLM_PLUGIN_DIR . 'admin/views/products.php';
        } else {
            echo '<div class="wrap"><h1>Produkte</h1><p>Produktverwaltung wird geladen...</p></div>';
        }
    }

    public function render_settings(): void {
        if ( file_exists( CWLM_PLUGIN_DIR . 'admin/views/settings.php' ) ) {
            include CWLM_PLUGIN_DIR . 'admin/views/settings.php';
        } else {
            echo '<div class="wrap"><h1>Einstellungen</h1><p>Einstellungen werden geladen...</p></div>';
        }
    }

    /**
     * WordPress Dashboard Widget registrieren.
     */
    public function register_dashboard_widget(): void {
        wp_add_dashboard_widget(
            'cwlm_quick_links',
            __( 'CacheWarmer License Manager', 'cwlm' ),
            [ $this, 'render_dashboard_widget' ]
        );
    }

    /**
     * Dashboard Widget: Quick Links + Mini-KPIs.
     */
    public function render_dashboard_widget(): void {
        try {
            $this->render_dashboard_widget_content();
        } catch ( \Throwable $e ) {
            echo '<p>' . esc_html__( 'Widget konnte nicht geladen werden.', 'cwlm' ) . '</p>';
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                echo '<p><small>' . esc_html( $e->getMessage() ) . '</small></p>';
            }
        }
    }

    /**
     * Dashboard Widget Inhalt rendern (intern).
     */
    private function render_dashboard_widget_content(): void {
        // Transient-Cache: Widget-KPIs nur alle 10 Minuten abfragen
        $cache_key   = 'cwlm_widget_kpis';
        $widget_data = get_transient( $cache_key );

        if ( false === $widget_data ) {
            global $wpdb;
            $prefix = $wpdb->prefix . CWLM_DB_PREFIX;

            // Prüfe ob Tabellen existieren
            $table_licenses      = $wpdb->get_var( "SHOW TABLES LIKE '" . esc_sql( $prefix . 'licenses' ) . "'" );
            $table_installations = $wpdb->get_var( "SHOW TABLES LIKE '" . esc_sql( $prefix . 'installations' ) . "'" );

            $active_val   = 0;
            $installs_val = 0;
            $expiring_val = 0;

            if ( $table_licenses ) {
                $row = $wpdb->get_row( $wpdb->prepare(
                    "SELECT
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_licenses,
                        SUM(CASE WHEN status = 'active' AND expires_at BETWEEN %s AND %s THEN 1 ELSE 0 END) AS expiring_soon
                     FROM {$prefix}licenses",
                    gmdate( 'Y-m-d H:i:s' ),
                    gmdate( 'Y-m-d H:i:s', strtotime( '+7 days' ) )
                ) );

                if ( $row ) {
                    $active_val   = (int) $row->active_licenses;
                    $expiring_val = (int) $row->expiring_soon;
                }
            }

            if ( $table_installations ) {
                $installs_val = (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$prefix}installations WHERE is_active = 1"
                );
            }

            $widget_data = [
                'active'   => $active_val,
                'installs' => $installs_val,
                'expiring' => $expiring_val,
            ];
            set_transient( $cache_key, $widget_data, 600 ); // 10 Minuten
        }

        $active   = $widget_data['active'];
        $installs = $widget_data['installs'];
        $expiring = $widget_data['expiring'];

        $links = [
            [
                'slug'    => 'cwlm-dashboard',
                'label'   => __( 'Dashboard', 'cwlm' ),
                'icon'    => 'dashicons-chart-area',
                'desc'    => __( 'KPIs & Charts', 'cwlm' ),
            ],
            [
                'slug'    => 'cwlm-licenses',
                'label'   => __( 'Lizenzen', 'cwlm' ),
                'icon'    => 'dashicons-admin-network',
                'desc'    => sprintf( __( '%d aktiv', 'cwlm' ), $active ),
                'badge'   => $active,
            ],
            [
                'slug'    => 'cwlm-installations',
                'label'   => __( 'Installationen', 'cwlm' ),
                'icon'    => 'dashicons-desktop',
                'desc'    => sprintf( __( '%d aktiv', 'cwlm' ), $installs ),
                'badge'   => $installs,
            ],
            [
                'slug'    => 'cwlm-audit',
                'label'   => __( 'Audit Log', 'cwlm' ),
                'icon'    => 'dashicons-list-view',
                'desc'    => __( 'Aktivitäten', 'cwlm' ),
            ],
            [
                'slug'    => 'cwlm-stripe',
                'label'   => __( 'Stripe Events', 'cwlm' ),
                'icon'    => 'dashicons-money-alt',
                'desc'    => __( 'Webhooks', 'cwlm' ),
            ],
            [
                'slug'    => 'cwlm-products',
                'label'   => __( 'Produkte', 'cwlm' ),
                'icon'    => 'dashicons-tag',
                'desc'    => __( 'Stripe Mapping', 'cwlm' ),
            ],
            [
                'slug'    => 'cwlm-settings',
                'label'   => __( 'Einstellungen', 'cwlm' ),
                'icon'    => 'dashicons-admin-generic',
                'desc'    => __( 'Konfiguration', 'cwlm' ),
            ],
        ];
        ?>
        <style>
            .cwlm-widget-kpis {
                display: flex;
                gap: 12px;
                margin-bottom: 14px;
                padding-bottom: 14px;
                border-bottom: 1px solid #e2e4e7;
            }
            .cwlm-widget-kpi {
                flex: 1;
                text-align: center;
                padding: 8px 4px;
                background: #f6f7f7;
                border-radius: 4px;
            }
            .cwlm-widget-kpi .cwlm-wk-value {
                font-size: 22px;
                font-weight: 700;
                line-height: 1.2;
                color: #1d2327;
            }
            .cwlm-widget-kpi .cwlm-wk-label {
                font-size: 11px;
                color: #646970;
            }
            .cwlm-widget-kpi.cwlm-wk-warn .cwlm-wk-value {
                color: #dba617;
            }
            .cwlm-widget-links {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 6px;
            }
            .cwlm-widget-link {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 10px;
                background: #f6f7f7;
                border-radius: 4px;
                text-decoration: none;
                color: #1d2327;
                transition: background 0.15s;
            }
            .cwlm-widget-link:hover {
                background: #e2e4e7;
                color: #0073aa;
            }
            .cwlm-widget-link .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
                color: #646970;
            }
            .cwlm-widget-link:hover .dashicons {
                color: #0073aa;
            }
            .cwlm-widget-link-text {
                flex: 1;
                line-height: 1.3;
            }
            .cwlm-widget-link-label {
                font-weight: 600;
                font-size: 13px;
            }
            .cwlm-widget-link-desc {
                font-size: 11px;
                color: #646970;
            }
        </style>

        <div class="cwlm-widget-kpis">
            <div class="cwlm-widget-kpi">
                <div class="cwlm-wk-value"><?php echo esc_html( $active ); ?></div>
                <div class="cwlm-wk-label"><?php esc_html_e( 'Lizenzen', 'cwlm' ); ?></div>
            </div>
            <div class="cwlm-widget-kpi">
                <div class="cwlm-wk-value"><?php echo esc_html( $installs ); ?></div>
                <div class="cwlm-wk-label"><?php esc_html_e( 'Installs', 'cwlm' ); ?></div>
            </div>
            <div class="cwlm-widget-kpi <?php echo $expiring > 0 ? 'cwlm-wk-warn' : ''; ?>">
                <div class="cwlm-wk-value"><?php echo esc_html( $expiring ); ?></div>
                <div class="cwlm-wk-label"><?php esc_html_e( 'Ablaufend', 'cwlm' ); ?></div>
            </div>
        </div>

        <div class="cwlm-widget-links">
            <?php foreach ( $links as $link ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $link['slug'] ) ); ?>" class="cwlm-widget-link">
                    <span class="dashicons <?php echo esc_attr( $link['icon'] ); ?>"></span>
                    <span class="cwlm-widget-link-text">
                        <span class="cwlm-widget-link-label"><?php echo esc_html( $link['label'] ); ?></span>
                        <br><span class="cwlm-widget-link-desc"><?php echo esc_html( $link['desc'] ); ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * AJAX: Lizenzen als CSV exportieren.
     */
    public function ajax_export_licenses(): void {
        check_ajax_referer( 'cwlm_admin', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( -1 );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . CWLM_DB_PREFIX;

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=cwlm-licenses-' . gmdate( 'Y-m-d' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, [ 'License Key', 'Email', 'Name', 'Tier', 'Plan', 'Status', 'Max Sites', 'Active Sites', 'Expires', 'Created' ] );

        // Paginierte Abfrage um Speicherverbrauch bei großen Datenmengen zu begrenzen
        $batch_size = 500;
        $offset     = 0;

        do {
            $licenses = $wpdb->get_results( $wpdb->prepare(
                "SELECT license_key, customer_email, customer_name, tier, plan, status,
                        max_sites, active_sites, expires_at, created_at
                 FROM {$prefix}licenses ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $batch_size,
                $offset
            ) );

            foreach ( $licenses as $license ) {
                fputcsv( $output, (array) $license );
            }

            $offset += $batch_size;
        } while ( count( $licenses ) === $batch_size );

        fclose( $output );
        wp_die();
    }

    /**
     * AJAX: Dashboard-Statistiken als JSON.
     */
    public function ajax_dashboard_stats(): void {
        check_ajax_referer( 'cwlm_admin', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized', 403 );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . CWLM_DB_PREFIX;

        $stats = [
            'total_licenses'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}licenses" ),
            'active_licenses' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}licenses WHERE status = %s", 'active' ) ),
            'active_installs' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}installations WHERE is_active = 1" ),
            'revenue_tiers'   => $wpdb->get_results(
                "SELECT tier, COUNT(*) as count FROM {$prefix}licenses WHERE status IN ('active','grace_period') GROUP BY tier",
                OBJECT_K
            ),
        ];

        wp_send_json_success( $stats );
    }
}
