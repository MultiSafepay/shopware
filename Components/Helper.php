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
 * @copyright   Copyright (c) 2019 MultiSafepay, Inc. (https://www.multisafepay.com)
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace MltisafeMultiSafepayPayment\Components;

use Shopware\Models\Order\Status;
use Shopware\Models\Order\Order;

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
        return (string)$xml->version;
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
        return self::isValidOrder($order) && $order->getClearedDate() !== null;
    }

    /**
     * @param $order
     * @return bool
     */
    public static function isValidOrder($order)
    {
        return $order instanceof \Shopware\Models\Order\Order;
    }

    /**
     * @param $order
     * @return bool
     */
    public static function isOrderAllowedToChangePaymentStatus($order)
    {
        if (!self::isValidOrder($order)) {
            return false;
        }

        return $order->getPaymentStatus()->getId() !== Status::PAYMENT_STATE_REVIEW_NECESSARY;
    }

    /**
     * @param $status
     * @param null $shop
     * @return int
     */
    public function getPaymentStatus($status, $shop = null)
    {
        $config = $this->getMultiSafepaySettings($shop);

        if ($this->isValidPaymentStatus($config['msp_update_' . $status . ''])) {
            return $config['msp_update_' . $status . ''];
        }

        switch ($status) {
            case 'expired':
            case 'cancelled':
            case 'void':
            case 'chargedback':
            case 'declined':
                $payment_status = Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
                break;
            case 'completed':
                $payment_status = Status::PAYMENT_STATE_COMPLETELY_PAID;
                break;
            case 'uncleared':
                $payment_status = Status::PAYMENT_STATE_RESERVED;
                break;
            case 'refund':
                $payment_status = Status::PAYMENT_STATE_THE_CREDIT_HAS_BEEN_ACCEPTED;
                break;
            default:
                $payment_status = Status::PAYMENT_STATE_OPEN;
                break;
        }

        return $payment_status;
    }

    /**
     * @param $shop
     * @return mixed
     */
    public function getMultiSafepaySettings($shop = null)
    {
        $config = Shopware()->Container()
            ->get('shopware.plugin.cached_config_reader')
            ->getByPluginName('MltisafeMultiSafepayPayment', $shop);
        return $config;
    }

    /**
     * @param $status
     * @return bool
     */
    public function isValidPaymentStatus($status)
    {
        if ($status && !is_int($status)) {
            return false;
        }

        $orderRepository = Shopware()->Models()->getRepository(Order::class);
        $allPaymentStatuses = $orderRepository->getPaymentStatusQuery()->getArrayResult();

        foreach ($allPaymentStatuses as $paymentStatus) {
            if ($paymentStatus['id'] === $status) {
                return true;
            }
        }

        return false;
    }
}
