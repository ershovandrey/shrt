<?php
/**
 * @file
 * Contains \Drupal\shorty\Form\ShortyAddForm.
 */

namespace Drupal\shorty\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\shorty\Service\ShortyHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create Shorty form class..
 */
class ShortyAddForm extends FormBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The registered logger for this channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Shorty Helper Service.
   *
   * @var \Drupal\shorty\Service\ShortyHelper
   */
  protected ShortyHelper $helper;

  /**
   * Constructs a new NewContractSubscriber object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, ShortyHelper $helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('shorty');
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('shorty.helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shorty_create_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $storage = $form_state->getStorage();

    $form['long_url'] = array(
      '#type' => 'textfield',
      '#maxlength' => 2048,
      '#default_value' => $storage['shorty']['long_url'] ?? FALSE,
      '#attributes' => array('tabindex' => 1, 'placeholder' => t('Enter a long URL to make short')),
    );

    if (isset($storage['shorty']['final_url'])) {
      $form['result'] = array(
        '#type' => 'textfield',
        '#size' => 30,
        '#value' => $storage['shorty']['final_url'],
        '#field_prefix' => t('Your short URL:'),
      );
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Shorty it!'),
      '#attributes' => array('tabindex' => 2),
    );

    unset($storage['shorty']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setValue('long_url', trim($form_state->getValue('long_url')));
    $long_url = $form_state->getValue('long_url');

    // check that they've entered a URL
    if ($long_url === '' || $long_url === 'http://' || $long_url === 'https://') {
      $form_state->setErrorByName('long_url', t('Please enter a web URL'));
    }
    elseif (!$this->helper->validateLongUrl($long_url)) {
      $form_state->setErrorByName('long_url', t('Invalid URL'));
    }

    $short = $this->helper->getNextShortUrl();
    $form_state->setValue('short_url', $short);
    $form_state->setStorage(['shorty' => ['short_url' => $short]]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $long_url = $form_state->getValue('long_url');
    $short_url = $form_state->getValue('short_url');
    $shorty_base = $this->config('shorty.settings')->get('shorty_base');

    $form_state->setStorage([
      'shorty' => [
        'long_url' => $long_url,
        'short_url' => $short_url,
        'final_url' => urldecode($shorty_base . '/' . $short_url),
      ],
    ]);

    $form_state->setRebuild();

    $entity_type_id = 'shorty';
    $hash = md5($long_url);
    $fields = [
      'type' => $entity_type_id,
      'destination' => $long_url,
      'hash' => $hash,
      'source' => $short_url,
      'expire_on' => NULL,
    ];
    try {
      $this->entityTypeManager
        ->getStorage($entity_type_id)
        ->create($fields)
        ->save();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException $e) {
      $this->logger->error('Failed to save shorty for user uid %uid with hash %hash and short URL %short_url.',
        [
          '%uid' => $this->currentUser()->id(),
          '%hash' => $hash,
          '%short_url' => $short_url
        ]);
      $this->messenger()->addError(t('Failed to save shorty with short URL %url', ['%url' => $short_url]));
      return;
    }
    $this->messenger()->addStatus(t('Your shorty is saved!'));
  }
}
