# Omschrijving
Een module die orders inschiet bij Boekuwzending.

# Installatie
Als de module klaar is, zou dit de installatie moeten zijn met servertoegang:

    composer require boekuwzending/magento 
    bin/magento module:enable Boekuwzending_Magento
    bin/magento setup:upgrade
    bin/magento setup:di:compile

# Development-installatie
Wanneer tijdens de ontwikkeling van de module een dependency toegevoegd dient te worden, moet deze met de hand in de Magento-root (dus niet de module-directory) worden uitgevoerd:

    /magento2$composer require boekuwzending/php-sdk

Maar dit moet dus voor iedere volgende dependency weer gebeuren, en deze dependency moet ook worden opgenomen in de composer.json van dit project.

Daarna:

    bin/magento module:enable Boekuwzending_Magento
    bin/magento setup:upgrade
    bin/magento setup:di:compile

# Debugging
Developer mode voor nuttige foutmeldingen:

    bin/magento deploy:mode:set developer

XDebug staat enabled. Disablen voor de snelheid:

    phpdismod xdebug

En weer aan:

    phpenmod xdebug

Wanneer het is ingeschakeld, kun je vanuit VS Code met F5 attachen. Breakpoints worden geraakt, maar exceptions worden door Magento opgegeten.

# Configuratie
* Klik op Stores, en onder Settings op Configuration.
* Ga naar Sales -> Delivery Methods
* Scroll naar "BoekuwZending Shipping Module"

