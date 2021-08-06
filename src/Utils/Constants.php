<?php

namespace Boekuwzending\Magento\Utils;

class Constants
{
    public const ERROR_CONFIGURATION_DATA_MISSING = 10000;
    public const ERROR_ORDER_ALREADY_SHIPPED = 10001;

    public const CONFIG_CLIENTID_PATH = "carriers/boekuwzending/clientId";
    public const CONFIG_CLIENTSECRET_PATH = "carriers/boekuwzending/clientSecret";
    public const CONFIG_TESTMODE_PATH = "carriers/boekuwzending/testmode";
    public const CONFIG_WEBHOOK_LABELCREATED_SHIPORDER = "carriers/boekuwzending/shipOrderOnLabelCreated";
}