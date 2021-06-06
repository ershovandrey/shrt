<?php

namespace Drupal\shorty\Plugin\Field\FieldFormatter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'Short Url' formatter.
 *
 * @FieldFormatter(
 *   id = "shorty_short_url",
 *   label = @Translation("Short Url"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class ShortUrlFormatter extends FormatterBase {

  /**
   * The immutable config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Create an instance of ShortUrlFormatter.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ConfigFactoryInterface $configFactory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->config = $configFactory->get('shorty.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $options = parent::defaultSettings();

    $options['as_link'] = TRUE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);

    $form['as_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Render URL as link'),
      '#default_value' => $this->getSetting('as_link'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];
    $shorty_base = $this->config->get('shorty_base');
    $as_link = $this->getSetting('as_link');
    foreach ($items as $delta => $item) {
      $source_url = Url::fromUri(urldecode($shorty_base . '/' . $item->value));
      $source_url_string = $source_url->toString();
      if ($as_link) {
        $element[$delta] = [
          '#type' => 'link',
          '#url' => $source_url,
          '#title' => $source_url_string,
        ];
      }
      else {
        $element[$delta] = [
          '#markup' => $source_url_string,
        ];
      }

    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    return $field_definition->getTargetEntityTypeId() === 'shorty' && $field_definition->getName() === 'source';
  }

}
