<?php

namespace Drupal\monitoring_drupal\Profiler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Profiler for Drupal monitoring.
 */
class Profiler {
  protected $collectors = [];
  protected $eventDispatcher;
  
  public function __construct(array $collectors, EventDispatcherInterface $eventDispatcher) {
    $this->collectors = $collectors;
    $this->eventDispatcher = $eventDispatcher;
  }
  
  /**
   * Collect data from all collectors.
   */
  public function collect(Request $request, Response $response) {
    foreach ($this->collectors as $collector) {
      $collector->collect($request, $response);
    }
  }
  
  /**
   * Get all collectors.
   */
  public function getCollectors(): array {
    return $this->collectors;
  }
  
  /**
   * Get a specific collector by name.
   */
  public function getCollector(string $name): ?DataCollectorInterface {
    return $this->collectors[$name] ?? null;
  }
  
  /**
   * Check if profiler is enabled.
   */
  public function isEnabled(): bool {
    return \Drupal::config('monitoring_drupal.settings')->get('enabled') ?? FALSE;
  }
}