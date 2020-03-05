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

namespace MltisafeMultiSafepayPayment\Components\API\Object;

class Issuers extends Core
{
    public $success;
    public $data;

    /**
     * @param string $endpoint
     * @param string $type
     * @param array $body
     * @param bool $query_string
     * @return mixed
     * @throws \Exception
     */
    public function get($endpoint = 'issuers', $type = 'ideal', $body = array(), $query_string = false)
    {
        $result = parent::get($endpoint, $type, $body, $query_string);
        $this->success = $result->success;
        $this->data = $result->data;

        return $this->data;
    }
}
