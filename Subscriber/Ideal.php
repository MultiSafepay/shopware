<?php

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
use Symfony\Component\DependencyInjection\ContainerInterface;
use MltisafeMultiSafepayPayment\Components\API\MspClient;

class Ideal implements SubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
            $msp = new MspClient();
            $shop = $this->container->get('shop');
            $pluginConfig = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('MltisafeMultiSafepayPayment', $shop);
            $msp->setApiKey($pluginConfig['msp_api_key']);
            if (!$pluginConfig['msp_environment']) {
                $msp->setApiUrl('https://testapi.multisafepay.com/v1/json/');
            } else {
                $msp->setApiUrl('https://api.multisafepay.com/v1/json/');
            }
            $issuers = $msp->issuers->get();

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
