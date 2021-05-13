<?php

namespace Drupal\cypress_smoketest\Controller;

use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller for the endpoints that help out Cypress.
 */
class CypressSmoketestController extends ControllerBase {

  /**
   * Login.
   *
   * @return string
   *   Return Hello string.
   */
  public function login($role) {
    $roles = $this->entityTypeManager()->getStorage('user_role')->loadMultiple();
    $logger = $this->getLogger('cypress_smoketest');
    if (array_key_exists($role, $roles)) {
      $username = 'cypress_' . $role;

      // Check if qa user already exists.
      $user_storage = $this->entityTypeManager->getStorage('user');
      $uids = $user_storage
        ->getQuery()
        ->condition('name', $username)
        ->accessCheck(FALSE)
        ->range(0, 1)
        ->execute();

      if ($uids) {
        $logger->notice('User @name already exists.', ['@name' => $username]);
        $uid = (int) reset($uids);
        $user = User::load($uid);
      }
      else {
        /** @var \Drupal\user\Entity\User $user */
        $user = $user_storage->create();
        $user->enforceIsNew();
        $user->setUsername($username);
        $user->setEmail($username . '@example.com');
        $user->setPassword($username);
        if ($role !== 'authenticated') {
          $user->addRole($role);
        }
        $user->activate();
        $user->save();
        $logger->notice('Created user @name.', ['@name' => $username]);
      }

      $timestamp = \Drupal::time()->getRequestTime();
      $path = 'test';
      $link = Url::fromRoute(
        'user.reset.login',
        [
          'uid' => $user->id(),
          'timestamp' => $timestamp,
          'hash' => user_pass_rehash($user, $timestamp),
        ],
        [
          'absolute' => TRUE,
          'query' => $path ? ['destination' => $path] : [],
          'language' => \Drupal::languageManager()->getLanguage($user->getPreferredLangcode()),
        ]
      )->toString();
      return [
        '#type' => 'markup',
        '#markup' => $link,
      ];
    }
    else {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('Role does not exist.'),
      ];
    }
  }

}
