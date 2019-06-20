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
 * @copyright   Copyright (c) 2019 MultiSafepay, Inc. (http://www.multisafepay.com)
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

class HelperTest extends TestCase
{
    /**
     * Setup Unit test.
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * TearDown Unit test.
     */
    public function tearDown()
    {
        parent::tearDown();
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
}
