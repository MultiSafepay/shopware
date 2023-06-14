<?php declare(strict_types=1);
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
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

namespace MltisafeMultiSafepayPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentFilter implements SubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;
    private $session;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, \Enlight_Components_Session_Namespace $session)
    {
        $this->container = $container;
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'onFilterPaymentMeans',
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function onFilterPaymentMeans(\Enlight_Event_EventArgs $args)
    {
        $paymentMeans = $args->getReturn();

        foreach ($paymentMeans as $index => $paymentMean) {
            if (substr($paymentMean['name'], 0, 13) === "multisafepay_") {
                $customerLocale = Shopware()->Container()->get('shop')->getLocale()->getLocale();
                $logo_path = __DIR__
                    . '/../Resources/views/frontend/_public/src/img/'
                    . $customerLocale
                    . '/'
                    . strtolower($paymentMeans[$index]['name'])
                    . '.png';
                if (file_exists($logo_path)) {
                    $paymentMeans[$index]['msp_logo_locale'] = $customerLocale;
                } else {
                    $paymentMeans[$index]['msp_logo_locale'] = 'en_GB';
                }

                $genericImage = Shopware()->Container()
                    ->get('shopware.plugin.cached_config_reader')
                    ->getByPluginName('MltisafeMultiSafepayPayment')['msp_generic_gateway_image'];

                $paymentMeans[$index]['generic_image'] = $genericImage === null ? false : $genericImage;

                $amount = $this->getOrderAmount();
                $attributes = $this->container->get('shopware_attribute.data_loader')->load('s_core_paymentmeans_attributes', $paymentMean['id']);

                $min_amount = $attributes['msp_min_amount'];
                $max_amount = $attributes['msp_max_amount'];
                if ((!empty($min_amount) && $amount < $min_amount) || (!empty($max_amount) && $amount > $max_amount)) {
                    unset($paymentMeans[$index]);
                }
            }
        }
        $args->setReturn($paymentMeans);
    }

    /**
     * @return float
     */
    private function getOrderAmount()
    {
        $amount = $this->session->get('sOrderVariables')['sAmount'];

        // Fallback when session does not have sAmount
        if ($amount === null) {
            $amount = $this->session->get('sBasketAmount');
        }
        return (float) $amount;
    }
}
