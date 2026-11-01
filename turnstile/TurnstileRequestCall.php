<?php
/**
 * @PHP       Version >= 8.0
 * @Liberary  CloudflareTurnstile
 * @Project   CloudflareTurnstile
 * @copyright ©2024 Maatify.dev
 * @see       https://www.maatify.dev Visit Maatify.dev
 * @link      https://github.com/Maatify/CloudflareTurnstile View project on GitHub
 * @since     2023-08-05 09:15 PM
 * @author    Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @Maatify   CloudflareTurnstile :: TurnstileRequestCall
 * @note      This Project using for Call CloudflareTurnstile Validation
 *
 * This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 *
 */

namespace Maatify\Turnstile;

use CurlHandle;
use Maatify\Functions\GeneralFunctions;
use Maatify\Logger\Logger;
use stdClass;

abstract class TurnstileRequestCall
{
    protected string $secret_key;
    private string $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    private false|CurlHandle $ch;
    private array $params;

    public function __construct(string $secret_key = '')
    {
        if(!empty($secret_key)){
            $this->secret_key = $secret_key;
        }
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        // Required for HTTP error codes to be reported via our call to curl_error($ch)
//        curl_setopt($this->ch, CURLOPT_FAILONERROR, false);

    }

    public function curlPost(array $params): stdClass
    {
        $this->params = $params;
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));
        return $this->call();
    }

    public function curlGet(): stdClass
    {
        $this->params = [];
        curl_setopt($this->ch, CURLOPT_POST, 0);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));
        return $this->call();
    }

    private function call():stdClass
    {
        curl_setopt($this->ch, CURLOPT_URL, $this->url);

//        curl_setopt($this->ch, CURLOPT_HEADER, true);
        $result = curl_exec($this->ch);
        $httpCode = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        $curl_errno = curl_errno($this->ch);
        $curl_error = curl_error($this->ch);

/*
        $headerSize = curl_getinfo($this->ch, CURLINFO_HEADER_SIZE);
        $rawHeaders = substr($result, 0, $headerSize);
        $body = substr($result, $headerSize);


        // Parse headers
        $headers = [];
        $headerLines = explode("\r\n", $rawHeaders);
        foreach ($headerLines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(': ', $line, 2);
                $headers[$key] = $value;
            }
        }


        Logger::RecordLog(['header' => $headers, 'body' => $body], 'debug');
*/

        curl_close($this->ch);
        if ($curl_errno > 0) {
            $error_message = "CURL Error #:" . $curl_errno . " - " . $curl_error;
        } else {
            if ($resultArray = json_decode($result)) {
                $this->logSuccess($resultArray);
                return $resultArray;
            } else {
                $error_message = ($httpCode != 200) ?
                    "Error header response " . $httpCode
                    :
                    "There is no response from server (err-" . __LINE__ . ")";

            }
        }
        $this->logError($error_message);
        $obj = new stdClass();
        $obj->success = false;
        return $obj;
    }

    private function logSuccess(array|stdClass $result): void
    {
        $this->log('success', 'info', success: $result);
    }

    private function logError(string $message): void
    {
        $this->log('failed', 'error', error_details: $message);
    }

    private function log(string $file_name, string $level, string $error_details = '', array|stdClass $success = []): void
    {
        if(!empty($error_details)){
            $log['error'] = $error_details;
        }
        if(!empty($success)){
            $log['response'] = array_intersect_key((array)$success, array_flip(['success', 'error-codes', 'hostname', 'action', 'challenge_ts']));
        }
        // Parameter values (secret, token, remote IP) are never logged; names only.
        $log['param_names'] = array_keys($this->params);
        $log['url'] = $this->url;
        $arguments = [
            'logFile' => 'CloudflareTurnstile/turnstile_' . $file_name . '_' . (intval(microtime(true) * 1000)),
        ];
        if ($this->loggerAcceptsLevel()) {
            $arguments['level'] = $level;
        }
        // Named arguments keep this call valid for every maatify/logger 5.x signature.
        call_user_func_array([Logger::class, 'RecordLog'], ['message' => $log] + $arguments);
    }

    /**
     * maatify/logger 5.0.x has RecordLog($message, $logFile, $extension); 5.1+ has
     * RecordLog($message, $level, $logFile). Pass `level` only where it exists.
     */
    private function loggerAcceptsLevel(): bool
    {
        foreach ((new \ReflectionMethod(Logger::class, 'RecordLog'))->getParameters() as $parameter) {
            if ($parameter->getName() === 'level') {
                return true;
            }
        }
        return false;
    }
}