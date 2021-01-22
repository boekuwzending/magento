<?php
namespace Boekuwzending\Magento\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Sales\Model\Order as MagentoOrder;

use Boekuwzending\Resource\Order as BoekuwzendingApiOrder;
use \Boekuwzending\Serializer\Serializer as BoekuwzendingApiSerializer;
use Boekuwzending\Magento\Api\OrderRepositoryInterface as BoekuwzendingOrderRepositoryInterface;

class CreateOrder extends Action
{    
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $magentoOrderRepository;
 
    /**
     * @var BoekuwzendingOrderRepositoryInterface
     */
    private $boekuwzendingOrderRepository;
 
    /**
    * @var \Magento\Framework\Controller\Result\JsonFactory
    */
    private $resultJsonFactory;
    
    /**
    * @var \Boekuwzending\Magento\Service\IBoekuwzendingClient $client
    */
    private $client;

    /**
     * @param RequestInterface $request
     * @param Context $context
     * @param \Magento\Sales\Api\OrderRepositoryInterface $magentoOrderRepository,
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Boekuwzending\Magento\Service\IBoekuwzendingClient $client
     * @param BoekuwzendingOrderRepositoryInterface $boekuwzendingOrderRepository
     */
    public function __construct(
        RequestInterface $request,
        Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $magentoOrderRepository,
        \Magento\Sales\Model\ResourceModel\Order $orderResourceModel,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Boekuwzending\Magento\Service\IBoekuwzendingClient $client,
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

        // Prepare for return (TODO: what to return?)
        foreach ($buzOrder->getOrderLines() as &$line) {
            $line->setDimensions(null);
        }

        $buzSerializer = new BoekuwzendingApiSerializer();

        $response = [
            "success" => "true",
            "magento_order_id" => $magentoOrderId,
            "boekuwzending_id" => "".$buzOrder->getId(),
            "boekuwzending_external_id" => "".$buzOrder->getExternalId(),
            "buz_order" => $buzSerializer->serialize($buzOrder),
            "magento_order" => $order
        ];

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setData($response);
        return $resultJson;
    }
}