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

namespace MltisafeMultiSafepayPayment\Tests\Intergration\Components;

use MltisafeMultiSafepayPayment\Components\Helper;

class HelperTest extends \Enlight_Components_Test_TestCase
{
    /**
     * @var Shopware\Components\Model\ModelManager
     */
    protected $em;
    /**
     * @var Shopware\Models\User\Repository
     */
    protected $repo;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->em = Shopware()->Models();
        $this->repo = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
    }

    /**
     * @return \Shopware\Models\Order\Order
     * @throws \Exception
     */
    public function createOrder()
    {
        $paymentStatusOpen = $this->em->getReference('\Shopware\Models\Order\Status', 17);
        $orderStatusOpen = $this->em->getReference('\Shopware\Models\Order\Status', 0);
        $paymentDebit = $this->em->getReference('\Shopware\Models\Payment\Payment', 2);
        $dispatchDefault = $this->em->getReference('\Shopware\Models\Dispatch\Dispatch', 9);
        $defaultShop = $this->em->getReference('\Shopware\Models\Shop\Shop', 1);
        $partner = new \Shopware\Models\Partner\Partner();
        $partner->setCompany('Dummy');
        $partner->setIdCode('Dummy');
        $partner->setDate(new \DateTime());
        $partner->setContact('Dummy');
        $partner->setStreet('Dummy');
        $partner->setZipCode('Dummy');
        $partner->setCity('Dummy');
        $partner->setPhone('Dummy');
        $partner->setFax('Dummy');
        $partner->setCountryName('Dummy');
        $partner->setEmail('Dummy');
        $partner->setWeb('Dummy');
        $partner->setProfile('Dummy');
        $this->em->persist($partner);
        $order = new \Shopware\Models\Order\Order();
        $order->setNumber('abc');
        $order->setPaymentStatus($paymentStatusOpen);
        $order->setOrderStatus($orderStatusOpen);
        $order->setPayment($paymentDebit);
        $order->setDispatch($dispatchDefault);
        $order->setPartner($partner);
        $order->setShop($defaultShop);
        $order->setInvoiceAmount(5);
        $order->setInvoiceAmountNet(5);
        $order->setInvoiceShipping(5);
        $order->setInvoiceShippingNet(5);
        $order->setTransactionId(5);
        $order->setComment('Dummy');
        $order->setCustomerComment('Dummy');
        $order->setInternalComment('Dummy');
        $order->setNet(true);
        $order->setTaxFree(false);
        $order->setTemporaryId(5);
        $order->setReferer('Dummy');
        $order->setTrackingCode('Dummy');
        $order->setLanguageIso('Dummy');
        $order->setCurrency('EUR');
        $order->setCurrencyFactor(5);
        $order->setRemoteAddress('127.0.0.1');
        return $order;
    }

    /**
     * @throws \Exception
     */
    public function testOrderHasClearedDate()
    {
        $this->assertFalse(Helper::orderHasClearedDate($this));

        $order = $this->createOrder();
        $this->assertFalse(Helper::orderHasClearedDate($order));

        $order->setClearedDate(new \DateTime());
        $this->assertTrue(Helper::orderHasClearedDate($order));
    }
}
