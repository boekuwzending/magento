<?php

namespace Boekuwzending\Magento\Model;

use JsonSerializable;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Boekuwzending\Magento\Api\Data\OrderInterface;

class Order extends AbstractModel implements OrderInterface, JsonSerializable
{
    public const FIELD_BOEKUWZENDING_ORDER_ID = 'boekuwzending_order_id'; // PK
    public const FIELD_SALES_ORDER_ID = 'sales_order_id'; // FK
    public const FIELD_BOEKUWZENDING_EXTERNAL_ORDER_ID = 'boekuwzending_external_order_id';

    /**
     * Do not touch - Magento/Symfony-specific. Everything breaks if you change it.
     *
     * @noinspection MagicMethodsValidityInspection
     * @noinspection ReturnTypeCanBeDeclaredInspection
     * @noinspection ClassConstantCanBeUsedInspection
     */
    protected function _construct()
    {
        $this->_init('Boekuwzending\Magento\Model\ResourceModel\Order');
    }

    /**
     * Get the entity id.
     *
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->getData(static::FIELD_BOEKUWZENDING_ORDER_ID);
    }

    /**
     * Get the id at Boekuwzending (external from this viewpoint)
     *
     * @return array|mixed|null
     */
    public function getBoekuwzendingExternalOrderId(): string
    {
        return $this->getData(static::FIELD_BOEKUWZENDING_EXTERNAL_ORDER_ID);
    }

    /**
     * Set the id at Boekuwzending (external from this viewpoint)
     *
     * @param string $value
     * @return $this
     */
    public function setBoekuwzendingExternalOrderId($value): OrderInterface
    {
        return $this->setData(static::FIELD_BOEKUWZENDING_EXTERNAL_ORDER_ID, $value);
    }

    /**
     * Set the Magento order id.
     *
     * @param string $value
     * @return $this
     */
    public function setSalesOrderId($value): OrderInterface
    {
        return $this->setData(static::FIELD_SALES_ORDER_ID, $value);
    }

    /**
     * Get the Magento order id.
     *
     * @return string
     */
    public function getSalesOrderId(): string
    {
        return $this->getData(static::FIELD_SALES_ORDER_ID);
    }

    public function jsonSerialize(): array
    {
        return [
            self::FIELD_BOEKUWZENDING_ORDER_ID => $this->getEntityId(),
            self::FIELD_SALES_ORDER_ID => $this->getSalesOrderId(),
            self::FIELD_BOEKUWZENDING_EXTERNAL_ORDER_ID => $this->getBoekuwzendingExternalOrderId()
        ];
    }
}
