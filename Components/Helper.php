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

namespace MltisafeMultiSafepayPayment\Components;

use MltisafeMultiSafepayPayment\Service\CachedConfigService;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

/**
 * Class Helper
 *
 * @package MltisafeMultiSafepayPayment\Components
 */
class Helper
{
    /**
     * Split the address into street and house number with an extension.
     *
     * @param string $address1
     * @param string $address2
     * @return array
     */
    public static function parseAddress(string $address1, string $address2 = ''): array
    {
        // Trim the addresses
        $address1 = trim($address1);
        $address2 = trim($address2);
        $fullAddress = trim("$address1 $address2");
        $fullAddress = preg_replace("/[[:blank:]]+/", ' ', $fullAddress);

        // Make an array of all regex matches
        $matches = [];

        /**
         * Regex part one: Add all before number.
         * If the number contains whitespace, Add it also to the street.
         * All after that will be added to an apartment
         */
        $pattern = '/(.+?)\s?(\d+\S*)(\s?[A-Za-z]*?)$/';
        preg_match($pattern, $fullAddress, $matches);

        // Save the street and apartment and trim the result
        $street = $matches[1] ?? '';
        $apartment = $matches[2] ?? '';
        $extension = $matches[3] ?? '';
        $street = trim($street);
        $apartment = trim($apartment . $extension);

        return [
            $street,
            $apartment
        ];
    }

    /**
     * Validate the IP
     *
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
        }

        return null;
    }

    /**
     * Get the remote IP
     *
     * @return mixed|string|null
     */
    public static function getRemoteIP()
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return self::validateIP($_SERVER['REMOTE_ADDR']);
        }

        return '';
    }

    /**
     * Get the forwarded IP
     *
     * @return mixed|string|null
     */
    public static function getForwardedIP()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return self::validateIP($_SERVER['HTTP_X_FORWARDED_FOR']);
        }

        return '';
    }

    /**
     * Get the plugin version
     *
     * @return string
     */
    public static function getPluginVersion(): string
    {
        $xml = simplexml_load_string(file_get_contents(__DIR__ . '/../plugin.xml'));

        return (string)$xml->version;
    }

    /**
     * Get the seconds active
     *
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
     * Order has cleared date
     *
     * @param $order
     * @return bool
     */
    public static function orderHasClearedDate($order): bool
    {
        return self::isValidOrder($order) && ($order->getClearedDate() !== null);
    }

    /**
     * Method to check if the order is considered paid
     *
     * @param $multiSafepayStatus
     * @return bool
     */
    public static function isConsideredPaid($multiSafepayStatus): bool
    {
        return in_array($multiSafepayStatus, ['completed', 'refunded', 'uncleared']);
    }

    /**
     * Method to check if the order is valid
     *
     * @param $order
     * @return bool
     */
    public static function isValidOrder($order): bool
    {
        return $order instanceof Order;
    }

    /**
     * Method to check if the order is allowed to change the payment status
     *
     * @param $order
     * @return bool
     */
    public static function isOrderAllowedToChangePaymentStatus($order): bool
    {
        if (!self::isValidOrder($order)) {
            return false;
        }

        return $order->getPaymentStatus()->getId() !== Status::PAYMENT_STATE_REVIEW_NECESSARY;
    }

    /**
     * Get the payment status
     *
     * @param $status
     * @param $pluginConfig
     * @return int
     */
    public function getPaymentStatus($status, $pluginConfig): int
    {
        if ($this->isValidPaymentStatus($pluginConfig['msp_update_' . $status])) {
            return $pluginConfig['msp_update_' . $status];
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
     * Get the MultiSafepay settings
     *
     * @return mixed
     */
    public function getMultiSafepaySettings()
    {
        [$cachedConfigReader, $shop] = (new CachedConfigService(Shopware()->Container()))->selectConfigReader();

        return $cachedConfigReader ? $cachedConfigReader->getByPluginName('MltisafeMultiSafepayPayment', $shop) : [];
    }

    /**
     * Method to check if the payment status is valid
     *
     * @param $status
     * @return bool
     */
    public function isValidPaymentStatus($status): bool
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

    /**
     * Method to check if status mail is allowed to be sent
     *
     * @param $status
     * @param $pluginConfig
     * @return bool
     */
    public function isAllowedToSendStatusMail($status, $pluginConfig): bool
    {
        $sendStatusMailOnCompleted = $pluginConfig['msp_send_status_mail_on_completed'];
        if (!$sendStatusMailOnCompleted && ($status === 'completed')) {
            return false;
        }

        return true;
    }
}
