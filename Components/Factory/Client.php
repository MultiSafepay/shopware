<?php declare(strict_types=1);
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the MultiSafepay plugin
 * to newer versions in the future. If you wish to customize the plugin for your
 * needs please document your changes and make backups before you update.
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

namespace MltisafeMultiSafepayPayment\Components\Factory;

use Buzz\Client\Curl as CurlClient;
use GuzzleHttp\Client as GuzzleClient;
use MultiSafepay\Sdk;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;

class Client
{
    private function getClient(): ClientInterface
    {
        $client = new GuzzleClient();
        if (!$client instanceof ClientInterface) {
            $client = new CurlClient(new Psr17Factory());
        }

        return $client;
    }

    public function getSdk(array $pluginConfig): Sdk
    {
        return new Sdk(
            $pluginConfig['msp_api_key'],
            (bool)$pluginConfig['msp_environment'],
            $this->getClient(),
            new Psr17Factory(),
            new Psr17Factory()
        );
    }
}
