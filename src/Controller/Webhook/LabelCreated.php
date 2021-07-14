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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Class Label
 *
 * This label webhook gets called by the platform when a label is made for one of our orders.
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
     * @throws LocalizedException
     */
    public function execute(): Json
    {
        $requestBody = $this->deserializeRequestBody();

        if (null === $requestBody) {
            return $this->badRequest(__("Could not parse JSON request body"));
        }

        // TODO: how to document what body we expect?
        if (!array_key_exists('orderId', $requestBody)) {
            return $this->badRequest(__("Missing request body key '%1'", 'orderId'));
        }
        if (!array_key_exists('carrierCode', $requestBody)) {
            return $this->badRequest(__("Missing request body key '%1'", 'carrierCode'));
        }
        if (!array_key_exists('carrierTitle', $requestBody)) {
            return $this->badRequest(__("Missing request body key '%1'", 'carrierTitle'));
        }
        if (!array_key_exists('trackingNumber', $requestBody)) {
            return $this->badRequest(__("Missing request body key '%1'", 'trackingNumber'));
        }

        $orderId = $requestBody["orderId"];
        $carrierCode = $requestBody["carrierCode"];
        $carrierTitle = $requestBody["carrierTitle"];
        $trackingNumber = $requestBody["trackingNumber"];

        $requestHmac = $this->getRequestHmac();
        $controlHmac = $this->calculateHmac([ $orderId, $carrierCode, $carrierTitle, $trackingNumber ]);

        if ($controlHmac !== $requestHmac) {
            return $this->unauthorized(__("Invalid HMAC"));
        }

        try {
            $shipment = $this->orderShipper->ship($orderId, $carrierCode, $carrierTitle, $trackingNumber);
            $this->logger->info(__METHOD__ . " shipment created: " . $shipment->getId());

            return $this->ok();
        }
        catch (NotFoundException $ex)
        {
            return $this->notFound(__("Order '%1' not found", $orderId));
        }
        catch (Exception $ex)
        {
            $message = $ex->getMessage();

            if (Constants::ERROR_ORDER_ALREADY_SHIPPED === $ex->getCode()) {
                $message = __("Order already fully shipped");
                $this->logger->info(__METHOD__ . " " . $message);
            }
            else
            {
                $this->logger->error(__METHOD__ . " exception creating shipment: " . $ex);
            }

            return $this->badRequest($message);
        }
    }
}
