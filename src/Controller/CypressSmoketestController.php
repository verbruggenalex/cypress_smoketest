<?php

namespace Drupal\cypress_smoketest\Controller;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for the endpoints that help out Cypress.
 */
class CypressSmoketestController extends ControllerBase {

  /**
   * Drupal\Core\Routing\RouteProviderInterface definition.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routerRouteProvider;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->routerRouteProvider = $container->get('router.route_provider');
    return $instance;
  }

  /**
   * Login endpoint to create and login user as role.
   * @param $role
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
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
          // $uid = (int) reset($uids);
          $uid = 1;
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

        $timestamp = \Drupal::time()->getRequestTime() - 10;
        $path = \Drupal::service('path.current')->getPath();

        // Login with same destination to re-list routers as logged in user.
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
        return new RedirectResponse($link);
      }
      else {
        return [
          '#type' => 'markup',
          '#markup' => $this->t('User is logged in.'),
        ];
      }
    }
    else {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('Role does not exist.'),
      ];
    }
  }

  /**
   * Get watchdog messages between two timestamps.
   *
   * @todo: apparently timestamps on JS and PHP are different. Needs to be resolved.
   *
   * @param int $test_start_timestamp
   * @param int $test_end_timestamp
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getWatchdogPhpMessages(int $test_start_timestamp, int $test_end_timestamp) {
    $errors = [];
    $result = \Drupal::database()->select('watchdog', 'w')
      ->fields('w', [])
      ->condition('w.type', 'php', '=')
      ->condition('w.timestamp', [$test_start_timestamp, $test_end_timestamp], 'BETWEEN')
      ->execute();
    foreach ($result as $entry) {
      $messagePlaceholders = unserialize($entry->variables);
      $message = new FormattableMarkup($entry->message, $messagePlaceholders);
      $errors[] = strip_tags($message);
    }
    return new JsonResponse(json_encode($errors));
  }

  /**
   * Helper function to return all admin menu links for current user.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getAdminMenuLinksForCurrentUser() {
    // Get all links from the toolbar.
    $menu_tree = \Drupal::service('toolbar.menu_tree');
    $parameters = new MenuTreeParameters();
    $parameters->setMinDepth(2)->setMaxDepth(5);
    $tree = $menu_tree->load('admin', $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ['callable' => 'toolbar_menu_navigation_links'],
    ];
    $tree = $menu_tree->transform($tree, $manipulators);
    $links = $this->retrieveLinks($tree);
    // Remove some things we don't really want or fail.
    $links = array_filter($links, function ($link) {
      return (stripos($link, '?') === FALSE);
    });
    $links = array_diff($links, [
      '/update.php',
      '/user/logout',
      '/admin/structure/contact/manage/personal',
      '/admin/structure/contact/manage/personal/delete',
    ]);
    return new JsonResponse($links);
  }

  /**
   * Get menu links out of tree.
   *
   * @param $tree
   *
   * @return array
   */
  public function retrieveLinks($tree) {
    $links = [];

    foreach ($tree as $element) {
      $links[] = $element->link->getUrlObject()->toString();
      if ($element->subtree) {
        $links = array_merge($links, $this->retrieveLinks($element->subtree));
      }
    }
    return $links;
  }

}
