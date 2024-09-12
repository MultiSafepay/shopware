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

use Doctrine\ORM\PersistentCollection;
use MltisafeMultiSafepayPayment\Components\Builder\OrderRequestBuilder;
use MltisafeMultiSafepayPayment\Components\Documents\Invoice;
use MltisafeMultiSafepayPayment\Components\Factory\Client;
use MltisafeMultiSafepayPayment\Components\Helper;
use MltisafeMultiSafepayPayment\Service\CachedConfigService;
use MltisafeMultiSafepayPayment\Service\LoggerService;
use MultiSafepay\Api\Transactions\TransactionResponse;
use MultiSafepay\Exception\ApiException;
use MultiSafepay\Util\Notification;
use Psr\Http\Client\ClientExceptionInterface;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\OptinService;
use Shopware\Components\OptinServiceInterface;
use Shopware\Models\Order\Document\Document;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware\Models\Payment\Payment;

/**
 * Class Shopware_Controllers_Frontend_MultiSafepayPayment
 *
 * @package MltisafeMultiSafepayPayment\Components
 */
class Shopware_Controllers_Frontend_MultiSafepayPayment extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    /**
     * @var int
     */
    private const MULTISAFEPAY_CREATE_ORDER_BEFORE = 2;

    /**
     * @var LoggerService
     */
    private $logger;

    /**
     * @var Client
     */
    private $client;

    /**
     * Pre-dispatch method
     *
     * @return void
     * @throws Exception
     */
    public function preDispatch(): void
    {
        $this->client = $this->get('multisafepay.factory.client');
        $this->logger = new LoggerService($this->container);
    }

    /**
     * Get whitelist for CSRF check
     *
     * @return array
     */
    public function getWhitelistedCSRFActions(): array
    {
        return [
            'index',
            'gateway',
            'notify',
            'return',
            'cancel'
        ];
    }

    /**
     * Index action method
     *
     * Forwards to the correct action
     *
     * @return void
     * @throws Exception
     */
    public function indexAction(): void
    {
        if (preg_match('/multisafepay_(.+)/', $this->getPaymentShortName(), $matches)) {
            $this->redirect(array('action' => 'gateway', 'payment' => $matches[1], 'forceSecure' => true));
            return;
        }

        $this->redirect(['controller' => 'checkout']);
    }

    /**
     * Gateway action method
     *
     * Collects the payment information and transmit it to MultiSafepay
     *
     * @return void
     * @throws Exception
     */
    public function gatewayAction(): void
    {
        [$cachedConfigReader, $shop] = (new CachedConfigService($this->container))->selectConfigReader();
        if (is_null($cachedConfigReader)) {
            $this->logger->addLog(
                LoggerService::WARNING,
                'Could not load plugin configuration',
                [
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Action' => $this->Request()->getActionName()
                ]
            );
            return;
        }
        $pluginConfig = $cachedConfigReader->getByPluginName('MltisafeMultiSafepayPayment', $shop);

        try {
            /** @var OrderRequestBuilder $orderRequestBuilder */
            $orderRequestBuilder = $this->get('multisafepay.builder.order_request_builder');
            $orderRequest = $orderRequestBuilder->build($this, $this->container, $this->getSignature());

            $session = $this->get('session');
            if (!is_null($session) && $session->get('payload')) {
                $orderRequest->addType('direct');
                $orderRequest->addData(
                    [
                        'payment_data' => [
                            'payload' => $session->get('payload')
                        ]
                    ]
                );
                $session->offsetUnset('payload');
            }

            $clientSdk = $this->client->getSdk($pluginConfig);
            $transactionManager = $clientSdk->getTransactionManager();
            $response = $transactionManager->create($orderRequest);

            if ((int)$pluginConfig['multisafepay_order_creation'] === self::MULTISAFEPAY_CREATE_ORDER_BEFORE) {
                $this->saveOrder(
                    $response->getOrderId(),
                    $response->getOrderId(),
                    null,
                    null
                );
            }
        } catch (ApiException | ClientExceptionInterface $exception) {
            $this->logger->addLog(
                LoggerService::ERROR,
                'API error occurred while trying to create a transaction',
                [
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Action' => $this->Request()->getActionName(),
                    'Exception' => $exception->getMessage()
                ]
            );
            $this->redirect(['controller' => 'checkout', 'action' => 'shippingPayment', 'multisafepay_error_message' => $exception->getMessage()]);
            return;
        }

        $this->redirect($response->getPaymentUrl());
    }

    /**
     * Notify action method
     *
     * @throws Exception
     */
    public function notifyAction()
    {
        $this->Front()
            ->Plugins()
            ->ViewRenderer()
            ->setNoRender();

        $transactionId = $this->Request()->getParam('transactionid');
        [$cachedConfigReader, $shop] = (new CachedConfigService($this->container))->selectConfigReader();
        if (is_null($cachedConfigReader)) {
            $this->logger->addLog(
                LoggerService::INFO,
                'Order is not yet created',
                [
                    'TransactionId' => $this->Request()->getParam('transactionid'),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Action' => $this->Request()->getActionName()
                ]
            );
            return $this->Response()
                ->setBody('Order is not yet created')
                ->setHttpResponseCode(403);
        }
        $pluginConfig = $cachedConfigReader->getByPluginName('MltisafeMultiSafepayPayment', $shop);
        $helper = new Helper();

        // Session data must be restored to ensure verifyBasketSignature() returns true,
        // because the 'sUserId' in the session needs to be refreshed to generate a signature
        // that matches the one produced by getSignature() and sent in var1.
        $hash = $this->Request()->getParam('hash');
        $this->fillMissingSessionData($hash);

        if ('POST' === $this->Request()->getMethod()) {
            $request = $this->Request();

            if (!Notification::verifyNotification(
                $request->getContent(),
                $_SERVER['HTTP_AUTH'],
                $pluginConfig['msp_api_key']
            )) {
                return $this->Response()
                    ->setBody('NG')
                    ->setHttpResponseCode(403);
            }
            $transaction = new TransactionResponse(
                json_decode($request->getContent(), true),
                $request->getContent()
            );
        } else {
            try {
                $clientSdk = $this->client->getSdk($pluginConfig);
                $transactionManager = $clientSdk->getTransactionManager();
                $transaction = $transactionManager->get($transactionId);
            } catch (ApiException | ClientExceptionInterface $exception) {
                $this->logger->addLog(
                    LoggerService::ERROR,
                    'API error occurred while trying to get the transaction',
                    [
                        'TransactionId' => $this->Request()->getParam('transactionid'),
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'Action' => $this->Request()->getActionName(),
                        'Exception' => $exception->getMessage()
                    ]
                );
                return $this->Response()
                    ->setBody('NG')
                    ->setHttpResponseCode(403);
            }
        }

        $status = $transaction->getStatus();
        $signature = $transaction->getVar1();
        $gatewayCode = $transaction->getPaymentDetails()->getType();

        /** @var Order $order */
        $order = Shopware()
            ->Models()
            ->getRepository(Order::class)
            ->findOneBy(['transactionId' => $transactionId]);

        $update_order = false;
        $payment_status = 0;
        switch ($status) {
            case 'initialized':
                break;
            case 'expired':
                $update_order = true;
                $payment_status = $helper->getPaymentStatus('expired', $pluginConfig);
                break;
            case 'cancelled':
            case 'void':
                $update_order = true;
                $payment_status = $helper->getPaymentStatus('cancelled', $pluginConfig);
                break;
            case 'chargedback':
                $update_order = true;
                $payment_status = $helper->getPaymentStatus('chargedback', $pluginConfig);
                break;
            case 'completed':
                if (Helper::orderHasClearedDate($order) === false) {
                    $update_order = true;
                }
                $payment_status = $helper->getPaymentStatus('completed', $pluginConfig);
                break;
            case 'uncleared':
                $payment_status = $helper->getPaymentStatus('uncleared', $pluginConfig);
                break;
            case 'declined':
                $update_order = true;
                $payment_status = $helper->getPaymentStatus('declined', $pluginConfig);
                break;
            case 'refunded':
                if ($pluginConfig['msp_update_refund_active'] &&
                    is_int($pluginConfig['msp_update_refund']) &&
                    ($pluginConfig['msp_update_refund'] > 0)
                ) {
                    $payment_status = $helper->getPaymentStatus('refund', $pluginConfig);
                    $update_order = true;
                }
                break;
        }

        if (is_null($order) && Helper::isConsideredPaid($status)) {
            $basket = $this->getBasketBasedOnSignature($signature);
            if ($basket) {
                $this->saveOrder(
                    $transactionId,
                    $transactionId,
                    null,
                    true
                );
                $this->savePaymentStatus(
                    $transactionId,
                    $transactionId,
                    $payment_status,
                    $helper->isAllowedToSendStatusMail($status, $pluginConfig)
                );
            } elseif (!Helper::isValidOrder($order)) {
                $this->saveOrder(
                    $transactionId,
                    $transactionId,
                    Status::PAYMENT_STATE_REVIEW_NECESSARY,
                    true
                );
            }
        }

        if ($update_order && Helper::isOrderAllowedToChangePaymentStatus($order)) {
            $this->savePaymentStatus(
                $transactionId,
                $transactionId,
                $payment_status,
                $helper->isAllowedToSendStatusMail($status, $pluginConfig)
            );
        }

        $order = Shopware()
            ->Models()
            ->getRepository(Order::class)
            ->findOneBy(
                ['transactionId' => $transactionId]
            );

        if (($status === 'completed') && !Helper::orderHasClearedDate($order)) {
            $this->generateInvoice($order, $pluginConfig);
            $this->setClearedDate($transactionId);
        }

        if (!empty($gatewayCode) && Helper::isValidOrder($order)) {
            $this->changePaymentMethod($order, $gatewayCode);
        }

        return $this->Response()
            ->setBody('OK')
            ->setHttpResponseCode(200);
    }

    /**
     * Return action method
     *
     * @return void
     * @throws Exception
     */
    public function returnAction(): void
    {
        $request = $this->Request();
        [$cachedConfigReader, $shop] = (new CachedConfigService($this->container))->selectConfigReader();
        if (is_null($cachedConfigReader)) {
            $this->logger->addLog(
                LoggerService::WARNING,
                'Could not load plugin configuration',
                [
                    'TransactionId' => $this->Request()->getParam('transactionid'),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Action' => $this->Request()->getActionName()
                ]
            );
            return;
        }
        $pluginConfig = $cachedConfigReader->getByPluginName('MltisafeMultiSafepayPayment', $shop);
        $transactionId = $request->getParam('transactionid');

        $hash = $request->getParam('hash');
        $this->fillMissingSessionData($hash);

        try {
            $clientSdk = $this->client->getSdk($pluginConfig);
            $transactionManager = $clientSdk->getTransactionManager();
            $transaction = $transactionManager->get($transactionId);
        } catch (ApiException | ClientExceptionInterface | Exception $exception) {
            $this->logger->addLog(
                LoggerService::ERROR,
                'API error occurred while trying to get the transaction',
                [
                    'TransactionId' => $this->Request()->getParam('transactionid'),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Action' => $this->Request()->getActionName(),
                    'Exception' => $exception->getMessage()
                ]
            );
            return;
        }

        $order = Shopware()
            ->Models()
            ->getRepository(Order::class)
            ->findOneBy(
                ['transactionId' => $transactionId]
            );

        if ($order instanceof Order) {
            $this->redirect(['controller' => 'checkout', 'action' => 'finish', 'sUniqueID' => $transactionId]);
            return;
        }

        $signature = $transaction->getVar1();
        if ($this->getBasketBasedOnSignature($signature)) {
            $this->saveOrder($transactionId, $transactionId, null, true);
            $this->redirect(['controller' => 'checkout', 'action' => 'finish', 'sUniqueID' => $transactionId]);
            return;
        }

        $this->saveOrder($transactionId, $transactionId, Status::PAYMENT_STATE_REVIEW_NECESSARY, true);
        $this->redirect(['controller' => 'checkout', 'action' => 'finish', 'sUniqueID' => $transactionId]);
    }

    /**
     * Cancel action method
     *
     * @return void
     * @throws Exception
     */
    public function cancelAction(): void
    {
        $request = $this->Request();
        $transactionId = $request->getParam('transactionid');

        $order = Shopware()->Models()->getRepository(Order::class)->findOneBy(['transactionId' => $transactionId]);

        if ($order instanceof Order) {
            [$cachedConfigReader, $shop] = (new CachedConfigService($this->container))->selectConfigReader();
            if (is_null($cachedConfigReader)) {
                $this->logger->addLog(
                    LoggerService::WARNING,
                    'Could not load plugin configuration',
                    [
                        'TransactionId' => $transactionId,
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'Action' => $this->Request()->getActionName()
                    ]
                );
                return;
            }
            $pluginConfig = $cachedConfigReader->getByPluginName('MltisafeMultiSafepayPayment', $shop);

            $basketRestoreService = $this->container->get('multisafepay.service.basket_restore_service');
            if ($basketRestoreService) {
                $basketRestoreService->restoreBasketByOrder($order);
            }
            $orderService = $this->container->get('multisafepay.service.order_service');
            if ($orderService) {
                $orderService->cancelOrder($order);
            }
            if ($pluginConfig['msp_reset_stock']) {
                $stockService = $this->container->get('multisafepay.service.stock_service');
                if ($stockService) {
                    $stockService->restoreStockByOrder($order);
                }
            }
        }
        $this->redirect(['controller' => 'checkout']);
    }

    /**
     * Restore session data
     *
     * @param array $hashData
     * @return void
     */
    private function restoreSession(array $hashData): void
    {
        $sessionId = $hashData['sessionId'];

        if ((string)$sessionId === session_id()) {
            $this->logger->addLog(
                LoggerService::INFO,
                'Session Id is the same, no further actions required',
                [
                    'TransactionId' => $this->Request()->getParam('transactionid'),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'OrderSessionId' => $sessionId,
                    'Action' => $this->Request()->getActionName()
                ]
            );
            return;
        }
        $this->logger->addLog(
            LoggerService::INFO,
            'Start session restore',
            [
                'TransactionId' => $this->Request()->getParam('transactionid'),
                'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                'OrderSessionId' => $sessionId,
                'Action' => $this->Request()->getActionName()
            ]
        );

        if (class_exists(\Enlight_Components_Session::class)) {
            \Enlight_Components_Session::writeClose();
            \Enlight_Components_Session::setId($sessionId);
            \Enlight_Components_Session::start();
            return;
        }

        $this->logger->addLog(
            LoggerService::INFO,
            'Finding session in database',
            [
                'TransactionId' => $this->Request()->getParam('transactionid'),
                'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                'OrderSessionId' => $sessionId,
                'Action' => $this->Request()->getActionName()
            ]
        );

        $container = Shopware()->Container();
        $db = $container ? $container->get('db') : null;

        if (!is_null($db)) {
            try {
                $session = $db->fetchRow(
                    'SELECT * FROM `s_core_sessions` WHERE `id` = :sessionId',
                    [
                        'sessionId' => $sessionId
                    ]
                );

                if ($session === false) {
                    $this->logger->addLog(
                        LoggerService::WARNING,
                        'No session found in the database for the given session ID',
                        [
                            'TransactionId' => $this->Request()->getParam('transactionid'),
                            'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                            'OrderSessionId' => $sessionId,
                            'Action' => $this->Request()->getActionName()
                        ]
                    );
                    return;
                }
            } catch (Exception $exception) {
                $this->logger->addLog(
                    LoggerService::ERROR,
                    'Database error occurred while trying to fetch the session',
                    [
                        'TransactionId' => $this->Request()->getParam('transactionid'),
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'OrderSessionId' => $sessionId,
                        'Action' => $this->Request()->getActionName(),
                        'Exception' => $exception->getMessage()
                    ]
                );
                return;
            }
        } else {
            $this->logger->addLog(
                LoggerService::ERROR,
                'Database service is not available',
                [
                    'TransactionId' => $this->Request()->getParam('transactionid'),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'OrderSessionId' => $sessionId,
                    'Action' => $this->Request()->getActionName()
                ]
            );
            return;
        }

        if ($session) {
            $this->logger->addLog(
                LoggerService::INFO,
                'Session found in database, trying to restore it',
                [
                    'TransactionId' => $this->Request()->getParam('transactionid'),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'OrderSessionId' => $sessionId,
                    'DatabaseData' => $session,
                    'Action' => $this->Request()->getActionName()
                ]
            );

            try {
                Shopware()->Session()->save();
                session_id($sessionId);
                $this->logger->addLog(
                    LoggerService::INFO,
                    'Successfully restored session',
                    [
                        'TransactionId' => $this->Request()->getParam('transactionid'),
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'OrderSessionId' => $sessionId,
                        'Action' => $this->Request()->getActionName()
                    ]
                );
            } catch (Exception $exception) {
                $this->logger->addLog(
                    LoggerService::ERROR,
                    'Could not restore session',
                    [
                        'TransactionId' => $this->Request()->getParam('transactionid'),
                        'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                        'OrderSessionId' => $sessionId,
                        'Action' => $this->Request()->getActionName(),
                        'Exception' => $exception->getMessage()
                    ]
                );
            }
            return;
        }

        if ($hashData['sessionData']) {
            $this->logger->addLog(
                LoggerService::INFO,
                'Trying to restore session using opt-in service ',
                [
                    'TransactionId' => $this->Request()->getParam('transactionid'),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'OrderSessionId' => $sessionId,
                    'Action' => $this->Request()->getActionName()
                ]
            );

            $sessionData = json_decode($hashData['sessionData'], true);

            foreach ($sessionData as $key => $sessionDatum) {
                if (!Shopware()->Session()->get($key)) {
                    Shopware()
                        ->Session()
                        ->offsetSet(
                            $key,
                            $sessionDatum
                        );
                }
            }
            return;
        }
        $this->logger->addLog(
            LoggerService::WARNING,
            'Cannot restore session, no data found in the database',
            [
                'TransactionId' => $this->Request()->getParam('transactionid'),
                'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                'OrderSessionId' => $sessionId,
                'Action' => $this->Request()->getActionName()
            ]
        );
    }

    /**
     * Set cleared date
     *
     * @param $transactionId
     * @return void
     */
    private function setClearedDate($transactionId): void
    {
        $order = Shopware()
            ->Models()
            ->getRepository(Order::class)
            ->findOneBy(
                ['transactionId' => $transactionId]
            );

        // Check if the date has not been set yet
        if ($order && !Helper::orderHasClearedDate($order)) {
            $order->setClearedDate(new DateTime());
            $modelsService = $this->container->get('models');
            if ($modelsService) {
                try {
                    $modelsService->flush($order);
                } catch (Exception $exception) {
                    $this->logger->addLog(
                        LoggerService::ERROR,
                        'Could not set the cleared date in the database',
                        [
                            'TransactionId' => $this->Request()->getParam('transactionid'),
                            'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                            'Action' => $this->Request()->getActionName(),
                            'Exception' => $exception->getMessage()
                        ]
                    );
                }
            }
        }
    }

    /**
     * Get signature
     *
     * @return string
     */
    private function getSignature(): string
    {
        return $this->persistBasket();
    }

    /**
     * Get the basket based on signature
     *
     * @param $signature
     * @return bool|ArrayObject
     */
    private function getBasketBasedOnSignature($signature)
    {
        $this->logger->addLog(
            LoggerService::INFO,
            'Start signature check',
            [
                'TransactionId' => $this->Request()->getParam('transactionid'),
                'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                'Signature' => $signature,
                'Action' => $this->Request()->getActionName()
            ]
        );

        // To prevent race conditions, if the basket cannot be found, we will NOT set the order to review necessary
        try {
            $basket = $this->loadBasketFromSignature($signature);
        } catch (RuntimeException $runtimeException) {
            $this->logger->addLog(
                LoggerService::ERROR,
                RuntimeException::class . ': Could not verify the signature',
                [
                    'TransactionId' => $this->Request()->getParam('transactionid'),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Signature' => $signature,
                    'Basket' => null,
                    'Action' => $this->Request()->getActionName(),
                    'Exception' => $runtimeException->getMessage()
                ]
            );
            return true;
        }
        $this->logger->addLog(
            LoggerService::INFO,
            'Successfully loaded the basket',
            [
                'TransactionId' => $this->Request()->getParam('transactionid'),
                'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                'Signature' => $signature,
                'Basket' => $basket,
                'Action' => $this->Request()->getActionName()
            ]
        );

        try {
            $this->verifyBasketSignature($signature, $basket);
        } catch (RuntimeException $runtimeException) {
            $this->logger->addLog(
                LoggerService::ERROR,
                RuntimeException::class . ': Could not verify the signature',
                [
                    'TransactionId' => $this->Request()->getParam('transactionid'),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'Signature' => $signature,
                    'Basket' => $basket ?: null,
                    'Action' => $this->Request()->getActionName(),
                    'Exception' => $runtimeException->getMessage()
                ]
            );
            return false;
        }
        $this->logger->addLog(
            LoggerService::INFO,
            'Successfully verified the basket',
            [
                'TransactionId' => $this->Request()->getParam('transactionid'),
                'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                'Signature' => $signature,
                'Basket' => $basket,
                'Action' => $this->Request()->getActionName()
            ]
        );

        return $basket;
    }

    /**
     * Fill missing session data
     *
     * @param $hash
     * @return void
     */
    private function fillMissingSessionData($hash): void
    {
        // Backend order
        if (is_null($hash)) {
            return;
        }
        /** @var OptinService $optinService */
        $optinService = $this->container->get('shopware.components.optin_service');
        $data = $optinService->get(
            OptinServiceInterface::TYPE_CUSTOMER_LOGIN_FROM_BACKEND,
            $hash
        );

        if (null === $data) {
            return;
        }

        $this->restoreSession($data);
    }

    /**
     * Change the payment method of the order
     *
     * @param Order $order
     * @param $gatewayCode
     * @return void
     * @throws \Doctrine\ORM\ORMException
     */
    private function changePaymentMethod(Order $order, $gatewayCode): void
    {
        $paymentMethodId = Shopware()->Models()
            ->getRepository(Payment::class)
            ->getActivePaymentsQuery(['name' => 'multisafepay_' . $gatewayCode])
            ->getResult()[0]['id'];

        // If the payment method is the same, don't change it
        if ($order->getPayment()->getId() === $paymentMethodId) {
            return;
        }

        try {
            $paymentMethod = Shopware()->Models()->find(Payment::class, $paymentMethodId);
            $order->setPayment($paymentMethod);
        } catch (Exception $exception) {
            $this->logger->addLog(
                LoggerService::ERROR,
                'Could not change the payment method',
                [
                    'TransactionId' => $order->getTransactionId(),
                    'PaymentMethodId' => $paymentMethodId,
                    'PaymentMethod' => $paymentMethod ?? null,
                    'Action' => $this->Request()->getActionName(),
                    'Exception' => $exception->getMessage()
                ]
            );
        }

        try {
            Shopware()->Models()->persist($order);
        } catch (Exception $exception) {
            $this->logger->addLog(
                LoggerService::ERROR,
                'Could not persist the order',
                [
                    'TransactionId' => $order->getTransactionId(),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'PaymentMethodId' => $paymentMethodId,
                    'PaymentMethod' => $paymentMethod ?? null,
                    'Action' => $this->Request()->getActionName(),
                    'Exception' => $exception->getMessage(),
                ]
            );
        }

        try {
            Shopware()->Models()->flush($order);
        } catch (Exception $exception) {
            $this->logger->addLog(
                LoggerService::ERROR,
                'Could not flush the order',
                [
                    'TransactionId' => $order->getTransactionId(),
                    'CurrentSessionId' => isset($_SESSION['Shopware']['sessionId']) ? session_id() : 'session_id_not_found',
                    'PaymentMethodId' => $paymentMethodId,
                    'PaymentMethod' => $paymentMethod ?? null,
                    'Action' => $this->Request()->getActionName(),
                    'Exception' => $exception->getMessage()
                ]
            );
        }
    }

    /**
     * Generate invoice
     *
     * @param $order
     * @param $pluginConfig
     * @return void
     */
    private function generateInvoice($order, $pluginConfig): void
    {
        if (!$pluginConfig['multisafepay_create_invoice']) {
            return;
        }

        /** @var PersistentCollection $documents */
        $documents = $order->getDocuments();
        $invoiceExist = false;

        /** @var Document $document */
        foreach ($documents as $document) {
            if ($document->getTypeId() === Invoice::INVOICE_DOCUMENT_TYPE) {
                $invoiceExist = true;
            }
        }

        if (!$invoiceExist) {
            $invoiceController = $this->container->get('multisafepay.components.document.invoice');
            $invoiceController->create($order);
        }
    }
}
