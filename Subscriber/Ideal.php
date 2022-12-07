<?php declare(strict_types=1);
/**
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
 *
 * @category    MultiSafepay
 * @package     Connect
 * @author      MultiSafepay <techsupport@multisafepay.com>
 * @copyright   Copyright (c) 2018 MultiSafepay, Inc. (http://www.multisafepay.com)
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
use MltisafeMultiSafepayPayment\Components\Factory\Client;
use MltisafeMultiSafepayPayment\Components\Gateways;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Ideal implements SubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var Client
     */
    private $client;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, Client $client)
    {
        $this->container = $container;
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchCheckout',
            'Shopware_Modules_Admin_UpdatePayment_FilterSql' => 'onUpdatePaymentPaymentPage',
        ];
    }

    /**
     * @param \Enlight_Controller_ActionEventArgs $args
     * @throws \Exception
     */
    public function onPostDispatchCheckout(\Enlight_Controller_ActionEventArgs $args)
    {
        if ($args->getRequest()->getControllerName() === 'checkout') {
            $shop = $this->container->get('shop');
            $pluginConfig = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('MltisafeMultiSafepayPayment', $shop);

            $privateIssuers = $this->client->getSdk($pluginConfig)->getIssuerManager()->getIssuersByGatewayCode(Gateways::GATEWAYS['IDEAL']['code']);
            $issuers = [];
            foreach ($privateIssuers as $issuer) {
                $issuers[] = [
                    'code' => $issuer->getCode(),
                    'description' => $issuer->getDescription()
                ];
            }

            $view = $args->getSubject()->View();
            $view->assign('currentIssuer', $this->container->get('session')->get('ideal_issuer'));
            $view->assign('idealIssuers', $issuers);
        }
        if ($args->getRequest()->getActionName() === 'saveShippingPayment') {
            $ideal_issuer = $args->getRequest()->getPost('ideal_issuers');
            $session = $this->container->get('session');
            $session->offsetSet('ideal_issuer', $ideal_issuer);
        }
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     * @return mixed
     */
    public function onUpdatePaymentPaymentPage(\Enlight_Event_EventArgs $args)
    {
        // get issuer
        $idealIssuer = Shopware()->Front()->Request()->getPost('ideal_issuers');

        $session = $this->container->get('session');
        $session->offsetSet('ideal_issuer', $idealIssuer);

        return $args->getReturn();
    }
}
