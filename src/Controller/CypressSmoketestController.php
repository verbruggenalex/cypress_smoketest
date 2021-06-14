<?php

namespace Drupal\cypress_smoketest\Controller;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\user\UserInterface;
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
   * Drupal\Core\Path\CurrentPathStack definition.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $pathCurrent;

  /**
   * Drupal\Component\Datetime\TimeInterface definition.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $datetimeTime;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Menu\MenuLinkTreeInterface definition.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $toolbarMenuTree;

  /**
   * Drupal\Core\Language\LanguageManagerInterface definition.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->routerRouteProvider = $container->get('router.route_provider');
    $instance->pathCurrent = $container->get('path.current');
    $instance->datetimeTime = $container->get('datetime.time');
    $instance->database = $container->get('database');
    $instance->toolbarMenuTree = $container->get('toolbar.menu_tree');
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * Login endpoint to create and login user as role.
   *
   * @param string $role
   *   The name of the role for which we need to login.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to use a one time login link.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function login(string $role) {
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
          /** @var \Drupal\user\UserInterface $user */
          $user = $user_storage->load($uid);
        }
        else {
          /** @var \Drupal\user\UserInterface $user */
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

        $timestamp = $this->datetimeTime->getRequestTime() - 10;
        $path = $this->pathCurrent->getPath();

        // Login with same destination as current page.
        if ($user instanceof UserInterface) {
          /** @var string $link */
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
              'language' => $this->languageManager->getLanguage($user->getPreferredLangcode()),
            ]
          )->toString(FALSE);
          return new RedirectResponse($link);
        }
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
   * @todo apparently timestamps on JS and PHP are different. Needs to be resolved.
   *
   * @param int $test_start_timestamp
   *   The start time for when we want to fetch watchdog messages.
   * @param int $test_end_timestamp
   *   The end time until when we want to fetch watchdog messages.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A json response in the form of an array.
   */
  public function getWatchdogPhpMessages(int $test_start_timestamp, int $test_end_timestamp) {
    $errors = [];
    $result = $this->database->select('watchdog', 'w')
      ->fields('w', [])
      ->condition('w.type', 'php', '=')
      ->condition('w.timestamp', [$test_start_timestamp, $test_end_timestamp], 'BETWEEN')
      ->execute();
    if ($result) {
      foreach ($result as $entry) {
        $messagePlaceholders = unserialize($entry->variables);
        $message = new FormattableMarkup(str_replace(' @backtrace_string', '', $entry->message), $messagePlaceholders);
        $errors[] = [
          'wid' => $entry->wid,
          'message' => strip_tags($message),
        ];
      }
    }

    return new JsonResponse(json_encode($errors));
  }

  /**
   * Helper function to return all admin menu links for current user.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A json response in the form of an array of urls.
   */
  public function getAdminMenuLinksForCurrentUser(): JsonResponse {
    // Get all links from the toolbar.
    $parameters = new MenuTreeParameters();
    $parameters->setMinDepth(2)->setMaxDepth(5);
    $menu_tree = $this->toolbarMenuTree;
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
   * @param array $tree
   *   The tree out of which we want to get links.
   *
   * @return array
   *   An array of links retrieved from the menu tree.
   */
  public function retrieveLinks(array $tree): array {
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
