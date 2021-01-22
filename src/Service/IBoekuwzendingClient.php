<?php
namespace Boekuwzending\Magento\Service;

use Magento\Sales\Model\Order as MagentoOrder;

interface IBoekuwzendingClient {
    function isStaging() : bool;
    function getOrderById(string $id) : \Boekuwzending\Resource\Order;
    function createOrder(MagentoOrder $order) : \Boekuwzending\Resource\Order;
}