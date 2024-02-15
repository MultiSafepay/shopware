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

namespace MltisafeMultiSafepayPayment\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Controller_ActionEventArgs;
use Enlight_Event_EventArgs;

/**
 * Class Frontend
 *
 * @package MltisafeMultiSafepayPayment\Subscriber
 */
class Frontend implements SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginDir;

    /**
     * Frontend constructor
     *
     * @param string $pluginDir
     */
    public function __construct(string $pluginDir)
    {
        $this->pluginDir = $pluginDir;
    }

    /**
     * Get subscribed events
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Theme_Inheritance_Template_Directories_Collected' => 'onCollectTemplateDir',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onPostDispatchCheckout',
            'Enlight_Controller_Dispatcher_ControllerPath_Frontend_MultiSafepayPayment' => 'onGetControllerPathFrontend',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_MultiSafepayPayment' => 'onGetControllerPathBackend'
        ];
    }

    /**
     * On get controller path frontend
     *
     * @return string
     */
    public function onGetControllerPathFrontend(): string
    {
        return $this->pluginDir . '/Controllers/Frontend/MultiSafepayPayment.php';
    }

    /**
     * On get controller path backend
     *
     * @return string
     */
    public function onGetControllerPathBackend(): string
    {
        return $this->pluginDir . '/Controllers/Backend/MultiSafepayPayment.php';
    }

    /**
     * On post-dispatch checkout
     *
     * @param Enlight_Controller_ActionEventArgs $args
     * @return void
     */
    public function onPostDispatchCheckout(Enlight_Controller_ActionEventArgs $args): void
    {
        $errorMessage = $args->getRequest()->getParam('multisafepay_error_message');
        if ($errorMessage) {
            $view = $args->getSubject()->View();

            $errorMessage = explode('<br />', urldecode($errorMessage));
            $view->assign('sErrorMessages', $errorMessage);
        }
    }

    /**
     * On collect template dir
     *
     * @param Enlight_Event_EventArgs $args
     * @return void
     */
    public function onCollectTemplateDir(Enlight_Event_EventArgs $args): void
    {
        $dirs = $args->getReturn();
        $dirs[] = $this->pluginDir . '/Resources/views/';

        $args->setReturn($dirs);
    }
}
