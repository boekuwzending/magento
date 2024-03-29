<?php

namespace Boekuwzending\Magento\Controller\Webhook;

use Boekuwzending\Magento\Service\OrderShipperInterface;
use Boekuwzending\Magento\Utils\Constants;
use Exception;
use JsonException;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * This /labelCreated webhook gets called by the platform when a label is made for one of our orders.
 *
 * @package Boekuwzending\Magento\Controller\Webhook
 */
class LabelCreated extends WebhookBase implements HttpPostActionInterface
{
    /**
     * @var OrderShipperInterface
     */
    private $orderShipper;

    /**
     * Label constructor.
     *
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderShipperInterface $orderShipper
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(LoggerInterface $logger,
                                RequestInterface $request,
                                JsonFactory $resultJsonFactory,
                                ScopeConfigInterface $scopeConfig,
                                OrderShipperInterface $orderShipper)
    {
        parent::__construct($logger, $request, $resultJsonFactory, $scopeConfig);

        $this->orderShipper = $orderShipper;
    }

    /**
     * Handles the POST /boekuwzending/webhook/labelCreated call.
     *
     * @return Json
     *
     * @throws LocalizedException
     * @throws JsonException
     */
    public function execute(): Json
    {
        $requestBody = $this->deserializeRequestBody();

        if (null === $requestBody) {
            return $this->badRequest(__('Could not parse JSON request body'));
        }

        $data = $requestBody['data'];

        // TODO: how to document what body we expect?
        if (false === array_key_exists('entity_id', $data)) {
            return $this->missingRequestBodyKey('data.entity_id');
        }
        if (false === array_key_exists('shipment_id', $data)) {
            return $this->missingRequestBodyKey('data.shipment_id');
        }
        if (false === array_key_exists('external_order_id', $data)) {
            return $this->missingRequestBodyKey('data.external_order_id');
        }
        if (false === array_key_exists('carrier_code', $data)) {
            return $this->missingRequestBodyKey('data.carrier_code');
        }
        if (false === array_key_exists('carrier_title', $data)) {
            return $this->missingRequestBodyKey('data.carrier_title');
        }
        if (false === array_key_exists('tracking_number', $data)) {
            return $this->missingRequestBodyKey('data.tracking_number');
        }

        $entityId = $data['entity_id'];
        $shipmentId = $data['shipment_id'];
        $externalOrderId = $data['external_order_id'];
        $carrierCode = $data['carrier_code'];
        $carrierTitle = $data['carrier_title'];
        $trackingNumber = $data['tracking_number'];

        $requestHmac = $this->getRequestHmac($requestBody);
        $controlHmac = $this->calculateHmac([
            'entity_id' => $entityId,
            'shipment_id' => $shipmentId,
            'external_order_id' => $externalOrderId,
            'carrier_code' => $carrierCode,
            'carrier_title' => $carrierTitle,
            'tracking_number' => $trackingNumber
        ]);

        if ($controlHmac !== $requestHmac) {
            return $this->unauthorized(__('Invalid HMAC'));
        }

        try {
            $createShipment = $this->scopeConfig->getValue(Constants::CONFIG_WEBHOOK_LABELCREATED_SHIPORDER);

            if ($createShipment !== "1") {
                $this->logger->info(vsprintf('%s(): call for external order Id "%s" was semantically correct, but setting is disabled.', [
                    __METHOD__,
                    $externalOrderId,
                ]));

                return $this->ok();
            }

            $shipment = $this->orderShipper->ship($externalOrderId, $carrierCode, $carrierTitle, $trackingNumber);
            $this->logger->info(sprintf('%s(): shipment created: %s', __METHOD__, $shipment->getId()));

            return $this->ok();
        } catch (NotFoundException $ex) {
            return $this->notFound(__('Order "%1" not found', $externalOrderId));
        } catch (Exception $ex) {
            $message = $ex->getMessage();

            if (Constants::ERROR_ORDER_ALREADY_SHIPPED === $ex->getCode()) {
                $message = __('Order already fully shipped');
                $this->logger->info(sprintf('%s %s', __METHOD__, $message));
            } else {
                $this->logger->error(sprintf('%s(): exception creating shipment: %s', __METHOD__, $ex));
            }

            return $this->badRequest($message);
        }
    }
}
