<?php

namespace Drupal\monitoring_drupal\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Drupal\monitoring_drupal\Services\TimerMonitoring;
use Drupal\monitoring_drupal\Profiler\Profiler;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Render\RendererInterface;

/**
 * monitoring_drupal event subscriber.
 */
class MonitoringDrupalSubscriber implements EventSubscriberInterface {
  
  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *        The messenger.
   */
  public function __construct(private MessengerInterface $messenger, private Profiler $profiler, private Stopwatch $stopwatch, private RendererInterface $renderer) {
  }
  
  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *        Response event.
   */
  public function onKernelRequest(RequestEvent $event) {
    if (!$event->isMainRequest()) {
      return;
    }
    $this->stopwatch->openSection();
    $this->stopwatch->start('drupal_request', 'request');
  }
  
  /**
   * Kernel response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *        Response event.
   */
  public function onKernelResponse(ResponseEvent $event) {
    if (!$event->isMainRequest()) {
      return;
    }
    
    $this->stopwatch->stop('drupal_request');
    $this->profiler->collect($event->getRequest(), $event->getResponse());
    
    // Ajouter la toolbar au response
    $this->injectToolbar($event->getResponse());
  }
  
  protected function injectToolbar(Response $response) {
    $content = $response->getContent();
    
    if (strpos($response->headers->get('Content-Type'), 'text/html') === false) {
      return;
    }
    
    $toolbar = $this->renderToolbar();
    
    $pos = strripos($content, '</body>');
    if ($pos !== false) {
      $content = substr($content, 0, $pos) . $toolbar . substr($content, $pos);
      $response->setContent($content);
    }
  }
  
  protected function renderToolbar(): string {
    $collectors = $this->profiler->getCollectors();
    
    $build = [
      '#theme' => 'webprofiler_profiler_toolbar',
      '#time' => $collectors['time']->getTotalTime(),
      '#memory' => memory_get_peak_usage(true) / 1024 / 1024,
      '#queries' => $collectors['database']->getQueryCount(),
      '#cache_hits' => $collectors['cache']->getTotalHits(),
      '#cache_misses' => $collectors['cache']->getTotalMisses(),
      '#cache_ratio' => $collectors['cache']->getHitRatio(),
      '#cache_sets' => $collectors['cache']->getTotalSets(),
      '#caches_errors' => $collectors['cache']->getBinError()
    ];
    return $this->renderer->renderInIsolation($build);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => [
        'onKernelRequest',
        1000
      ],
      KernelEvents::RESPONSE => [
        'onKernelResponse',
        1000
      ]
    ];
  }
}
