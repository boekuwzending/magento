docker-compose up -d --force-recreate
docker-compose exec --user=root web chown -R kevin:kevin /magento2

# TODO: automatically run contents of webserver-install.sh on "web"

# If you're reading this, you should do that manually.

# Problem: how to provide api keys for magento repo?

# https://marketplace.magento.com/customer/accessKeys/