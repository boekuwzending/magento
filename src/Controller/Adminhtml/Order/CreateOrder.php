<?php
namespace Boekuwzending\Magento\Controller\Adminhtml\Order;

use Boekuwzending\Magento\Service\IBoekuwzendingClient;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as MagentoOrder;

use Boekuwzending\Resource\Order as BoekuwzendingApiOrder;
use Boekuwzending\Magento\Api\OrderRepositoryInterface as BoekuwzendingOrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order;

class CreateOrder extends Action
{    
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var OrderRepositoryInterface
     */
    private $magentoOrderRepository;
 
    /**
     * @var BoekuwzendingOrderRepositoryInterface
     */
    private $boekuwzendingOrderRepository;
 
    /**
    * @var JsonFactory
    */
    private $resultJsonFactory;
    
    /**
    * @var IBoekuwzendingClient
    */
    private $client;

    /**
     * @var Order
     */
    private $orderResourceModel;

    /**
     * @param RequestInterface $request
     * @param Context $context
     * @param OrderRepositoryInterface $magentoOrderRepository,
     * @param JsonFactory $resultJsonFactory
     * @param IBoekuwzendingClient $client
     * @param BoekuwzendingOrderRepositoryInterface $boekuwzendingOrderRepository
     */
    public function __construct(
        RequestInterface $request,
        Context $context,
        OrderRepositoryInterface $magentoOrderRepository,
        Order $orderResourceModel,
        JsonFactory $resultJsonFactory,
        IBoekuwzendingClient $client,
        BoekuwzendingOrderRepositoryInterface $boekuwzendingOrderRepository
    ) {
        parent::__construct($context);
        $this->request = $request;
        $this->boekuwzendingOrderRepository = $boekuwzendingOrderRepository;
        $this->magentoOrderRepository = $magentoOrderRepository;
        $this->orderResourceModel = $orderResourceModel;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->client = $client;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute() {
        $magentoOrderId = $this->request->getParam("order_id");
        
        /**
         * @var MagentoOrder
         */
        $order = $this->magentoOrderRepository->get($magentoOrderId);

        // TODO: status handling, API unreachable?
        /**
         * @var BoekuwzendingApiOrder
         */
        $buzOrder = $this->client->createOrder($order);
        
        $buzId = $buzOrder->getId();
        
        // Prepare the entity, linking it to the Magento order and the Boekuwzending order
        $boekuwzendingOrder = $this->boekuwzendingOrderRepository->create();

        $boekuwzendingOrder->setSalesOrderId($magentoOrderId);
        $boekuwzendingOrder->setBoekuwzendingExternalOrderId($buzId);

        // Create in database
        $boekuwzendingOrder->save();

        $response = [
            "success" => "true",
            "magento_order_id" => $magentoOrderId,
            "boekuwzending_id" => "".$buzOrder->getId(),
        ];

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($response);
        return $resultJson;
    }
}