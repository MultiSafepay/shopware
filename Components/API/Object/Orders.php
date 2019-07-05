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

class Orders extends Core
{
    public $success;
    public $data;

    /**
     * @param array $body
     * @param string $endpoint
     * @return mixed
     * @throws \Exception
     */
    public function patch($body, $endpoint = '')
    {
        $result = parent::patch(json_encode($body), $endpoint);
        $this->success = $result->success;
        $this->data = $result->data;
        return $result;
    }

    /**
     * @param $type
     * @param $id
     * @param array $body
     * @param bool $query_string
     * @return mixed
     * @throws \Exception
     */
    public function get($type, $id, $body = array(), $query_string = false)
    {
        $result = parent::get($type, $id, $body, $query_string);
        $this->success = $result->success;
        $this->data = $result->data;
        return $this->data;
    }

    /**
     * @param $body
     * @param string $endpoint
     * @return mixed
     * @throws \Exception
     */
    public function post($body, $endpoint = 'orders')
    {
        $result = parent::post(json_encode($body), $endpoint);
        $this->success = $result->success;
        $this->data = $result->data;
        return $this->data;
    }

    /**
     * @return mixed
     */
    public function getPaymentLink()
    {
        return $this->data->payment_url;
    }
}
