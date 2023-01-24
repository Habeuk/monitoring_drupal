<?php

namespace Drupal\monitoring_drupal\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for monitoring_drupal routes.
 */
class MonitoringDrupalController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
