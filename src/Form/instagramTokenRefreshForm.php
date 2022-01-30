<?php

namespace Drupal\instagram_feed\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Configure example settings for instagram feed.
 */

class instagramTokenRefreshForm extends ConfigFormBase {

  private $auth_url = 'https://api.instagram.com/oauth/authorize';

	public function getFormId() {
    return 'instagram_token_refresh_form_admin';
  }

  protected function getEditableConfigNames() {
    return ['instagram_tokens.settings'];
  }


  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('instagram_tokens.settings');

    $form['app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App ID'),
      '#description' => $this->t('This is the instagram app ID, it should be valid and required to get access tokens.'),
      '#default_value' => $config->get('app_id'),
      '#required' => TRUE,
    ];

    $form['app_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App Secret'),
      '#description' => $this->t('This is the instagram app secret, it should be valid and required to get access tokens.'),
      '#default_value' => $config->get('app_secret'),
      '#required' => TRUE,
    ];

    $form['redirect_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URI'),
      '#description' => $this->t('Please enter the redirect URI here, where instagram will redirect the browser on successful authentication, and where other code will be executed post authorization for getting access tokens. Usually this will be @url', ['@url' => \Drupal::request()->getSchemeAndHttpHost() . '/instagram-auth']),
      '#default_value' => $config->get('redirect_uri'),
      '#required' => TRUE,
    ];

    $form['short_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Short Token'),
      '#description' => $this->t('Automatically updated when clicked on Save Configuration button.'),
      '#default_value' => $config->get('short_token'),
      '#disabled' => TRUE,
    ];

    $form['long_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Long Token'),
      '#description' => $this->t('Automatically updated when clicked on Save Configuration button.'),
      '#default_value' => $config->get('long_token'),
      '#disabled' => TRUE,
    ];

    $form['expires'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Long Token Expires on'),
      '#description' => $this->t('Date when this long token will expire. Short token is valid for only 2 hours.'),
      '#default_value' => $config->get('expires'),
      '#disabled' => TRUE,
    ];

    $form['feed_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Instagram feed count'),
      '#description' => $this->t('The number of feeds to be fetched from Instagram.'),
      '#default_value' => $config->get('feed_count'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration and save.
    $this->configFactory->getEditable('instagram_tokens.settings')
      // Set the submitted configuration setting.
      ->set('app_id', $form_state->getValue('app_id'))
      ->set('app_secret', $form_state->getValue('app_secret'))
      ->set('redirect_uri', $form_state->getValue('redirect_uri'))
      ->set('feed_count', $form_state->getValue('feed_count'))
      ->save();

    // Required in case of Config forms.
    parent::submitForm($form, $form_state);

    // Redirect the user to instagram authorization page.
    $authorize_url = $this->auth_url;
    $query = [
      'client_id' => $form_state->getValue('app_id'),
      'redirect_uri' => $form_state->getValue('redirect_uri'),
      'scope' => 'user_profile,user_media',
      'response_type' => 'code',
    ];

    $url = \Drupal\Core\Url::fromUri($authorize_url, ['query' => $query])->toString();
    $response = new TrustedRedirectResponse($url);

    // Do not cache this request.
    $metadata = $response->getCacheableMetadata();
    $metadata->setCacheMaxAge(0);

    // Set the response in the form_state.
    $form_state->setResponse($response);
  }
}
