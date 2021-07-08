<?php

namespace Boekuwzending\Magento\Controller\Webhook;

use Boekuwzending\Magento\Api\OrderRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Handle constructor.
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param OrderRepositoryInterface $orderRepository
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(LoggerInterface $logger, RequestInterface $request, OrderRepositoryInterface $orderRepository, JsonFactory $resultJsonFactory)
    {
        $this->logger = $logger;
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        if ($this->request->getParam('test')) {
            return $this->ok();
        }

        $orderId = $this->request->getParam('id');
        $this->logger->info("Webhook::Label::execute() called for ID '" . $orderId . "'");

        $buzOrder = $this->orderRepository->getByExternalOrderId($orderId);
        if (null === $buzOrder) {
            return $this->error(404, __('Not found')->render());
        }

        return $this->ok($buzOrder);

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





