<?php

namespace Drupal\monitoring_drupal\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Drupal\monitoring_drupal\Services\TimerMonitoring;

/**
 * monitoring_drupal event subscriber.
 */
class MonitoringDrupalSubscriber implements EventSubscriberInterface {
  
  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;
  
  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *        The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }
  
  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *        Response event.
   */
  public function onKernelRequest(RequestEvent $event) {
    TimerMonitoring::start('start_build_page');
    // $this->messenger->addStatus(__FUNCTION__);
  }
  
  /**
   * Kernel response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *        Response event.
   */
  public function onKernelResponse(ResponseEvent $event) {
    // $this->messenger->addStatus(__FUNCTION__);
    $time = TimerMonitoring::stop('start_build_page');
    // dump($time);
  }
  
  /**
   *
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => [
        'onKernelRequest'
      ],
      KernelEvents::RESPONSE => [
        'onKernelResponse'
      ]
    ];
  }
  
}
