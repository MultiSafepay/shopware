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
    public static function parseAddress($address1, $address2 = '')
    {
        $address = trim($address1 . ' ' . $address2);
        $aMatch = array();
        $pattern        = '#^([\w[:punct:] ]+) ([0-9]{1,5})\s*(.*)$#';
        $matchResult    = preg_match($pattern, $address, $aMatch);
        $street         = (isset($aMatch[1])) ? $aMatch[1] : '';
        $apartment      = (isset($aMatch[2])) ? $aMatch[2] : '' ;
        $apartment     .= (isset($aMatch[3])) ? $aMatch[3] : '';
        return array($street, $apartment);
    }

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

    public static function getRemoteIP()
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return self::validateIP($_SERVER['REMOTE_ADDR']);
        } else {
            return '';
        }
    }

    public static function getForwardedIP()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return self::validateIP($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            return '';
        }
    }

    public static function getPluginVersion()
    {
        $xml = simplexml_load_file(__DIR__ . '/../plugin.xml');
        return (string) $xml->version;
    }
    
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

    public static function orderHasClearedDate($order)
    {
        if ($order instanceof \Shopware\Models\Order\Order && !is_null($order->getClearedDate())) {
            return true;
        }
        return false;
    }
}
