<?php

namespace Boekuwzending\Magento\Api;

interface OrderServiceInterface {

    /**
     * @param $order_id
     *
     * @return \Boekuwzending\Resource\Order
     */
    public function getById($order_id);
}