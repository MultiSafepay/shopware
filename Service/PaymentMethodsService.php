<?php declare(strict_types=1);
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs, please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Shopware
 * @author      MultiSafepay <integration@multisafepay.com>
 * @copyright   Copyright (c) MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MltisafeMultiSafepayPayment\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Exception;
use MltisafeMultiSafepayPayment\Components\Factory\Client;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Exception\InvalidDataInitializationException;
use Psr\Http\Client\ClientExceptionInterface;
use Shopware\Models\Country\Country;
use Shopware\Models\Payment\Payment;
use Zend_Cache_Core;
use Zend_Cache_Exception;

/**
 * Class PaymentMethods
 *
 * @package MltisafeMultiSafepayPayment\Service
 */
class PaymentMethodsService
{
    /**
     * @var mixed
     */
    private $container;

    /**
     * @var Client
     */
    private $client;

    /**
     * PaymentMethods constructor
     *
     * @param $container
     */
    public function __construct($container)
    {
        $this->container = $container;
        $this->client = new Client();
    }

    /**
     * Get country IDs for a specific payment method
     *
     * @param array $allowedCountries
     * @return array
     */
    public function getCountryIdsForPaymentMethod(array $allowedCountries): array
    {
        $countryIds = [];

        if (!empty($allowedCountries)) {
            // Use the ORM to get country IDs for the allowed countries
            $repository = $this->container->get('models')->getRepository(Country::class);
            $queryBuilder = $repository->createQueryBuilder('country');
            $queryBuilder->select('DISTINCT country.id')
                ->where($queryBuilder->expr()->in('country.iso', ':allowedCountries'))
                ->setParameter('allowedCountries', $allowedCountries);

            $result = $queryBuilder->getQuery()->getArrayResult();
            $countryIds = array_column($result, 'id');
        }

        return $countryIds ?: [];
    }

    /**
     * Install payment methods in Shopware
     *
     * @param array $paymentMethod
     * @return void
     */
    public function processAllowedCountries(array $paymentMethod): array
    {
        // Getting the brand name from segment 1 in the exploded name
        $filteredPaymentName = $this->filterBrandedPayment($paymentMethod['id'], 1);

        $allowedCountries = [];
        // It will collect the root allowed countries if any
        if (!empty($paymentMethod['allowed_countries'])) {
            $allowedCountries = $paymentMethod['allowed_countries'];
        }

        // It will collect the allowed countries for the specific brand
        if (!empty($paymentMethod['brands']) && is_array($paymentMethod['brands'])) {
            foreach ($paymentMethod['brands'] as $brand) {
                if (!empty($brand['allowed_countries']) &&
                    is_array($brand['allowed_countries']) &&
                    ($brand['id'] === $filteredPaymentName)
                ) {
                    $allowedCountries[] = $brand['allowed_countries'];
                }
            }
        }

        return $allowedCountries;
    }

    /**
     * Add specific countries for the payment method
     *
     * @param Payment $payment
     * @param array $countryIds
     *
     * @return void
     */
    public function addCountriesForPaymentMethod(Payment $payment, array $countryIds): void
    {
        $tableName = 's_core_paymentmeans_countries';

        /** @var Connection $db */
        $db = $this->container->get('dbal_connection');

        foreach ($countryIds as $countryId) {
            try {
                $paymentId = $payment->getId();

                // Check if the entry already exists
                $existingEntry = $db->fetchOne(
                    'SELECT COUNT(*) FROM `' . $tableName . '` WHERE `paymentID` = ? AND `countryID` = ?',
                    [$paymentId, $countryId]
                );

                if (empty($existingEntry)) {
                    $db->insert($tableName, [
                        'paymentID' => $paymentId,
                        'countryID' => $countryId
                    ]);
                }
            } catch (DBALException $exception) {
                (new LoggerService($this->container))->addLog(
                    LoggerService::ERROR,
                    'Failed to insert country for payment method',
                    [
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'paymentID' => $payment->getId(),
                        'countryID' => $countryId,
                        'Exception' => $exception->getMessage()
                    ]
                );
            }
        }
    }

    /**
     * Convert branded payment method
     *
     * @param string  $paymentMethod
     *
     * @return string
     */
    public function filterBrandedPayment(string $paymentMethod): string
    {
        if (str_contains($paymentMethod, '-')) {
            $paymentMethod = explode('-', $paymentMethod)[0];
        }
        return trim($paymentMethod);
    }

