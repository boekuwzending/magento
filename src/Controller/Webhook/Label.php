<?php

namespace Boekuwzending\Magento\Controller\Webhook;

use Boekuwzending\Magento\Service\OrderShipperInterface;
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
     * @var OrderShipperInterface
     */
    private $orderShipper;

    /**
     * Label constructor.
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param OrderShipperInterface $orderShipper
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(LoggerInterface $logger,
                                RequestInterface $request,
                                OrderShipperInterface $orderShipper,
                                JsonFactory $resultJsonFactory)
    {
        $this->logger = $logger;
        $this->request = $request;
        $this->orderShipper = $orderShipper;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Handles the POST /boekuwzending/webhook/label call.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $logPrefix = "Webhook::Label::execute() ";

        // Assume a happy flow.
        $response = $this->resultJsonFactory->create();
        $response->setHttpResponseCode(200);
        $response->setData(['success' => true]);

        // Immediately return a positive response so the platform can call us to test a webhook.
        // TODO: only after HMAC validation?
        if ($this->request->getParam('test')) {
            return $response;
        }

        // TODO: how to document what body we expect?
        try {
            $requestBody = $this->request->getContent();
            $this->logger->debug($logPrefix . "JSON: " . $requestBody);
            $postBody = json_decode($requestBody, true, 2, JSON_THROW_ON_ERROR);
        }
        catch (\JsonException $ex) {
            $this->logger->error($logPrefix . "error parsing JSON: " . $ex);
            return $this->badRequest($response);
        }

        $orderId = $postBody["orderId"];
        $carrierCode = $postBody["carrierCode"];
        $carrierTitle = $postBody["carrierTitle"];
        $trackingNumber = $postBody["trackingNumber"];

        try {
            $shipment = $this->orderShipper->ship($orderId, $carrierCode, $carrierTitle, $trackingNumber);
            $this->logger->info($logPrefix . "shipment created: " . $shipment->getId());

            return $response;
        }
        catch (NotFoundException $ex)
        {
            return $this->notFound($response);
        }
        catch (\Exception $ex)
        {
            $logString = "Error creating shipment for order ID '" . $orderId . "': " . $ex;
            $this->logger->error("Webhook::Label::execute() " . $logString);
            return $this->badRequest($response);
        }
    }

    private function badRequest(Json $response): Json
    {
        return $this->error($response, 400);
    }

    private function notFound(Json $response): Json
    {
        return $this->error($response, 404);
    }

    private function error(Json $response, int $code): Json
    {
        $response->setHttpResponseCode($code);
        // TODO: ProblemResponse?
        $response->setData(['success' => false]);
        return $response;
    }
}
