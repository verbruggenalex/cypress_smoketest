<?php

namespace Drupal\cypress_smoketest\Controller;

use Drupal\Core\Controller\ControllerBase;

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
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: login with parameter(s): $role'),
    ];
  }

}
