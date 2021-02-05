<?php

namespace Boekuwzending\Magento\Api;

use Boekuwzending\Magento\Api\Data\OrderInterface;

interface OrderRepositoryInterface
{
    /**
     * Get the Boekuwzending orders for the given Magento orderId.
     *
     * @param string $orderId
     * @return array
     */
    public function getByOrderId(string $orderId): array;

    /**
     * Create a new order entity.
     *
     * @param array $data
     * @return OrderInterface
     */
    public function create(array $data = []): OrderInterface;
}