    /**
     * Count the occurrences of the brand name after the hyphen,
     * so the brand name can be removed later if is not repeated
     *
     * @param array $paymentMethodsWithBrands
     *
     * @return array
     */
    private function countBrandNamesOccurrences(array $paymentMethodsWithBrands): array
    {
        $secondNames = [];

        foreach ($paymentMethodsWithBrands as $paymentMethod) {
            $parts = explode(' - ', $paymentMethod['name']);
            $secondName = $parts[1] ?? '';
            $secondNames[] = $secondName;
        }

        // Count the occurrences of the brand name
        return array_count_values($secondNames);
    }

    /**
     *  Format those payment names with brands, showing just the brand
     *  if it is not repeated in others payment methods
     *
     * @param array $paymentMethodsWithBrands
     * @param array $brandNameCounts
     *
     * @return array
     */
    private function formatPaymentMethods(array $paymentMethodsWithBrands, array $brandNameCounts): array
    {
        $formattedPaymentMethods = [];

        foreach ($paymentMethodsWithBrands as $paymentMethod) {
            $parts = explode(' - ', $paymentMethod['name']);
            $brandName = $parts[1] ?? '';

            // Show just the brand name if it is not repeated in others payment methods
            if (!isset($brandNameCounts[$brandName]) || ($brandNameCounts[$brandName] <= 1)) {
                $paymentMethod['name'] = $brandName;
            }
            $formattedPaymentMethods[] = $paymentMethod;
        }

        return $formattedPaymentMethods;
    }

    /**
     * Add brand to payment methods
     *
     * @param array $paymentMethods
     *
     * @return array
     */
    private function addBrandToPaymentMethods(array $paymentMethods): array
    {
        $paymentMethodsWithBrands = [];

        foreach ($paymentMethods as $paymentMethod) {
            if (!empty($paymentMethod['brands'])) {
                $paymentMethodsWithBrands[] = $paymentMethod;
                foreach ($paymentMethod['brands'] as $brand) {
                    $paymentMethodWithBrand = $paymentMethod;
                    // Add the brand to the payment method
                    $paymentMethodWithBrand['id'] .= '-' . $brand['id'];
                    // Add the brand to the name.
                    // Attention to the blank space before and after the hyphen
                    $paymentMethodWithBrand['name'] .= ' - ' . $brand['name'];
                    $paymentMethodsWithBrands[] = $paymentMethodWithBrand;
                }
            } else {
                $paymentMethodsWithBrands[] = $paymentMethod;
            }
        }

        $brandNamesCounts = $this->countBrandNamesOccurrences($paymentMethodsWithBrands);
        return $this->formatPaymentMethods($paymentMethodsWithBrands, $brandNamesCounts);
    }

    /**
     * Load payment methods
     *
     * @param bool|null $force Force the loading of
     *                         payment methods
     *
     * @param bool|null $shop Defaults to active shop.
     *                        If false, no shop will be used
     *
     * @return PaymentMethod[]
     */
    public function loadPaymentMethods(bool $force = false, bool $shop = null): array
    {
        /** @var Zend_Cache_Core $cache */
        $cache = $this->container->get('cache');
        $cacheId = md5('multisafepay_payment_methods');

        $shop = ($shop === false) ? null : $this->container->get('shop');
        $configReader = $this->container->get('shopware.plugin.config_reader');
        $pluginConfig = $configReader ? $configReader->getByPluginName('MltisafeMultiSafepayPayment', $shop) : [];

        // Load payment methods using a call to the API
        if ($force || ($cache->load($cacheId) === false)) {
            $options = [];

            if ($pluginConfig['msp_group_card_payment']) {
                $options['group_cards'] = 1;
            }

            try {
                $clientSdk = $this->client->getSdk($pluginConfig);
                $paymentMethodManager = $clientSdk->getPaymentMethodManager();
                $paymentMethods = $this->addBrandToPaymentMethods(
                    $paymentMethodManager->getPaymentMethodsAsArray(true, $options)
                );
                $cache->save($paymentMethods, $cacheId);
            } catch (ApiException | ClientExceptionInterface | InvalidDataInitializationException $exception) {
                (new LoggerService($this->container))->addLog(
                    LoggerService::ERROR,
                    'Could not load the payment methods',
                    [
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'Exception' => $exception->getMessage()
                    ]
                );
                $paymentMethods = [];
            } catch (Zend_Cache_Exception $zendException) {
                (new LoggerService($this->container))->addLog(
                    LoggerService::ERROR,
                    'Problem in the payment methods cache',
                    [
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'Exception' => $zendException->getMessage()
                    ]
                );
                $paymentMethods = [];
            }
        } else {
            $paymentMethods = $cache->load($cacheId);
        }

        return $paymentMethods;
    }
}
