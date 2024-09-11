<?php
// Debug
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Session
session_start();

// Vendor
$vendorFilePath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorFilePath)) {
    die("Composer install is required");
}
require $vendorFilePath;

// Execution timestamp
$execAt = microtime(True);

// Config
class Config
{
    static $indexUrl = './';
    
    static $successUrl = './?route=order';

    static $getActionsUrl = './get-payment-actions.php';

    public const SESSION_PREFIX = 'cko-sdk-tool';

    static $session;
}

Config::$session = & $_SESSION[Config::SESSION_PREFIX];


/**
 * Save log into logs in session
 *
 * @param string $name
 * @param array $responseBody
 * @param boolean $reset
 * @return void
 */
function saveLog($name, Array $responseBody, $reset=false, $endedAt=null)
{
    $stats = $response->getStats();
    $request = $stats->getRequest();
    // Rewind the stream
    $request->getBody()->rewind();

    // Content
    $requestContentArray = json_decode($request->getBody()->getContents());
    $responseContentArray = $response->toArray();

    // Stats
    $endedAt = ($endedAt) ? $endedAt : microtime(true);
    $startedAt = $endedAt - $stats->getTransferTime();
    
    // Log
    $logs = ($reset) ? [] : Config::$session['logs'];
    $logs[] = [
        'fromSDK' => false,
        'name' => $name, 
        'datetime' => date("c"), 
        'uri' => urldecode($stats->getEffectiveUri()->__toString()),
        'method' => $request->getMethod(),
        'transferTime' => $stats->getTransferTime(),
        'request' => [
            'content' => ($requestContentArray) ? json_encode($requestContentArray, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : '',
            'datetime' => printDateTime($startedAt),
        ],
        'response' => [
            'content' => ($responseContentArray) ? json_encode($responseContentArray, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : '',
            'datetime' => printDateTime($endedAt),
        ],
    ];
    Config::$session['logs'] = $logs;
}

/**
 * Save log into logs in session
 *
 * @param string $name
 * @param \Psr\Http\Message\RequestInterface $request
 * @param boolean $reset
 * @return void
 */
function saveErrorLog($name, \Psr\Http\Message\RequestInterface $request, $reset=false, $lastStats=null)
{    
    global $execAt;

    // Content
    $requestContentArray = json_decode($request->getBody());
    // Timestamp
    $endedAt = isset($lastStats['endedAt']) ? $lastStats['endedAt'] : microtime(true);
    $startedAt = isset($lastStats['startedAt']) ? $lastStats['startedAt'] : $execAt;
    $transferTime = $endedAt - $startedAt;
    // Log
    $logs = ($reset) ? [] : Config::$session['logs'];
    $logs[] = [
        'name' => $name, 
        'datetime' => date("c"), 
        'uri' => urldecode($request->getUri()),
        'method' => $request->getMethod(),
        'transferTime' => $transferTime,
        'request' => [
            'content' => ($requestContentArray) ? json_encode($requestContentArray, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : '',
            'datetime' => printDateTime($startedAt),
        ],
        'response' => [
            'content' => '',
            'datetime' => printDateTime($endedAt) . ' *Timeout',
        ],
    ];
    Config::$session['logs'] = $logs;
}

/**
 * Merchant Helper
 */
class Merchant
{
    private static $configPath = __DIR__ . "/_merchants.php";

    /**
     * Get merchant list from config
     *
     * @return array
     */
    public static function getList()
    {
        if (file_exists(self::$configPath)) {
        
            $data = include self::$configPath;

            // Check format
            foreach ((array)$data as $key => $each) {
                if (!isset($each['apiSecretKey'])) {
                    die("<strong>ERROR:</strong> Incorrect merchant config format - Each merchant must include `apiSecretKey`.<br>\n (" . self::$configPath . ")");
                }
            }

            return $data;
        }

        return null;
    }

    /**
     * Get a merchant data by key
     *
     * @param string $key
     * @return array
     */
    public static function getMerchant($key)
    {
        $merchants = self::getList();

        return isset($merchants[$key]) ? $merchants[$key] : null;
    }
}

/**
 * Print formatted date time
 *
 * @param float $timestamp
 * @return string
 */
function printDateTime($timestamp)
{
    return DateTime::createFromFormat('U.u', $timestamp)->setTimeZone(new DateTimeZone(date_default_timezone_get()))->format("Y-m-d H:i:s.u");
}

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

class Logger implements LoggerInterface
{
    // Implement all methods from LoggerInterface
    public function emergency($message, array $context = [])
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = [])
    {
        // Validate the log level
        $levels = [
            LogLevel::EMERGENCY,
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::ERROR,
            LogLevel::WARNING,
            LogLevel::NOTICE,
            LogLevel::INFO,
            LogLevel::DEBUG
        ];

        if (!in_array($level, $levels)) {
            throw new InvalidArgumentException('Invalid log level: ' . $level);
        }

        // Write log entry to the file
        // $date = date('Y-m-d H:i:s');
        // $logEntry = "[$date] $level: $message" . PHP_EOL;

        // Log
        $logs = Config::$session['logs'];
        $logs[] = [
            'fromSDK' => true,
            'name' => "SDK log - (Level: $level)", 
            'msg' => $message,
            'datetime' => date("c"), 
        ];
        Config::$session['logs'] = $logs;
    }
}
