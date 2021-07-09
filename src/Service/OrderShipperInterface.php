<?php


namespace Boekuwzending\Magento\Service;

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
     * @return mixed
     */
    public function ship(string $orderId, string $carrierCode, string $carrierTitle, string $trackingNumber);
}