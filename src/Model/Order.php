<?php

namespace Boekuwzending\Magento\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Boekuwzending\Magento\Api\Data\OrderInterface;

class Order extends AbstractModel implements OrderInterface
{
    public const FIELD_BOEKUWZENDING_ORDER_ID = 'boekuwzending_order_id'; // PK
    public const FIELD_SALES_ORDER_ID = 'sales_order_id'; // FK
    public const FIELD_BOEKUWZENDING_EXTERNAL_ORDER_ID = 'boekuwzending_external_order_id';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param OrderFactory $orderFactory
     * @param DateTime $dateTime
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        DateTime $dateTime,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $dateTime, $resource, $resourceCollection, $data);
        $this->_init(__CLASS__);
    }

    /**
     * Get the id at Boekuwzending (external from this viewpoint)
     *
     * @return string
     */
    public function getBoekuwzendingExternalOrderId() : mixed
    {
        return $this->getData(static::FIELD_BOEKUWZENDING_EXTERNAL_ORDER_ID);
    }

    /**
     * Set the id at Boekuwzending (external from this viewpoint)
     *
     * @param string $value
     * @return $this
     */
    public function setBoekuwzendingExternalOrderId($value): Order
    {
        return $this->setData(static::FIELD_BOEKUWZENDING_EXTERNAL_ORDER_ID, $value);
    }

    /**
     * Set the Magento order id.
     *
     * @param string $value
     * @return $this
     */
    public function setSalesOrderId($value)
    {
        return $this->setData(static::FIELD_SALES_ORDER_ID, $value);
    }

    /**
     * Get the Magento order id.
     *
     * @return string
     */
    public function getSalesOrderId()
    {
        return $this->getData(static::FIELD_SALES_ORDER_ID);
    }
}
