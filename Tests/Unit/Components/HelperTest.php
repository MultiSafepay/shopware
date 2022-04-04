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

namespace MltisafeMultiSafepayPayment\Tests\Unit\Components;

use MltisafeMultiSafepayPayment\Components\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    /**
     * @dataProvider addressProvider
     */
    public function testParseAddress($address1, $address2, $expected_street, $expected_apartment)
    {
        $result = Helper::parseAddress($address1, $address2);
        $this->assertEquals($expected_street, $result[0]);
        $this->assertEquals($expected_apartment, $result[1]);
    }

    /**
     * @return array
     */
    public function addressProvider()
    {
        return [
            [
                'address1' => "Kraanspoor",
                'address2' => "39",
                'street' => "Kraanspoor",
                'apartment' => "39",
            ],
            [
                'address1' => "Kraanspoor ",
                'address2' => "39",
                'street' => "Kraanspoor",
                'apartment' => "39",
            ],
            [
                'address1' => "Kraanspoor 39",
                'address2' => "",
                'street' => "Kraanspoor",
                'apartment' => "39",
            ],
            [
                'address1' => "Kraanspoor 39 ",
                'address2' => "",
                'street' => "Kraanspoor",
                'apartment' => "39",
            ],
            [
                'address1' => "Kraanspoor",
                'address2' => "39 ",
                'street' => "Kraanspoor",
                'apartment' => "39",
            ],
            [
                'address1' => "Kraanspoor39",
                'address2' => "",
                'street' => "Kraanspoor",
                'apartment' => "39",
            ],
            [
                'address1' => "Kraanspoor39c",
                'address2' => "",
                'street' => "Kraanspoor",
                'apartment' => "39c",
            ],
            [
                'address1' => "laan 1933 2",
                'address2' => "",
                'street' => "laan 1933",
                'apartment' => "2",
            ],
            [
                'address1' => "laan 1933",
                'address2' => "2",
                'street' => "laan 1933",
                'apartment' => "2",
            ],
            [
                'address1' => "18 septemberplein 12",
                'address2' => "",
                'street' => "18 septemberplein",
                'apartment' => "12",
            ],
            [
                'address1' => "18 septemberplein",
                'address2' => "12",
                'street' => "18 septemberplein",
                'apartment' => "12",
            ],
            [
                'address1' => "kerkstraat 42-f3",
                'address2' => "",
                'street' => "kerkstraat",
                'apartment' => "42-f3",
            ],
            [
                'address1' => "kerkstraat",
                'address2' => "42-f3",
                'street' => "kerkstraat",
                'apartment' => "42-f3",
            ],
            [
                'address1' => "Kerk straat 2b",
                'address2' => "",
                'street' => "Kerk straat",
                'apartment' => "2b",
            ],
            [
                'address1' => "Kerk straat",
                'address2' => "2b",
                'street' => "Kerk straat",
                'apartment' => "2b",
            ],
            [
                'address1' => "1e constantijn huigensstraat 1b",
                'address2' => "",
                'street' => "1e constantijn huigensstraat",
                'apartment' => "1b",
            ],
            [
                'address1' => "1e constantijn huigensstraat",
                'address2' => "1b",
                'street' => "1e constantijn huigensstraat",
                'apartment' => "1b",
            ],
            [
                'address1' => "Heuvel, 2a",
                'address2' => "",
                'street' => "Heuvel,",
                'apartment' => "2a",
            ],
            [
                'address1' => "1e Jan  van  Kraanspoor",
                'address2' => "2",
                'street' => "1e Jan van Kraanspoor",
                'apartment' => "2",
            ],
            [
                'address1' => "Neherkade 1 XI",
                'address2' => "",
                'street' => "Neherkade",
                'apartment' => "1 XI",
            ]
        ];
    }
}
