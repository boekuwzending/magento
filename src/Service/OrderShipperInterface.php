<?php

namespace Boekuwzending\Magento\Service;

use Magento\Sales\Model\Order\Shipment;

interface OrderShipperInterface
{
    /**
     * This ships the order.
     *
     * @param string $orderId
     * @param string $carrierCode
     * @param string $carrierTitle
     * @param string $trackingNumber
     *
     * @return Shipment
     */
    public function ship(string $orderId, string $carrierCode, string $carrierTitle, string $trackingNumber);
}