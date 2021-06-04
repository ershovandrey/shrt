<?php

namespace Drupal\shorty;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the feed schema handler.
 */
class ShortyStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping): array {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name === $this->storage->getBaseTable()) {
      switch ($field_name) {
        case 'source':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE, 255);
          break;
        case 'hash':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE, 32);
          break;
      }
    }

    return $schema;
  }

}
