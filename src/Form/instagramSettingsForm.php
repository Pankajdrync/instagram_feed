<?php

namespace Drupal\instagram_feed\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for instagram feed.
 */

class instagramSettingsForm extends ConfigFormBase {

	const SETTINGS = 'instagram_feed.settings';

	public function getFormId() {
    return 'instagram_feed_admin_settings';
  }

  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }


  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['instagram_feed_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Instagram feed count'),
      '#default_value' => $config->get('instagram_feed_count'),
    ]; 

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable(static::SETTINGS)
      // Set the submitted configuration setting.
      ->set('instagram_feed_count', $form_state->getValue('instagram_feed_count'))
      ->save();

    parent::submitForm($form, $form_state);
  }



}
