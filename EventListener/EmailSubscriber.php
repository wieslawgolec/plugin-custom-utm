<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCustomUtmBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use MauticPlugin\MauticCustomUtmBundle\Helper\CustomUtmHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Ensures that any custom UTM parameters stored on the Email entity
 * travel with the standard utmTags array when the email is sent / previewed.
 *
 * In practice the core already passes the whole utmTags array to
 * TrackableModel / RedirectModel, so this subscriber mainly acts as a
 * safety net and a place for future token resolution if needed.
 */
class EmailSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CustomUtmHelper $helper = new CustomUtmHelper(),
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_SEND    => ['onEmailSend', 0],
            EmailEvents::EMAIL_ON_DISPLAY => ['onEmailDisplay', 0],
        ];
    }

    public function onEmailSend(EmailSendEvent $event): void
    {
        $this->ensureCustomTagsPresent($event);
    }

    public function onEmailDisplay(EmailSendEvent $event): void
    {
        $this->ensureCustomTagsPresent($event);
    }

    private function ensureCustomTagsPresent(EmailSendEvent $event): void
    {
        $email = $event->getEmail();
        if (null === $email) {
            return;
        }

        $utmTags = $email->getUtmTags() ?? [];
        // Nothing to do – the whole array (including custom keys) is already
        // handed to the trackable / redirect layer by core code.
        // We keep the method for future extension points (e.g. token
        // replacement inside custom values).
        if ([] === $utmTags) {
            return;
        }

        // Optional: resolve simple tokens inside custom values if desired.
        // Left as a no-op for now to keep behaviour identical to core UTMs.
    }
}
