<?php

namespace Drupal\shorty\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\shorty\Entity\Shorty;
use Drupal\shorty\Service\ShortyHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create Shorty form class..
 */
class ShortyAddForm extends FormBase {

  /**
   * Defines the format that dates should be stored in.
   */
  public const DATE_STORAGE_FORMAT = 'Y-m-d';

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
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * Date Formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Constructs a new NewContractSubscriber object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, ShortyHelper $helper, TimeInterface $time, DateFormatterInterface $date_formatter) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('shorty');
    $this->helper = $helper;
    $this->time = $time;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('shorty.helper'),
      $container->get('datetime.time'),
      $container->get('date.formatter')
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
    $form['long_url'] = [
      '#type' => 'textfield',
      '#size' => 100,
      '#maxlength' => 2048,
      '#title' => $this->t('Long URL'),
      '#default_value' => '',
      '#required' => TRUE,
      '#attributes' => [
        'tabindex' => 1,
        'placeholder' => $this->t('Enter a long URL to make short'),
      ],
    ];

    $current_date = $this->time->getCurrentTime();
    $format = self::DATE_STORAGE_FORMAT;
    $next_day_timestamp = $current_date + Shorty::SHORTY_DAY_PERIOD;
    $next_day_date = $this->dateFormatter->format($next_day_timestamp, 'custom', $format);
    $form['expire_on'] = [
      '#type' => 'date',
      '#title' => $this->t('Expire on'),
      '#required' => TRUE,
      '#attributes' => [
        'tabindex' => 3,
        'min' => $next_day_date,
      ],
    ];
    if ($this->currentUser()->hasPermission('set shorty long expiration')) {
      $next_year_timestamp = $current_date + Shorty::SHORTY_YEAR_PERIOD;
      $next_year_date = $this->dateFormatter->format($next_year_timestamp, 'custom', $format);
      $form['expire_on']['#default_value'] = $next_year_date;
      $form['expire_on']['#attributes']['max'] = $next_year_date;
    }
    else {
      $next_month_timestamp = $current_date + Shorty::SHORTY_MONTH_PERIOD;
      $next_month_date = $this->dateFormatter->format($next_month_timestamp, 'custom', $format);
      $form['expire_on']['#default_value'] = $next_month_date;
      $form['expire_on']['#attributes']['max'] = $next_month_date;
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Shorty it!'),
      '#attributes' => ['tabindex' => 2],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setValue('long_url', trim($form_state->getValue('long_url')));
    $long_url = $form_state->getValue('long_url');

    if (
      $long_url === '' ||
      $long_url === 'http://' ||
      $long_url === 'https://' ||
      !$this->helper->validateLongUrl($long_url)
    ) {
      $form_state->setErrorByName('long_url', $this->t('Please enter a valid web URL.'));
      return;
    }

    $expire_on = $form_state->getValue('expire_on');
    $dateTime = new \DateTime($expire_on);
    $expire_on_timestamp = $dateTime->format('U');
    $form_state->setValue('expire_on_timestamp', $expire_on_timestamp);
    $current_date = $this->time->getCurrentTime();
    $next_year_timestamp = $current_date + Shorty::SHORTY_YEAR_PERIOD;
    $next_month_timestamp = $current_date + Shorty::SHORTY_MONTH_PERIOD;

    if ($expire_on_timestamp <= $current_date) {
      $form_state->setErrorByName('expire_on', $this->t('Expire on date should be in future.'));
      return;
    }

    $long_expiration_user = $this->currentUser()
      ->hasPermission('set shorty long expiration');
    if (
      $long_expiration_user &&
      ($expire_on_timestamp > $next_year_timestamp)
    ) {
      // Registered users can create one year valid shorties.
      $form_state->setErrorByName('expire_on', $this->t('Maximum valid period for the short link is one year.'));
      return;
    }

    if (
      !$long_expiration_user &&
      ($expire_on_timestamp > $next_month_timestamp)
    ) {
      // Anonymous users can create one month valid shorties.
      $form_state->setErrorByName('expire_on', $this->t('Maximum valid period for the short link is one month. Please register to create short urls with one year expiration.'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Create Short URL.
    $shorty_base = $this->config('shorty.settings')->get('shorty_base');
    $short = $this->helper->getNextShortUrl();
    $short_url = urldecode($shorty_base . '/' . $short);

    $entity_type_id = 'shorty';
    $long_url = $form_state->getValue('long_url');
    $expire_on_timestamp = $form_state->getValue('expire_on_timestamp');
    $expire_on_date = $this->dateFormatter->format($expire_on_timestamp, 'short');
    $fields = [
      'type' => $entity_type_id,
      'destination' => $long_url,
      'source' => $short,
      'expire_on' => $expire_on_timestamp,
    ];
    try {
      $this->entityTypeManager
        ->getStorage($entity_type_id)
        ->create($fields)
        ->save();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException $e) {
      $this->logger->error('Failed to save shorty with short URL %url.', ['%url' => $short_url]);
      $this->messenger()->addError($this->t('Failed to save shorty with short URL %url', ['%url' => $short_url]));
      return;
    }

    $this->messenger()
      ->addStatus($this->t('Your short URL: %url <br/>Valid until: %expire', [
        '%url' => $short_url,
        '%expire' => $expire_on_date,
      ]));
  }

}
