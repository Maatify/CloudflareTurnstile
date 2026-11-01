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
use Psr\Log\LogLevel;
use RuntimeException;
use stdClass;

abstract class TurnstileRequestCall
{
    protected string $secret_key;
    private const string URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    /** Response fields safe to log; everything else (e.g. cdata, metadata) is omitted. */
    private const array LOGGED_RESPONSE_FIELDS = ['success', 'error-codes', 'hostname', 'action', 'challenge_ts'];
    private CurlHandle $ch;
    /** @var array<string, scalar|null> */
    private array $params = [];

    public function __construct(string $secret_key = '')
    {
        if(!empty($secret_key)){
            $this->secret_key = $secret_key;
        }
        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException('Unable to initialize cURL.');
        }
        $this->ch = $ch;
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

    /**
     * @param array<string, scalar|null> $params
     */
    public function curlPost(array $params): stdClass
    {
        $this->params = $params;
        $body = json_encode($params);
        if ($body === false) {
            $this->logError('Unable to encode request parameters: ' . json_last_error_msg());
            return $this->failure();
        }
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));
        return $this->call();
    }

    public function curlGet(): stdClass
    {
        $this->params = [];
        curl_setopt($this->ch, CURLOPT_POST, false);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
        ));
        return $this->call();
    }

    private function call():stdClass
    {
        curl_setopt($this->ch, CURLOPT_URL, $this->endpoint());

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

        if ($curl_errno > 0) {
            $error_message = "CURL Error #:" . $curl_errno . " - " . $curl_error;
        } else {
            $resultObject = is_string($result) ? json_decode($result) : null;
            if ($resultObject instanceof stdClass) {
                $this->logSuccess($resultObject);
                return $resultObject;
            } else {
                $error_message = ($httpCode != 200) ?
                    "Error header response " . $httpCode
                    :
                    "There is no response from server (err-" . __LINE__ . ")";

            }
        }
        $this->logError($error_message);
        return $this->failure();
    }

    /**
     * Verification endpoint; overridable so tests can target a local deterministic server.
     *
     * @return non-empty-string
     */
    protected function endpoint(): string
    {
        return self::URL;
    }

    private function failure(): stdClass
    {
        $obj = new stdClass();
        $obj->success = false;
        return $obj;
    }

    private function logSuccess(stdClass $result): void
    {
        $this->log('success', LogLevel::INFO, success: $result);
    }

    private function logError(string $message): void
    {
        $this->log('failed', LogLevel::ERROR, error_details: $message);
    }

    private function log(string $file_name, string $level, string $error_details = '', ?stdClass $success = null): void
    {
        $log = [];
        if(!empty($error_details)){
            $log['error'] = $error_details;
        }
        if ($success !== null) {
            $response = array_intersect_key((array)$success, array_flip(self::LOGGED_RESPONSE_FIELDS));
            if ($response !== []) {
                $log['response'] = $response;
            }
        }
        // Parameter values (secret, token, remote IP) are never logged; names only.
        $log['param_names'] = array_keys($this->params);
        $log['url'] = $this->endpoint();
        Logger::RecordLog(
            $log,
            level: $level,
            logFile: 'CloudflareTurnstile/turnstile_' . $file_name . '_' . (intval(microtime(true) * 1000))
        );
    }
}