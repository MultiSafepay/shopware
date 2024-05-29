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

namespace MltisafeMultiSafepayPayment\Service;

use Exception;
use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;

/**
 * Class CachedConfigService
 *
 * @package MltisafeMultiSafepayPayment\Service
 */
class CachedConfigService
{
    /**
     * @var mixed The container object, which can be of any type.
     */
    private $container;

    /**
     * @var Order|null The order object, or null if no order is provided.
     */
    private $order;

    /**
     * @var LoggerService
     */
    private $logger;

    /**
     * CachedConfigService constructor.
     *
     * @param mixed $container The container object, which can be of any type.
     * @param Order|null $order The order object, or null if no order is provided.
     */
    public function __construct($container, Order $order = null)
    {
        $this->container = $container;
        $this->order = $order;
        $this->logger = new LoggerService($container);
    }

    /**
     * Get the Shopware version
     *
     * @return string The Shopware version, or 0 if not found.
     */
    private function getShopwareVersion(): string
    {
        return $this->container ? $this->container->getParameter('shopware.release.version') : '0';
    }

    /**
     * Determine if the new config reader should be used based on the Shopware version
     *
     * @param string $shopwareVersion The Shopware version.
     * @param string $cachedReaderClass The class name of the cached reader.
     *
     * @return bool True if the new config reader should be used, false otherwise.
     *
     * "New" refers to the config reader
     */
    private function shouldUseNewConfigReader(string $shopwareVersion, string $cachedReaderClass): bool
    {
        return class_exists($cachedReaderClass) &&
            version_compare($shopwareVersion, '5.7', '>=');
    }

    /**
     * Get the Shop object for older CachedConfigReader class and 'cached_config_reader' service
     *
     * @return Shop|null The Shop object, or null if not found or an exception occurs.
     */
    private function getShopObject(): ?Shop
    {
        try {
            return !is_null($this->order) ? $this->order->getShop() : $this->container->get('shop');
        } catch (Exception $exception) {
            $this->logger->addLog(
                LoggerService::INFO,
                'Shop as object was not found',
                [
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Exception' => $exception->getMessage()
                ]
            );
            return null;
        }
    }

    /**
     * Get Shop ID from the Shop model
     *
     * @return int|null The ID of the active default shop, or null if not found.
     */
    private function getShopIdFromActiveDefault(): ?int
    {
        $shopRepository = Shopware()->Models()->getRepository(Shop::class);

        return $shopRepository->getActiveDefault()->getId();
    }

    /**
     * Get the Shop ID for newer CachedConfigReader class
     *
     * @param Shop|null $shop The Shop object, or null if not found.
     *
     * @return int|null The ID of the shop associated with the order,
     * or the ID of the active default shop,
     * or null if not found or an exception occurs.
     */
    private function getShopId(?Shop $shop): ?int
    {
        try {
            return $shop ? $shop->getId() : $this->getShopIdFromActiveDefault();
        } catch (Exception $exception) {
            $this->logger->addLog(
                LoggerService::INFO,
                'Shop ID as integer was not found',
                [
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Exception' => $exception->getMessage()
                ]
            );
            return null;
        }
    }

    /**
     * Select the correct config reader based on the Shopware version
     *
     * @param Shop|null $shopObject The Shop object, or null if not found.
     *
     * @return array{0: object, 1: Shop|int|null} An array containing the selected config reader and the shop Object or ID.
     *
     * The first element is the selected config reader, which is an object.
     * The second element is either a Shop object, an integer representing the shop ID, or null if not found.
     */
    public function selectConfigReader(?Shop $shopObject = null): array
    {
        $shopwareVersion = $this->getShopwareVersion();
        if ($shopObject instanceof Shop) {
            $shop = $shopObject;
        } else {
            $shop = $this->getShopObject();
        }

        // No error if some class does not exist because using: ::class is just a string
        $cachedReaderClass = \Shopware\Components\Plugin\Configuration\CachedReader::class;
        $legacyCachedReaderClass = \Shopware\Components\Plugin\CachedConfigReader::class;

        // Using the new config reader for Shopware 5.7 and higher
        if (class_exists($cachedReaderClass) &&
            $this->shouldUseNewConfigReader($shopwareVersion, $cachedReaderClass)
        ) {
            $cachedConfigReader = $this->container->get($cachedReaderClass);
            $shop = $this->getShopId($shop);
        } elseif (class_exists($legacyCachedReaderClass)) {
            // Using the old config reader for Shopware 5.6 and lower
            $cachedConfigReader = $this->container->get($legacyCachedReaderClass);
        } else {
            // Using what has been configured in the services.xml file
            $cachedConfigReader = $this->container->get('shopware.plugin.cached_config_reader');
        }

        return [$cachedConfigReader, $shop];
    }
}
