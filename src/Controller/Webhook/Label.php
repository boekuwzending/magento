<?php

namespace Boekuwzending\Magento\Controller\Webhook;

use Boekuwzending\Magento\Api\OrderRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Shipping\Model\ShipmentNotifier;
use Magento\Sales\Api\OrderRepositoryInterface as MagentoOrderRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Label
 *
 * This label webhook gets called by the platform when a label is made for one of our orders. We then take the unshipped and shippable items from this order,
 * and assign them to a new shipment. This ships the order.
 *
 * @package Boekuwzending\Magento\Controller\Webhook
 */
class Label implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var RequestInterface
     */
    private $request;
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
     * @var ShipmentNotifier
     */
    private $shipmentNotifier;
    /**
     * @var TrackFactory
     */
    private $trackFactory;

    /**
     * Handle constructor.
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param OrderRepositoryInterface $orderRepository
     * @param MagentoOrderRepositoryInterface $magentoOrderRepository
     * @param ConvertOrder $orderConverter
     * @param TrackFactory $trackFactory
     * @param ShipmentNotifier $shipmentNotifier
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(LoggerInterface $logger,
                                RequestInterface $request,
                                OrderRepositoryInterface $orderRepository,
                                MagentoOrderRepositoryInterface $magentoOrderRepository,
                                ConvertOrder $orderConverter,
                                TrackFactory $trackFactory,
                                ShipmentNotifier $shipmentNotifier,
                                JsonFactory $resultJsonFactory)
    {
        $this->logger = $logger;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->magentoOrderRepository = $magentoOrderRepository;
        $this->orderConverter = $orderConverter;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->trackFactory = $trackFactory;
    }

    /**
     * @throws LocalizedException
     */
    public function execute()
    {
        // So the platform can call us
        if ($this->request->getParam('test')) {
            return $this->ok();
        }

        // TODO: how to document what body we expect?
        $postBody = json_decode($this->request->getContent(), true, 2, JSON_THROW_ON_ERROR);

        $orderId = $postBody["orderId"];
        $trackingNumber = $postBody["trackingNumber"];
        $carrierCode = $postBody["carrierCode"];
        $carrierTitle = $postBody["carrierTitle"];

        $logString = "Webhook::Label::execute() called for order ID '" . $orderId . "', carrier '" . $carrierTitle . "' ('" . $carrierCode . "'), tracking number: '" . $trackingNumber . "')";
        $this->logger->info($logString);

        $buzOrder = $this->orderRepository->getByExternalOrderId($orderId);
        if (null === $buzOrder) {
            return $this->error(404, __('Not found')->render());
        }

        $magentoOrder = $this->magentoOrderRepository->get($buzOrder->getSalesOrderId());

        if (!$magentoOrder->canShip()) {
            $errorString = "Can't ship order '" . $magentoOrder->getId() . "': nothing to ship";
            $this->logger->warning("Webhook::Label::execute(): " . $errorString);
            throw new LocalizedException(__($errorString));
        }

        $shipment = $this->orderConverter->toShipment($magentoOrder);
        foreach ($magentoOrder->getAllItems() as $orderItem) {
            // Check if order item has qty to ship or is virtual
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }

            $qtyShipped = $orderItem->getQtyToShip();
            $shipmentItem = $this->orderConverter->itemToShipmentItem($orderItem)->setQty($qtyShipped);
            $shipment->addItem($shipmentItem);
        }

        $track = $this->trackFactory->create()->setNumber($trackingNumber)->setCarrierCode($carrierCode)->setTitle($carrierTitle);
        $shipment->addTrack($track);
        $shipment->register();
        $shipment->getOrder()->setIsInProcess(true);

        try {
            // Save created shipment and order
            $shipment->save();
            $shipment->getOrder()->save();

            // Send email (TODO: from config)
            $this->shipmentNotifier->notify($shipment);

            $shipment->save();
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }

        return $this->ok($shipment);

        return $this->error(500, __('Not yet implemented')->render());
    }

    private function ok(?object $data): Json
    {
        $result = $this->resultJsonFactory->create();
        $result->setData(['success' => true]);

        if (null !== $data) {
            $result->setData(['data' => $data]);
        }

        return $result;
    }

    private function error(int $code, string $message = null): Json
    {
        $result = $this->resultJsonFactory->create();
        $result->setHttpResponseCode($code);

        $result->setData(['success' => false]);

        if ($message) {
            $result->setData(['message' => $message]);
        }

        return $result;
    }
}





