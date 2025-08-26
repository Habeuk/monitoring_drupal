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
  public function __construct(private MessengerInterface $messenger, private Profiler $profiler, private Stopwatch $stopwatch) {
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
    $this->stopwatch->start('request', 'request');
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
    
    $this->stopwatch->stop('request');
    $this->profiler->collect($event->getRequest(), $event->getResponse());
    
    // Ajouter la toolbar au response
    $this->injectToolbar($event->getResponse());
  }
  
  protected function injectToolbar(Response $response) {
    if (strpos($response->headers->get('Content-Type'), 'text/html') === false) {
      return;
    }
    
    $content = $response->getContent();
    $toolbar = $this->renderToolbar();
    
    // Insérer la toolbar avant la fermeture du body
    $pos = strripos($content, '</body>');
    if ($pos !== false) {
      $content = substr($content, 0, $pos) . $toolbar . substr($content, $pos);
      $response->setContent($content);
    }
  }
  
  protected function renderToolbar(): string {
    $collectors = $this->profiler->getCollectors();
    
    $data = [
      'time' => $collectors['time']->getTotalTime(),
      'memory' => memory_get_peak_usage(true) / 1024 / 1024,
      'queries' => $collectors['database']->getQueryCount() ?? 0
    ];
    
    return '
    <div id="web-profiler" style="position: fixed; bottom: 0; right: 0; background: #333; color: white; padding: 10px; z-index: 10000;">
      Time: ' . round($data['time'] * 1000, 2) . 'ms |
      Memory: ' . round($data['memory'], 2) . 'MB |
      Queries: ' . $data['queries'] . '
    </div>';
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
