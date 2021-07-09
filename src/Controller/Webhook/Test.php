<?php

namespace Boekuwzending\Magento\Controller\Webhook;

use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Test
 *
 * Platform calls this webhook to test integration.
 *
 * @package Boekuwzending\Magento\Controller\Webhook
 */
class Test extends WebhookBase implements HttpPostActionInterface
{
    /**
     * Handles the POST /boekuwzending/webhook/label call.
     *
     * @return Json
     * @throws LocalizedException
     */
    public function execute(): Json
    {
        $logPrefix = "Webhook::" . __METHOD__ . " ";

        $response = $this->createResponse();

        $requestBody = $this->deserializeRequestBody();

        // TODO: how to document what body we expect?
        if (!array_key_exists('test', $requestBody)) {
            return $this->badRequest($response, "Missing key 'test'");
        }

        if ('test' !== $requestBody['test']) {
            return $this->badRequest($response, "Invalid data for key 'test'");
        }

        $requestHmac = $this->getRequestHmac();
        $controlHmac = $this->calculateHmac([ $requestBody["test"] ]);

        if ($controlHmac !== $requestHmac) {
            return $this->badRequest($response, "Invalid HMAC");
        }

        return $response;
    }
}
