<?php

namespace Boekuwzending\Magento\Controller\Webhook;

use Boekuwzending\Magento\Service\OrderShipperInterface;
use Boekuwzending\Magento\Utils\Constants;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Class Label
 *
 * This label webhook gets called by the platform when a label is made for one of our orders.
 *
 * @package Boekuwzending\Magento\Controller\Webhook
 */
class Label extends WebhookBase implements HttpPostActionInterface
{
    /**
     * @var OrderShipperInterface
     */
    private $orderShipper;

    /**
     * Label constructor.
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
     * Handles the POST /boekuwzending/webhook/label call.
     *
     * @return Json
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(): Json
    {
        $logPrefix = "Webhook::" . __METHOD__ . " ";

        $response = $this->createResponse();

        $requestBody = $this->deserializeRequestBody();

        if (null === $requestBody) {
            return $this->badRequest($response, "Could not parse JSON request body");
        }

        // TODO: how to document what body we expect?
        if (!array_key_exists('orderId', $requestBody)) {
            return $this->badRequest($response, "Missing key 'orderId'");
        }
        // ...

        $orderId = $requestBody["orderId"];
        $carrierCode = $requestBody["carrierCode"];
        $carrierTitle = $requestBody["carrierTitle"];
        $trackingNumber = $requestBody["trackingNumber"];

        $requestHmac = $this->getRequestHmac();
        $controlHmac = $this->calculateHmac([ $orderId, $carrierCode, $carrierTitle, $trackingNumber ]);

        if ($controlHmac !== $requestHmac) {
            return $this->badRequest($response, "Invalid HMAC");
        }

        try {
            $shipment = $this->orderShipper->ship($orderId, $carrierCode, $carrierTitle, $trackingNumber);
            $this->logger->info($logPrefix . "shipment created: " . $shipment->getId());

            return $response;
        }
        catch (NotFoundException $ex)
        {
            return $this->notFound($response);
        }
        catch (Exception $ex)
        {
            $message = $ex->getMessage();

            if (Constants::ERROR_ORDER_ALREADY_SHIPPED === $ex->getCode()) {
                $message = "Order already fully shipped";
            }

            return $this->badRequest($response, $message);
        }
    }
}
