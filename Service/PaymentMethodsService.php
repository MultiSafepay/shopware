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

use MltisafeMultiSafepayPayment\Components\Factory\Client;
use MultiSafepay\Api\PaymentMethods\PaymentMethod;
use MultiSafepay\Exception\ApiException;
use Psr\Http\Client\ClientExceptionInterface;
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
                $paymentMethods = $paymentMethodManager->getPaymentMethodsAsArray(true, $options);
                $cache->save($paymentMethods, $cacheId);
            } catch (ApiException | ClientExceptionInterface $exception) {
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
