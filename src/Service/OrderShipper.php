<?php

namespace Boekuwzending\Magento\Service;

use Boekuwzending\Magento\Api\OrderRepositoryInterface;
use Boekuwzending\Magento\Utils\Constants;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Sales\Api\OrderRepositoryInterface as MagentoOrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class OrderShipper
 *
 * Gets called by the LabelCreated webhook to ship an order and apply tracking codes (labels) to the shipment.
 *
 * @package Boekuwzending\Magento\Service
 */
class OrderShipper implements OrderShipperInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var MagentoOrderRepositoryInterface
     */
    private $magentoOrderRepository;
    /**
     * @var ConvertOrder
     */
    private $orderConverter;
    /**
     * @var TrackFactory
     */
    private $trackFactory;
    /**
     * @var ShipmentNotifier
     */
    private $shipmentNotifier;
    /**
     * @var ShipmentCommentCreationInterface
     */
    private $commentCreation;

    /**
     * OrderShipper constructor.
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param MagentoOrderRepositoryInterface $magentoOrderRepository
     * @param ConvertOrder $orderConverter
     * @param TrackFactory $trackFactory
     * @param ShipmentNotifier $shipmentNotifier
     */
    public function __construct(LoggerInterface $logger,
                                OrderRepositoryInterface $orderRepository,
                                MagentoOrderRepositoryInterface $magentoOrderRepository,
                                ConvertOrder $orderConverter,
                                TrackFactory $trackFactory,
                                ShipmentNotifier $shipmentNotifier)
    {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->magentoOrderRepository = $magentoOrderRepository;
        $this->orderConverter = $orderConverter;
        $this->trackFactory = $trackFactory;
        $this->shipmentNotifier = $shipmentNotifier;
    }

    /**
     * @param string $orderId
     * @param string $carrierCode
     * @param string $carrierTitle
     * @param string $trackingNumber
     *
     * @return Shipment
     * @throws LocalizedException
     * @throws NotFoundException
     */
    public function ship(string $orderId, string $carrierCode, string $carrierTitle, string $trackingNumber): Shipment
    {
        $logPrefix = __METHOD__ . " ";

        $logString = $logPrefix . "called for order ID '" . $orderId . "', carrier '" . $carrierTitle . "' ('" . $carrierCode . "'), tracking number: '" . $trackingNumber . "')";
        $this->logger->info($logString);

        $buzOrder = $this->orderRepository->getByExternalOrderId($orderId);
        if (null === $buzOrder) {
            $errorString = __("Order '%1' not found", $orderId);
            $this->logger->error($logPrefix . $errorString);
            throw new NotFoundException($errorString);
        }

        $magentoOrder = $this->magentoOrderRepository->get($buzOrder->getSalesOrderId());

        if (!$magentoOrder->canShip()) {
            $errorString = __("Can't ship order '%1': nothing to ship", $magentoOrder->getId());
            $this->logger->error($logPrefix . $errorString);
            throw new LocalizedException($errorString, null, Constants::ERROR_ORDER_ALREADY_SHIPPED);
        }

        /** @var Shipment $shipment */
        $shipment = $this->orderConverter->toShipment($magentoOrder);

        // Add all non-virtual items that still need to be shipped (i.e. not on a prior shipment) to the shipment
        foreach ($magentoOrder->getAllItems() as $orderItem) {
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                $this->logger->debug($logPrefix . "not shipping item " . $orderItem->getId());
                continue;
            }

            // Convert and add to shipment
            $quantityToShip = $orderItem->getQtyToShip();
            $shipmentItem = $this->orderConverter->itemToShipmentItem($orderItem)->setQty($quantityToShip);
            $this->logger->debug($logPrefix . "shipping " . $quantityToShip . "/" . $orderItem->getQtyOrdered() . " of item " . $orderItem->getId());
            $shipment->addItem($shipmentItem);
        }

        // Tracking code / Track&Trace / Label
        $track = $this->trackFactory->create()->setNumber($trackingNumber)->setCarrierCode($carrierCode)->setTitle($carrierTitle);
        $shipment->addTrack($track);

        // Don't notify the customer and don't show this comment on the front (false, false).
        $shipment->addComment(__("Shipment created automatically by Boekuwzending label creation"), false, false);

        // Order status stuff
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);

        try {
            // Save created shipment and order
            $shipment->save();
            $shipment->getOrder()->save();

            // Send email (TODO: from config)
            $this->shipmentNotifier->notify($shipment);

            $shipment->save();

            return $shipment;
        } catch (Exception $ex) {
            $logString = __("Error creating shipment for order ID '%1': %2", $orderId, $ex->getMessage());
            $this->logger->error($logPrefix . $logString);
            throw new LocalizedException($logString);
        }
    }
}