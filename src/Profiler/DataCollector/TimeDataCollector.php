<?php

namespace Drupal\monitoring_drupal\Profiler\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class TimeDataCollector implements DataCollectorInterface {
  protected $stopwatch;
  protected $data = [];
  
  public function __construct(Stopwatch $stopwatch) {
    $this->stopwatch = $stopwatch;
  }
  
  public function collect(Request $request, Response $response, \Throwable $exception = null) {
    $this->data = [
      'total_time' => microtime(true) - $request->server->get('REQUEST_TIME_FLOAT'),
      'events' => $this->stopwatch->getSectionEvents('__root__')
    ];
  }
  
  public function getName() {
    return 'time';
  }
  
  public function reset() {
    $this->data = [];
    $this->stopwatch->reset();
  }
  
  public function getTotalTime(): float {
    return $this->data['total_time'] ?? 0;
  }
  
  public function getEvents(): array {
    return $this->data['events'] ?? [];
  }
}