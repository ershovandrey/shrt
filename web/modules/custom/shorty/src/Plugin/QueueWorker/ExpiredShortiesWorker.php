<?php

namespace Drupal\shorty\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\shorty\Entity\Shorty;

/**
 * Defines 'expired_shorties' queue worker.
 *
 * @QueueWorker(
 *   id = "expired_shorties",
 *   title = @Translation("Expired Shorties Worker"),
 *   cron = {"time" = 60}
 * )
 */
class ExpiredShortiesWorker extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    // Block expired shorty entity.
    if (is_numeric($data)) {
      $shorty = Shorty::load($data);
      if (
        $shorty &&
        $shorty->isExpired() &&
        $shorty->isActive()
      ) {
        $shorty->setBlocked()->save();
      }
    }
  }

}
