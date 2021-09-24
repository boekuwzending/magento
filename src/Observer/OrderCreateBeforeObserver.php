<?php

namespace Boekuwzending\Magento\Observer;

use Magento\Framework\Event\ObserverInterface;

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
    }
}
