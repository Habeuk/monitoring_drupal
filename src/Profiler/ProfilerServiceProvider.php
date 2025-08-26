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
  
  public function register(ContainerBuilder $container) {
    // On enlève la factory problématique et on crée les collectors directement
  }
  
  public static function getDataCollectors(ContainerBuilder $container) {
    $collectors = [];
    
    // Récupère manuellement les services sans créer de dépendance circulaire
    $serviceIds = [
      'monitoring_drupal.data_collector.database',
      'monitoring_drupal.data_collector.memory',
      'monitoring_drupal.data_collector.time',
      'monitoring_drupal.data_collector.cache'
    ];
    
    foreach ($serviceIds as $serviceId) {
      if ($container->has($serviceId)) {
        $collector = $container->get($serviceId);
        $collectors[$collector->getName()] = $collector;
      }
    }
    
    return $collectors;
  }
}