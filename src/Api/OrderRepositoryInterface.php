<?php

namespace Boekuwzending\Magento\Api;

interface OrderRepositoryInterface
{
    /**
     * Get the Boekuwzending orders for the given Magento orderId.
     *
     * @param string $orderId
     * @return array
     */
    public function getByOrderId($orderId);

    /**
     * Create a new order entity.
     *
     * @param array $data
     * @return OrderInterface
     */
    public function create(array $data = []);
}
