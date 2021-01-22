<?php

namespace Boekuwzending\Magento\Model\ResourceModel\Order;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Boekuwzending\Magento\Model\Order', 'Boekuwzending\Magento\Model\ResourceModel\Order');
    }
}