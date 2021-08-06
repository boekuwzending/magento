<?php

namespace Boekuwzending\Magento\Api;

use Boekuwzending\Magento\Api\Data\OrderInterface;

interface OrderRepositoryInterface
{
    /**
     * Get the Boekuwzending orders for the given Magento orderId.
     *
     * @param int $orderId
     * @return OrderInterface[]|null
     */
    public function getByOrderId(int $orderId): ?array;

    /**
     * Create a new order entity.
     *
     * @param array $data
     * @return OrderInterface
     */
    public function create(array $data = []): OrderInterface;

    /**
     * @param string $externalOrderId
     * @return OrderInterface
     */
    public function getByExternalOrderId(string $externalOrderId): ?OrderInterface;
}
