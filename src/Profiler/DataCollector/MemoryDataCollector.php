<?php

namespace Drupal\monitoring_drupal\Profiler\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * Collects memory usage data.
 */
class MemoryDataCollector implements DataCollectorInterface {
  protected $data = [];
  
  public function collect(Request $request, Response $response, \Throwable $exception = null) {
    $this->data = [
      'memory_usage' => memory_get_usage(true),
      'memory_peak' => memory_get_peak_usage(true)
    ];
  }
  
  public function getName() {
    return 'memory';
  }
  
  public function reset() {
    $this->data = [];
  }
  
  public function getMemoryUsage(): int {
    return $this->data['memory_usage'] ?? 0;
  }
  
  public function getPeakMemory(): int {
    return $this->data['memory_peak'] ?? 0;
  }
}