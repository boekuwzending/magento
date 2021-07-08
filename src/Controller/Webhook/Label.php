<?php

namespace Boekuwzending\Magento\Controller\Webhook;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Label implements HttpGetActionInterface
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
     * Handle constructor.
     * @param RequestInterface $request
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(RequestInterface $request, JsonFactory $resultJsonFactory)
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
    }

    public function execute()
    {
        if ($this->request->getParam('test')) {
            return $this->ok();
        }

        return $this->error(500, __('Not yet implemented')->render());
    }

    private function ok(): Json
    {
        $result = $this->resultJsonFactory->create();
        $result->setData(['success' => true]);
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





