<?php
namespace Boekuwzending\Magento\Block\Adminhtml\Order\View;

use Boekuwzending\Magento\Api\OrderRepositoryInterface;
use Boekuwzending\Magento\Service\BoekuwzendingClientInterface;

class OrderShipmentView extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /**
     * @var BoekuwzendingClientInterface
     */
    protected $client;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
     
    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param array $data
     */
    public function __construct(
        BoekuwzendingClientInterface $client,
        OrderRepositoryInterface $orderRepository,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $adminHelper, $data);        

        $this->client = $client;
        $this->orderRepository = $orderRepository;
    }

    public function getBoekuwzendingOrderUrl() {
        if ($this->client->isStaging()) {
            return "https://staging.mijn.boekuwzending.com/bestellingen/{id}/bewerken";
        }
        return "https://mijn.boekuwzending.com/bestellingen/{id}/bewerken";
    }

    public function getBoekuwzendingOrders() {
        $order = $this->getOrder();
        $boekuwzendingOrders = $this->orderRepository->getByOrderId($order->getId());
        return $boekuwzendingOrders;
    }
}