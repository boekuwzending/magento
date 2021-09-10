# 
docker-compose up -d
docker-compose exec --user=root web chown -R kevin:kevin /magento2

# This starts the site.
docker-compose exec -e BUZ_API_URL=https://dev.api.boekuwzending.com/ web php -S 0.0.0.0:5000 -t /magento2/pub/ /magento2/phpserver/router.php
#docker-compose exec web php -S 0.0.0.0:5000 -t /magento2/pub/ /magento2/phpserver/router.php
