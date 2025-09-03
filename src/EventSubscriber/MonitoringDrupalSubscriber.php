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
  private array $requestData = [];
  private array $responseData = [];
  private array $additionalInfo = [];
  
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
    $request = $event->getRequest();
    
    // Récupérer les informations de la requête
    $requestInfo = [
      'method' => $request->getMethod(),
      'uri' => $request->getRequestUri(),
      'scheme' => $request->getScheme(),
      'host' => $request->getHost(),
      'port' => $request->getPort(),
      'path' => $request->getPathInfo(),
      'query_string' => $request->getQueryString(),
      'client_ip' => $request->getClientIp(),
      'user_agent' => $request->headers->get('User-Agent'),
      'accept' => $request->headers->get('Accept'),
      'content_type' => $request->headers->get('Content-Type'),
      'is_ajax' => $request->isXmlHttpRequest() ? "true" : "false",
      'is_secure' => $request->isSecure() ? "true" : "false"
    ];
    $this->requestData = $requestInfo;
    
    $this->stopwatch->openSection();
    $this->stopwatch->start('drupal_request', 'request');
    // on lance la collecte des requtes.
    \Drupal\Core\Database\Database::startLog('wb_horizon_full_querry');
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
    $request = $event->getRequest();
    $response = $event->getResponse();
    // Récupérer les informations de la réponse
    $responseInfo = [
      'status_code' => $response->getStatusCode(),
      'status_text' => $response->getStatusCode() . ' ' . Response::$statusTexts[$response->getStatusCode()],
      'content_type' => $response->headers->get('Content-Type'),
      'content_length' => $response->headers->get('Content-Length'),
      'charset' => $response->getCharset()
    ];
    $this->responseData = $responseInfo;
    $additionalInfo = [
      'locale' => $request->getLocale(),
      'default_locale' => $request->getDefaultLocale(),
      'languages' => $request->getLanguages(),
      'charsets' => $request->getCharsets(),
      'encodings' => $request->getEncodings(),
      // 'acceptable_content_types' => $request->getAcceptableContentTypes(),
      'auth_user' => $request->getUser(),
      'auth_password' => $request->getPassword(),
      'session_id' => $request->getSession() ? $request->getSession()->getId() : null,
      'route_name' => $request->attributes->get('_route'),
      // 'route_params' => $request->attributes->get('_route_params', []),
      'controller' => $request->attributes->get('_controller')
    ];
    $this->additionalInfo = $additionalInfo;
    
    /**
     * pour filtrer d'avantages.
     */
    // Ne pas injecter pour les requêtes Ajax
    // if ($request->isXmlHttpRequest()) {
    // return;
    // }
    // // Vérifier le type de contenu (uniquement HTML)
    // $contentType = $response->headers->get('Content-Type');
    // if (strpos($contentType, 'text/html') === false) {
    // return;
    // }
    // // Vérifier le code de statut (uniquement les réponses réussies)
    // if ($response->getStatusCode() !== 200) {
    // return;
    // }
    
    $this->stopwatch->stop('drupal_request');
    $this->profiler->collect($event->getRequest(), $event->getResponse());
    // Ajouter la toolbar au response
    $this->injectToolbar($event->getResponse());
  }
  
  protected function injectToolbar(Response $response) {
    $content = $response->getContent();
    if (str_contains($this->responseData['content_type'], "text/html") || str_contains($this->responseData['content_type'], "application/json")) {
      $toolbar = $this->renderToolbar();
      if (!empty($toolbar)) {
        $pos = strripos($content, '</body>');
        if ($pos !== false) {
          $content = substr($content, 0, $pos) . $toolbar . substr($content, $pos);
          $response->setContent($content);
        }
      }
    }
  }
  
  protected function renderToolbar(): string {
    $collectors = $this->profiler->getCollectors();
    $memory_usage = $collectors['memory']->getMemoryUsage();
    $memory_peak = $collectors['memory']->getPeakMemory();
    $build = [
      '#theme' => 'webprofiler_profiler_toolbar',
      '#time' => $collectors['time']->getTotalTime() * 1000,
      '#memory_usage' => $memory_usage ? $memory_usage / 1024 / 1024 : 0,
      '#memory_peak' => $memory_peak ? $memory_peak / 1024 / 1024 : 0,
      '#queries' => $collectors['database']->getQueryCount(),
      '#queries_time' => $collectors['database']->getTotalTime() * 1000,
      '#queries_list' => $collectors['database']->getQueries(),
      '#cache_hits' => $collectors['cache']->getTotalHits(),
      '#cache_misses' => $collectors['cache']->getTotalMisses(),
      '#cache_ratio' => $collectors['cache']->getHitRatio(),
      '#cache_sets' => $collectors['cache']->getTotalSets(),
      '#caches_errors' => $collectors['cache']->getBinError(),
      '#response_data' => $this->responseData,
      '#request_data' => $this->requestData,
      '#additional_info' => $this->additionalInfo
    ];
    if (!empty($this->requestData['uri'])) {
      // $time = round($build['#time'], 3);
      // $uri = $time . 'ms---' . str_replace('/', '__',
      // $this->requestData['uri']);
      // \Stephane888\Debug\debugLog::$max_depth = 10;
      // \Stephane888\Debug\debugLog::symfonyDebug($build, $uri, true);
    }
    if (str_contains($this->responseData['content_type'], "text/html"))
      return $this->renderer->renderInIsolation($build);
    else
      return '';
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
