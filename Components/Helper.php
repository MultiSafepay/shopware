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

namespace MltisafeMultiSafepayPayment\Components;

class Helper
{
    /**
     * Split the address into street and house number with extension.
     *
     * @param string $address1
     * @param string $address2
     * @return array
     */
    public static function parseAddress($address1, $address2 = '')
    {
        // Trim the addresses
        $address1 = trim($address1);
        $address2 = trim($address2);
        $fullAddress = trim("{$address1} {$address2}");
        $fullAddress = preg_replace("/[[:blank:]]+/", ' ', $fullAddress);

        // Make array of all regex matches
        $matches = [];

        /**
         * Regex part one: Add all before number.
         * If number contains whitespace, Add it also to street.
         * All after that will be added to apartment
         */
        $pattern = '/(.+?)\s?([\d]+[\S]*)(\s?[A-z]*?)$/';
        preg_match($pattern, $fullAddress, $matches);

        //Save the street and apartment and trim the result
        $street = isset($matches[1]) ? $matches[1] : '';
        $apartment = isset($matches[2]) ? $matches[2] : '';
        $extension = isset($matches[3]) ? $matches[3] : '';
        $street = trim($street);
        $apartment = trim($apartment . $extension);

        return [$street, $apartment];
    }

    /**
     * @param $ip
     * @return mixed|null
     */
    private static function validateIP($ip)
    {
        $ipList = explode(',', $ip);
        $ip = trim(reset($ipList));

        $isValid = filter_var($ip, FILTER_VALIDATE_IP);
        if ($isValid) {
            return $isValid;
        } else {
            return null;
        }
    }

    /**
     * @return mixed|string|null
     */
    public static function getRemoteIP()
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return self::validateIP($_SERVER['REMOTE_ADDR']);
        } else {
            return '';
        }
    }

    /**
     * @return mixed|string|null
     */
    public static function getForwardedIP()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return self::validateIP($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    public static function getPluginVersion()
    {
        $xml = simplexml_load_file(__DIR__ . '/../plugin.xml');
        return (string) $xml->version;
    }

    /**
     * @param $time_label
     * @param $time_active
     * @return float|int
     */
    public static function getSecondsActive($time_label, $time_active)
    {
        $seconds_active = 2592000;
        switch ($time_label) {
            case 1: //Days
                $seconds_active = $time_active * 24 * 60 * 60;
                break;
            case 2: //Hours
                $seconds_active = $time_active * 60 * 60;
                break;
            case 3: //Seconds
                $seconds_active = $time_active;
                break;
        }
        return $seconds_active;
    }

    /**
     * @param $order
     * @return bool
     */
    public static function orderHasClearedDate($order)
    {
        if ($order instanceof \Shopware\Models\Order\Order && !is_null($order->getClearedDate())) {
            return true;
        }
        return false;
    }
}
