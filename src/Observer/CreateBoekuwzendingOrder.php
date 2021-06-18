<?php

namespace Boekuwzending\Magento\Observer;

use Boekuwzending\Magento\Service\IBoekuwzendingClient;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use Boekuwzending\Magento\Api\OrderRepositoryInterface as BoekuwzendingOrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class CreateBoekuwzendingOrder implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var IBoekuwzendingClient
     */
    private $client;

    /**
     * @var BoekuwzendingOrderRepositoryInterface
     */
    private $boekuwzendingOrderRepository;

    /**
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $magentoOrderRepository
     * @param IBoekuwzendingClient $client
     * @param BoekuwzendingOrderRepositoryInterface $boekuwzendingOrderRepository
     */
    public function __construct(
        LoggerInterface $logger,
        IBoekuwzendingClient $client,
        BoekuwzendingOrderRepositoryInterface $boekuwzendingOrderRepository
    )
    {
        $this->logger = $logger;
        $this->client = $client;
        $this->boekuwzendingOrderRepository = $boekuwzendingOrderRepository;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        // try-catch it all, because if this method throws, the order won't be placed.
        try {
            $this->createBoekuwzendingOrder($observer);
        } catch (\Throwable $th) {
            $this->logger->error("Error executing CreateBoekuwzendingOrder::execute(): " . $th);
        }
    }

    private function createBoekuwzendingOrder(Observer $observer)
    {
        /** @var MagentoOrder $magentoOrder */
        $order = $observer->getEvent()->getOrder();

        $this->logger->info("CreateBoekuwzendingOrder::createBoekuwzendingOrder() for Magento order '" . $order->getId() . "'");

        $buzId = $order->getBoekuwzendingOrderId();
        $magentoOrderId = $order->getId();

        if ($buzId) {
            $this->logger->info("Already known at Boekuwzending: '" . $buzId . "'");
            return;
        }
        if (!$magentoOrderId) {
            $this->logger->info("Empty Magento order id. Is the order saved?");
            return;
        }

        // TODO: status handling, API unreachable?
        $buzOrder = $this->client->createOrder($order);

        $buzId = $buzOrder->getId();

        // Prepare the entity, linking it to the Magento order and the Boekuwzending order
        $boekuwzendingOrder = $this->boekuwzendingOrderRepository->create();

        $boekuwzendingOrder->setSalesOrderId($magentoOrderId);
        $boekuwzendingOrder->setBoekuwzendingExternalOrderId($buzId);

        // Create in database
        $boekuwzendingOrder->save();

        $this->logger->info("Created Boekuwzending order: '" . $buzId . "' for Magento order '" . $magentoOrderId . "'");
    }
}
