<?php
namespace Boekuwzending\Magento\Plugin\Order;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use Psr\Log\LoggerInterface;

class OrderRepositoryPlugin {

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

        $this->logger->warning("OrderRepositoryPlugin::__construct?");
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
        throw new \Exception("test");

        /*if (\Magento\Sales\Api\Data\Order:: === $result->getState()) {
            // TODO
        }*/

        $state = $result->getState();
        $collection = $this->statusCollection->toOptionArray();

        return $result;
    }
}
