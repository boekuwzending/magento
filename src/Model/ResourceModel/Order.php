<?php

namespace Boekuwzending\Magento\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Order extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('boekuwzending_order', 'boekuwzending_order_id');
    }
}
