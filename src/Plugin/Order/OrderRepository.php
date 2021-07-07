<?php

namespace Boekuwzending\Magento\Plugin\Order;

use Boekuwzending\Magento\Service\IBoekuwzendingClient;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\Collection;
use Psr\Log\LoggerInterface;

class OrderRepository
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Collection
     */
    private $statusCollection;
    /**
     * @var \Boekuwzending\Magento\Api\OrderRepositoryInterface
     */
    private $boekuwzendingOrderRepository;
    /**
     * @var IBoekuwzendingClient
     */
    private $client;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Collection $statusCollection
     * @param LoggerInterface $logger
     * @param \Boekuwzending\Magento\Api\OrderRepositoryInterface $boekuwzendingOrderRepo
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Collection $statusCollection,
        LoggerInterface $logger,
        \Boekuwzending\Magento\Api\OrderRepositoryInterface $boekuwzendingOrderRepo,
        IBoekuwzendingClient $client
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->statusCollection = $statusCollection;
        $this->logger = $logger;
        $this->boekuwzendingOrderRepository = $boekuwzendingOrderRepo;
        $this->client = $client;
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
    )
    {
        try {
            $magentoOrderId = $result->getEntityId();

            $triggerStates = $this->scopeConfig->getValue("carriers/boekuwzending/triggerOnOrderStatus", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $triggerStates = explode(",", $triggerStates ?? "");

            // Skip non-"processing" states (or whatever is configured)
            $state = $result->getState();
            $this->logger->debug("Order " . $magentoOrderId . " has state '" . $state . "', expecting '" . implode("', '", $triggerStates) . "'");
            if (!in_array($state, $triggerStates, true)) {
                return $result;
            }

            $boekuwzendingOrders = $this->boekuwzendingOrderRepository->getByOrderId($magentoOrderId);
            $orderCount = is_array($boekuwzendingOrders) ? count($boekuwzendingOrders) : 0;

            // Skip if one or more already exists
            if (null !== $boekuwzendingOrders && 0 < $orderCount) {
                $this->logger->debug("Order state '" . $state . "' and " . $orderCount . " Boekuwzending orders");
                return $result;
            }

            $this->createBoekuwzendingOrder($magentoOrderId, $result);

            return $result;
        } catch (\Exception $ex) {
            $this->logger->error("Error creating order: " . $ex);
        }
    }

    private function createBoekuwzendingOrder(int $magentoOrderId, OrderInterface $order): void
    {
        // TODO: status handling, API unreachable?
        /**
         * @var BoekuwzendingApiOrder
         */
        $buzOrder = $this->client->createOrder($order);

        $buzId = $buzOrder->getId();

        // Prepare the entity, linking it to the Magento order and the Boekuwzending order
        $boekuwzendingOrder = $this->boekuwzendingOrderRepository->create();

        $boekuwzendingOrder->setSalesOrderId($magentoOrderId);
        $boekuwzendingOrder->setBoekuwzendingExternalOrderId($buzId);

        // Create in database
        $boekuwzendingOrder->save();
    }
}
