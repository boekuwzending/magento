<?php

namespace Boekuwzending\Magento\Controller\Webhook;

use Boekuwzending\Magento\Utils\Constants;
use JsonException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

abstract class WebhookBase
{
    protected const HMAC_HEADER_NAME = 'Hmac';

    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * WebhookBase constructor.
     *
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(LoggerInterface $logger, RequestInterface $request, JsonFactory $resultJsonFactory, ScopeConfigInterface $scopeConfig)
    {
        $this->logger = $logger;
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Tries to deserialize the request body, returning null if that fails.
     *
     * @return array|null
     */
    protected function deserializeRequestBody(): ?array
    {
        try {
            /** @noinspection PhpUndefinedMethodInspection - it's in there */
            $requestBody = $this->request->getContent();
            $this->logger->debug(__METHOD__ . ": JSON: " . $requestBody);
            return json_decode($requestBody, true, 2, JSON_THROW_ON_ERROR);
        } catch (JsonException $ex) {
            $this->logger->error(__METHOD__ . ": error parsing JSON: " . $ex);
            return null;
        }
    }

    /**
     * Reads the `Hmac` request header.
     */
    protected function getRequestHmac(): ?string
    {
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $requestHmac = $this->request->getHeader(self::HMAC_HEADER_NAME);
        $this->logger->debug(__METHOD__ . ": request HMAC: " . $requestHmac);
        return $requestHmac;
    }

    /**
     * @throws LocalizedException
     */
    protected function calculateHmac(array $fields): string
    {
        $clientId = $this->scopeConfig->getValue(Constants::CONFIG_CLIENTID_PATH, ScopeInterface::SCOPE_STORE);
        $secret = $this->scopeConfig->getValue(Constants::CONFIG_CLIENTSECRET_PATH, ScopeInterface::SCOPE_STORE);

        if (empty($clientId) || empty($secret)) {
            throw new LocalizedException(__("Module is not configured for receiving webhooks"), null, Constants::ERROR_CONFIGURATION_DATA_MISSING);
        }

        if (null === $fields || empty($fields)) {
            throw new LocalizedException(__("Module is not configured for receiving webhooks"), null, Constants::ERROR_);
        }

        $hmacData = implode("|", $fields);
        $calculatedHmac = hash_hmac("sha256", $hmacData, $clientId . $secret);

        $this->logger->debug(__METHOD__ . ": calculated HMAC: " . $calculatedHmac . ", from data: " . $hmacData);

        return $calculatedHmac;
    }

    /**
     * Creates a 200 OK response with a JSON body indicating success.
     *
     * @return Json
     */
    protected function createResponse(): Json
    {
        $response = $this->resultJsonFactory->create();
        $response->setHttpResponseCode(200);
        $response->setData(['success' => true]);
        return $response;
    }

    /**
     * Modifies and returns the given response to a 400 Bad Request status, optionally with the provided message in its body.
     *
     * @param Json $response
     * @param string|null $message
     * @return Json
     */
    protected function badRequest(Json $response, ?string $message = null): Json
    {
        return $this->error($response, 400, $message);
    }

    /**
     * Modifies and returns the given response to a 404 Not Found status.
     *
     * @param Json $response
     * @return Json
     */
    protected function notFound(Json $response): Json
    {
        return $this->error($response, 404);
    }

    /**
     * Modifies and returns the given response into an error response with the provided status code and optional message in its body.
     *
     * @param Json $response
     * @param int $code
     * @param string|null $message
     * @return Json
     */
    protected function error(Json $response, int $code, ?string $message = null): Json
    {
        $response->setHttpResponseCode($code);

        // TODO: ProblemResponse?
        $response->setData([
            'status' => $code,
            'success' => false,
            'message' => $message
        ]);

        return $response;
    }
}