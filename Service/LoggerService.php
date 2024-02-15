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

use DateTime;
use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\HostnameProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Shopware\Models\Log\Log;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoggerService
{
    /**
     * Logger levels
     *
     * @var int
     */
    public const DEBUG = Logger::DEBUG;

    /**
     * @var int
     */
    public const INFO = Logger::INFO;

    /**
     * @var int
     */
    public const NOTICE = Logger::NOTICE;

    /**
     * @var int
     */
    public const WARNING = Logger::WARNING;

    /**
     * @var int
     */
    public const ERROR = Logger::ERROR;

    /**
     * @var int
     */
    public const CRITICAL = Logger::CRITICAL;

    /**
     * @var int
     */
    public const ALERT = Logger::ALERT;

    /**
     * @var int
     */
    public const EMERGENCY = Logger::EMERGENCY;

    /**
     * Maximum number of log files to keep
     *
     * @var int
     */
    public const MAX_LOG_FILES = 7;

    /**
     * Monolog Logger instance
     *
     * @var Logger
     */
    private $logger;

    /**
     * Shopware service container
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * LoggerService constructor
     *
     * @param ContainerInterface $container The Shopware service container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->initializeLogger();
    }

    /**
     * Initializes the logger instance
     *
     * @return void
     */
    private function initializeLogger(): void
    {
        // Logger instance using Monolog
        $this->logger = new Logger('multisafepay');

        // Log is only written to file if the path to var/log is present and writable
        $defaultPath = $this->getDefaultLogPath();
        if ($defaultPath) {
            // Adding the caller class and method to the log entries
            $introspectionProcessor = new IntrospectionProcessor(Logger::DEBUG, [__CLASS__]);
            $this->logger->pushProcessor($introspectionProcessor);

            // Adding the web request data
            $webProcessor = new WebProcessor();
            $this->logger->pushProcessor($webProcessor);

            // Adding the hostname
            $hostnameProcessor = new HostnameProcessor();
            $this->logger->pushProcessor($hostnameProcessor);

            // Adding a rotating file handler.
            // A limited number of files are kept, one for each day of the week
            $rotatingFileHandler = new RotatingFileHandler($defaultPath, self::MAX_LOG_FILES);
            // Formatting the log entries
            $rotatingFileHandler->setFormatter($this->getLineFormatter());

            // Adding the handler to the logger instance
            $this->logger->pushHandler($rotatingFileHandler);
        }
    }

    /**
     * Adds a log entry using the Monolog logger instance
     *
     * @param int $logLevel Log level (use class constants)
     * @param string $message Log message
     * @param array $context Contextual data array
     * @return void
     */
    public function addLog(int $logLevel = self::DEBUG, string $message = '', array $context = []): void
    {
        try {
            // Log if the log level is equal or higher than the error level
            $shouldLog = $logLevel >= self::ERROR;

            // Taking into account the other cases with lower log levels
            if (!$shouldLog) {
                // Getting the plugin configuration
                [$cachedConfigReader, $shop] = (new CachedConfigService($this->container))->selectConfigReader();
                $pluginConfig = $cachedConfigReader ? $cachedConfigReader->getByPluginName('MltisafeMultiSafepayPayment', $shop) : [];
                // Log if the debug mode is enabled
                $shouldLog = isset($pluginConfig['multisafepay_debug_mode']) && $pluginConfig['multisafepay_debug_mode'];
            }

            if ($shouldLog) {
                // Log to file because any of the above two conditions are met
                $this->logger->log($logLevel, $message, $context);
            }
        } catch (Exception $exception) {
            // Log the exception using the same logger feature
            $this->logger->log(self::ERROR, $exception->getMessage());
        }
    }

    /**
     * Gets the default log path
     *
     * Ensures no trailing slashes on the path, checks if 'var/log' exists,
     * creates it if not and checks if it is writable and then appends
     * the log filename
     *
     * @return string Default log path
     */
    private function getDefaultLogPath(): string
    {
        // Getting the base path of Shopware using Symphony's kernel.root_dir,
        // avoiding pass null to rtrim because PHP 8.x will throw a TypeError
        $basePath = rtrim($this->container->getParameter('kernel.root_dir') ?? '', '/');
        if (!$basePath) {
            // Getting the base path of Shopware using the native DocPath
            $basePath = rtrim(Shopware()->DocPath() ?? '', '/');
        }
        if (!$basePath) {
            // Log to database as alternative
            $this->logToDatabase('Both kernel.root_dir and DocPath are empty');
            // Early return to avoid creating var/log folder in the wrong place later,
            // because is appended to the base path below
            return '';
        }
        // Appending the log folder to the base path
        $basePath .= '/var/log';

        // Check if the log directory exists, otherwise creates it and checks if the creation was ok
        if (!is_dir($basePath) && !@mkdir($basePath, 0755, true) && !is_dir($basePath)) {
            // var/log folder could not be created mostly due to permission issues
            $this->logToDatabase('Could not create log directory: ' . $basePath);
            return '';
        }
        if (!is_writable($basePath)) {
            // The folder was created by who is not the web server user
            $this->logToDatabase('Log directory is not writable: ' . $basePath);
            return '';
        }

        return $basePath . '/multisafepay.log';
    }

    /**
     * Creates and returns a LineFormatter instance.
     *
     * @return LineFormatter The configured LineFormatter instance
     */
    private function getLineFormatter(): LineFormatter
    {
        return new LineFormatter(
            '[%datetime%] %level_name%: %message% %context% %extra%' . "\n",
            'Y-m-d H:i:s', // Date format for %datetime%
            false, // Allow inline line breaks in %context% and %extra%
            true // Ignore empty %context% and %extra%
        );
    }

    /**
     * Get ready all data to insert into the log core database
     *
     * @param string $errorMessage
     * @return Log
     */
    private function prepareLogEntry(string $errorMessage): Log
    {
        // Getting the client IP address and user agent
        $request = Shopware()->Front()->Request();
        $ipAddress = $request ? $request->getClientIp() : '';
        $userAgent = $request ? $request->headers->get('User-Agent') : '';
        // Truncate the user agent to 255 characters to fit the database field
        $userAgentFormatted = !empty($userAgent) ? substr($userAgent, 0, 255) : '';

        // Creating the log entity so will fit the 's_core_log' table.
        // None of them can be null, so they need to be set
        $log = new Log();
        $log->setType('error');
        $log->setKey('MultiSafepay');
        $log->setText($errorMessage);
        $log->setDate(new DateTime());
        $log->setUser('LoggerService');
        $log->setIpAddress($ipAddress);
        $log->setUserAgent($userAgentFormatted);
        $log->setValue4('');

        return $log;
    }

    /**
     * Write a message to the log database (accessible from the backend)
     * as var/log folder "could not be created and/or is not writable"
     *
     * This method was created because all the log files managed by
     * Shopware/Symphony loggers are dropped to var/log directory,
     * so what would happen if that the folder is not present
     * or writable accidentally?
     *
     * @param string $errorMessage The error message to log
     * @return void
     */
    private function logToDatabase(string $errorMessage): void
    {
        // Getting the entity manager
        $em = $this->container->get('models');
        if (is_null($em)) {
            return;
        }
        // Creating the log entity with the message and all the required data
        $log = $this->prepareLogEntry($errorMessage);

        try {
            // Starting the transaction
            $em->getConnection()->beginTransaction();
            // Persisting the log entity
            $em->persist($log);
            // Committing the data
            $em->getConnection()->commit();
        } catch (Exception $exception) {
            // Log to root folder of the installation, otherwise to temp folder
            $logFile = ($this->getDocumentRoot() ?? sys_get_temp_dir()) . '/multisafepay.log';
            $logMessage = '[' . date('Y-m-d H:i:s') . '] Exception occurred: ' .
                $exception->getMessage() . ' logging: ' . $errorMessage . "\n";
            file_put_contents($logFile, $logMessage, FILE_APPEND);
        }
    }

    /**
     * Gets the document root
     *
     * @return string|null
     */
    public function getDocumentRoot(): ?string
    {
        // First, we catch the usual places where the document root is stored
        if (!empty($_SERVER['DOCUMENT_ROOT'])) {
            return rtrim($_SERVER['DOCUMENT_ROOT'], '/');
        }

        // Secondly, from the environment variable
        $envRoot = getenv('DOCUMENT_ROOT');
        if (!empty($envRoot)) {
            return rtrim($envRoot, '/');
        }

        // Otherwise, from Shopware 5 installation root
        $maxDepth = 5; // Max depth to search for shopware.php to avoid infinite loops, "if any"

        // Search for the mentioned main landing file to get the "folder root", where
        // the installation was placed, since distros sometimes use different paths
        $currentDir = realpath(__DIR__);
        while (($currentDir !== '/') && !file_exists($currentDir . '/shopware.php') && ($maxDepth > 0)) {
            $currentDir = dirname($currentDir);
            $maxDepth--;
        }
        if (!empty($currentDir) && file_exists($currentDir . '/shopware.php')) {
            // shopware.php is found, so the current directory is identified
            // as the target to add the log file
            return rtrim($currentDir, '/');
        }

        // If all the above fails, return 'null', therefore the
        // default log path will be the temp folder of the system
        return null;
    }
}
