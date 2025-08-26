<?php

namespace Drupal\monitoring_drupal\Profiler\DataCollector;

use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * Collects database queries information.
 */
class DatabaseDataCollector implements DataCollectorInterface {
  protected $database;
  protected $data = [];
  
  public function __construct($database) {
    $this->database = $database;
  }
  
  public function collect(Request $request, Response $response, \Throwable $exception = null) {
    $queries = Database::getLog('wb_horizon_full_querry');
    usort($queries, function ($a, $b) {
      return $b['time'] <=> $a['time']; // décroissant
    });
    $this->data = [
      'query_count' => count($queries),
      'queries' => $queries,
      'total_time' => array_sum(array_column($queries, 'time'))
    ];
  }
  
  public function getName() {
    return 'database';
  }
  
  public function getDatas() {
    return $this->data;
  }
  
  public function reset() {
    $this->data = [];
  }
  
  public function getQueryCount(): int {
    return $this->data['query_count'] ?? 0;
  }
  
  public function getQueries(): array {
    return $this->data['queries'] ?? [];
  }
  
  public function getTotalTime(): float {
    return $this->data['total_time'] ?? 0;
  }
}