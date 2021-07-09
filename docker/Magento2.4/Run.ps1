# 
docker-compose up -d
docker-compose exec --user=root web chown -R kevin:kevin /magento2
docker-compose exec web php -S 0.0.0.0:5000 -t /magento2/pub/ /magento2/phpserver/router.php
