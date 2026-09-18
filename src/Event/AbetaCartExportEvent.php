<?php

declare(strict_types=1);

namespace Abeta\PunchOut\Event;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after the Abeta cart export data is fully assembled, so other plugins can add to it
 * without modifying this plugin.
 */
final class AbetaCartExportEvent extends Event implements ShopwareSalesChannelEvent
{
    public function __construct(
        private readonly Cart $cart,
        private array $data,
        private readonly SalesChannelContext $salesChannelContext,
    ) {
    }

    public function getCart(): Cart
    {
        return $this->cart;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }
}