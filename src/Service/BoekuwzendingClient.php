<?php
namespace Boekuwzending\Magento\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order as MagentoOrder;

use Boekuwzending\Resource\Address;
use Boekuwzending\Resource\Contact;
use Boekuwzending\Resource\Order;
use Boekuwzending\Resource\OrderLine;
use Boekuwzending\Magento\Utils\AddressParser;

class BoekuwzendingClient implements IBoekuwzendingClient
{
    /**
    * @var \Psr\Log\LoggerInterface
    */
    private $logger;

    /**
    * @var \Boekuwzending\Client
    */
    private $client;

    /**
     * @var AddressParser
     */
    private $addressParser;

    /**
     * @var string
     */
    private $environment;
    /**
     * @var mixed
     */
    private $clientId;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param AddressParser $addressParser
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        AddressParser $addressParser 
    ) {
        $this->logger = $logger;
        $this->clientId = $scopeConfig->getValue("carriers/boekuwzending/clientId", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $secret = $scopeConfig->getValue("carriers/boekuwzending/clientSecret", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $staging = $scopeConfig->getValue("carriers/boekuwzending/testmode", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        
        $this->environment = $staging ? \Boekuwzending\Client::ENVIRONMENT_STAGING : \Boekuwzending\Client::ENVIRONMENT_LIVE;
        $this->client = \Boekuwzending\ClientFactory::build($this->clientId, $secret, $this->environment);

        $this->addressParser = $addressParser;
    }

    public function isStaging() : bool {
        return $this->environment == \Boekuwzending\Client::ENVIRONMENT_STAGING;
    }

    public function getOrderById(string $id) : \Boekuwzending\Resource\Order {
        return $this->client->order->get($id);
    }

    public function createOrder(MagentoOrder $order) : \Boekuwzending\Resource\Order {
        $this->logger->info("BoekuwzendingClient::createOrder(): " . $order->getId() . ", clientId: " . $this->clientId);
        
        // TODO: try-catch, status handling
        $buzOrder = $this->mapOrder($order);
        $buzOrder = $this->client->order->create($buzOrder);
        
        $this->logger->info("Created Boekuwzending order: '" . $buzOrder->getId() . "'"); 
        
        return $buzOrder;
    }

    private function mapOrder(MagentoOrder $order) : \Boekuwzending\Resource\Order {
        $buzOrder = new Order("0");

        $buzOrder->setExternalId($order->getId());
        $buzOrder->setReference($order->getIncrementId());
        $buzOrder->setCreatedAtSource(new \DateTime());
        
        $shippingAddress = $order->getShippingAddress();
        
        $contact = new Contact();
        $contact->setName($order->getCustomerName());
        $contact->setCompany($shippingAddress->getCompany());
        $contact->setPhoneNumber($shippingAddress->getTelephone() ?? "");
        $contact->setEmailAddress($shippingAddress->getEmail());

        $buzOrder->setShipToContact($contact);

        $address = new Address();
                
        $street = $shippingAddress->getStreet();
        $parsedAddress = $this->addressParser->parseAddressLine($street[0]);

        $address->setStreet($parsedAddress->street);
        $address->setNumber($parsedAddress->number);
        $address->setNumberAddition($parsedAddress->numberAddition);
        
        $address->setPostcode($shippingAddress->getPostcode());
        $address->setCity($shippingAddress->getCity());
        $address->setCountryCode($shippingAddress->getCountryId());

        $buzOrder->setShipToAddress($address);
        $lines = [];
        
        /* TODO: https://devdocs.magento.com/guides/v2.4/graphql/interfaces/order-item-interface.html:
            
            * Which quantity? (quantity_ordered - quantity_returned - quantity_canceled)? quantity_shipped?
            * WWhich price? Does that account for discounts?
        */

        foreach ($order->getAllVisibleItems() as $item) {
            
            $qty = $item->getQtyOrdered();

            $line = new OrderLine();

            $line->setExternalId($item->getId());
            $line->setDescription($item->getName());
            $line->setQuantity(intval($qty));
            $line->setValue($item->getPrice());
        
            $lines[] = $line;
        }
        
        $buzOrder->setOrderLines($lines);

        return $buzOrder;
    }
}