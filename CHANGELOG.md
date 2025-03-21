# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.3.0] - 2025-03-18
### Added
+ PLGSHPS-290: Add support for tokenization

***

## [3.2.0] - 2025-02-06
### Added
+ PLGSHPS-305: Add the default countries in root and branded payments
+ PLGSHPS-296: Add support for branded cards
+ PLGSHPS-292: Add the new logging to all calls to the configuration reader

### Fixed
+ PLGSHPS-309: Fix getDefaultLogPath method, within LoggerService
+ DAVAMS-873: Fix gift cards hidden at checkout when the maximum amount is surpassed

***

## [3.1.0] - 2024-09-17
### Added
+ PLGSHPS-303: Add support for iDEAL 2.0

### Removed
+ PLGSHPS-299: Remove iDEAL issuer dropdown

### Fixed
+ PLGSHPS-302: Fix billing-shipping addresses sometimes is missing on confirmation page
+ PLGSHPS-301: Fix wrong usage of getIsoName() to retrieve the ISO country ID
+ PLGSHPS-300: Fix Apple Pay template is missing when payment methods are installed
+ PLGSHPS-297: Fix issue where session is lost on notifyAction()

***

## [3.0.3] - 2024-05-30
### Fixed
+ PLGSHPS-291: Fix OrderUpdateSubscriber
+ DAVAMS-748: Fix the 'template_id' setting field within the Payment Component

***

## [3.0.2] - 2024-03-14
### Fixed
+ PLGSHPS-288: Fix payment method gathering performance in the checkout

***

## [3.0.1] - 2024-03-12
### Fixed
+ PLGSHPS-285: Fix updating plugin via the marketplace

***

## [3.0.0] - 2024-03-11
### Added
+ PLGSHPS-242: Add support to register the payment methods dynamically, via API request

### Removed
+ DAVAMS-705: Santander Betaal per Maand to be discontinued

***

## [2.12.1] - 2023-07-31
### Fixed
+ PLGSHPS-259: Fix error handling on update transaction shipment request

***

## [2.12.0] - 2023-06-21
### Added
+ PLGSHPS-254: Add an option to switch the order flow for creating orders
+ PLGSHPS-125: Add POST notification support

***

## [2.11.5] - 2023-03-08
### Added
+ DAVAMS-579: Add Pay After Delivery Installments

### Changed
+ DAVAMS-589: Rebrand Pay After Delivery logo

### Fixed
+ PLGSHPS-251: Fix error when description is missing
+ PLGSHPS-252: Fix error when VAT is 0

***

## [2.11.4] - 2023-02-07
### Changed
+ PLGSHPS-249: Move shippingRequest to OrderUpdateSubscriber and create empty file OrderHistorySubscriber to fix error on update

***

## [2.11.3] - 2023-01-26
### Changed
+ PLGSHPS-248: Listen to Shopware\Models\Order\Order::postUpdate instead of Events::preUpdate for sending shipped requests
+ PLGSHPS-247: Add scheduleClearCache function on update

### Fixed
+ PLGSHPS-246: Fix PHP error when weight is null

***

## [2.11.2] - 2023-01-12
### Fixed
+ PLGSHPS-244: Fix 500 error on update

***

## [2.11.1] - 2022-12-07
### Fixed
+ Fix incorrect shipping name being used on payment page
+ Fix incorrect hash being generated on multi-stores

***

