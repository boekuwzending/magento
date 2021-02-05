<?php

namespace Boekuwzending\Magento\Api;

use Boekuwzending\Resource\Order;

interface OrderServiceInterface {

    /**
     * @param $order_id
     * @return Order
     */
    public function getById($order_id): Order;
}