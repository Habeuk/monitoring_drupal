<?php

namespace Drupal\monitoring_drupal\Profiler;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service provider for the profiler.
 */
class ProfilerServiceProvider extends ServiceProviderBase {
  protected $container;
  
  public function __construct($container) {
    $this->container = $container;
  }
  
  public function getDataCollectors() {
    $collectors = [];
    $serviceIds = $this->container->getServiceIds();
    
    foreach ($serviceIds as $serviceId) {
      if (strpos($serviceId, 'monitoring_drupal.data_collector.') === 0) {
        $collector = $this->container->get($serviceId);
        $collectors[$collector->getName()] = $collector;
      }
    }
    
    return $collectors;
  }
}