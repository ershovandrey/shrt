<?php

namespace Drupal\shorty\Entity;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Url;
use Drupal\shorty\ShortyInterface;
use Drupal\user\UserInterface;

/**
 * Defines the shorty entity class.
 *
 * @ContentEntityType(
 *   id = "shorty",
 *   label = @Translation("Shorty"),
 *   label_collection = @Translation("Shorties"),
 *   handlers = {
 *     "storage_schema" = "Drupal\shorty\ShortyStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\shorty\ShortyListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\shorty\ShortyAccessControlHandler",
 *     "form" = {
 *       "edit" = "Drupal\shorty\Form\ShortyEditForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "shorty",
 *   admin_permission = "administer shorty",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/shorty/{shorty}",
 *     "edit-form" = "/admin/content/shorty/{shorty}/edit",
 *     "delete-form" = "/admin/content/shorty/{shorty}/delete",
 *     "collection" = "/admin/content/shorty"
 *   },
 *   field_ui_base_route = "entity.shorty.settings"
 * )
 */
class Shorty extends ContentEntityBase implements ShortyInterface {

  use EntityChangedTrait;

  /**
   * Denotes that the Shorty is active.
   */
  public const SHORTY_STATUS_ACTIVE = 1;

  /**
   * Denotes that the Shorty is blocked.
   */
  public const SHORTY_STATUS_BLOCKED = 0;


