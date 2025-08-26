<?php

namespace Drupal\monitoring_drupal\Services;

use Drupal\Component\Utility\Timer;

/**
 *
 * @author stephane
 */
class TimerMonitoring extends Timer {
  
  static public function stop($name) {
    $time = [];
    if (!empty(static::$timers[$name])) {
      $time = parent::stop($name);
      if ($time) {
        // static::sendData($time);
      }
    }
    return $time;
  }
  
  /**
   * Envoit les données vers le serveur
   */
  static protected function sendData($time) {
    
    /**
     *
     * @var \GuzzleHttp\Client $curl
     */
    $curl = \Drupal::httpClient();
    $uri = "http://analyse-perfomance.kksa/monitoring-server/save-performance";
    $routeName = \Drupal::routeMatch()->getRouteName();
    $datas = [
      'timer' => $time,
      'route_name' => $routeName
    ];
    try {
      $curl->post($uri, $datas);
    }
    catch (\Exception $e) {
      \Drupal::logger('monitoring_drupal')->error($e->getMessage());
    }
  }
  
}