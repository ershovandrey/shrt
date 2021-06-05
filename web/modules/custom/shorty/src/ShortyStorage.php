<?php

namespace Drupal\shorty;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\shorty\Entity\Shorty;

/**
 * Defines the storage handler class for feed entities.
 *
 * This extends the base storage class, adding required special handling for
 * feed entities.
 */
class ShortyStorage extends SqlContentEntityStorage {

  /**
   * Get Expired Shorty Ids.
   *
   * @return array
   *   Array of expired shorty ids.
   */
  public function getExpiredShortyIds(): array {
    $current_time = \Drupal::time()->getRequestTime();
    $table = $this->getBaseTable();
    return $this->database->select($table)
      ->fields($table, ['id'])
      ->condition('status', Shorty::SHORTY_STATUS_ACTIVE)
      ->condition('expire_on', $current_time, '<=')
      ->execute()->fetchCol();
  }

}
