<?php
/**
 * Settings page template.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$cw_is_free       = ! CacheWarmer_License::is_premium_or_above();
$cw_is_not_ent    = ! CacheWarmer_License::is_enterprise();
$cw_pricing_url   = 'https://cachewarmer.drossmedia.de/pricing/';
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
                &mdash;
                <?php
                printf(
                    /* translators: %s: opening link tag, %s: closing link tag */
                    esc_html__( 'Upgrade or purchase a license at %1$scachewarmer.drossmedia.de%2$s', 'cachewarmer' ),
                    '<a href="https://cachewarmer.drossmedia.de" target="_blank" rel="noopener">',
                    '</a>'
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
                               class="regular-text"
                               placeholder="CW-PRO-XXXXXXXXXXXXXXXX">
                        <p class="description">
                            <?php esc_html_e( 'Enter the license key you received after purchase to unlock Premium or Enterprise features.', 'cachewarmer' ); ?>
                            <br>
                            <?php
                            printf(
                                /* translators: %s: link to purchase page */
                                esc_html__( 'Don\'t have a key yet? %1$sGet one here%2$s.', 'cachewarmer' ),
                                '<a href="https://cachewarmer.drossmedia.de" target="_blank" rel="noopener">',
                                '</a>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- API Key -->
        <div class="cachewarmer-settings-section">
            <h2><?php esc_html_e( 'General', 'cachewarmer' ); ?></h2>
            <p class="description"><?php esc_html_e( 'General settings that apply to CacheWarmer as a whole.', 'cachewarmer' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cachewarmer_api_key"><?php esc_html_e( 'API Key', 'cachewarmer' ); ?></label>
                    </th>
                    <td>
                        <input type="password" id="cachewarmer_api_key" name="cachewarmer_api_key"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_api_key', '' ) ); ?>"
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e( 'A secret password that protects the CacheWarmer REST API so only authorized tools or scripts can trigger warming remotely.', 'cachewarmer' ); ?>
                            <br>
                            <?php esc_html_e( 'Leave empty if you only want to use CacheWarmer from within the WordPress admin area.', 'cachewarmer' ); ?>
                        </p>
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
            <p class="description">
                <?php esc_html_e( 'Visits every page on your site in the background so that your CDN (e.g. Cloudflare, Fastly) stores a cached copy. This means real visitors will load pages much faster because the content is already prepared.', 'cachewarmer' ); ?>
            </p>
            <p class="description cw-help-note">
                <?php esc_html_e( 'No additional API keys needed. CacheWarmer simply visits each URL like a regular browser would.', 'cachewarmer' ); ?>
            </p>
            <table class="form-table">
                <tr>
                    <th><label for="cachewarmer_cdn_concurrency"><?php esc_html_e( 'Concurrency', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="number" id="cachewarmer_cdn_concurrency" name="cachewarmer_cdn_concurrency"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_cdn_concurrency', 3 ) ); ?>"
                               min="1" max="20" class="small-text">
                        <p class="description"><?php esc_html_e( 'How many pages to warm at the same time. Higher = faster, but uses more server resources. Start with 3 and increase if your server can handle it.', 'cachewarmer' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cachewarmer_cdn_timeout"><?php esc_html_e( 'Timeout (seconds)', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="number" id="cachewarmer_cdn_timeout" name="cachewarmer_cdn_timeout"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_cdn_timeout', 30 ) ); ?>"
                               min="5" max="120" class="small-text">
                        <p class="description"><?php esc_html_e( 'Maximum seconds to wait for a page to load before skipping it. Increase this if you have slow pages.', 'cachewarmer' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cachewarmer_cdn_user_agent"><?php esc_html_e( 'User Agent', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="text" id="cachewarmer_cdn_user_agent" name="cachewarmer_cdn_user_agent"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_cdn_user_agent', 'Mozilla/5.0 (compatible; CacheWarmer/1.0)' ) ); ?>"
                               class="large-text">
                        <p class="description"><?php esc_html_e( 'The browser identity CacheWarmer uses when visiting your pages. The default works fine for most setups.', 'cachewarmer' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Facebook (Premium) -->
        <div class="cachewarmer-settings-section<?php echo $cw_is_free ? ' cw-pro-locked' : ''; ?>">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_facebook_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_facebook_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_facebook_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Facebook Sharing Debugger', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description">
                <?php esc_html_e( 'When someone shares your page on Facebook, it shows a preview with a title, description and image. This feature tells Facebook to fetch those details in advance, so the preview is always correct and up-to-date.', 'cachewarmer' ); ?>
            </p>
            <p class="description cw-tool-link">
                <span class="dashicons dashicons-external"></span>
                <?php
                printf(
                    esc_html__( 'Test manually: %1$sFacebook Sharing Debugger%2$s — paste any URL to check its preview.', 'cachewarmer' ),
                    '<a href="https://developers.facebook.com/tools/debug/" target="_blank" rel="noopener">',
                    '</a>'
                );
                ?>
            </p>
            <div class="cw-locked-content">
                <table class="form-table">
                    <tr>
                        <th><label for="cachewarmer_facebook_app_id"><?php esc_html_e( 'App ID', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="text" id="cachewarmer_facebook_app_id" name="cachewarmer_facebook_app_id"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_facebook_app_id', '' ) ); ?>"
                                   class="regular-text">
                            <p class="description">
                                <?php
                                printf(
                                    esc_html__( 'Create a free Facebook App at %1$sdevelopers.facebook.com%2$s and copy the App ID here.', 'cachewarmer' ),
                                    '<a href="https://developers.facebook.com" target="_blank" rel="noopener">',
                                    '</a>'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cachewarmer_facebook_app_secret"><?php esc_html_e( 'App Secret', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="password" id="cachewarmer_facebook_app_secret" name="cachewarmer_facebook_app_secret"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_facebook_app_secret', '' ) ); ?>"
                                   class="regular-text">
                            <p class="description"><?php esc_html_e( 'Found in your Facebook App dashboard under Settings > Basic. Keep this secret — do not share it publicly.', 'cachewarmer' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cachewarmer_facebook_rate_limit"><?php esc_html_e( 'Rate Limit (req/s)', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="number" id="cachewarmer_facebook_rate_limit" name="cachewarmer_facebook_rate_limit"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_facebook_rate_limit', 10 ) ); ?>"
                                   min="1" max="50" class="small-text">
                            <p class="description"><?php esc_html_e( 'Maximum requests per second to the Facebook API. Keep this at 10 or lower to avoid being blocked.', 'cachewarmer' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php if ( $cw_is_free ) : ?>
                <div class="cw-pro-upgrade-overlay">
                    <span class="dashicons dashicons-lock"></span>
                    <strong><?php esc_html_e( 'Premium Feature', 'cachewarmer' ); ?></strong>
                    <p><?php esc_html_e( 'Social media cache warming keeps your Facebook, LinkedIn and Twitter link previews always up-to-date.', 'cachewarmer' ); ?></p>
                    <a href="<?php echo esc_url( $cw_pricing_url ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Upgrade to Premium', 'cachewarmer' ); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- LinkedIn (Premium) -->
        <div class="cachewarmer-settings-section<?php echo $cw_is_free ? ' cw-pro-locked' : ''; ?>">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_linkedin_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_linkedin_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_linkedin_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'LinkedIn Post Inspector', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description">
                <?php esc_html_e( 'Ensures that the link preview shown when sharing your page on LinkedIn is up-to-date. CacheWarmer tells LinkedIn to re-read your page\'s title, description and image.', 'cachewarmer' ); ?>
            </p>
            <p class="description cw-tool-link">
                <span class="dashicons dashicons-external"></span>
                <?php
                printf(
                    esc_html__( 'Test manually: %1$sLinkedIn Post Inspector%2$s — paste any URL to refresh its LinkedIn preview.', 'cachewarmer' ),
                    '<a href="https://www.linkedin.com/post-inspector/" target="_blank" rel="noopener">',
                    '</a>'
                );
                ?>
            </p>
            <div class="cw-locked-content">
                <table class="form-table">
                    <tr>
                        <th><label for="cachewarmer_linkedin_session_cookie"><?php esc_html_e( 'Session Cookie (li_at)', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="password" id="cachewarmer_linkedin_session_cookie" name="cachewarmer_linkedin_session_cookie"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_linkedin_session_cookie', '' ) ); ?>"
                                   class="large-text">
                            <p class="description">
                                <?php esc_html_e( 'This is a login token from your LinkedIn account. To find it:', 'cachewarmer' ); ?>
                                <br>
                                <?php esc_html_e( '1. Log in to LinkedIn in your browser.', 'cachewarmer' ); ?>
                                <br>
                                <?php esc_html_e( '2. Open the browser developer tools (F12 or right-click > "Inspect").', 'cachewarmer' ); ?>
                                <br>
                                <?php esc_html_e( '3. Go to Application > Cookies > linkedin.com and copy the value of the "li_at" cookie.', 'cachewarmer' ); ?>
                                <br>
                                <em><?php esc_html_e( 'Note: This cookie expires periodically. You may need to update it from time to time.', 'cachewarmer' ); ?></em>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cachewarmer_linkedin_delay"><?php esc_html_e( 'Delay (ms)', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="number" id="cachewarmer_linkedin_delay" name="cachewarmer_linkedin_delay"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_linkedin_delay', 5000 ) ); ?>"
                                   min="1000" max="30000" class="small-text">
                            <p class="description"><?php esc_html_e( 'Waiting time in milliseconds between requests. LinkedIn is strict about rate limits, so keep this at 5000 ms or higher.', 'cachewarmer' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php if ( $cw_is_free ) : ?>
                <div class="cw-pro-upgrade-overlay">
                    <span class="dashicons dashicons-lock"></span>
                    <strong><?php esc_html_e( 'Premium Feature', 'cachewarmer' ); ?></strong>
                    <p><?php esc_html_e( 'Social media cache warming keeps your link previews always fresh and accurate.', 'cachewarmer' ); ?></p>
                    <a href="<?php echo esc_url( $cw_pricing_url ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Upgrade to Premium', 'cachewarmer' ); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Twitter/X (Premium) -->
        <div class="cachewarmer-settings-section<?php echo $cw_is_free ? ' cw-pro-locked' : ''; ?>">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_twitter_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_twitter_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_twitter_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Twitter/X Card Validator', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description">
                <?php esc_html_e( 'Updates the link preview (Twitter Card) that appears when your page is shared on Twitter/X. No API key is needed — this works automatically through the public Tweet Composer.', 'cachewarmer' ); ?>
            </p>
            <p class="description cw-tool-link">
                <span class="dashicons dashicons-external"></span>
                <?php
                printf(
                    esc_html__( 'Test manually: %1$sX (Twitter) Card Validator%2$s — paste any URL to preview its Twitter Card.', 'cachewarmer' ),
                    '<a href="https://cards-dev.twitter.com/validator" target="_blank" rel="noopener">',
                    '</a>'
                );
                ?>
            </p>
            <div class="cw-locked-content">
                <table class="form-table">
                    <tr>
                        <th><label for="cachewarmer_twitter_concurrency"><?php esc_html_e( 'Concurrency', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="number" id="cachewarmer_twitter_concurrency" name="cachewarmer_twitter_concurrency"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_twitter_concurrency', 2 ) ); ?>"
                                   min="1" max="10" class="small-text">
                            <p class="description"><?php esc_html_e( 'How many pages to process at once. Keep this at 2 or lower to avoid being rate-limited by Twitter.', 'cachewarmer' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cachewarmer_twitter_delay"><?php esc_html_e( 'Delay (ms)', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="number" id="cachewarmer_twitter_delay" name="cachewarmer_twitter_delay"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_twitter_delay', 3000 ) ); ?>"
                                   min="1000" max="30000" class="small-text">
                            <p class="description"><?php esc_html_e( 'Waiting time in milliseconds between requests. A value of 3000 (3 seconds) works well.', 'cachewarmer' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php if ( $cw_is_free ) : ?>
                <div class="cw-pro-upgrade-overlay">
                    <span class="dashicons dashicons-lock"></span>
                    <strong><?php esc_html_e( 'Premium Feature', 'cachewarmer' ); ?></strong>
                    <p><?php esc_html_e( 'Keep your Twitter/X card previews always up-to-date automatically.', 'cachewarmer' ); ?></p>
                    <a href="<?php echo esc_url( $cw_pricing_url ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Upgrade to Premium', 'cachewarmer' ); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Google Indexing API (Premium) -->
        <div class="cachewarmer-settings-section<?php echo $cw_is_free ? ' cw-pro-locked' : ''; ?>">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_google_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_google_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_google_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Google Indexing API', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description">
                <?php esc_html_e( 'Tells Google directly that a page has been updated, so it gets re-crawled and re-indexed faster. This can significantly speed up how quickly your changes appear in Google search results.', 'cachewarmer' ); ?>
            </p>
            <p class="description cw-tool-link">
                <span class="dashicons dashicons-external"></span>
                <?php
                printf(
                    esc_html__( 'Test manually: %1$sGoogle Search Console%2$s — use "URL Inspection" to check indexing status of any page.', 'cachewarmer' ),
                    '<a href="https://search.google.com/search-console" target="_blank" rel="noopener">',
                    '</a>'
                );
                ?>
            </p>
            <div class="cw-locked-content">
                <table class="form-table">
                    <tr>
                        <th><label for="cachewarmer_google_service_account"><?php esc_html_e( 'Service Account JSON', 'cachewarmer' ); ?></label></th>
                        <td>
                            <textarea id="cachewarmer_google_service_account" name="cachewarmer_google_service_account"
                                      rows="6" class="large-text code"><?php echo esc_textarea( get_option( 'cachewarmer_google_service_account', '' ) ); ?></textarea>
                            <p class="description">
                                <?php esc_html_e( 'Paste the contents of the JSON key file from your Google Cloud Service Account. To set this up:', 'cachewarmer' ); ?>
                                <br>
                                <?php
                                printf(
                                    esc_html__( '1. Go to %1$sGoogle Cloud Console%2$s and create a Service Account.', 'cachewarmer' ),
                                    '<a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" rel="noopener">',
                                    '</a>'
                                );
                                ?>
                                <br>
                                <?php esc_html_e( '2. Enable the "Web Search Indexing API" in the API Library.', 'cachewarmer' ); ?>
                                <br>
                                <?php esc_html_e( '3. Download the JSON key and paste it here.', 'cachewarmer' ); ?>
                                <br>
                                <?php esc_html_e( '4. Add the Service Account email as an owner in Google Search Console.', 'cachewarmer' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cachewarmer_google_daily_quota"><?php esc_html_e( 'Daily Quota', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="number" id="cachewarmer_google_daily_quota" name="cachewarmer_google_daily_quota"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_google_daily_quota', 200 ) ); ?>"
                                   min="1" max="10000" class="small-text">
                            <p class="description"><?php esc_html_e( 'Google allows 200 URL submissions per day by default. CacheWarmer stops automatically once this limit is reached.', 'cachewarmer' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php if ( $cw_is_free ) : ?>
                <div class="cw-pro-upgrade-overlay">
                    <span class="dashicons dashicons-lock"></span>
                    <strong><?php esc_html_e( 'Premium Feature', 'cachewarmer' ); ?></strong>
                    <p><?php esc_html_e( 'Notify Google, Bing and other search engines instantly when your content changes.', 'cachewarmer' ); ?></p>
                    <a href="<?php echo esc_url( $cw_pricing_url ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Upgrade to Premium', 'cachewarmer' ); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bing Webmaster (Premium) -->
        <div class="cachewarmer-settings-section<?php echo $cw_is_free ? ' cw-pro-locked' : ''; ?>">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_bing_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_bing_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_bing_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Bing Webmaster Tools', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description">
                <?php esc_html_e( 'Submits your updated pages directly to Bing so they appear in Bing search results faster. Also improves discoverability on Yahoo, DuckDuckGo and other Bing-powered search engines.', 'cachewarmer' ); ?>
            </p>
            <p class="description cw-tool-link">
                <span class="dashicons dashicons-external"></span>
                <?php
                printf(
                    esc_html__( 'Test manually: %1$sBing Webmaster Tools%2$s — submit URLs and monitor your site\'s Bing search performance.', 'cachewarmer' ),
                    '<a href="https://www.bing.com/webmasters" target="_blank" rel="noopener">',
                    '</a>'
                );
                ?>
            </p>
            <div class="cw-locked-content">
                <table class="form-table">
                    <tr>
                        <th><label for="cachewarmer_bing_api_key"><?php esc_html_e( 'API Key', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="password" id="cachewarmer_bing_api_key" name="cachewarmer_bing_api_key"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_bing_api_key', '' ) ); ?>"
                                   class="regular-text">
                            <p class="description">
                                <?php
                                printf(
                                    esc_html__( 'Get your API key from %1$sBing Webmaster Tools%2$s under Settings > API Access.', 'cachewarmer' ),
                                    '<a href="https://www.bing.com/webmasters" target="_blank" rel="noopener">',
                                    '</a>'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cachewarmer_bing_daily_quota"><?php esc_html_e( 'Daily Quota', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="number" id="cachewarmer_bing_daily_quota" name="cachewarmer_bing_daily_quota"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_bing_daily_quota', 10000 ) ); ?>"
                                   min="1" max="100000" class="small-text">
                            <p class="description"><?php esc_html_e( 'Bing allows up to 10,000 URL submissions per day. CacheWarmer stops automatically once this limit is reached.', 'cachewarmer' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php if ( $cw_is_free ) : ?>
                <div class="cw-pro-upgrade-overlay">
                    <span class="dashicons dashicons-lock"></span>
                    <strong><?php esc_html_e( 'Premium Feature', 'cachewarmer' ); ?></strong>
                    <p><?php esc_html_e( 'Submit pages directly to Bing for faster indexing.', 'cachewarmer' ); ?></p>
                    <a href="<?php echo esc_url( $cw_pricing_url ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Upgrade to Premium', 'cachewarmer' ); ?></a>
                </div>
            <?php endif; ?>
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
            <p class="description">
                <?php esc_html_e( 'IndexNow is a free, open protocol that instantly notifies multiple search engines (Bing, Yandex, Seznam, Naver and others) when your content changes. One submission reaches all participating search engines at once.', 'cachewarmer' ); ?>
            </p>
            <p class="description cw-tool-link">
                <span class="dashicons dashicons-external"></span>
                <?php
                printf(
                    esc_html__( 'Learn more: %1$sIndexNow.org%2$s — documentation and list of supported search engines.', 'cachewarmer' ),
                    '<a href="https://www.indexnow.org/" target="_blank" rel="noopener">',
                    '</a>'
                );
                ?>
            </p>
            <table class="form-table">
                <tr>
                    <th><label for="cachewarmer_indexnow_key"><?php esc_html_e( 'IndexNow Key', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="text" id="cachewarmer_indexnow_key" name="cachewarmer_indexnow_key"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_indexnow_key', '' ) ); ?>"
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e( 'A unique key that proves you own the website. You can use any random string of letters and numbers (e.g. "my-indexnow-key-12345").', 'cachewarmer' ); ?>
                            <br>
                            <?php esc_html_e( 'Important: You must also upload a text file with this key as the filename to your website root (see "Key Location URL" below).', 'cachewarmer' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label for="cachewarmer_indexnow_key_location"><?php esc_html_e( 'Key Location URL', 'cachewarmer' ); ?></label></th>
                    <td>
                        <input type="url" id="cachewarmer_indexnow_key_location" name="cachewarmer_indexnow_key_location"
                               value="<?php echo esc_attr( get_option( 'cachewarmer_indexnow_key_location', '' ) ); ?>"
                               class="regular-text"
                               placeholder="https://example.com/your-key.txt">
                        <p class="description">
                            <?php esc_html_e( 'The full URL to the .txt file containing your key, e.g. https://example.com/my-indexnow-key-12345.txt. The file must contain exactly the key string above.', 'cachewarmer' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Pinterest Rich Pin Validator (Premium) -->
        <div class="cachewarmer-settings-section<?php echo $cw_is_free ? ' cw-pro-locked' : ''; ?>">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_pinterest_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_pinterest_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_pinterest_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Pinterest Rich Pin Validator', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description">
                <?php esc_html_e( 'Refreshes the Rich Pin preview that Pinterest shows when someone pins your page. Ensures your title, description and image are always up-to-date on Pinterest.', 'cachewarmer' ); ?>
            </p>
            <p class="description cw-tool-link">
                <span class="dashicons dashicons-external"></span>
                <?php
                printf(
                    esc_html__( 'Test manually: %1$sPinterest Rich Pin Validator%2$s — paste any URL to check its Rich Pin preview.', 'cachewarmer' ),
                    '<a href="https://developers.pinterest.com/tools/url-debugger/" target="_blank" rel="noopener">',
                    '</a>'
                );
                ?>
            </p>
            <div class="cw-locked-content">
                <p class="description cw-help-note">
                    <?php esc_html_e( 'No API key required. CacheWarmer uses Pinterest\'s public Rich Pin Validator to refresh previews automatically.', 'cachewarmer' ); ?>
                </p>
            </div>
            <?php if ( $cw_is_free ) : ?>
                <div class="cw-pro-upgrade-overlay">
                    <span class="dashicons dashicons-lock"></span>
                    <strong><?php esc_html_e( 'Premium Feature', 'cachewarmer' ); ?></strong>
                    <p><?php esc_html_e( 'Keep your Pinterest Rich Pin previews always up-to-date.', 'cachewarmer' ); ?></p>
                    <a href="<?php echo esc_url( $cw_pricing_url ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Upgrade to Premium', 'cachewarmer' ); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Cloudflare Cache Purge (Enterprise) -->
        <div class="cachewarmer-settings-section<?php echo $cw_is_not_ent ? ' cw-ent-locked' : ''; ?>">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_cloudflare_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_cloudflare_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_cloudflare_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Cloudflare Cache Purge', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description">
                <?php esc_html_e( 'Purges specific URLs from Cloudflare\'s CDN cache via their API before re-warming them. This ensures visitors always get the latest version of your pages instead of a stale cached copy.', 'cachewarmer' ); ?>
            </p>
            <p class="description cw-tool-link">
                <span class="dashicons dashicons-external"></span>
                <?php
                printf(
                    esc_html__( 'Get credentials: %1$sCloudflare Dashboard%2$s — go to your domain > Overview to find your Zone ID, then create an API Token under My Profile > API Tokens.', 'cachewarmer' ),
                    '<a href="https://dash.cloudflare.com" target="_blank" rel="noopener">',
                    '</a>'
                );
                ?>
            </p>
            <div class="cw-locked-content">
                <table class="form-table">
                    <tr>
                        <th><label for="cachewarmer_cloudflare_api_token"><?php esc_html_e( 'API Token', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="password" id="cachewarmer_cloudflare_api_token" name="cachewarmer_cloudflare_api_token"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_cloudflare_api_token', '' ) ); ?>"
                                   class="regular-text"
                                   <?php echo $cw_is_not_ent ? 'disabled' : ''; ?>>
                            <p class="description">
                                <?php esc_html_e( 'A Cloudflare API Token with the "Zone — Cache Purge — Purge" permission. To create one:', 'cachewarmer' ); ?>
                                <br>
                                <?php esc_html_e( '1. Log in to Cloudflare and go to My Profile > API Tokens.', 'cachewarmer' ); ?>
                                <br>
                                <?php esc_html_e( '2. Click "Create Token" and use the "Custom token" template.', 'cachewarmer' ); ?>
                                <br>
                                <?php esc_html_e( '3. Add permission: Zone > Cache Purge > Purge. Restrict to your specific zone.', 'cachewarmer' ); ?>
                                <br>
                                <?php esc_html_e( '4. Copy the generated token and paste it here.', 'cachewarmer' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cachewarmer_cloudflare_zone_id"><?php esc_html_e( 'Zone ID', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="text" id="cachewarmer_cloudflare_zone_id" name="cachewarmer_cloudflare_zone_id"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_cloudflare_zone_id', '' ) ); ?>"
                                   class="regular-text"
                                   placeholder="e.g. 023e105f4ecef8ad9ca31a8372d0c353"
                                   <?php echo $cw_is_not_ent ? 'disabled' : ''; ?>>
                            <p class="description">
                                <?php esc_html_e( 'Your Cloudflare Zone ID — a 32-character hex string. Find it on your domain\'s Overview page in the Cloudflare Dashboard, in the right sidebar under "API".', 'cachewarmer' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <div class="cw-api-example">
                    <p class="description"><strong><?php esc_html_e( 'API Reference:', 'cachewarmer' ); ?></strong></p>
                    <pre class="cw-code-block"><?php echo esc_html( 'POST https://api.cloudflare.com/client/v4/zones/{zone_id}/purge_cache
Authorization: Bearer {api_token}
Content-Type: application/json

{
  "files": [
    "https://example.com/page-1",
    "https://example.com/page-2"
  ]
}

// Batch limit: 30 URLs per request
// CacheWarmer handles batching automatically' ); ?></pre>
                </div>
            </div>
            <?php if ( $cw_is_not_ent ) : ?>
                <div class="cw-pro-upgrade-overlay">
                    <span class="dashicons dashicons-lock"></span>
                    <strong><?php esc_html_e( 'Enterprise Feature', 'cachewarmer' ); ?></strong>
                    <p><?php esc_html_e( 'Purge and re-warm your Cloudflare CDN cache via API for instant content updates.', 'cachewarmer' ); ?></p>
                    <a href="<?php echo esc_url( $cw_pricing_url ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Upgrade to Enterprise', 'cachewarmer' ); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Imperva (Incapsula) Cache Purge (Enterprise) -->
        <div class="cachewarmer-settings-section<?php echo $cw_is_not_ent ? ' cw-ent-locked' : ''; ?>">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_imperva_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_imperva_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_imperva_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Imperva (Incapsula) Cache Purge', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description">
                <?php esc_html_e( 'Purges cached pages from Imperva\'s Cloud WAF/CDN network. If your site is behind Imperva (formerly Incapsula), this clears old cached content so visitors see your latest changes immediately.', 'cachewarmer' ); ?>
            </p>
            <p class="description cw-tool-link">
                <span class="dashicons dashicons-external"></span>
                <?php
                printf(
                    esc_html__( 'Get credentials: %1$sImperva Cloud Security Console%2$s — go to Account Settings > API to find your API ID and API Key, and your Site ID under Sites > your site.', 'cachewarmer' ),
                    '<a href="https://my.imperva.com" target="_blank" rel="noopener">',
                    '</a>'
                );
                ?>
            </p>
            <div class="cw-locked-content">
                <table class="form-table">
                    <tr>
                        <th><label for="cachewarmer_imperva_api_id"><?php esc_html_e( 'API ID', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="text" id="cachewarmer_imperva_api_id" name="cachewarmer_imperva_api_id"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_imperva_api_id', '' ) ); ?>"
                                   class="regular-text"
                                   <?php echo $cw_is_not_ent ? 'disabled' : ''; ?>>
                            <p class="description">
                                <?php esc_html_e( 'Your Imperva API ID (numeric). Found under Account Settings > API in the Imperva Console.', 'cachewarmer' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cachewarmer_imperva_api_key"><?php esc_html_e( 'API Key', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="password" id="cachewarmer_imperva_api_key" name="cachewarmer_imperva_api_key"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_imperva_api_key', '' ) ); ?>"
                                   class="regular-text"
                                   <?php echo $cw_is_not_ent ? 'disabled' : ''; ?>>
                            <p class="description">
                                <?php esc_html_e( 'Your Imperva API Key. Found alongside the API ID in Account Settings > API. Keep this secret.', 'cachewarmer' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cachewarmer_imperva_site_id"><?php esc_html_e( 'Site ID', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="text" id="cachewarmer_imperva_site_id" name="cachewarmer_imperva_site_id"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_imperva_site_id', '' ) ); ?>"
                                   class="regular-text"
                                   placeholder="e.g. 12345678"
                                   <?php echo $cw_is_not_ent ? 'disabled' : ''; ?>>
                            <p class="description">
                                <?php esc_html_e( 'The numeric Site ID for your website in Imperva. Find it under Sites > your site > Settings in the Imperva Console.', 'cachewarmer' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <div class="cw-api-example">
                    <p class="description"><strong><?php esc_html_e( 'API Reference:', 'cachewarmer' ); ?></strong></p>
                    <pre class="cw-code-block"><?php echo esc_html( 'POST https://my.incapsula.com/api/prov/v1/sites/performance/purge
Content-Type: application/x-www-form-urlencoded

api_id={api_id}&api_key={api_key}&site_id={site_id}
&purge_pattern=https://example.com/page-1

// Purges matching URLs from Imperva\'s global CDN
// Purge propagation: typically < 500ms worldwide' ); ?></pre>
                </div>
            </div>
            <?php if ( $cw_is_not_ent ) : ?>
                <div class="cw-pro-upgrade-overlay">
                    <span class="dashicons dashicons-lock"></span>
                    <strong><?php esc_html_e( 'Enterprise Feature', 'cachewarmer' ); ?></strong>
                    <p><?php esc_html_e( 'Purge and re-warm your Imperva (Incapsula) CDN cache via API.', 'cachewarmer' ); ?></p>
                    <a href="<?php echo esc_url( $cw_pricing_url ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Upgrade to Enterprise', 'cachewarmer' ); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Akamai Fast Purge (Enterprise) -->
        <div class="cachewarmer-settings-section<?php echo $cw_is_not_ent ? ' cw-ent-locked' : ''; ?>">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_akamai_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_akamai_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_akamai_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Akamai Fast Purge', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description">
                <?php esc_html_e( 'Invalidates cached content on Akamai\'s global CDN in under 5 seconds. If your site uses Akamai for content delivery, this ensures cache is cleared and re-warmed whenever your content changes.', 'cachewarmer' ); ?>
            </p>
            <p class="description cw-tool-link">
                <span class="dashicons dashicons-external"></span>
                <?php
                printf(
                    esc_html__( 'Get credentials: %1$sAkamai Control Center%2$s — go to Identity & Access Management > API Clients to create EdgeGrid credentials.', 'cachewarmer' ),
                    '<a href="https://control.akamai.com" target="_blank" rel="noopener">',
                    '</a>'
                );
                ?>
            </p>
            <div class="cw-locked-content">
                <table class="form-table">
                    <tr>
                        <th><label for="cachewarmer_akamai_host"><?php esc_html_e( 'API Host', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="text" id="cachewarmer_akamai_host" name="cachewarmer_akamai_host"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_akamai_host', '' ) ); ?>"
                                   class="regular-text"
                                   placeholder="e.g. akaa-xxxxx.luna.akamaiapis.net"
                                   <?php echo $cw_is_not_ent ? 'disabled' : ''; ?>>
                            <p class="description">
                                <?php esc_html_e( 'The Akamai API hostname from your EdgeGrid credentials file (.edgerc). Looks like "akaa-xxxxx.luna.akamaiapis.net".', 'cachewarmer' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cachewarmer_akamai_client_token"><?php esc_html_e( 'Client Token', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="password" id="cachewarmer_akamai_client_token" name="cachewarmer_akamai_client_token"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_akamai_client_token', '' ) ); ?>"
                                   class="regular-text"
                                   <?php echo $cw_is_not_ent ? 'disabled' : ''; ?>>
                            <p class="description">
                                <?php esc_html_e( 'The client_token from your EdgeGrid credentials. Starts with "akab-".', 'cachewarmer' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cachewarmer_akamai_client_secret"><?php esc_html_e( 'Client Secret', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="password" id="cachewarmer_akamai_client_secret" name="cachewarmer_akamai_client_secret"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_akamai_client_secret', '' ) ); ?>"
                                   class="regular-text"
                                   <?php echo $cw_is_not_ent ? 'disabled' : ''; ?>>
                            <p class="description">
                                <?php esc_html_e( 'The client_secret from your EdgeGrid credentials. This is a Base64-encoded string. Keep it secret.', 'cachewarmer' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cachewarmer_akamai_access_token"><?php esc_html_e( 'Access Token', 'cachewarmer' ); ?></label></th>
                        <td>
                            <input type="password" id="cachewarmer_akamai_access_token" name="cachewarmer_akamai_access_token"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_akamai_access_token', '' ) ); ?>"
                                   class="regular-text"
                                   <?php echo $cw_is_not_ent ? 'disabled' : ''; ?>>
                            <p class="description">
                                <?php esc_html_e( 'The access_token from your EdgeGrid credentials. Starts with "akab-".', 'cachewarmer' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cachewarmer_akamai_network"><?php esc_html_e( 'Network', 'cachewarmer' ); ?></label></th>
                        <td>
                            <select id="cachewarmer_akamai_network" name="cachewarmer_akamai_network"
                                    <?php echo $cw_is_not_ent ? 'disabled' : ''; ?>>
                                <?php
                                $akamai_network = get_option( 'cachewarmer_akamai_network', 'production' );
                                ?>
                                <option value="production" <?php selected( $akamai_network, 'production' ); ?>>
                                    <?php esc_html_e( 'Production', 'cachewarmer' ); ?>
                                </option>
                                <option value="staging" <?php selected( $akamai_network, 'staging' ); ?>>
                                    <?php esc_html_e( 'Staging', 'cachewarmer' ); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php esc_html_e( 'Which Akamai network to purge. Use "Production" for your live site. "Staging" is for testing before going live.', 'cachewarmer' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <div class="cw-api-example">
                    <p class="description"><strong><?php esc_html_e( 'API Reference:', 'cachewarmer' ); ?></strong></p>
                    <pre class="cw-code-block"><?php echo esc_html( 'POST https://{host}/ccu/v3/invalidate/url/{network}
Authorization: EG1-HMAC-SHA256 client_token=...;access_token=...;...
Content-Type: application/json

{
  "objects": [
    "https://example.com/page-1",
    "https://example.com/page-2"
  ]
}

// Batch limit: 50 URLs per request
// Invalidation in < 5 seconds globally
// Auth: EdgeGrid signature (computed automatically by CacheWarmer)' ); ?></pre>
                </div>
                <div class="cw-api-example">
                    <p class="description"><strong><?php esc_html_e( 'EdgeGrid credentials file example (.edgerc):', 'cachewarmer' ); ?></strong></p>
                    <pre class="cw-code-block"><?php echo esc_html( '[default]
host = akaa-xxxxx.luna.akamaiapis.net
client_token = akab-xxxxx
client_secret = xxxxxxxxxxxxxxxxxxxxxxxxxxxx
access_token = akab-xxxxx
max-body = 131072' ); ?></pre>
                </div>
            </div>
            <?php if ( $cw_is_not_ent ) : ?>
                <div class="cw-pro-upgrade-overlay">
                    <span class="dashicons dashicons-lock"></span>
                    <strong><?php esc_html_e( 'Enterprise Feature', 'cachewarmer' ); ?></strong>
                    <p><?php esc_html_e( 'Purge and re-warm your Akamai CDN cache via the Fast Purge API.', 'cachewarmer' ); ?></p>
                    <a href="<?php echo esc_url( $cw_pricing_url ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Upgrade to Enterprise', 'cachewarmer' ); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Scheduler (Premium) -->
        <div class="cachewarmer-settings-section<?php echo $cw_is_free ? ' cw-pro-locked' : ''; ?>">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_scheduler_enabled" value="0">
                    <input type="checkbox" name="cachewarmer_scheduler_enabled" value="1"
                        <?php checked( get_option( 'cachewarmer_scheduler_enabled', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Scheduled Warming', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description">
                <?php esc_html_e( 'Automatically warms all your registered sitemaps on a recurring schedule. Great for keeping your cache fresh without having to click "Warm Now" manually.', 'cachewarmer' ); ?>
            </p>
            <div class="cw-locked-content">
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
                            <p class="description"><?php esc_html_e( 'How often CacheWarmer should automatically warm all registered sitemaps. "Daily" is a good default for most sites.', 'cachewarmer' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php if ( $cw_is_free ) : ?>
                <div class="cw-pro-upgrade-overlay">
                    <span class="dashicons dashicons-lock"></span>
                    <strong><?php esc_html_e( 'Premium Feature', 'cachewarmer' ); ?></strong>
                    <p><?php esc_html_e( 'Automate your cache warming with scheduled runs.', 'cachewarmer' ); ?></p>
                    <a href="<?php echo esc_url( $cw_pricing_url ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Upgrade to Premium', 'cachewarmer' ); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Logging -->
        <div class="cachewarmer-settings-section">
            <h2><?php esc_html_e( 'Logging', 'cachewarmer' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Controls how much detail CacheWarmer records in its log. Useful for troubleshooting.', 'cachewarmer' ); ?></p>
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
                        <p class="description">
                            <?php esc_html_e( 'Debug = log everything (best for troubleshooting). Info = normal operation. Warn = only potential issues. Error = only failures.', 'cachewarmer' ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Auto-Warm on Publish (Premium) -->
        <div class="cachewarmer-settings-section<?php echo $cw_is_free ? ' cw-pro-locked' : ''; ?>">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_auto_warm_on_publish" value="0">
                    <input type="checkbox" name="cachewarmer_auto_warm_on_publish" value="1"
                        <?php checked( get_option( 'cachewarmer_auto_warm_on_publish', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Auto-Warm on Publish', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description">
                <?php esc_html_e( 'Automatically warms the cache every time you publish or update a post or page. The CDN cache and social media previews are refreshed instantly — no manual action needed.', 'cachewarmer' ); ?>
            </p>
            <div class="cw-locked-content">
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
                            <p class="description"><?php esc_html_e( 'Choose which services should be refreshed when you publish or update content.', 'cachewarmer' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php if ( $cw_is_free ) : ?>
                <div class="cw-pro-upgrade-overlay">
                    <span class="dashicons dashicons-lock"></span>
                    <strong><?php esc_html_e( 'Premium Feature', 'cachewarmer' ); ?></strong>
                    <p><?php esc_html_e( 'Automatically warm caches when you publish or update content.', 'cachewarmer' ); ?></p>
                    <a href="<?php echo esc_url( $cw_pricing_url ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Upgrade to Premium', 'cachewarmer' ); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- URL Exclude Patterns (Enterprise) -->
        <div class="cachewarmer-settings-section<?php echo $cw_is_not_ent ? ' cw-ent-locked' : ''; ?>">
            <h2><?php esc_html_e( 'URL Exclude Patterns', 'cachewarmer' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Skip certain pages during warming. Useful for excluding admin pages, tag archives, author pages, or PDF files that don\'t need caching.', 'cachewarmer' ); ?></p>
            <div class="cw-locked-content">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cachewarmer_exclude_patterns"><?php esc_html_e( 'Exclude Patterns', 'cachewarmer' ); ?></label>
                        </th>
                        <td>
                            <textarea id="cachewarmer_exclude_patterns" name="cachewarmer_exclude_patterns"
                                      rows="6" class="large-text code"
                                      placeholder="/tag/*&#10;/author/*&#10;*.pdf&#10;/wp-admin/*"
                                      <?php echo $cw_is_not_ent ? 'disabled' : ''; ?>><?php echo esc_textarea( get_option( 'cachewarmer_exclude_patterns', '' ) ); ?></textarea>
                            <p class="description">
                                <?php esc_html_e( 'Enter one pattern per line. Any URL containing the pattern will be skipped.', 'cachewarmer' ); ?>
                                <br>
                                <?php esc_html_e( 'Examples: /tag/ (skip tag pages), /author/ (skip author pages), .pdf (skip PDF files), /wp-admin/ (skip admin pages).', 'cachewarmer' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php if ( $cw_is_not_ent ) : ?>
                <div class="cw-pro-upgrade-overlay">
                    <span class="dashicons dashicons-lock"></span>
                    <strong><?php esc_html_e( 'Enterprise Feature', 'cachewarmer' ); ?></strong>
                    <p><?php esc_html_e( 'Define URL exclude patterns to skip specific pages during cache warming.', 'cachewarmer' ); ?></p>
                    <a href="<?php echo esc_url( $cw_pricing_url ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Upgrade to Enterprise', 'cachewarmer' ); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Email Notifications (Enterprise) -->
        <div class="cachewarmer-settings-section<?php echo $cw_is_not_ent ? ' cw-ent-locked' : ''; ?>">
            <h2>
                <label>
                    <input type="hidden" name="cachewarmer_email_notifications" value="0">
                    <input type="checkbox" name="cachewarmer_email_notifications" value="1"
                        <?php checked( get_option( 'cachewarmer_email_notifications', '0' ), '1' ); ?>>
                    <?php esc_html_e( 'Email Notifications', 'cachewarmer' ); ?>
                </label>
            </h2>
            <p class="description">
                <?php esc_html_e( 'Get an email whenever a warming job finishes or fails. Handy if you run scheduled jobs and want to stay informed without checking the dashboard.', 'cachewarmer' ); ?>
            </p>
            <div class="cw-locked-content">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cachewarmer_notification_email"><?php esc_html_e( 'Notification Email', 'cachewarmer' ); ?></label>
                        </th>
                        <td>
                            <input type="email" id="cachewarmer_notification_email" name="cachewarmer_notification_email"
                                   value="<?php echo esc_attr( get_option( 'cachewarmer_notification_email', get_option( 'admin_email' ) ) ); ?>"
                                   class="regular-text">
                            <p class="description"><?php esc_html_e( 'The email address where notifications are sent. Defaults to the WordPress admin email.', 'cachewarmer' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php if ( $cw_is_not_ent ) : ?>
                <div class="cw-pro-upgrade-overlay">
                    <span class="dashicons dashicons-lock"></span>
                    <strong><?php esc_html_e( 'Enterprise Feature', 'cachewarmer' ); ?></strong>
                    <p><?php esc_html_e( 'Get notified by email when warming jobs complete or fail.', 'cachewarmer' ); ?></p>
                    <a href="<?php echo esc_url( $cw_pricing_url ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Upgrade to Enterprise', 'cachewarmer' ); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Webhook Notifications (Enterprise) -->
        <div class="cachewarmer-settings-section<?php echo $cw_is_not_ent ? ' cw-ent-locked' : ''; ?>">
            <h2><?php esc_html_e( 'Webhook Notifications', 'cachewarmer' ); ?></h2>
            <p class="description">
                <?php esc_html_e( 'Send automatic notifications to external services (like Slack, Zapier or a custom server) when a warming job completes or fails.', 'cachewarmer' ); ?>
            </p>
            <div class="cw-locked-content">
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
                                   <?php echo $cw_is_not_ent ? 'disabled' : ''; ?>>
                            <p class="description"><?php esc_html_e( 'Paste the URL where CacheWarmer should send event data (in JSON format). For example, a Slack incoming webhook URL or a Zapier catch hook.', 'cachewarmer' ); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            <?php if ( $cw_is_not_ent ) : ?>
                <div class="cw-pro-upgrade-overlay">
                    <span class="dashicons dashicons-lock"></span>
                    <strong><?php esc_html_e( 'Enterprise Feature', 'cachewarmer' ); ?></strong>
                    <p><?php esc_html_e( 'Connect CacheWarmer to Slack, Zapier or any webhook endpoint for real-time notifications.', 'cachewarmer' ); ?></p>
                    <a href="<?php echo esc_url( $cw_pricing_url ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Upgrade to Enterprise', 'cachewarmer' ); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <?php submit_button( __( 'Save Settings', 'cachewarmer' ) ); ?>
    </form>

    <div class="cachewarmer-footer">
        <?php
        printf(
            'made with %s by <a href="https://dross.net/media/?ref=cachewarmer" target="_blank" rel="noopener">Dross:Media</a>',
            '<span class="cachewarmer-heart">&hearts;</span>'
        );
        ?>
    </div>
</div>
