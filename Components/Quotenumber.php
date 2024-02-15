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
use Shopware\Components\NumberRangeIncrementerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Quotenumber
 *
 * @package MltisafeMultiSafepayPayment\Components
 */
class Quotenumber
{
    /**
     * @var string
     */
    public const DEFAULT_PATTERN  = "%s%'.09d%s";

    /**
     * @var NumberRangeIncrementerInterface
     */
    private $numberRangeIncrementer;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Quotenumber constructor
     *
     * @param NumberRangeIncrementerInterface $numberRangeIncrementer
     * @param ContainerInterface $container
     */
    public function __construct(
        NumberRangeIncrementerInterface $numberRangeIncrementer,
        ContainerInterface $container
    ) {
        $this->numberRangeIncrementer = $numberRangeIncrementer;
        $this->container = $container;
    }

    /**
     * Get the next quote number
     *
     * @return string|string[]|null
     */
    public function getNextQuotenumber()
    {
        $quoteNumber = $this->numberRangeIncrementer->increment('msp_quote_number');
        [$cachedConfigReader, $shop] = (new CachedConfigService($this->container))->selectConfigReader();
        $pluginConfig = $cachedConfigReader ? $cachedConfigReader->getByPluginName('MltisafeMultiSafepayPayment', $shop) : null;
        $formattedNumber = $pluginConfig ? sprintf(self::DEFAULT_PATTERN, $pluginConfig['msp_quote_prefix'], $quoteNumber, $pluginConfig['msp_quote_suffix']) : $quoteNumber;

        return preg_replace('/\s+/', '', $formattedNumber);
    }
}
