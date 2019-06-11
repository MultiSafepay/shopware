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

return array_merge($this->loadConfig($this->AppPath() . 'Configs/Default.php'), [
    'front' => [
        'throwExceptions' => true,
        'disableOutputBuffering' => false,
        'showException' => true,
    ],
    'errorHandler' => [
        'throwOnRecoverableError' => true,
    ],
    'session' => [
        'unitTestEnabled' => true,
        'name' => 'SHOPWARESID',
        'cookie_lifetime' => 0,
        'use_trans_sid' => false,
        'gc_probability' => 1,
        'gc_divisor' => 100,
        'save_handler' => 'db',
    ],
    'mail' => [
        'type' => 'file',
        'path' => $this->getCacheDir(),
    ],
    'phpSettings' => [
        'error_reporting' => E_ALL,
        'display_errors' => 1,
        'date.timezone' => 'Europe/Berlin',
        'max_execution_time' => 0,
    ],
    'csrfProtection' => [
        'frontend' => false,
        'backend' => false,
    ],
]);
