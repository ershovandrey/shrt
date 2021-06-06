<?php

namespace Drupal\shorty\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\shorty\Service\ShortyHelper;
use Drupal\shorty\ShortyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Shorty event subscriber.
 */
class ShortUrlRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected CurrentPathStack $currentPath;

  /**
   * Shorty Helper Service.
   *
   * @var \Drupal\shorty\Service\ShortyHelper
   */
  protected ShortyHelper $helper;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   Current path service.
   * @param \Drupal\shorty\Service\ShortyHelper $helper
   *   Shorty Helper Service.
   */
  public function __construct(MessengerInterface $messenger, CurrentPathStack $current_path, ShortyHelper $helper) {
    $this->messenger = $messenger;
    $this->currentPath = $current_path;
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onKernelRequest', 0],
    ];
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Request event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onKernelRequest(RequestEvent $event): void {
    $path = $this->currentPath->getPath();
    $short_url = str_replace("/", "", $path);

    if ($this->helper->validateShortUrl($short_url)) {
      $shorty = $this->helper->getShortyByShortUrl($short_url, TRUE);
      if ($shorty instanceof ShortyInterface) {
        if (!$shorty->isExpired()) {
          // Found not expired shorty - redirect to the long URL.
          $this->shortyRedirect($shorty);
          return;
        }

        // Found active shorty, but it's already expired and should block it.
        $shorty->setDisabled()->save();
      }
    }
  }

  /**
   * Redirect to the short URL.
   *
   * @param \Drupal\shorty\ShortyInterface $shorty
   *   Shorty entity.
   */
  protected function shortyRedirect(ShortyInterface $shorty): void {
    $url = $shorty->getDestinationUrl()->toString();
    $url = str_replace(["\n", "\r"], '', $url);

    session_write_close();

    $response = new RedirectResponse($url);
    $response->send();

    $shorty->incrementVisits();
    exit();
  }

}
