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
    public function getBoekuwzendingExternalOrderId();
    
    /**
     * Set the id at Boekuwzending (external from this viewpoint)
     *
     * @param string $value
     * @return $this
     */
    public function setBoekuwzendingExternalOrderId($value);

    /**
     * Get the Magento order id
     *
     * @return string
     */
    public function getSalesOrderId();

    /**
     * Set the Magento order id
     *
     * @param string $value
     * @return $this
     */
    public function setSalesOrderId($value);
}
