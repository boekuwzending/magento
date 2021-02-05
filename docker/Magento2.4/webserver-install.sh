composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition /magento2

bin/magento setup:install \
  --base-url=http://localhost:5000 \
  --backend-frontname=admin_16ydhi \
  --db-host=mysql \
  --db-name=magento \
  --db-user=magento \
  --db-password=magento \
  --admin-firstname=Admin \
  --admin-lastname=MyStore \
  --admin-email=admin@admin.com \
  --admin-user=admin \
  --admin-password=magentorocks1 \
  --language=en_US \
  --currency=EUR \
  --timezone=Europe/Amsterdam \
  --use-rewrites=1 \
  --elasticsearch-host=elasticsearch \
  --elasticsearch-port=9200  
 
bin/magento sampledata:deploy
bin/magento setup:upgrade
bin/magento indexer:reindex
bin/magento cache:flush

# 2FA uit voor development
bin/magento module:disable Magento_TwoFactorAuth
bin/magento setup:di:compile
