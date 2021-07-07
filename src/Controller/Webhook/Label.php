<?php

namespace Boekuwzending\Magento\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Label extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * Handle constructor.
     * @param Context $context
     */
    public function __construct(Context $context, JsonFactory $resultJsonFactory)
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        if ($this->getRequest()->getParam('test')) {
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





