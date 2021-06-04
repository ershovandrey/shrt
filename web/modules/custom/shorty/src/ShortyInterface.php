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
   * Returns the shorty status.
   *
   * @return bool
   *   TRUE if the shorty is enabled, FALSE otherwise.
   */
  public function isActive(): bool;

  /**
   * Sets the shorty status.
   *
   * @param bool $status
   *   TRUE to enable this shorty, FALSE to disable.
   *
   * @return \Drupal\shorty\ShortyInterface
   *   The called shorty entity.
   */
  public function setStatus(bool $status): ShortyInterface;

  /**
   * Gets the shorty expire_on timestamp.
   *
   * @return int|null
   *   Expire On timestamp of the shorty.
   */
  public function getExpireOnTime(): ?int;

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
   * Get the Hash value.
   *
   * @return string
   *   The shorty Hash value.
   */
  public function getHashValue(): string;

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
