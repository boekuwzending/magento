<?php

namespace Boekuwzending\Magento\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Boekuwzending\Client;
use Boekuwzending\ClientFactory;

/**
 * Custom shipping model
 */
class BoekuwzendingCarrier extends AbstractCarrier implements CarrierInterface
{
    /**
     * Deze code wordt gebruikt om de config uit te lezen, en om de shipment op te slaan.
     * @var string
     */
    protected $_code = 'boekuwzending';

    /**
     * @var bool
     */
    protected $_isFixed = false;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    private $rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    private $rateMethodFactory;

    /**
     * @var \Magento\Shipping\Model\Tracking\ResultFactory
     */
    private $trackFactory;

    /**
     * @var \Magento\Shipping\Model\Tracking\Result\StatusFactory
     */
    private $trackStatusFactory;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

        $this->trackFactory = $trackFactory;
        $this->trackStatusFactory = $trackStatusFactory;
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
    }

    public function isTrackingAvailable()
    {
        return true;
    }

    public function isShippingLabelsAvailable()
    {
        return true;
    }

    public function getTrackingInfo($tracking_number)
    {
        $result = $this->trackFactory->create();
        $tracking = $this->trackStatusFactory->create();

        $tracking->setCarrier($this->_code);
        $tracking->setCarrierTitle('Carrier Title');
        $tracking->setTracking($tracking_number);
        $tracking->setUrl('https://carrier_domain.tld/trackId=' . $tracking_number);

        $result->append($tracking);

        return $tracking;
    }

    /**
     * Custom Shipping Rates Collector
     *
     * @param RateRequest $request
     * @return \Magento\Shipping\Model\Rate\Result|bool
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();

        // TODO: onderstaande code is zonder verzendmatrix. Als die later wordt ingeschakeld, controleer of 
        // de gebruiker een verzendmethode binnen deze verzendmethode heeft gekozen.

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('name'));

        $shippingCost = (float)$this->getConfigData('shipping_cost');

        $method->setPrice($shippingCost);
        $method->setCost($shippingCost);

        $result->append($method);

        return $result;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
}