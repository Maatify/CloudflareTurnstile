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

namespace Maatify\Turnstile;

use Maatify\Json\Json;

class TurnstileValidation extends TurnstileRequestCall
{

    public ?bool $success = null;
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
        $params = array(
            'secret'   => $this->secret_key,
            'response' => $_POST['cf-turnstile-response'] ?? '',
        );

        $response_data = $this->curlPost($params);
        $this->response = (array)$response_data;

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
            if (! empty($this->response['error-codes'][0])) {
                Json::Invalid('g-recaptcha-response', $this->response['error-codes'][0]);
            }
            Json::Invalid('cf-turnstile-response', Json::JsonFormat($this->response));
        }
    }

    public function isSuccess(): bool
    {
        return $this->validate();
    }

    public function getResponse(): array
    {
        $this->validate();

        return $this->response;
    }
}