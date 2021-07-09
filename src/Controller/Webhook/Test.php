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
        $this->logger->info(__METHOD__ . " called");

        $requestBody = $this->deserializeRequestBody();

        // TODO: how to document what body we expect?
        if (!array_key_exists('test', $requestBody)) {
            return $this->badRequest(__("Missing request body key '%1'", 'test'));
        }

        if ('test' !== $requestBody['test']) {
            return $this->badRequest(__("Invalid data for key '%1'", 'test'));
        }

        $requestHmac = $this->getRequestHmac();
        $controlHmac = $this->calculateHmac([ $requestBody["test"] ]);

        if ($controlHmac !== $requestHmac) {
            return $this->unauthorized(__("Invalid HMAC"));
        }

        return $this->ok();
    }
}
