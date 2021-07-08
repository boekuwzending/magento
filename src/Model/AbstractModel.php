<?php

namespace Boekuwzending\Magento\Model;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel as AbstractBasicModel;

class AbstractModel extends AbstractBasicModel
{
    public const FIELD_CREATED_DATETIME = 'created_datetime';
    public const FIELD_UPDATED_DATETIME = 'updated_datetime';

    /**
     * @var DateTime
     */
    private $dateTime;

    public function __construct(
        Context $context,
        Registry $registry,
        DateTime $dateTime,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->dateTime = $dateTime;
    }

    /**
     * @return $this
     */
    public function beforeSave()
    {
        $this->changeUpdatedDatetime($this->dateTime->gmtDate());

        if (!$this->getCreatedDatetime()) {
            $this->changeCreatedDatetime($this->dateTime->gmtDate());
        }

        return parent::beforeSave();
    }

    /**
     * @param string
     *
     * @return $this
     */
    public function changeCreatedDatetime($value)
    {
        return $this->setData(static::FIELD_CREATED_DATETIME, $value);
    }

    /**
     * @return null|string
     */
    public function getCreatedDatetime()
    {
        return $this->getData(static::FIELD_CREATED_DATETIME);
    }

    /**
     * @param string
     *
     * @return $this
     */
    public function changeUpdatedDatetime($value)
    {
        return $this->setData(static::FIELD_UPDATED_DATETIME, $value);
    }

    /**
     * @return null|string
     */
    public function getUpdatedDatetime()
    {
        return $this->getData(static::FIELD_UPDATED_DATETIME);
    }
}
