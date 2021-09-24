<?php

namespace Boekuwzending\Magento\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Example event observer for intercepting orders before they get sent to the API.
 *
 * See src/etc/events.xml
 */
class OrderCreateBeforeObserver implements ObserverInterface
{
    public function __construct()
    {
    }

    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        /** @var Boekuwzending\Resource\Order $order */
        $order = $observer->getData('boekuwzending_order');

        /** @var Boekuwzending\Resource\Address $shipTo */
        $shipTo = $order->getShipToAddress();

        $shipTo->setStreet('Observerstreet');
        $shipTo->setNumber('NROBS');
        $shipTo->setNumberAddition('AddObs');

        /** @var Magento\Sales\Model\Order $magentoOrder */
        $magentoOrder = $observer->getData('magento_order');

        dd($magentoOrder->getShippingAddress()->getStreet());
    }
}
