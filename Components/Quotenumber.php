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

use Shopware\Components\NumberRangeIncrementerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Quotenumber
{
    public const DEFAULT_PATTERN  = "%s%'.09d%s";
    /**
     * @var NumberRangeIncrementerInterface
     */
    private $numberRangeIncrementer;
    private $container;

    /**
     * @param NumberRangeIncrementerInterface $numberRangeIncrementer
     */
    public function __construct(
        NumberRangeIncrementerInterface $numberRangeIncrementer,
        ContainerInterface $container
    ) {
        $this->numberRangeIncrementer = $numberRangeIncrementer;
        $this->container = $container;
    }

    /**
     * @return string|string[]|null
     */
    public function getNextQuotenumber()
    {
        $quoteNumber = $this->numberRangeIncrementer->increment('msp_quote_number');
        $shop = $this->container->get('shop');
        $pluginConfig = $this->container->get('shopware.plugin.cached_config_reader')->getByPluginName('MltisafeMultiSafepayPayment', $shop);
        $quoteNumber = sprintf(self::DEFAULT_PATTERN, $pluginConfig['msp_quote_prefix'], $quoteNumber, $pluginConfig['msp_quote_suffix']);
        $quoteNumber = preg_replace('/\s+/', '', $quoteNumber);
        return $quoteNumber;
    }
}
