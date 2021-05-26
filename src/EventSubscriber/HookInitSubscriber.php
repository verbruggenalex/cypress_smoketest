<?php

namespace Drupal\cypress_smoketest\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Class HookInitSubscriber.
 */
class HookInitSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new HookInitSubscriber object.
   */
  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['kernel.controller'] = ['kernelController'];

    return $events;
  }

  /**
   * This method is called when the kernel.controller is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function kernelController(Event $event) {
//    \Drupal::messenger()->addMessage('Event kernel.controller thrown by Subscriber in module cypress_smoketest.', 'status', TRUE);
    $this->logPhpMessage();
  }

  /**
   * Creates a watchdog php message with the following context.
   */
  public function logPhpMessage() {
    $level = 3;
    $message = '%type: @message in %function (line %line of %file) @backtrace_string.';
    $context = [
      '@backtrace_string' => '',
      'channel' => 'php',
      '%type' => 'RuntimeException',
      '@message' => 'Failed to start the session because headers have already been sent by "/home/project/web/modules/contrib/watchdog_registry/watchdog_registry.module" at line 19.',
      '%function' => 'Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage->start()',
      '%file' => '/home/project/vendor/symfony/http-foundation/Session/Storage/NativeSessionStorage.php',
      '%line' => 150,
      'severity_level' => 3,
    ];

    \Drupal::logger('cypress_smoketest')->log($level, $message, $context);
  }

}
