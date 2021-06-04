<?php

namespace Drupal\shorty;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the shorty entity type.
 */
class ShortyAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer shorty')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $is_owner = ($account->id() && $account->id() === $entity->getOwnerId());
    switch ($operation) {
      case 'view':
        if ($account->hasPermission('view any shorty')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($is_owner && $account->hasPermission('view own shorty')) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        return AccessResult::neutral("The following permissions are required: 'view any shorty' OR 'view own shorty'.")->cachePerPermissions();

      case 'update':
        if ($account->hasPermission('edit any shorty')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($is_owner && $account->hasPermission('edit own shorty')) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        return AccessResult::neutral("The following permissions are required: 'update any shorty' OR 'update own shorty'.")->cachePerPermissions();

      case 'delete':
        if ($account->hasPermission('delete any shorty')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($is_owner && $account->hasPermission('delete own shorty')) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser()->addCacheableDependency($entity);
        }
        return AccessResult::neutral("The following permissions are required: 'delete any shorty' OR 'delete own shorty'.")->cachePerPermissions();

      default:
        // No opinion.
        return AccessResult::neutral()->cachePerPermissions();
    }

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $permissions = ['create shorty', 'administer shorty'];
    return AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
  }

}
