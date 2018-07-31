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

class Gateways
{
    const GATEWAYS = array(
        'AFTERPAY' => array('code' => 'AFTERPAY', 'name' => 'AfterPay', 'type' => 'redirect'),
        'ALIPAY' => array('code' => 'ALIPAY', 'name' => 'Alipay', 'type' => 'direct'),
        'AMEX' => array('code' => 'AMEX', 'name' => 'American Express', 'type' => 'redirect'),
        'MISTERCASH' => array('code' => 'MISTERCASH', 'name' => 'Bancontact', 'type' => 'redirect'),
        'BANKTRANS' => array('code' => 'BANKTRANS', 'name' => 'Banktransfer', 'type' => 'direct'),
        'BELFIUS' => array('code' => 'BELFIUS', 'name' => 'Belfius', 'type' => 'redirect'),
        'DIRDEB' => array('code' => 'DIRDEB', 'name' => 'Direct Debit', 'type' => 'redirect'),
        'DOTPAY' => array('code' => 'DOTPAY', 'name' => 'Dotpay', 'type' => 'redirect'),
        'EINVOICE' => array('code' => 'EINVOICE', 'name' => 'E-Invoice', 'type' => 'redirect'),
        'EPS' => array('code' => 'EPS', 'name' => 'EPS', 'type' => 'redirect'),
        'FERBUY' => array('code' => 'FERBUY', 'name' => 'Ferbuy', 'type' => 'redirect'),
        'GIROPAY' => array('code' => 'GIROPAY', 'name' => 'Giropay', 'type' => 'redirect'),
        'IDEAL' => array('code' => 'IDEAL', 'name' => 'iDEAL', 'type' => 'direct', 'template' => 'multisafepay_ideal.tpl'),
        'IDEALQR' => array('code' => 'IDEALQR', 'name' => 'iDEALQR', 'type' => 'redirect'),        
        'INGHOME' => array('code' => 'INGHOME', 'name' => 'ING HomePay', 'type' => 'direct'),
        'KBC' => array('code' => 'KBC', 'name' => 'KBC', 'type' => 'direct'),
        'KLARNA' => array('code' => 'KLARNA', 'name' => 'Klarna', 'type' => 'redirect'),
        'MAESTRO' => array('code' => 'MAESTRO', 'name' => 'Maestro', 'type' => 'redirect'),
        'MASTERCARD' => array('code' => 'MASTERCARD', 'name' => 'MasterCard', 'type' => 'redirect'),
        'PAYAFTER' => array('code' => 'PAYAFTER', 'name' => 'Pay After Delivery	', 'type' => 'redirect'),
        'PAYPAL' => array('code' => 'PAYPAL', 'name' => 'PayPal', 'type' => 'direct'),
        'PSAFECARD' => array('code' => 'PSAFECARD', 'name' => 'PaySafeCard', 'type' => 'redirect'),
        'SANTANDER' => array('code' => 'SANTANDER', 'name' => 'Santander Betaalplan', 'type' => 'redirect'),
        'DIRECTBANK' => array('code' => 'DIRECTBANK', 'name' => 'SOFORT Banking', 'type' => 'redirect'),
        'TRUSTLY' => array('code' => 'TRUSTLY', 'name' => 'Trustly', 'type' => 'direct'),
        'TRUSTPAY' => array('code' => 'TRUSTPAY', 'name' => 'Trustpay', 'type' => 'redirect'),
        'VISA' => array('code' => 'VISA', 'name' => 'Visa', 'type' => 'redirect'),
        'WALLET' => array('code' => 'WALLET', 'name' => 'Wallet', 'type' => 'redirect'),

        'BABYGIFTCARD' => array('code' => 'BABYGIFTCARD', 'name' => 'Babygiftcard', 'type' => 'redirect'),
        'BEAUTYANDWELLNESS' => array('code' => 'BEAUTYANDWELLNESS', 'name' => 'Beauty and wellness', 'type' => 'redirect'),
        'BOEKENBON' => array('code' => 'BOEKENBON', 'name' => 'Boekenbon', 'type' => 'redirect'),
        'EROTIEKBON' => array('code' => 'EROTIEKBON', 'name' => 'Erotiekbon', 'type' => 'redirect'),
        'FASHIONCHEQUE' => array('code' => 'FASHIONCHEQUE', 'name' => 'Fashioncheque', 'type' => 'redirect'),
        'FASHIONGIFTCARD' => array('code' => 'FASHIONGIFTCARD', 'name' => 'Fashiongiftcard', 'type' => 'redirect'),
        'FIETSENBON' => array('code' => 'FIETSENBON', 'name' => 'Fietsenbon', 'type' => 'redirect'),
        'GEZONDHEIDSBON' => array('code' => 'GEZONDHEIDSBON', 'name' => 'Gezondheidsbon', 'type' => 'redirect'),
        'GIVACARD' => array('code' => 'GIVACARD', 'name' => 'Givacard', 'type' => 'redirect'),
        'GOODCARD' => array('code' => 'GOODCARD', 'name' => 'Goodcard', 'type' => 'redirect'),
        'NATIONALETUINBON' => array('code' => 'NATIONALETUINBON', 'name' => 'Nationale tuinbon', 'type' => 'redirect'),
        'NATIONALEVERWENCADEAUBON' => array('code' => 'NATIONALEVERWENCADEAUBON', 'name' => 'Nationale verwencadeaubon', 'type' => 'redirect'),
        'PARFUMCADEAUKAART' => array('code' => 'PARFUMCADEAUKAART', 'name' => 'Parfumcadeaukaart', 'type' => 'redirect'),
        'PODIUM' => array('code' => 'PODIUM', 'name' => 'Podium', 'type' => 'redirect'),
        'SPORTENFIT' => array('code' => 'SPORTENFIT', 'name' => 'Sportenfit', 'type' => 'redirect'),
        'VVVGIFTCRD' => array('code' => 'VVVGIFTCRD', 'name' => 'VVV giftcard', 'type' => 'redirect'),
        'WELLNESSGIFTCARD' => array('code' => 'WELLNESSGIFTCARD', 'name' => 'Wellnessgiftcard', 'type' => 'redirect'),
        'WIJNCADEAU' => array('code' => 'WIJNCADEAU', 'name' => 'Wijncadeau', 'type' => 'redirect'),
        'WINKELCHEQUE' => array('code' => 'WINKELCHEQUE', 'name' => 'Winkelcheque', 'type' => 'redirect'),
        'YOURGIFT' => array('code' => 'YOURGIFT', 'name' => 'Yourgift', 'type' => 'redirect'),
    );

    public static function getGatewayCode($code)
    {
        return $code == 'WALLET' ? '' : self::GATEWAYS[$code]['code'];
    }

    public static function getGatewayType($code)
    {
        return self::GATEWAYS[$code]['type'];
    }

    public static function getGatewayTemplate($code)
    {
        return !empty(self::GATEWAYS[$code]['template']) ? self::GATEWAYS[$code]['template'] : '';
    }
}