## [2.11.0] - 2022-12-07
### Added
+ PLGSHPS-223: Add support for the [PHP-SDK](https://github.com/MultiSafepay/php-sdk)

***

## [2.10.0] - 2022-11-02
### Added
+ DAVAMS-519: Add Amazon Pay
+ DAVAMS-487: Add MyBank payment method
+ PLGSHPS-192: Add automated invoice creation on completed orders

### Changed
+ DAVAMS-545: Update Afterpay to Riverty

### Fixed
+ PLGSHPS-237: Fix billing and shipping address missing on thank-you page

***

## [2.9.0] - 2022-07-11
### Added
+ DAVAMS-478: Add Alipay plus

### Changed
+ PLGSHPS-221: Disable ING Home'Pay on update and install
+ PLGSHPS-179: Disable Babygiftcard, Nationale verwencadeaubon and Erotiekbon on update and install

### Fixed
+ PLGSHPS-200: Potential fix for orders getting the review necessary status while it shouldn't

***

## [2.8.4] - 2021-12-13
### Changed
+ PLGSHPS-214: Improve logger for signature check
+ PLGSHPS-220: Add support to change payment method in backend on notification

***

## [2.8.3] - 2021-11-30
### Fixed
+ PLGSHPS-219: Fix payment link not created for backend orders

***

## [2.8.2] - 2021-10-04
### Fixed
+ PLGSHPS-217: Fix fatal error getArrayCopy on success page

***

## [2.8.1] - 2021-09-10
### Fixed
+ PLGSHPS-212: Fix missing payment link for backend orders, when device type is set
+ PLGSHPS-213: Fix orders are not created when state is not available in customer request
+ PLGSHPS-216: Fix bug when double orders are being created 

***

## [2.8.0] - 2021-04-01
### Added
+ DAVAMS-240: Add in3 payment method
+ PLGSHPS-175: Add support for paymentlinks with backend orders

***

## [2.7.0] - 2020-12-16
### Added
+ SUPD-746: Add Good4fun Giftcard

### Fixed
+ PLGSHPS-202: Resolve error when saving order in backend

### Changed
+ DAVAMS-349: Update Trustly logo

***

## [2.6.0] - 2020-12-07
### Added
+ Add generic gateway which can be used for branded giftcards

### Removed
+ Remove 'Mark order as shipped' button in backend

### Changed
+ DAVAMS-318: Rebrand Klarna to Klarna - buy now, pay later
+ Order is marked as shipped at MultiSafepay when order status is changed to Completely delivered

***

## [2.5.0] - 2020-09-30
### Added
+ DAVAMS-274: Add CBC payment method
+ Add support for tax free products

### Fixed
+ PLGSHPS-198: Fix shipping costs tax rounding issue

### Changed
+ DAVAMS-301: Rebrand Direct Bank Transfer to Request to Pay

***

## [2.4.0] - 2020-06-24
### Added
+ PLGSHPS-194: Make send status mail on paid order optional
+ PLGSHPS-193: Install new payment methods on update

### Fixed
+ PLGSHPS-195: Fix payment method amount filter not working
+ Add null check when Optin service record is not found

### Changed
+ Make the text "Choose your bank" translatable
+ DAVAMS-226: Update logo and name for Santander

***

## [2.3.0] - 2020-03-26
### Added
+ PLGSHPS-189: Add Apple Pay
+ PLGSHPS-188: Add Direct Bank Transfer

***

## [2.2.1] - 2020-03-11
### Fixed
+ PLGSHPS-176: Fix payment status stuck on review necessary

***

## [2.2.0] - 2020-03-05
### Added
+ PLGSHPS-128: Send shipped status for all payment methods after shipping
+ PLGSHPS-120: Add basket signature checking
+ PLGSHPS-81: Add customizable payment status changes for order updates

### Fixed
+ PLGSHPS-178: Fix second chance issues by using optin service
+ PLGSHPS-143: Unable to ship orders with Wuunder
+ PLGSHPS-132: postDispatchSecure event not triggering on notifyAction (Thanks to Martin Dieleman)
+ PLGSHPS-124: Fixed undefined class for refund and shipped button

### Changed
+ PLGSHPS-154: Improve parsing of address fields into street and apartment
+ PLGSHPS-127: Add error message to payment selection page if the payment link could not be generated by the API
+ PLGSHPS-146: Prevent current payment status to be 0 after refunding with customizable refund status

***

## [2.1.0] - 2019-07-02
### Added
+ PLGSHPS-97: Add Webshop Giftcard as a giftcard
+ PLGSHPS-80: Implement customizable refund and shipment statuses
+ PLGSHPS-78: Add track trace code in shipment request
+ PLGSHPS-91: Add notification when update transaction status to shipped is declined
+ PLGSHPS-91: Add notification when refund is declined

### Fixed
+ PLGSHPS-118: Prevent sporadic CSRF token warning on checkout
+ PLGSHPS-86: Do not update payment status if it was already set to paid
+ PLGSHPS-87: Prevent duplicated calls to setting the cleared date on an order
+ PLGSHPS-134: Disable auto-submit for iDEAL issuers dropdown
+ PLGSHPS-129: Save iDEAL issuer choice when switching between shipment methods

### Changed
+ PLGSHPS-112: Correct spelling of ING Home'Pay
+ PLGSHPS-110: Use shipment name in shopping cart
+ PLGSHPS-114: Hide iDEAL issuers on preferred payment page

***

## [2.0.2] - 2019-03-19
### Fixed
+ PLGSHPS-130: Add support for Shopware 5.5.7
+ PLGSHPS-119: Fix refund and shipment didn't work for Shopware 5.5.x

***

## [2.0.1] - 2018-08-24
### Added
+ PLGSHPS-105: Add support for subshops

### Fixed
+ PLGSHPS-104: Remove spaces in quote number to prevent 1006 errors
+ PLGSHPS-107: Fix error 1000: optional ipaddress

***

## [2.0.0] - 2018-07-24
### Changes
+ Shopware MultiSafepay Plug-in 2.0.0
