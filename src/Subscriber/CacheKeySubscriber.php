<?php
declare(strict_types=1);

namespace Abeta\PunchOut\Subscriber;

use Abeta\PunchOut\Struct\AbetaSession;
use Shopware\Core\Framework\Adapter\Cache\Event\HttpCacheCookieEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CacheKeySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            HttpCacheCookieEvent::class => 'onGenerateCacheHash',
        ];
    }

    public function onGenerateCacheHash(HttpCacheCookieEvent $event): void
    {
        if ($event->context->hasExtension(AbetaSession::EXTENSION_NAME)) {
            $event->add('abeta-session', '1');
        }
    }
}
