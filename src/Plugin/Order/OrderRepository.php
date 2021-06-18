<?php
namespace Boekuwzending\Magento\Plugin\Order;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use Psr\Log\LoggerInterface;

class OrderRepository {

    /**
     * @var Collection
     */
    private $statusCollection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Collection $statusCollection
     * @param LoggerInterface $logger
     */
    public function __construct(
        Collection $statusCollection,
        LoggerInterface $logger
    ) {
        $this->statusCollection = $statusCollection;
        $this->logger = $logger;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $result
     * @return mixed
     * @throws \Exception
     */
    public function afterSave(
        OrderRepositoryInterface $subject,
        OrderInterface $result
    ) {
        /*if (\Magento\Sales\Api\Data\Order:: === $result->getState()) {
            // TODO
        }*/

        $state = $result->getState();

        $this->logger->debug("Order state: " . $state);

        $collection = $this->statusCollection->toOptionArray();

        return $result;
    }
}
