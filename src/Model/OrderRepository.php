<?php

namespace Boekuwzending\Magento\Model;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

use Boekuwzending\Magento\Api\OrderRepositoryInterface;
use Boekuwzending\Magento\Api\Data\OrderInterface;
use Boekuwzending\Magento\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory; // Does not exist, instantiated by reflection.

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * @var SearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
 
    /**
     * @var OrderCollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var OrderFactory // Does not exist, instantiated by reflection.
     */
    protected $orderFactory;
    
    /**
     * @var SearchResultsInterface
     */
    protected $searchResults;

    /**
     * AbstractRepository constructor.
     *
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param SearchCriteriaBuilder         $searchCriteriaBuilder
     */
    public function __construct(
        SearchResultsInterfaceFactory $searchResultsFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderCollectionFactory $collectionFactory,
        OrderFactory $orderFactory
    ) {
        $this->searchResultsFactory = $searchResultsFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->collectionFactory = $collectionFactory;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Retrieve a list of Matrixrates.
     *
     * @param SearchCriteriaInterface $criteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $searchResults = $this->getSearchResults($criteria);
        $collection = $this->collectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $this->handleFilterGroups($filterGroup, $collection);
        }

        $searchResults->setTotalCount($collection->getSize());
        $this->handleSortOrders($criteria, $collection);

        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }
    
    /**
     * @param OrderInterface $order
     *
     * @return OrderInterface
     * @throws CouldNotSaveException
     */
    public function save(OrderInterface $order)
    {
        try {
            $order->save();
        } catch (\Exception $exception) {
            // @codingStandardsIgnoreLine
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $order;
    }

    /**
     * @param $identifier
     *
     * @return OrderInterface
     * @throws NoSuchEntityException
     */
    public function getById($identifier)
    {
        $order = $this->orderFactory->create();
        $order->load($identifier);

        if (!$order->getId()) {
            // @codingStandardsIgnoreLine
            throw new NoSuchEntityException(__('Order with id "%1" does not exist.', $identifier));
        }

        return $order;
    }

    /**
     * @param array $data
     *
     * @return OrderInterface
     */
    public function create(array $data = []): OrderInterface
    {
        return $this->orderFactory->create($data);
    }

    /**
     * @param int $orderId
     *
     * @return OrderInterface[]|null
     */
    public function getByOrderId(int $orderId)
    {
        return $this->getByFieldWithValue(Order::FIELD_SALES_ORDER_ID, $orderId);
    }

    // Repository boilerplate below

    /**
     * @param $field
     * @param $value
     *
     * @return OrderInterface[]|null
     */
    public function getByFieldWithValue($field, $value)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter($field, $value);

        /** @var \Magento\Framework\Api\SearchResults $list */
        $list = $this->getList($searchCriteria->create());

        if ($list->getTotalCount()) {
            $items = $list->getItems();
            return $items;
        }

        return null;
    }

    /**
     * @param SearchCriteriaInterface $criteria
     *
     * @return SearchResultsInterface
     */
    // @codingStandardsIgnoreLine
    protected function getSearchResults(SearchCriteriaInterface $criteria)
    {
        $this->searchResults = $this->searchResultsFactory->create();
        $this->searchResults->setSearchCriteria($criteria);

        return $this->searchResults;
    }

    /**
     * @param FilterGroup        $filterGroup
     * @param AbstractCollection $collection
     */
    // @codingStandardsIgnoreLine
    protected function handleFilterGroups($filterGroup, $collection)
    {
        $fields     = [];
        $conditions = [];

        /** @var Filter[] $filters */
        $filters = array_filter($filterGroup->getFilters(), function (Filter $filter) {
            return !empty($filter->getValue());
        });

        foreach ($filters as $filter) {
            $condition    = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
            $fields[]     = $filter->getField();
            $conditions[] = [$condition => $filter->getValue()];
        }

        if ($fields) {
            $collection->addFieldToFilter($fields, $conditions);
        }
    }

    /**
     * @param SearchCriteriaInterface $criteria
     * @param                         $collection
     */
    // @codingStandardsIgnoreLine
    protected function handleSortOrders(SearchCriteriaInterface $criteria, $collection)
    {
        $sortOrders = $criteria->getSortOrders();

        if (!$sortOrders) {
            return;
        }

        /** @var SortOrder $sortOrder */
        foreach ($sortOrders as $sortOrder) {
            $collection->addOrder(
                $sortOrder->getField(),
                ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
            );
        }
    }
    
}