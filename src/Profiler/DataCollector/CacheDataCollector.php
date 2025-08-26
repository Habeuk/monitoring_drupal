<?php

namespace Drupal\monitoring_drupal\Profiler\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * Collects cache information from multiple backends.
 */
class CacheDataCollector implements DataCollectorInterface {
  protected $data = [];
  
  public function collect(Request $request, Response $response, \Throwable $exception = null) {
    $caches = \Drupal\Core\Cache\Cache::getBins();
    $total_hits = 0;
    $total_misses = 0;
    $total_sets = 0;
    $bin_stats = [];
    $caches_error = [];
    
    foreach ($caches as $bin => $cache) {
      if ('static' == $bin) {
        continue;
      }
      try {
        if ($cache) {
          $stats = method_exists($cache, 'getStats') ? $cache->getStats() : [];
          $bin_stats[$bin] = [
            'hits' => $stats['hits'] ?? 0,
            'misses' => $stats['misses'] ?? 0,
            'sets' => $stats['sets'] ?? 0
          ];
          $total_hits += $bin_stats[$bin]['hits'];
          $total_misses += $bin_stats[$bin]['misses'];
          $total_sets += $bin_stats[$bin]['sets'];
        }
        else {
          $caches_error[] = $bin;
        }
      }
      catch (\Exception $e) {
        $caches_error[] = $bin;
      }
    }
    
    $this->data = [
      'total_hits' => $total_hits,
      'total_misses' => $total_misses,
      'total_sets' => $total_sets,
      'bin_stats' => $bin_stats,
      'hit_ratio' => $total_hits + $total_misses > 0 ? ($total_hits / ($total_hits + $total_misses)) * 100 : 0,
      'caches_error' => $caches_error
    ];
  }
  
  public function getName() {
    return 'cache';
  }
  
  public function reset() {
    $this->data = [];
  }
  
  public function getDatas() {
    return $this->data;
  }
  
  public function getTotalHits(): int {
    return $this->data['total_hits'] ?? 0;
  }
  
  public function getTotalMisses(): int {
    return $this->data['total_misses'] ?? 0;
  }
  
  public function getTotalSets(): int {
    return $this->data['total_sets'] ?? 0;
  }
  
  public function getHitRatio(): float {
    return $this->data['hit_ratio'] ?? 0;
  }
  
  public function getBinStats(): array {
    return $this->data['bin_stats'] ?? [];
  }
  
  public function getBinError(): array {
    return $this->data['caches_error'] ?? [];
  }
}