  /**
   * {@inheritdoc}
   *
   * When a new shorty entity is created, set the uid entity reference to
   * the current user as the creator of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values): void {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
      'created' => \Drupal::service('datetime.time')->getCurrentTime(),
      'visits' => 0,
      'status' => self::SHORTY_STATUS_ACTIVE,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getSourceUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus(bool $status): ShortyInterface {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime(int $timestamp): ShortyInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner(): UserInterface {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId(): ?int {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid): ShortyInterface {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account): ShortyInterface {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExpireOnTime(): ?int {
    return $this->get('expire_on')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisitsNumber(): int {
    return $this->get('visits')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementVisits(): ShortyInterface {
    $visits = $this->getVisitsNumber() + 1;
    $this->set('visits', $visits);
    $this->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHashValue(): string {
    return $this->get('hash')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceUrl(): Url {
    $shorty_base = \Drupal::service('config.factory')->get('shorty.settings')->get('shorty_base');
    $source = urldecode($shorty_base . '/' . $this->get('source')->value);
    return Url::fromUri($source);
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationUrl(): Url {
    return Url::fromUri($this->get('destination')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationUrlTrimmed(): string {
    return Unicode::truncate($this->getDestinationUrl()->toString(), 50, FALSE, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['destination'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('Redirect URL'))
      ->setDescription(t('Redirect destination URL.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'uri_link',
        'label' => 'above',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash of redirect URL'))
      ->setDescription(t('The hash of the redirect URL.'))
      ->setReadOnly(TRUE)
      ->setSettings([
        'max_length' => 32,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL);

    $fields['source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source Path'))
      ->setDescription(t('Redirect source path.'))
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'inline',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['expire_on'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expire on'))
      ->setDescription(t('The time that the shorty will be expired.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp_ago',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('A boolean indicating whether the shorty is enabled.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Active')
      ->setSetting('off_label', 'Disabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'inline',
        'weight' => 0,
        'settings' => [
          'format' => 'default',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['visits'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Visits'))
      ->setDescription(t('Visits count number.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number_integer',
        'label' => 'inline',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user ID of the shorty author.'))
      ->setSetting('target_type', 'user')
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the shorty was created.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp_ago',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the shorty was last edited.'));

//    $fields['destination'] = BaseFieldDefinition::create('uri')
//      ->setLabel(t('Redirect URL'))
//      ->setDescription(t('Redirect destination URL.'))
//      ->setRequired(TRUE)
//      ->setDisplayOptions('form', [
//        'type' => 'uri',
//        'settings' => [
//          'display_label' => FALSE,
//        ],
//        'weight' => 0,
//      ])
//      ->setDisplayConfigurable('form', FALSE)
//      ->setDisplayOptions('view', [
//        'type' => 'url',
//        'label' => 'above',
//        'weight' => 0,
//      ])
//      ->setDisplayConfigurable('view', TRUE);
//
//    $fields['hash'] = BaseFieldDefinition::create('string')
//      ->setLabel(t('Hash of redirect URL'))
//      ->setDescription(t('The hash of the redirect URL.'))
//      ->setReadOnly(TRUE)
//      ->setSettings([
//        'max_length' => 32,
//        'text_processing' => 0,
//      ])
//      // Set no default value.
//      ->setDefaultValue(NULL);
//
//    $fields['source'] = BaseFieldDefinition::create('uri')
//      ->setLabel(t('Source Path'))
//      ->setDescription(t('Redirect source path.'))
//      //->setRequired(TRUE)
//      ->setSetting('max_length', 255)
//      ->setDisplayOptions('form', [
//        'type' => 'uri',
//        'settings' => [
//          'display_label' => FALSE,
//        ],
//        'weight' => 0,
//      ])
//      ->setDisplayConfigurable('form', TRUE)
//      ->setDisplayOptions('view', [
//        'type' => 'url',
//        'label' => 'above',
//        'weight' => 0,
//      ])
//      ->setDisplayConfigurable('view', TRUE)
//      ->setConstraints(['SourceUrl']);
//
//    $fields['expire_on'] = BaseFieldDefinition::create('timestamp')
//      ->setLabel(t('Expire on'))
//      ->setDescription(t('The time that the shorty will be expired.'))
//      ->setDisplayOptions('view', [
//        'label' => 'above',
//        'type' => 'timestamp',
//        'weight' => 0,
//      ])
//      ->setDisplayConfigurable('form', TRUE)
//      ->setDisplayOptions('form', [
//        'type' => 'datetime_timestamp',
//        'weight' => 0,
//      ])
//      ->setDisplayConfigurable('view', TRUE);
//
//    $fields['status'] = BaseFieldDefinition::create('boolean')
//      ->setLabel(t('Status'))
//      ->setDescription(t('A boolean indicating whether the shorty is enabled.'))
//      ->setDefaultValue(TRUE)
//      ->setSetting('on_label', 'Active')
//      ->setDisplayOptions('form', [
//        'type' => 'boolean_checkbox',
//        'settings' => [
//          'display_label' => FALSE,
//        ],
//        'weight' => 0,
//      ])
//      ->setDisplayConfigurable('form', TRUE)
//      ->setDisplayOptions('view', [
//        'type' => 'boolean',
//        'label' => 'above',
//        'weight' => 0,
//        'settings' => [
//          'format' => 'enabled-disabled',
//        ],
//      ])
//      ->setDisplayConfigurable('view', TRUE);
//
//    $fields['visits'] = BaseFieldDefinition::create('integer')
//      ->setLabel(t('Visits'))
//      ->setDescription(t('Visits count number.'))
//      ->setReadOnly(TRUE)
//      ->setDisplayOptions('view', [
//        'type' => 'int',
//        'label' => 'above',
//        'weight' => 0,
//      ])
//      ->setDisplayConfigurable('view', TRUE);
//
//    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
//      ->setLabel(t('Author'))
//      ->setDescription(t('The user ID of the shorty author.'))
//      ->setSetting('target_type', 'user')
//      ->setDisplayOptions('form', [
//        'type' => 'entity_reference_autocomplete',
//        'settings' => [
//          'match_operator' => 'CONTAINS',
//          'size' => 60,
//          'placeholder' => '',
//        ],
//        'weight' => 0,
//      ])
//      ->setDisplayConfigurable('form', TRUE)
//      ->setDisplayOptions('view', [
//        'label' => 'above',
//        'type' => 'author',
//        'weight' => 0,
//      ])
//      ->setDisplayConfigurable('view', TRUE);
//
//    $fields['created'] = BaseFieldDefinition::create('created')
//      ->setLabel(t('Authored on'))
//      ->setDescription(t('The time that the shorty was created.'))
//      ->setDisplayOptions('view', [
//        'label' => 'above',
//        'type' => 'timestamp',
//        'weight' => 0,
//      ])
//      ->setDisplayConfigurable('form', TRUE)
//      ->setDisplayOptions('form', [
//        'type' => 'datetime_timestamp',
//        'weight' => 0,
//      ])
//      ->setDisplayConfigurable('view', TRUE);
//
//    $fields['changed'] = BaseFieldDefinition::create('changed')
//      ->setLabel(t('Changed'))
//      ->setDescription(t('The time that the shorty was last edited.'));

    return $fields;
  }

}
