<?php
namespace Boekuwzending\Magento\Plugin;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderRepositoryPlugin {
 
    protected $_orderExtensionFactory;

    /**
     * @param OrderExtensionFactory $orderExtensionFactory
     */
    public function __construct(
        OrderExtensionFactory $orderExtensionFactory
    ) {
        $this->_orderExtensionFactory = $orderExtensionFactory;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function afterGet(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        $extensionAttributes = $order->getExtensionAttributes();

        $orderExtension = $extensionAttributes ? $extensionAttributes : $this->_orderExtensionFactory->create();
        $orderId = $order->getFieldData("boekuwzending_order_id");
        $orderExtension->setBoekuwzendingOrderId($orderId);

        $order->setExtensionAttributes($orderExtension);

        return $order;
    }
}
