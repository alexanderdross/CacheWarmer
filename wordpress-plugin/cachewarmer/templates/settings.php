<?php
/**
 * Settings page template.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap cachewarmer-wrap">
    <h1>
        <span class="dashicons dashicons-admin-generic"></span>
        <?php esc_html_e( 'CacheWarmer Settings', 'cachewarmer' ); ?>
    </h1>

    <?php if ( isset( $_GET['settings-updated'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Settings saved.', 'cachewarmer' ); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="options.php">
        <?php settings_fields( 'cachewarmer_settings' ); ?>

        <!-- License -->
        <div class="cachewarmer-settings-section">
            <h2><?php esc_html_e( 'License', 'cachewarmer' ); ?></h2>
            <?php
            $current_tier = CacheWarmer_License::get_tier();
            $tier_labels  = array(
                'free'       => __( 'Free', 'cachewarmer' ),
                'premium'    => __( 'Premium', 'cachewarmer' ),
                'enterprise' => __( 'Enterprise', 'cachewarmer' ),
            );
            ?>
            <p class="description">
                <?php
                printf(
                    /* translators: %s: current tier name */
                    esc_html__( 'Current tier: %s', 'cachewarmer' ),
                    '<strong>' . esc_html( $tier_labels[ $current_tier ] ?? $current_tier ) . '</strong>'
                );
                ?>
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cachewarmer_license_key"><?php esc_html_e( 'License Key', 'cachewarmer' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="cachewarmer_license_key" name="cachewarmer_license_key"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_license_key', '' ) ); ?>"
                               class="regular-text">
                        <p class="description"><?php esc_html_e( 'Enter your license key to unlock Premium or Enterprise features.', 'cachewarmer' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- API Key -->
        <div class="cachewarmer-settings-section">
            <h2><?php esc_html_e( 'General', 'cachewarmer' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cachewarmer_api_key"><?php esc_html_e( 'API Key', 'cachewarmer' ); ?></label>
                    </th>
                    <td>
                        <input type="password" id="cachewarmer_api_key" name="cachewarmer_api_key"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_api_key', '' ) ); ?>"
                               class="regular-text">
                        <p class="description"><?php esc_html_e( 'API key for REST API authentication (Bearer token). Leave empty to require WP login only.', 'cachewarmer' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- CDN Warming -->
        <div class="cachewarmer-settings-section">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_cdn_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_cdn_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_cdn_enabled', '1' ), '1' ); ?>>
                    <?php esc_html_e( 'CDN Cache Warming', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description"><?php esc_html_e( 'Fetches each URL with desktop and mobile user-agents to warm CDN edge caches.', 'cachewarmer' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label for="cachewarmer_cdn_concurrency"><?php esc_html_e( 'Concurrency', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="number" id="cachewarmer_cdn_concurrency" name="cachewarmer_cdn_concurrency"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_cdn_concurrency', 3 ) ); ?>"
                               min="1" max="20" class="small-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="cachewarmer_cdn_timeout"><?php esc_html_e( 'Timeout (seconds)', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="number" id="cachewarmer_cdn_timeout" name="cachewarmer_cdn_timeout"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_cdn_timeout', 30 ) ); ?>"
                               min="5" max="120" class="small-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="cachewarmer_cdn_user_agent"><?php esc_html_e( 'User Agent', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="text" id="cachewarmer_cdn_user_agent" name="cachewarmer_cdn_user_agent"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_cdn_user_agent', 'Mozilla/5.0 (compatible; CacheWarmer/1.0)' ) ); ?>"
                               class="large-text">
                    </td>
                </tr>
            </table>
        </div>

        <!-- Facebook -->
        <div class="cachewarmer-settings-section">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_facebook_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_facebook_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_facebook_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Facebook Sharing Debugger', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description"><?php esc_html_e( 'Uses the Facebook Graph API to scrape/cache OG tags.', 'cachewarmer' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label for="cachewarmer_facebook_app_id"><?php esc_html_e( 'App ID', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="text" id="cachewarmer_facebook_app_id" name="cachewarmer_facebook_app_id"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_facebook_app_id', '' ) ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="cachewarmer_facebook_app_secret"><?php esc_html_e( 'App Secret', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="password" id="cachewarmer_facebook_app_secret" name="cachewarmer_facebook_app_secret"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_facebook_app_secret', '' ) ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="cachewarmer_facebook_rate_limit"><?php esc_html_e( 'Rate Limit (req/s)', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="number" id="cachewarmer_facebook_rate_limit" name="cachewarmer_facebook_rate_limit"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_facebook_rate_limit', 10 ) ); ?>"
                               min="1" max="50" class="small-text">
                    </td>
                </tr>
            </table>
        </div>

        <!-- LinkedIn -->
        <div class="cachewarmer-settings-section">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_linkedin_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_linkedin_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_linkedin_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'LinkedIn Post Inspector', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description"><?php esc_html_e( 'Triggers LinkedIn OG tag scraping via the Post Inspector API.', 'cachewarmer' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label for="cachewarmer_linkedin_session_cookie"><?php esc_html_e( 'Session Cookie (li_at)', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="password" id="cachewarmer_linkedin_session_cookie" name="cachewarmer_linkedin_session_cookie"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_linkedin_session_cookie', '' ) ); ?>"
                               class="large-text">
                        <p class="description"><?php esc_html_e( 'Extract the li_at cookie from your browser DevTools after logging in to LinkedIn.', 'cachewarmer' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cachewarmer_linkedin_delay"><?php esc_html_e( 'Delay (ms)', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="number" id="cachewarmer_linkedin_delay" name="cachewarmer_linkedin_delay"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_linkedin_delay', 5000 ) ); ?>"
                               min="1000" max="30000" class="small-text">
                    </td>
                </tr>
            </table>
        </div>

        <!-- Twitter/X -->
        <div class="cachewarmer-settings-section">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_twitter_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_twitter_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_twitter_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Twitter/X Card Validator', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description"><?php esc_html_e( 'Triggers Twitter card scraping via the Tweet Composer endpoint. No API key needed.', 'cachewarmer' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label for="cachewarmer_twitter_concurrency"><?php esc_html_e( 'Concurrency', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="number" id="cachewarmer_twitter_concurrency" name="cachewarmer_twitter_concurrency"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_twitter_concurrency', 2 ) ); ?>"
                               min="1" max="10" class="small-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="cachewarmer_twitter_delay"><?php esc_html_e( 'Delay (ms)', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="number" id="cachewarmer_twitter_delay" name="cachewarmer_twitter_delay"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_twitter_delay', 3000 ) ); ?>"
                               min="1000" max="30000" class="small-text">
                    </td>
                </tr>
            </table>
        </div>

        <!-- Google Indexing API -->
        <div class="cachewarmer-settings-section">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_google_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_google_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_google_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Google Indexing API', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description"><?php esc_html_e( 'Submit URL_UPDATED notifications to the Google Indexing API via a Service Account.', 'cachewarmer' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label for="cachewarmer_google_service_account"><?php esc_html_e( 'Service Account JSON', 'cachewarmer' ); ?></label></th>
                    <td>
                        <textarea id="cachewarmer_google_service_account" name="cachewarmer_google_service_account"
                                  rows="6" class="large-text code"><?php echo esc_textarea( get_option( 'cachewarmer_google_service_account', '' ) ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Paste the full JSON content of your Google Service Account key file.', 'cachewarmer' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cachewarmer_google_daily_quota"><?php esc_html_e( 'Daily Quota', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="number" id="cachewarmer_google_daily_quota" name="cachewarmer_google_daily_quota"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_google_daily_quota', 200 ) ); ?>"
                               min="1" max="10000" class="small-text">
                    </td>
                </tr>
            </table>
        </div>

        <!-- Bing Webmaster -->
        <div class="cachewarmer-settings-section">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_bing_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_bing_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_bing_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Bing Webmaster Tools', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description"><?php esc_html_e( 'Submit URLs via the Bing Webmaster URL Submission API.', 'cachewarmer' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label for="cachewarmer_bing_api_key"><?php esc_html_e( 'API Key', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="password" id="cachewarmer_bing_api_key" name="cachewarmer_bing_api_key"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_bing_api_key', '' ) ); ?>"
                               class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="cachewarmer_bing_daily_quota"><?php esc_html_e( 'Daily Quota', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="number" id="cachewarmer_bing_daily_quota" name="cachewarmer_bing_daily_quota"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_bing_daily_quota', 10000 ) ); ?>"
                               min="1" max="100000" class="small-text">
                    </td>
                </tr>
            </table>
        </div>

        <!-- IndexNow -->
        <div class="cachewarmer-settings-section">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_indexnow_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_indexnow_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_indexnow_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'IndexNow Protocol', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description"><?php esc_html_e( 'Submit URLs via the IndexNow protocol (supports Bing, Yandex, Seznam, Naver).', 'cachewarmer' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label for="cachewarmer_indexnow_key"><?php esc_html_e( 'IndexNow Key', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="text" id="cachewarmer_indexnow_key" name="cachewarmer_indexnow_key"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_indexnow_key', '' ) ); ?>"
                               class="regular-text">
                        <p class="description"><?php esc_html_e( 'Self-generated key. Must also be hosted as a .txt file on your site.', 'cachewarmer' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cachewarmer_indexnow_key_location"><?php esc_html_e( 'Key Location URL', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="url" id="cachewarmer_indexnow_key_location" name="cachewarmer_indexnow_key_location"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_indexnow_key_location', '' ) ); ?>"
                               class="regular-text"
                               placeholder="https://example.com/your-key.txt">
                    </td>
                </tr>
            </table>
        </div>

        <!-- Scheduler -->
        <div class="cachewarmer-settings-section">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_scheduler_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_scheduler_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_scheduler_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Scheduled Warming', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description"><?php esc_html_e( 'Automatically warm all registered sitemaps on a schedule via WP-Cron.', 'cachewarmer' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label for="cachewarmer_scheduler_cron"><?php esc_html_e( 'Schedule', 'cachewarmer' ); ?></label></th>
                    <td>
                        <select id="cachewarmer_scheduler_cron" name="cachewarmer_scheduler_cron">
                            <?php
                            $current = get_option( 'cachewarmer_scheduler_cron', 'daily' );
                            $options = array(
                                'hourly'        => __( 'Hourly', 'cachewarmer' ),
                                'every_6_hours' => __( 'Every 6 Hours', 'cachewarmer' ),
                                'every_12_hours'=> __( 'Every 12 Hours', 'cachewarmer' ),
                                'daily'         => __( 'Daily', 'cachewarmer' ),
                                'weekly'        => __( 'Weekly', 'cachewarmer' ),
                            );
                            foreach ( $options as $value => $label ) :
                            ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Logging -->
        <div class="cachewarmer-settings-section">
            <h2><?php esc_html_e( 'Logging', 'cachewarmer' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th><label for="cachewarmer_log_level"><?php esc_html_e( 'Log Level', 'cachewarmer' ); ?></label></th>
                    <td>
                        <select id="cachewarmer_log_level" name="cachewarmer_log_level">
                            <?php
                            $current_level = get_option( 'cachewarmer_log_level', 'info' );
                            $levels        = array( 'debug', 'info', 'warn', 'error' );
                            foreach ( $levels as $level ) :
                            ?>
                                <option value="<?php echo esc_attr( $level ); ?>" <?php selected( $current_level, $level ); ?>>
                                    <?php echo esc_html( ucfirst( $level ) ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Auto-Warm on Publish -->
        <div class="cachewarmer-settings-section">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_auto_warm_on_publish" value="0">
                    <input type="checkbox" name="cachewarmer_auto_warm_on_publish" value="1"
                        <?php checked( get_option( 'cachewarmer_auto_warm_on_publish', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Auto-Warm on Publish', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description">
                <?php esc_html_e( 'Automatically warm cache when a post or page is published. Requires Premium or Enterprise license.', 'cachewarmer' ); ?>
                <?php if ( ! CacheWarmer_License::is_premium_or_above() ) : ?>
                    <br><em><?php esc_html_e( 'This feature requires a Premium or Enterprise license.', 'cachewarmer' ); ?></em>
                <?php endif; ?>
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Targets', 'cachewarmer' ); ?></th>
                    <td>
                        <?php
                        $auto_warm_targets  = get_option( 'cachewarmer_auto_warm_targets', array( 'cdn', 'facebook', 'linkedin', 'twitter' ) );
                        if ( is_string( $auto_warm_targets ) ) {
                            $auto_warm_targets = array_filter( array_map( 'trim', explode( ',', $auto_warm_targets ) ) );
                        }
                        $available_targets = array(
                            'cdn'      => 'CDN',
                            'facebook' => 'Facebook',
                            'linkedin' => 'LinkedIn',
                            'twitter'  => 'Twitter/X',
                            'google'   => 'Google',
                            'bing'     => 'Bing',
                            'indexnow' => 'IndexNow',
                        );
                        foreach ( $available_targets as $key => $label ) :
                        ?>
                            <label style="margin-right: 12px;">
                                <input type="checkbox" name="cachewarmer_auto_warm_targets[]" value="<?php echo esc_attr( $key ); ?>"
                                    <?php checked( in_array( $key, $auto_warm_targets, true ) ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </label>
                        <?php endforeach; ?>
                        <p class="description"><?php esc_html_e( 'Select which targets to warm when a post is published.', 'cachewarmer' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- URL Exclude Patterns -->
        <div class="cachewarmer-settings-section">
            <h2><?php esc_html_e( 'URL Exclude Patterns', 'cachewarmer' ); ?></h2>
            <p class="description"><?php esc_html_e( 'URLs matching these patterns will be skipped during warming. One pattern per line. Supports wildcard (*) matching.', 'cachewarmer' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cachewarmer_exclude_patterns"><?php esc_html_e( 'Exclude Patterns', 'cachewarmer' ); ?></label>
                    </th>
                    <td>
                        <textarea id="cachewarmer_exclude_patterns" name="cachewarmer_exclude_patterns"
                                  rows="6" class="large-text code"
                                  placeholder="/tag/*&#10;/author/*&#10;*.pdf&#10;/wp-admin/*"><?php echo esc_textarea( get_option( 'cachewarmer_exclude_patterns', '' ) ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Examples: /tag/*, /author/*, *.pdf, /wp-admin/*', 'cachewarmer' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Email Notifications -->
        <div class="cachewarmer-settings-section">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_email_notifications" value="0">
                    <input type="checkbox" name="cachewarmer_email_notifications" value="1"
                        <?php checked( get_option( 'cachewarmer_email_notifications', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Email Notifications', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description">
                <?php esc_html_e( 'Receive email notifications when warming jobs complete or fail.', 'cachewarmer' ); ?>
                <?php if ( ! CacheWarmer_License::is_premium_or_above() ) : ?>
                    <br><em><?php esc_html_e( 'This feature requires a Premium or Enterprise license.', 'cachewarmer' ); ?></em>
                <?php endif; ?>
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cachewarmer_notification_email"><?php esc_html_e( 'Notification Email', 'cachewarmer' ); ?></label>
                    </th>
                    <td>
                        <input type="email" id="cachewarmer_notification_email" name="cachewarmer_notification_email"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_notification_email', get_option( 'admin_email' ) ) ); ?>"
                               class="regular-text">
                        <p class="description"><?php esc_html_e( 'Email address to receive notifications. Defaults to the admin email.', 'cachewarmer' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Webhook Notifications -->
        <div class="cachewarmer-settings-section">
            <h2><?php esc_html_e( 'Webhook Notifications', 'cachewarmer' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Send event notifications to an external webhook URL.', 'cachewarmer' ); ?>
                <?php if ( ! CacheWarmer_License::is_enterprise() ) : ?>
                    <br><em><?php esc_html_e( 'This feature requires an Enterprise license.', 'cachewarmer' ); ?></em>
                <?php endif; ?>
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cachewarmer_webhook_url"><?php esc_html_e( 'Webhook URL', 'cachewarmer' ); ?></label>
                    </th>
                    <td>
                        <input type="url" id="cachewarmer_webhook_url" name="cachewarmer_webhook_url"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_webhook_url', '' ) ); ?>"
                               class="regular-text"
                               placeholder="https://example.com/webhook"
                               <?php echo ! CacheWarmer_License::is_enterprise() ? 'disabled' : ''; ?>>
                        <p class="description"><?php esc_html_e( 'JSON payloads will be POSTed to this URL for events like job.completed and job.failed.', 'cachewarmer' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button( __( 'Save Settings', 'cachewarmer' ) ); ?>
    </form>
</div>
