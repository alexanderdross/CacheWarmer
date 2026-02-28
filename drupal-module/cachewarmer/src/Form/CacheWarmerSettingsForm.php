<?php

namespace Drupal\cachewarmer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * CacheWarmer settings form.
 */
class CacheWarmerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cachewarmer.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cachewarmer_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cachewarmer.settings');

    // General settings.
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#open' => TRUE,
    ];
    $form['general']['api_key'] = [
      '#type' => 'password',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('Bearer token for REST API authentication. Leave empty to keep current value.'),
      '#default_value' => '',
      '#attributes' => ['autocomplete' => 'off'],
    ];
    if (!empty($config->get('api_key'))) {
      $form['general']['api_key']['#description'] .= ' ' . $this->t('(Currently set)');
    }

    // CDN Cache Warming.
    $form['cdn'] = [
      '#type' => 'details',
      '#title' => $this->t('CDN Cache Warming'),
      '#open' => FALSE,
    ];
    $form['cdn']['cdn_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable CDN cache warming'),
      '#default_value' => $config->get('cdn.enabled'),
    ];
    $form['cdn']['cdn_concurrency'] = [
      '#type' => 'number',
      '#title' => $this->t('Concurrency'),
      '#min' => 1,
      '#max' => 20,
      '#default_value' => $config->get('cdn.concurrency') ?: 3,
    ];
    $form['cdn']['cdn_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Timeout (seconds)'),
      '#min' => 5,
      '#max' => 120,
      '#default_value' => $config->get('cdn.timeout') ?: 30,
    ];
    $form['cdn']['cdn_user_agent'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User Agent'),
      '#default_value' => $config->get('cdn.user_agent') ?: 'Mozilla/5.0 (compatible; CacheWarmer/1.0)',
      '#maxlength' => 512,
    ];

    // Facebook.
    $form['facebook'] = [
      '#type' => 'details',
      '#title' => $this->t('Facebook Sharing Debugger'),
      '#open' => FALSE,
    ];
    $form['facebook']['facebook_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Facebook warming'),
      '#default_value' => $config->get('facebook.enabled'),
    ];
    $form['facebook']['facebook_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App ID'),
      '#default_value' => $config->get('facebook.app_id'),
    ];
    $form['facebook']['facebook_app_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('App Secret'),
      '#attributes' => ['autocomplete' => 'off'],
    ];
    if (!empty($config->get('facebook.app_secret'))) {
      $form['facebook']['facebook_app_secret']['#description'] = $this->t('Currently set. Leave empty to keep current value.');
    }
    $form['facebook']['facebook_rate_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate limit (requests/second)'),
      '#min' => 1,
      '#max' => 50,
      '#default_value' => $config->get('facebook.rate_limit') ?: 10,
    ];

    // LinkedIn.
    $form['linkedin'] = [
      '#type' => 'details',
      '#title' => $this->t('LinkedIn Post Inspector'),
      '#open' => FALSE,
    ];
    $form['linkedin']['linkedin_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable LinkedIn warming'),
      '#default_value' => $config->get('linkedin.enabled'),
    ];
    $form['linkedin']['linkedin_session_cookie'] = [
      '#type' => 'password',
      '#title' => $this->t('Session Cookie (li_at)'),
      '#attributes' => ['autocomplete' => 'off'],
    ];
    if (!empty($config->get('linkedin.session_cookie'))) {
      $form['linkedin']['linkedin_session_cookie']['#description'] = $this->t('Currently set. Leave empty to keep current value.');
    }
    $form['linkedin']['linkedin_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay between requests (ms)'),
      '#min' => 1000,
      '#max' => 30000,
      '#default_value' => $config->get('linkedin.delay') ?: 5000,
    ];

    // Twitter/X.
    $form['twitter'] = [
      '#type' => 'details',
      '#title' => $this->t('Twitter/X Card Validator'),
      '#open' => FALSE,
    ];
    $form['twitter']['twitter_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Twitter/X warming'),
      '#default_value' => $config->get('twitter.enabled'),
    ];
    $form['twitter']['twitter_concurrency'] = [
      '#type' => 'number',
      '#title' => $this->t('Concurrency'),
      '#min' => 1,
      '#max' => 10,
      '#default_value' => $config->get('twitter.concurrency') ?: 2,
    ];
    $form['twitter']['twitter_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay between batches (ms)'),
      '#min' => 1000,
      '#max' => 30000,
      '#default_value' => $config->get('twitter.delay') ?: 3000,
    ];

    // Google.
    $form['google'] = [
      '#type' => 'details',
      '#title' => $this->t('Google Indexing API'),
      '#open' => FALSE,
    ];
    $form['google']['google_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Google indexing'),
      '#default_value' => $config->get('google.enabled'),
    ];
    $form['google']['google_service_account_json'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Service Account JSON'),
      '#description' => $this->t('Paste the full contents of your Google service account key JSON file.'),
      '#default_value' => $config->get('google.service_account_json'),
      '#rows' => 6,
    ];
    $form['google']['google_daily_quota'] = [
      '#type' => 'number',
      '#title' => $this->t('Daily quota'),
      '#min' => 1,
      '#max' => 10000,
      '#default_value' => $config->get('google.daily_quota') ?: 200,
    ];

    // Bing.
    $form['bing'] = [
      '#type' => 'details',
      '#title' => $this->t('Bing Webmaster Tools'),
      '#open' => FALSE,
    ];
    $form['bing']['bing_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Bing indexing'),
      '#default_value' => $config->get('bing.enabled'),
    ];
    $form['bing']['bing_api_key'] = [
      '#type' => 'password',
      '#title' => $this->t('API Key'),
      '#attributes' => ['autocomplete' => 'off'],
    ];
    if (!empty($config->get('bing.api_key'))) {
      $form['bing']['bing_api_key']['#description'] = $this->t('Currently set. Leave empty to keep current value.');
    }
    $form['bing']['bing_daily_quota'] = [
      '#type' => 'number',
      '#title' => $this->t('Daily quota'),
      '#min' => 1,
      '#max' => 100000,
      '#default_value' => $config->get('bing.daily_quota') ?: 10000,
    ];

    // IndexNow.
    $form['indexnow'] = [
      '#type' => 'details',
      '#title' => $this->t('IndexNow Protocol'),
      '#open' => FALSE,
    ];
    $form['indexnow']['indexnow_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable IndexNow'),
      '#default_value' => $config->get('indexnow.enabled'),
    ];
    $form['indexnow']['indexnow_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('IndexNow Key'),
      '#default_value' => $config->get('indexnow.key'),
    ];
    $form['indexnow']['indexnow_key_location'] = [
      '#type' => 'url',
      '#title' => $this->t('Key Location URL'),
      '#default_value' => $config->get('indexnow.key_location'),
    ];

    // Scheduler.
    $form['scheduler'] = [
      '#type' => 'details',
      '#title' => $this->t('Scheduled Warming'),
      '#open' => FALSE,
    ];
    $form['scheduler']['scheduler_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable scheduled warming'),
      '#default_value' => $config->get('scheduler.enabled'),
    ];
    $form['scheduler']['scheduler_frequency'] = [
      '#type' => 'select',
      '#title' => $this->t('Frequency'),
      '#options' => [
        'hourly' => $this->t('Hourly'),
        'every_6_hours' => $this->t('Every 6 hours'),
        'every_12_hours' => $this->t('Every 12 hours'),
        'daily' => $this->t('Daily'),
        'weekly' => $this->t('Weekly'),
      ],
      '#default_value' => $config->get('scheduler.frequency') ?: 'daily',
    ];

    // License.
    $form['license'] = [
      '#type' => 'details',
      '#title' => $this->t('License'),
      '#open' => FALSE,
    ];
    $form['license']['license_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('License Key'),
      '#description' => $this->t('Enter your license key to unlock premium or enterprise features. Keys starting with PRE- activate Premium, ENT- activate Enterprise.'),
      '#default_value' => $config->get('license_key'),
      '#maxlength' => 255,
    ];
    $currentTier = $config->get('license_tier') ?: 'free';
    $form['license']['license_tier_display'] = [
      '#type' => 'item',
      '#title' => $this->t('Current Tier'),
      '#markup' => '<strong>' . ucfirst($currentTier) . '</strong>',
    ];

    // Auto-warm on publish.
    $form['auto_warm'] = [
      '#type' => 'details',
      '#title' => $this->t('Auto-Warm on Publish'),
      '#open' => FALSE,
    ];
    $form['auto_warm']['auto_warm_on_publish'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically warm URLs when content is published'),
      '#default_value' => $config->get('auto_warm_on_publish'),
    ];
    $form['auto_warm']['auto_warm_targets'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Auto-warm targets'),
      '#options' => [
        'cdn' => $this->t('CDN'),
        'facebook' => $this->t('Facebook'),
        'linkedin' => $this->t('LinkedIn'),
        'twitter' => $this->t('Twitter/X'),
        'google' => $this->t('Google'),
        'bing' => $this->t('Bing'),
        'indexnow' => $this->t('IndexNow'),
      ],
      '#default_value' => $config->get('auto_warm_targets') ?: ['cdn', 'facebook'],
    ];

    // URL exclude patterns.
    $form['filtering'] = [
      '#type' => 'details',
      '#title' => $this->t('URL Filtering'),
      '#open' => FALSE,
    ];
    $form['filtering']['exclude_patterns'] = [
      '#type' => 'textarea',
      '#title' => $this->t('URL Exclude Patterns'),
      '#description' => $this->t('Enter URL patterns to exclude from warming, one per line. Supports simple wildcard matching with *.'),
      '#default_value' => $config->get('exclude_patterns'),
      '#rows' => 4,
    ];

    // Email notifications.
    $form['notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Email Notifications'),
      '#description' => $this->t('Email notifications require a Premium or Enterprise license.'),
      '#open' => FALSE,
    ];
    $form['notifications']['email_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable email notifications on job completion'),
      '#default_value' => $config->get('email_notifications'),
    ];
    $form['notifications']['notification_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Notification Email'),
      '#description' => $this->t('Email address to send notifications to. Defaults to site email if left empty.'),
      '#default_value' => $config->get('notification_email'),
    ];

    // Webhooks.
    $form['webhooks'] = [
      '#type' => 'details',
      '#title' => $this->t('Webhooks'),
      '#description' => $this->t('Webhook notifications are available with an Enterprise license only.'),
      '#open' => FALSE,
    ];
    $form['webhooks']['webhook_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Webhook URL'),
      '#description' => $this->t('Enterprise only. URL to receive POST notifications on job events.'),
      '#default_value' => $config->get('webhook_url'),
    ];

    // Logging.
    $form['logging'] = [
      '#type' => 'details',
      '#title' => $this->t('Logging'),
      '#open' => FALSE,
    ];
    $form['logging']['log_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Log level'),
      '#options' => [
        'debug' => $this->t('Debug'),
        'info' => $this->t('Info'),
        'warn' => $this->t('Warning'),
        'error' => $this->t('Error'),
      ],
      '#default_value' => $config->get('log_level') ?: 'info',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cachewarmer.settings');

    // Only update password fields if a value was provided.
    $apiKey = $form_state->getValue('api_key');
    if (!empty($apiKey)) {
      $config->set('api_key', $apiKey);
    }

    $config->set('cdn.enabled', (bool) $form_state->getValue('cdn_enabled'));
    $config->set('cdn.concurrency', (int) $form_state->getValue('cdn_concurrency'));
    $config->set('cdn.timeout', (int) $form_state->getValue('cdn_timeout'));
    $config->set('cdn.user_agent', $form_state->getValue('cdn_user_agent'));

    $config->set('facebook.enabled', (bool) $form_state->getValue('facebook_enabled'));
    $config->set('facebook.app_id', $form_state->getValue('facebook_app_id'));
    $fbSecret = $form_state->getValue('facebook_app_secret');
    if (!empty($fbSecret)) {
      $config->set('facebook.app_secret', $fbSecret);
    }
    $config->set('facebook.rate_limit', (int) $form_state->getValue('facebook_rate_limit'));

    $config->set('linkedin.enabled', (bool) $form_state->getValue('linkedin_enabled'));
    $liCookie = $form_state->getValue('linkedin_session_cookie');
    if (!empty($liCookie)) {
      $config->set('linkedin.session_cookie', $liCookie);
    }
    $config->set('linkedin.delay', (int) $form_state->getValue('linkedin_delay'));

    $config->set('twitter.enabled', (bool) $form_state->getValue('twitter_enabled'));
    $config->set('twitter.concurrency', (int) $form_state->getValue('twitter_concurrency'));
    $config->set('twitter.delay', (int) $form_state->getValue('twitter_delay'));

    $config->set('google.enabled', (bool) $form_state->getValue('google_enabled'));
    $config->set('google.service_account_json', $form_state->getValue('google_service_account_json'));
    $config->set('google.daily_quota', (int) $form_state->getValue('google_daily_quota'));

    $config->set('bing.enabled', (bool) $form_state->getValue('bing_enabled'));
    $bingKey = $form_state->getValue('bing_api_key');
    if (!empty($bingKey)) {
      $config->set('bing.api_key', $bingKey);
    }
    $config->set('bing.daily_quota', (int) $form_state->getValue('bing_daily_quota'));

    $config->set('indexnow.enabled', (bool) $form_state->getValue('indexnow_enabled'));
    $config->set('indexnow.key', $form_state->getValue('indexnow_key'));
    $config->set('indexnow.key_location', $form_state->getValue('indexnow_key_location'));

    $config->set('scheduler.enabled', (bool) $form_state->getValue('scheduler_enabled'));
    $config->set('scheduler.frequency', $form_state->getValue('scheduler_frequency'));

    $config->set('log_level', $form_state->getValue('log_level'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
