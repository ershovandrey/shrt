<?php

namespace Drupal\shorty;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a shorty entity type.
 */
interface ShortyInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

  /**
   * Returns the shorty status.
   *
   * @return bool
   *   TRUE if the shorty is enabled, FALSE otherwise.
   */
  public function isActive(): bool;

  /**
   * Sets the shorty status.
   *
   * @param int $status
   *   1 to enable this shorty, 0 to disable.
   *
   * @return \Drupal\shorty\ShortyInterface
   *   The called shorty entity.
   */
  public function setStatus(int $status): ShortyInterface;

  /**
   * Block the shorty.
   *
   * @return \Drupal\shorty\ShortyInterface
   *   The called shorty entity.
   */
  public function setBlocked(): ShortyInterface;

  /**
   * Gets the shorty creation timestamp.
   *
   * @return int
   *   Creation timestamp of the shorty.
   */
  public function getCreatedTime(): int;

  /**
   * Sets the shorty creation timestamp.
   *
   * @param int $timestamp
   *   The shorty creation timestamp.
   *
   * @return \Drupal\shorty\ShortyInterface
   *   The called shorty entity.
   */
  public function setCreatedTime(int $timestamp): ShortyInterface;

  /**
   * Gets the shorty expire_on timestamp.
   *
   * @return int|null
   *   Expire On timestamp of the shorty.
   */
  public function getExpireOnTime(): ?int;

  /**
   * Check if the shorty is expired.
   *
   * @return bool
   *   TRUE if shorty is expired, FALSE otherwise.
   */
  public function isExpired(): bool;

  /**
   * Get the shorty visits number.
   *
   * @return int
   *   Visits number.
   */
  public function getVisitsNumber(): int;

  /**
   * Increment visits number to 1.
   *
   * @return \Drupal\shorty\ShortyInterface
   *   The called shorty entity.
   */
  public function incrementVisits(): ShortyInterface;

  /**
   * Get shorty source URL.
   *
   * @return \Drupal\Core\Url
   *   The source URL.
   */
  public function getSourceUrl(): Url;

  /**
   * Get shorty destination URL.
   *
   * @return \Drupal\Core\Url
   *   The destination URL.
   */
  public function getDestinationUrl(): Url;

  /**
   * Get trimmed version of the destination URL.
   *
   * @return string
   *   Trimmed version of the Destination URL field value.
   */
  public function getDestinationUrlTrimmed(): string;

}
