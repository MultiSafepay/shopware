<p align="center">
  <img src="https://raw.githubusercontent.com/MultiSafepay/MultiSafepay-logos/master/MultiSafepay-logo-color.svg" width="400px" position="center">
</p>

# MultiSafepay plugin for Shopware 5

Easily integrate MultiSafepay payment solutions into your Shopware 5 webshop with our free plugin.

[![Latest stable version](https://img.shields.io/github/release/multisafepay/shopware.svg)](https://github.com/MultiSafepay/Shopware)

## About MultiSafepay

MultiSafepay is a collecting payment service provider, which means we take care of electronic contracts, technical details, and payment collection for each payment method. You can start selling online today and manage all your transactions in one place.

## Supported payment methods

See MultiSafepay Docs – [Shopware](https://docs.multisafepay.com/docs/shopware) > Payment methods.

## Prerequisites

- You will need a [MultiSafepay account](https://testmerchant.multisafepay.com/signup). Consider a test account first.
- Shopware version 5.6.x and above

## Installation and configuration

1. Sign in to your Shopware 5 backend.
2. Go to **Configuration** > **Plugin manager**.
3. Search for the MultiSafepay plugin and click **Download now**.
4. Go to **Configuration** > **Plugin manager** > **Installed**.
5. Search for the installed MultiSafepay plugin and click on the pencil icon.
6. In the **API key** field, enter your [API key](https://docs.multisafepay.com/docs/sites#site-id-api-key-and-security-code).
7. Fill out the other fields as required.
8. Go to **Configuration** and select the required payment methods.

For more information, see MultiSafepay Docs – [Shopware](https://docs.multisafepay.com/docs/shopware).

### Order flows
Since version 2.12.0, we have added support for multiple payment flows within our Shopware 5 plugin. Each one comes with it's pros and cons.

#### After flow (default)
The preferred method, but can cause some inconsistencies with the order creation due to session handling and [Second Chance](https://docs.multisafepay.com/docs/second-chance).

#### Before flow
Currently, the flow with the least order inconsistency, but has some drawbacks to analytics. This includes for example:

* The abandoned cart analytics won't be correctly displayed

## Contributors

If you see an opportunity to make an improvement, we invite you to create a pull request, create an issue, or email <integration@multisafepay.com>

As a thank you for your contribution, we'll send you a MultiSafepay t-shirt, making you part of the team!

## Want to be part of the team?

Are you a developer interested in working at MultiSafepay? Check out our [job openings](https://www.multisafepay.com/careers/#jobopenings) and feel free to get in touch!