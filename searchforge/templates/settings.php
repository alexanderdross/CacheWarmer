<?php
defined( 'ABSPATH' ) || exit;

$settings  = SearchForge\Admin\Settings::get_all();
$connected = ! empty( $settings['gsc_access_token'] );
$has_creds = ! empty( $settings['gsc_client_id'] ) && ! empty( $settings['gsc_client_secret'] );

// Handle property selection.
$sites       = [];
$sites_error = '';
if ( $connected && isset( $_GET['select_property'] ) ) {
	$client = new SearchForge\Integrations\GSC\Client();
	$result = SearchForge\Integrations\GSC\Client::list_sites();
	if ( is_wp_error( $result ) ) {
		$sites_error = $result->get_error_message();
	} else {
		$sites = $result;
	}
}

if ( isset( $_GET['gsc_connected'] ) ) : ?>
	<div class="notice notice-success is-dismissible">
		<p><?php esc_html_e( 'Google Search Console connected successfully!', 'searchforge' ); ?></p>
	</div>
<?php endif; ?>

<div class="wrap searchforge-wrap">
	<h1><?php esc_html_e( 'SearchForge Settings', 'searchforge' ); ?></h1>

	<form method="post" action="options.php">
		<?php settings_fields( 'searchforge_settings' ); ?>

		<!-- Google Search Console -->
		<h2><?php esc_html_e( 'Google Search Console', 'searchforge' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="gsc_client_id"><?php esc_html_e( 'Client ID', 'searchforge' ); ?></label>
				</th>
				<td>
					<input type="text" name="searchforge_settings[gsc_client_id]" id="gsc_client_id"
						value="<?php echo esc_attr( $settings['gsc_client_id'] ); ?>" class="regular-text" />
					<p class="description">
						<?php esc_html_e( 'From Google Cloud Console > APIs & Services > Credentials', 'searchforge' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="gsc_client_secret"><?php esc_html_e( 'Client Secret', 'searchforge' ); ?></label>
				</th>
				<td>
					<input type="password" name="searchforge_settings[gsc_client_secret]" id="gsc_client_secret"
						value="<?php echo esc_attr( $settings['gsc_client_secret'] ); ?>" class="regular-text" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Connection Status', 'searchforge' ); ?></th>
				<td>
					<?php if ( $connected ) : ?>
						<span class="sf-status sf-status-connected">
							<?php esc_html_e( 'Connected', 'searchforge' ); ?>
						</span>
						<?php if ( $settings['gsc_property'] ) : ?>
							<br />
							<strong><?php esc_html_e( 'Property:', 'searchforge' ); ?></strong>
							<?php echo esc_html( $settings['gsc_property'] ); ?>
						<?php endif; ?>
						<br />
						<button type="button" class="button" id="sf-disconnect-gsc">
							<?php esc_html_e( 'Disconnect', 'searchforge' ); ?>
						</button>
					<?php elseif ( $has_creds ) : ?>
						<a href="<?php echo esc_url( SearchForge\Integrations\GSC\OAuth::get_auth_url() ); ?>"
							class="button button-primary">
							<?php esc_html_e( 'Connect to Google Search Console', 'searchforge' ); ?>
						</a>
					<?php else : ?>
						<span class="sf-status sf-status-disconnected">
							<?php esc_html_e( 'Enter Client ID and Secret first, then save settings.', 'searchforge' ); ?>
						</span>
					<?php endif; ?>
				</td>
			</tr>
			<?php if ( $connected && ! $settings['gsc_property'] ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Select Property', 'searchforge' ); ?></th>
					<td>
						<?php if ( ! empty( $sites ) ) : ?>
							<select name="searchforge_settings[gsc_property]" id="gsc_property">
								<option value=""><?php esc_html_e( '— Select —', 'searchforge' ); ?></option>
								<?php foreach ( $sites as $site ) : ?>
									<option value="<?php echo esc_attr( $site['siteUrl'] ); ?>">
										<?php echo esc_html( $site['siteUrl'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						<?php elseif ( $sites_error ) : ?>
							<p class="sf-error"><?php echo esc_html( $sites_error ); ?></p>
						<?php else : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=searchforge-settings&select_property=1' ) ); ?>"
								class="button">
								<?php esc_html_e( 'Load Properties', 'searchforge' ); ?>
							</a>
						<?php endif; ?>
					</td>
				</tr>
			<?php endif; ?>
		</table>

		<!-- Bing Webmaster Tools (Pro) -->
		<h2><?php esc_html_e( 'Bing Webmaster Tools', 'searchforge' ); ?>
			<?php if ( ! SearchForge\Admin\Settings::is_pro() ) : ?>
				<span class="sf-pro-badge">Pro</span>
			<?php endif; ?>
		</h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable', 'searchforge' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="searchforge_settings[bing_enabled]" value="1"
							<?php checked( $settings['bing_enabled'] ); ?>
							<?php disabled( ! SearchForge\Admin\Settings::is_pro() ); ?> />
						<?php esc_html_e( 'Enable Bing Webmaster Tools integration', 'searchforge' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bing_api_key"><?php esc_html_e( 'API Key', 'searchforge' ); ?></label>
				</th>
				<td>
					<input type="password" name="searchforge_settings[bing_api_key]" id="bing_api_key"
						value="<?php echo esc_attr( $settings['bing_api_key'] ); ?>" class="regular-text"
						<?php disabled( ! SearchForge\Admin\Settings::is_pro() ); ?> />
					<p class="description">
						<?php esc_html_e( 'From Bing Webmaster Tools > Settings > API Access', 'searchforge' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="bing_site_url"><?php esc_html_e( 'Site URL', 'searchforge' ); ?></label>
				</th>
				<td>
					<input type="url" name="searchforge_settings[bing_site_url]" id="bing_site_url"
						value="<?php echo esc_attr( $settings['bing_site_url'] ); ?>" class="regular-text"
						placeholder="<?php echo esc_attr( home_url() ); ?>"
						<?php disabled( ! SearchForge\Admin\Settings::is_pro() ); ?> />
				</td>
			</tr>
		</table>

		<!-- Alerts & Monitoring (Pro) -->
		<h2><?php esc_html_e( 'Alerts & Monitoring', 'searchforge' ); ?>
			<?php if ( ! SearchForge\Admin\Settings::is_pro() ) : ?>
				<span class="sf-pro-badge">Pro</span>
			<?php endif; ?>
		</h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Enable Alerts', 'searchforge' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="searchforge_settings[alerts_enabled]" value="1"
							<?php checked( $settings['alerts_enabled'] ); ?>
							<?php disabled( ! SearchForge\Admin\Settings::is_pro() ); ?> />
						<?php esc_html_e( 'Enable email alerts for ranking drops, traffic anomalies, and content decay', 'searchforge' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="alert_email"><?php esc_html_e( 'Alert Email', 'searchforge' ); ?></label>
				</th>
				<td>
					<input type="email" name="searchforge_settings[alert_email]" id="alert_email"
						value="<?php echo esc_attr( $settings['alert_email'] ); ?>" class="regular-text"
						placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"
						<?php disabled( ! SearchForge\Admin\Settings::is_pro() ); ?> />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Ranking Drop Threshold', 'searchforge' ); ?></th>
				<td>
					<input type="number" name="searchforge_settings[alert_ranking_drop_threshold]"
						value="<?php echo esc_attr( $settings['alert_ranking_drop_threshold'] ); ?>"
						min="1" max="20" class="small-text"
						<?php disabled( ! SearchForge\Admin\Settings::is_pro() ); ?> />
					<?php esc_html_e( 'positions', 'searchforge' ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Traffic Anomaly Detection', 'searchforge' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="searchforge_settings[alert_traffic_anomaly]" value="1"
							<?php checked( $settings['alert_traffic_anomaly'] ); ?>
							<?php disabled( ! SearchForge\Admin\Settings::is_pro() ); ?> />
						<?php esc_html_e( 'Alert on unusual traffic spikes or drops (statistical outlier detection)', 'searchforge' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Weekly Digest', 'searchforge' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="searchforge_settings[weekly_digest_enabled]" value="1"
							<?php checked( $settings['weekly_digest_enabled'] ); ?>
							<?php disabled( ! SearchForge\Admin\Settings::is_pro() ); ?> />
						<?php esc_html_e( 'Send weekly summary email with key metrics and changes', 'searchforge' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<!-- General Settings -->
		<h2><?php esc_html_e( 'General', 'searchforge' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'llms.txt', 'searchforge' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="searchforge_settings[llms_txt_enabled]" value="1"
							<?php checked( $settings['llms_txt_enabled'] ); ?> />
						<?php esc_html_e( 'Enable llms.txt and llms-full.txt endpoints', 'searchforge' ); ?>
					</label>
					<?php if ( $settings['llms_txt_enabled'] ) : ?>
						<p class="description">
							<?php echo esc_html( home_url( '/llms.txt' ) ); ?> |
							<?php echo esc_html( home_url( '/llms-full.txt' ) ); ?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Sync Frequency', 'searchforge' ); ?></th>
				<td>
					<select name="searchforge_settings[sync_frequency]">
						<option value="daily" <?php selected( $settings['sync_frequency'], 'daily' ); ?>>
							<?php esc_html_e( 'Daily', 'searchforge' ); ?>
						</option>
						<option value="twicedaily" <?php selected( $settings['sync_frequency'], 'twicedaily' ); ?>>
							<?php esc_html_e( 'Twice Daily', 'searchforge' ); ?>
						</option>
						<option value="weekly" <?php selected( $settings['sync_frequency'], 'weekly' ); ?>>
							<?php esc_html_e( 'Weekly', 'searchforge' ); ?>
						</option>
					</select>
				</td>
			</tr>
		</table>

		<!-- License -->
		<h2><?php esc_html_e( 'License', 'searchforge' ); ?></h2>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="license_key"><?php esc_html_e( 'License Key', 'searchforge' ); ?></label>
				</th>
				<td>
					<input type="text" name="searchforge_settings[license_key]" id="license_key"
						value="<?php echo esc_attr( $settings['license_key'] ); ?>" class="regular-text"
						placeholder="SF-PRO-XXXXXXXXXXXXXXXX" />
					<p class="description">
						<?php esc_html_e( 'Current tier:', 'searchforge' ); ?>
						<strong><?php echo esc_html( ucfirst( $settings['license_tier'] ) ); ?></strong>
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
