<?php

namespace Drupal\cypress_smoketest\Controller;

use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for the endpoints that help out Cypress.
 */
class CypressSmoketestController extends ControllerBase {

  /**
   * A helper function returning results.
   */
  public function getRouterResults() {
    return [
      [
        "name" => "The Shawshank Redemption",
        "year" => 1994,
        "duration" => 142,
      ],
      [
        "name" => "The Godfather",
        "year" => 1972,
        "duration" => '',
      ],
      [
        "name" => "The Dark Knight",
        "year" => 2008,
        "duration" => 175,
      ],
      [
        "name" => "The Godfather: Part II",
        "year" => 1974,
        "duration" => '',
      ],
      [
        "name" => "Pulp Fiction",
        "year" => 1994,
        "duration" => '',
      ],
      [
        "name" => "The Lord of the Rings: The Return of the King",
        "year" => 2003,
        "duration" => '',
      ],
    ];
  }

  /**
   * Login.
   *
   * @return string
   *   Return Hello string.
   */
  public function login($role) {
    $roles = $this->entityTypeManager()->getStorage('user_role')->loadMultiple();
    $logger = $this->getLogger('cypress_smoketest');
    $user = $this->currentUser();
    if (array_key_exists($role, $roles)) {
      if ($user->isAnonymous()) {
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

        $timestamp = \Drupal::time()->getRequestTime()-10;
        $path = \Drupal::service('path.current')->getPath();
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
        // Login and retry to list routers.
        return new RedirectResponse($link);
      }
    }
    else {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('Role does not exist.'),
      ];
    }

    return new JsonResponse([
      'data' => $this->getRouterResults(),
      'method' => 'GET',
    ]);
  }

}
