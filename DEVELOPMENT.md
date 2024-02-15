## Requirements
- Docker and Docker Compose
- Expose token, follow instruction here: https://expose.beyondco.de/docs/introduction to get a token

## Installation
1. Clone the repository:
```
git clone https://github.com/MultiSafepay/shopware.git
``` 

2. Copy the example env file and make the required configuration changes in the .env file:
```
cp .env.example .env
```
- **EXPOSE_HOST** can be set to the expose server to connect to.
- **EXPOSE_TOKEN** must be filled in.
- **APP_SUBDOMAIN** replace the `-xx` in `shopware-dev-xx` with a number for example `shopware-dev-05`.
- **MULTISAFEPAY_API_KEY** must be filled in. You can find the API key in your MultiSafepay control panel.

3. Start the Docker containers
```
docker-compose up -d
```

4. Install Shopware 5 and the MultiSafepay plugin
```
make install
```
