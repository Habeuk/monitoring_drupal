<?php

namespace Drupal\monitoring_drupal\Profiler;

use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Profiler {
  protected $collectors = [];
  protected $eventDispatcher;
  
  public function __construct(array $collectors, EventDispatcherInterface $eventDispatcher) {
    $this->collectors = $collectors;
    $this->eventDispatcher = $eventDispatcher;
  }
  
  public function collect(Request $request, Response $response) {
    foreach ($this->collectors as $collector) {
      $collector->collect($request, $response);
    }
  }
  
  public function getCollectors(): array {
    return $this->collectors;
  }
  
  public function getCollector(string $name): ?DataCollectorInterface {
    return $this->collectors[$name] ?? null;
  }
}