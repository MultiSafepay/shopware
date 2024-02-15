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

namespace MltisafeMultiSafepayPayment\Tests\Integration\Components;

use DateTime;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectRepository;
use Enlight_Components_Test_TestCase;
use Exception;
use MltisafeMultiSafepayPayment\Components\Helper;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Dispatch\Dispatch;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware\Models\Partner\Partner;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

/**
 * Class HelperTest
 *
 * @package MltisafeMultiSafepayPayment\Tests\Integration\Components
 */
class HelperTest extends Enlight_Components_Test_TestCase
{
    /**
     * @var ModelManager
     */
    protected $em;

    /**
     * @var EntityRepository|ObjectRepository
     */
    protected $repo;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->em = Shopware()->Models();
        $this->repo = Shopware()->Models()->getRepository(Order::class);
    }

    /**
     * TearDown Unit test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Tests if Helper::orderHasClearedDate works correctly
     *
     * @return void
     * @throws Exception
     */
    public function testOrderHasClearedDate(): void
    {
        $this->assertFalse(Helper::orderHasClearedDate($this));

        $order = $this->createOrder();
        $this->assertFalse(Helper::orderHasClearedDate($order));

        $order->setClearedDate(new DateTime());
        $this->assertTrue(Helper::orderHasClearedDate($order));
    }

    /**
     * Test if Helper::isValidOrder can get the current class and validate it
     *
     * @return void
     * @throws Exception
     */
    public function testIsValidOrder(): void
    {
        $this->assertFalse(Helper::isValidOrder($this));

        $order = $this->createOrder();
        $this->assertTrue(Helper::isValidOrder($order));
    }

    /**
     * Test if Helper::isOrderAllowedToChangePaymentStatus can get the current class and validate it
     *
     * @return void
     * @throws \Doctrine\ORM\OptimisticLockException|\Doctrine\ORM\Exception\ORMException|\Doctrine\ORM\ORMException
     */
    public function testIsOrderAllowedToChangePaymentStatus(): void
    {
        //Should fail because it is not an order instance.
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
     *
     * @return void
     */
    public function testGetPaymentStatusWithInvalidValueAsString(): void
    {
        $helperMock = $this->setHelperMockInstanceWithUnclearedValue('invalid value');

        $pluginConfig = $helperMock->getMultiSafepaySettings()['msp_update_uncleared'];
        $result = $helperMock->getPaymentStatus('uncleared', $pluginConfig);
        $expected = Status::PAYMENT_STATE_RESERVED;
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the function Helper::getPaymentStatus() with invalid data as Integer.
     *
     * @return void
     */
    public function testGetPaymentStatusWithInvalidValueAsInteger(): void
    {
        $helperMock = $this->setHelperMockInstanceWithUnclearedValue(99);

        $pluginConfig = $helperMock->getMultiSafepaySettings()['msp_update_uncleared'];
        $result = $helperMock->getPaymentStatus('uncleared', $pluginConfig);
        $expected = Status::PAYMENT_STATE_RESERVED;
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the function Helper::getPaymentStatus() with invalid data
     * The function test if getPaymentStatus() reaches the fallback
     * when using an order status (int) instead of a payment status (int).
     *
     * @return void
     */
    public function testGetPaymentStatusWithInvalidValueAsOrderStatusValue(): void
    {
        $helperMock = $this->setHelperMockInstanceWithUnclearedValue(Status::ORDER_STATE_READY_FOR_DELIVERY);

        $pluginConfig = $helperMock->getMultiSafepaySettings()['msp_update_uncleared'];
        $result = $helperMock->getPaymentStatus('uncleared', $pluginConfig);
        $expected = Status::PAYMENT_STATE_RESERVED;
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the function Helper::getPaymentStatus() with valid data
     *
     * @return void
     */
    public function testGetPaymentStatusWithValidValue(): void
    {
        $helperMock = $this->setHelperMockInstanceWithUnclearedValue(Status::PAYMENT_STATE_COMPLETELY_PAID);

        $pluginConfig = $helperMock->getMultiSafepaySettings()['msp_update_completed'];
        $result = $helperMock->getPaymentStatus('completed', $pluginConfig);
        $expected = Status::PAYMENT_STATE_COMPLETELY_PAID;
        $this->assertEquals($expected, $result);
    }

    /**
     * Test the Payment status with the default value
     *
     * @return void
     */
    public function testGetPaymentStatusWithDefaultValue(): void
    {
        $helperMock = $this->setHelperMockInstanceWithUnclearedValue(Status::PAYMENT_STATE_RESERVED);

        $pluginConfig = $helperMock->getMultiSafepaySettings()['msp_update_uncleared'];
        $result = $helperMock->getPaymentStatus('uncleared', $pluginConfig);
        $expected = Status::PAYMENT_STATE_RESERVED;
        $this->assertEquals($expected, $result);
    }

    /**
     * Set the Helper mock instance with the given value
     *
     * @param $value
     * @return Helper
     */
    public function setHelperMockInstanceWithUnclearedValue($value): Helper
    {
        /** @var $helperMock Helper */
        $helperMock = $this->getMockBuilder(Helper::class)
            ->setMethodsExcept(
                ['getPaymentStatus', 'isValidPaymentStatus']
            )
            ->getMock();
        $helperMock->expects($this->once())
            ->method('getMultiSafepaySettings')
            ->willReturn([
                'msp_update_uncleared' => $value
            ]);
        return $helperMock;
    }

    /**
     * Create a test double order
     *
     * @return Order
     * @throws Exception
     */
    public function createOrder(): Order
    {
        $paymentStatusOpen = $this->em->getReference(Status::class, 17);
        $orderStatusOpen = $this->em->getReference(Status::class, 0);
        $paymentDebit = $this->em->getReference(Payment::class, 2);
        $dispatchDefault = $this->em->getReference(Dispatch::class, 9);
        $defaultShop = $this->em->getReference(Shop::class, 1);
        $partner = new Partner();
        $partner->setCompany('Dummy');
        $partner->setIdCode('Dummy');
        $partner->setDate(new DateTime());
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
        $order = new Order();
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
