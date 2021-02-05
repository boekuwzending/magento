# Scripts

## .\Install.ps1
Registreert de Docker-containers onder hun eigen stack-naam ("magentoboekuwzending", zie .env).

## Handmatige installatie
Run, via Bash, eenmalig de commando's uit het script `webserver-install.sh` op de Bash-terminal op de webserver.

## .\Run.ps1
Start de benodigde Docker-containers via docker-compose, en runt de webserver.

## .\Bash.ps1
Start een Bash-terminal op de webserver, zodat je daar Magento-commando's kunt uitvoeren.

# Development
Na wijzigingen in plugin-settings, routes, database, enzovoorts:

    bin/magento setup:upgrade

Na PHP-wijzigingen waarbij je een constructor aanpast of interface introduceert:

    bin/magento setup:di:compile

Met name relevant na wijzigingen in XML-bestanden of i18n-files:

    bin/magento cache:clean

# Vertalingen
Vertalingen worden by convention gevonden in `i18n/xx_YY.csv`, waarbij xx_YY een ISO-code à la `nl_NL` is. Strings in XML-files en binnen `__()`-calls kunnen worden gevonden en opgeslagen middels het volgende commando:

    bin/magento i18n:collect-phrases -o app/code/Boekuwzending/Magento/i18n/temp.csv app/code/Boekuwzending/Magento

Het CSV-formaat is `"Original string","Vertaalde string"[,module,$Module_Name]`, waarvan `[...]` optioneel. Wanneer een vertaling mist, wordt de originele string uit de XML (`<label>Original string</label>`of `__("Original string")`. Zorg dus dat de strings een duidelijke beschrijving hebben wanneer je ze vergeet te vertalen, of 

Dit slaat alle gevonden strings op in `/src/i18n/temp.csv`. Dit bestand wordt _niet_ gemerged, dus verplaats handmatig de nieuwe strings naar de relevante plek in `/src/i18n/nl_NL.csv`. Of gebruik een tool, die ik vanwege dependencies niet aan de praat krijg op Magento 2.4.

Na een wijziging in de CSV moet je de cache cleanen, zie boven.

# Magento-debugging
XDebug in VS Code: https://marketplace.visualstudio.com/items?itemName=felixfbecker.php-debug. Zie .vscode/launch.json:

    {
        "name": "Listen for XDebug",
        "type": "php",
        "request": "launch",
        "port": 9000,
        "pathMappings": {
            "/magento2/app/code/Boekuwzending/Magento": "${workspaceFolder}/src"
        },
        "xdebugSettings": {
            "max_data": 65535,
            "show_hidden": 1,
            "max_children": 100,
            "max_depth": 5
        }
    }

Exceptions lijken nog te worden opgegeten door Magento's error handler, maar breakpoints en step-through-debugging werken. Om Magento-code te kunnen debuggen, moet in de root van je client de map /magento2 bestaan, in mijn geval `C:\magento2\vendor\magento`.

Namen van blokken/templates/views:

* Menu -> Stores -> Configuration -> Advanced -> Developer -> Enable Template Path Hints for Admin

## Logging:
In /magento2, niet /:

    tail -f var/log/system.log
