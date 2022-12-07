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
 * @author      MultiSafepay <techsupport@multisafepay.com>
 * @copyright   Copyright (c) 2019 MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MltisafeMultiSafepayPayment\Tests\Integration\Components;

use MltisafeMultiSafepayPayment\Components\Helper;
use PHPUnit\Framework\TestCase;
use Shopware\Models\Order\Status;

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
     * TearDown Unit test.
     */
    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Tests if Helper::orderHasClearedDate works correctly
     */
    public function testOrderHasClearedDate()
    {
        $this->assertFalse(Helper::orderHasClearedDate($this));

        $order = $this->createOrder();
        $this->assertFalse(Helper::orderHasClearedDate($order));

        $order->setClearedDate(new \DateTime());
        $this->assertTrue(Helper::orderHasClearedDate($order));
    }

    /**
     * test if Helper::isValidOrder can get the current class and validate it
     */
    public function testIsValidOrder()
    {
        $this->assertFalse(Helper::isValidOrder($this));

        $order = $this->createOrder();
        $this->assertTrue(Helper::isValidOrder($order));
    }

    /**
     * @throws \Exception
     */
    public function testIsOrderAllowedToChangePaymentStatus()
    {
        //Should fail because it is not a order instance.
        $this->assertFalse(Helper::isOrderAllowedToChangePaymentStatus($this));

        $order = $this->createOrder();
        $this->assertTrue(Helper::isOrderAllowedToChangePaymentStatus($order));

        $paymentStatusReviewNecessary = $this->em->getReference(
            Status::class,
            Status::PAYMENT_STATE_REVIEW_NECESSARY
        );
        $order->setPaymentStatus($paymentStatusReviewNecessary);
        $this->assertFalse(Helper::isOrderAllowedToChangePaymentStatus($order));
    }

    /**
     * Test the function Helper::getPaymentStatus() with invalid data as String.
     */
    public function testGetPaymentStatusWithInvalidValueAsString()
    {
        $helperMock = $this->setHelperMockInstanceWithUnclearedValue('invalid value');

        $result = $helperMock->getPaymentStatus('uncleared');
        $expected = Status::PAYMENT_STATE_RESERVED;
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the function Helper::getPaymentStatus() with invalid data as Integer.
     */
    public function testGetPaymentStatusWithInvalidValueAsInteger()
    {
        $helperMock = $this->setHelperMockInstanceWithUnclearedValue(99);

        $result = $helperMock->getPaymentStatus('uncleared');
        $expected = Status::PAYMENT_STATE_RESERVED;
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the function Helper::getPaymentStatus() with invalid data
     * The function test if getPaymentStatus() reaches the fallback
     * when using a order status (int) instead of a payment status (int).
     */
    public function testGetPaymentStatusWithInvalidValueAsOrderStatusValue()
    {
        $helperMock = $this->setHelperMockInstanceWithUnclearedValue(Status::ORDER_STATE_READY_FOR_DELIVERY);

        $result = $helperMock->getPaymentStatus('uncleared');
        $expected = Status::PAYMENT_STATE_RESERVED;
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the function Helper::getPaymentStatus() with valid data
     */
    public function testGetPaymentStatusWithValidValue()
    {
        $helperMock = $this->setHelperMockInstanceWithUnclearedValue(Status::PAYMENT_STATE_COMPLETELY_PAID);

        $result = $helperMock->getPaymentStatus('uncleared');
        $expected = Status::PAYMENT_STATE_COMPLETELY_PAID;
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the Payment status with the default value
     */
    public function testGetPaymentStatusWithDefaultValue()
    {
        $helperMock = $this->setHelperMockInstanceWithUnclearedValue(Status::PAYMENT_STATE_RESERVED);

        $result = $helperMock->getPaymentStatus('uncleared');
        $expected = Status::PAYMENT_STATE_RESERVED;
        $this->assertEquals($expected, $result);
    }

    /**
     * @param $value
     * @return Helper
     */
    public function setHelperMockInstanceWithUnclearedValue($value)
    {
        /** @var $helperMock Helper */
        $helperMock = $this->getMockBuilder(Helper::class)
            ->setMethodsExcept(['getPaymentStatus', 'isValidPaymentStatus'])
            ->getMock();
        $helperMock->expects($this->once())
            ->method('getMultiSafepaySettings')
            ->willReturn([
                'msp_update_uncleared' => $value
            ]);
        return $helperMock;
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
}
