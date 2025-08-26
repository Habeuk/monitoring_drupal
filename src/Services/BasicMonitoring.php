<?php

namespace Drupal\monitoring_drupal\Services;

use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Cette classe permet de faire un monotoring basic.
 * elle utilise un procesus PHP cli pour transferer les informations vers un
 * autre serveur.
 * elle est basique car c'est la premiere approche.
 * on va donner 10s au serveur pour transferer les donnés faute de quoi, il ya
 * un soucis.
 * https://stackoverflow.com/questions/37936674/how-to-exit-php-code-after-a-few-seconds
 *
 * @see https://symfony.com/doc/5.0/components/stopwatch.html#sections
 *
 * @author stephane
 *
 */
class BasicMonitoring {
  /**
   *
   * @var \Symfony\Component\Stopwatch\StopwatchEvent
   */
  protected $event;

  /**
   *
   * @var \Symfony\Component\Stopwatch\Stopwatch
   */
  protected $Stopwatch;

  function __construct(Stopwatch $Stopwatch) {
    $this->Stopwatch = $Stopwatch;
  }

  /**
   * On commence à suivre l'evolution.
   *
   * @param string $name
   * @param string $category
   */
  function startProfiling(string $name, string $category = null) {
    $this->Stopwatch->start($name);
  }

  /**
   *
   * @param string $name
   */
  function stopProfiling(string $name) {
    $this->event = $this->Stopwatch->stop($name);
  }

}