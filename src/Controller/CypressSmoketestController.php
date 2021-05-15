<?php

namespace Drupal\cypress_smoketest\Controller;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
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
    }
    else {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('Role does not exist.'),
      ];
    }
    $shortlist = array_slice($list, -10);
    $shortlist = array_diff($shortlist, ['/views/ajax', '/admin/views/ajax/autocomplete/tag']);

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
   *
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
