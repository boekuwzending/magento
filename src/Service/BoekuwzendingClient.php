<?php
namespace Boekuwzending\Magento\Service;

use Boekuwzending\Client;
use Boekuwzending\ClientFactory;
use Boekuwzending\Exception\AuthorizationFailedException;
use Boekuwzending\Exception\RequestFailedException;
use Boekuwzending\Magento\Utils\Constants;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order as MagentoOrder;

use Boekuwzending\Resource\Address;
use Boekuwzending\Resource\Contact;
use Boekuwzending\Resource\Order;
use Boekuwzending\Resource\OrderLine;
use Boekuwzending\Magento\Utils\AddressParser;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class BoekuwzendingClient implements BoekuwzendingClientInterface
{
    /**
    * @var LoggerInterface
    */
    private $logger;

    /**
    * @var Client|null
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
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param AddressParser $addressParser
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        AddressParser $addressParser 
    ) {
        $this->logger = $logger;
        $this->clientId = $scopeConfig->getValue(Constants::CONFIG_CLIENTID_PATH, ScopeInterface::SCOPE_STORE);
        $secret = $scopeConfig->getValue(Constants::CONFIG_CLIENTSECRET_PATH, ScopeInterface::SCOPE_STORE);
        $staging = $scopeConfig->getValue(Constants::CONFIG_TESTMODE_PATH, ScopeInterface::SCOPE_STORE);
        
        $this->environment = $staging ? Client::ENVIRONMENT_STAGING : Client::ENVIRONMENT_LIVE;

        // Not configured, keep null (and check later).
        if (empty($this->clientId) || empty($secret)) {
            return;
        }

        $this->client = ClientFactory::build($this->clientId, $secret, $this->environment);
        $this->addressParser = $addressParser;
    }

    public function isStaging() : bool {
        return Client::ENVIRONMENT_STAGING === $this->environment;
    }

    /**
     * @throws AuthorizationFailedException
     * @throws RequestFailedException
     * @throws Exception
     */
    public function getOrderById(string $id) : ?Order
    {
        $this->logger->info("BoekuwzendingClient::getOrderById(): " . $id . ", clientId: " . $this->clientId);
        $this->throwIfNotConfigured();

        return $this->client->order->get($id);
    }

    /**
     * @throws AuthorizationFailedException
     * @throws RequestFailedException
     * @throws Exception
     */
    public function createOrder(MagentoOrder $order) : ?Order
    {
        $this->logger->info("BoekuwzendingClient::createOrder(): " . $order->getId() . ", clientId: " . $this->clientId);
        $this->throwIfNotConfigured();

        // TODO: try-catch, status handling
        $buzOrder = $this->mapOrder($order);
        $buzOrder = $this->client->order->create($buzOrder);
        
        $this->logger->info("Created Boekuwzending order: '" . $buzOrder->getId() . "'"); 
        
        return $buzOrder;
    }

    private function mapOrder(MagentoOrder $order) : Order
    {
        $buzOrder = new Order("0");

        $buzOrder->setExternalId($order->getId());
        $buzOrder->setReference($order->getIncrementId());
        $buzOrder->setCreatedAtSource(new \DateTime());
        
        $shippingAddress = $order->getShippingAddress();
        
        $contact = new Contact();
        $contact->setName($shippingAddress->getName());
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
            * Which price? Does that account for discounts?
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

    /**
     * @throws LocalizedException
     */
    private function throwIfNotConfigured(): void
    {
        if (null === $this->client) {
            throw new LocalizedException(__("Client is not configured"), null, Constants::ERROR_CONFIGURATION_DATA_MISSING);
        }
    }
}