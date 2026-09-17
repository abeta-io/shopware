<?php declare(strict_types=1);

namespace MagmodulesAbeta\Subscriber;

use MagmodulesAbeta\Struct\AbetaSession;
use Shopware\Core\System\SalesChannel\Event\SwitchContextEvent;
use Shopware\Core\System\SalesChannel\Exception\ContextPermissionsLockedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContextSwitchSubscriber implements EventSubscriberInterface
{
    public const KEEP_ABETA_CONTEXT_TOKEN = 'keepAbetaContext';

    public static function getSubscribedEvents(): array
    {
        return [
            SwitchContextEvent::DATABASE_CHECK => 'onSwitchContext',
        ];
    }

    public function onSwitchContext(SwitchContextEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();

        if (!$salesChannelContext->hasExtension(AbetaSession::EXTENSION_NAME)) {
            return;
        }

        try {
            $salesChannelContext->setPermissions([
                ...$salesChannelContext->getPermissions(),
                self::KEEP_ABETA_CONTEXT_TOKEN => true,
            ]);
        } catch (ContextPermissionsLockedException $e) {
            // ignore
        }
    }
}
