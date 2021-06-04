<?php

namespace Drupal\shorty\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for a shorty entity type.
 */
class ShortySettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames(): array {
    return array(
      'shorty.settings',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'shorty_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    global $base_url;
    $config = $this->config('shorty.settings');
    $form['shorty_url'] = array(
      '#type' => 'fieldset',
      '#title' => t('Base URL'),
      '#description' => t('If you want to use a dedicated url for the short URL\'s, enter below that short base URL to be used.'),
    );

    $form['shorty_url']['shorty_base'] = array(
      '#type' => 'textfield',
      '#description' => t('Default is the base URL of the Drupal installation.'),
      '#default_value' => $config->get('shorty_base'),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $custom_base_url = $form_state->getValue('shorty_base');
    if(!UrlHelper::isValid($custom_base_url, TRUE)){
      $form_state->setErrorByName('shorty_base', t('The base URL is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('shorty.settings');
    $form_state->cleanValues();

    foreach ($form_state->getValues() as $key => $value) {
      if(!empty($value) && !is_array($value)){
        $config->set($key, $value);
      }
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
