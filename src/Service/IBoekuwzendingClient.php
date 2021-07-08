<?php
namespace Boekuwzending\Magento\Service;

use Boekuwzending\Resource\Order;
use Magento\Sales\Model\Order as MagentoOrder;

interface IBoekuwzendingClient {
    /**
     * Returns whether the module is configured for staging mode. False means live mode.
     * @return bool
     */
    public function isStaging() : bool;

    /**
     * Get a Boekuwzending order by its ID.
     *
     * @param string $id
     * @return Order|null
     */
    public function getOrderById(string $id) : ?Order;

    /**
     * Create a Boekuwzending order from a Magento order.
     *
     * @param MagentoOrder $order
     * @return Order|null
     */
    public function createOrder(MagentoOrder $order) : ?Order;
}