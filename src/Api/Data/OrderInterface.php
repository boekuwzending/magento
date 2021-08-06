<?php

namespace Boekuwzending\Magento\Api\Data;

/**
 * The entity in the table `boekuwzending_order`
 */
interface OrderInterface
{
    /**
     * Get the id at Boekuwzending (external from this viewpoint)
     *
     * @return string
     */
    public function getBoekuwzendingExternalOrderId(): string;
    
    /**
     * Set the id at Boekuwzending (external from this viewpoint)
     *
     * @param string $value
     * @return $this
     */
    public function setBoekuwzendingExternalOrderId($value): OrderInterface;

    /**
     * Get the Magento order id
     *
     * @return string
     */
    public function getSalesOrderId(): string;

    /**
     * Set the Magento order id
     *
     * @param string $value
     * @return $this
     */
    public function setSalesOrderId($value): OrderInterface;
}
