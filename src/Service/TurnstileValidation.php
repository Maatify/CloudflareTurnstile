<?php
/**
 * @PHP       Version >= 8.0
 * @Liberary  CloudflareTurnstile
 * @Project   CloudflareTurnstile
 * @copyright ©2024 Maatify.dev
 * @see       https://www.maatify.dev Visit Maatify.dev
 * @link      https://github.com/Maatify/CloudflareTurnstile View project on GitHub
 * @since     2023-08-05 11:53 PM
 * @author    Mohamed Abdulalim (megyptm) <mohamed@maatify.dev>
 * @Maatify   CloudflareTurnstile :: TurnstileRequestValidation
 * @note      This Project using for Call CloudflareTurnstile Validation
 *
 * This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 *
 */

namespace Maatify\CloudflareTurnstile\Service;

use Maatify\CloudflareTurnstile\Adapter\TurnstileRequestCall;
use Maatify\Json\Json;

class TurnstileValidation extends TurnstileRequestCall
{

    public ?bool $success = null;
    /** @var array<mixed> */
    public array $response = [];

    private static ?self $instance = null;

    public static function getInstance(string $secret_key = ''): self
    {
        if (null === self::$instance) {
            self::$instance = new self($secret_key);
        }

        return self::$instance;
    }

    private function curlValidation(): bool
    {
        $token = $_POST['cf-turnstile-response'] ?? '';
        $params = array(
            'secret'   => $this->secret_key,
            'response' => is_string($token) ? $token : '',
        );

        $response_data = $this->curlPost($params);
        $this->response = get_object_vars($response_data);

        $this->success = (isset($response_data->success) && $response_data->success);

        return $this->success;
    }

    private function validate(): bool
    {
        if ($this->success === null) {
            $this->success = $this->curlValidation();
        }

        return $this->success;
    }

    public function jsonErrors(): void
    {
        if (empty($_POST['cf-turnstile-response'])) {
            Json::Missing('cf-turnstile-response');
        }
        if (! $this->validate()) {
            $errorCodes = $this->response['error-codes'] ?? null;
            if (is_array($errorCodes) && isset($errorCodes[0]) && is_string($errorCodes[0]) && $errorCodes[0] !== '') {
                Json::captchaInvalid($this->response, $errorCodes[0], __LINE__);
            }
            Json::captchaInvalid($this->response, Json::JsonFormat($this->response),  __LINE__);
        }
    }

    public function isSuccess(): bool
    {
        return $this->validate();
    }

    /**
     * @return array<mixed>
     */
    public function getResponse(): array
    {
        $this->validate();

        return $this->response;
    }
}