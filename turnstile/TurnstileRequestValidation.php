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

class TurnstileRequestValidation extends TurnstileRequestCall
{
    private static self $instance;

    public bool $success = false;

    public array $response = [];
    public static function obj(string $secret_key = ''): self
    {
        if (empty(self::$instance)) {
            self::$instance = new self($secret_key);
        }

        return self::$instance;
    }

    private function validate(): bool
    {
        $params = array(
            'secret'   => $this->secret_key,
            'response' => $_POST['cf-turnstile-response'],
        );

        $response_data = $this->curlPost($params);
        $this->response = (array) $response_data;
        if(isset($response_data->success) && $response_data->success){
            $this->success = true;
            return true;
        }else{
            return false;
        }
    }

    public function validationJsonErrorOnFailed(): void
    {
        if(!$this->Validate()){
            Json::Invalid('cf-turnstile-response');
        }
    }

    public function validationBool(): bool
    {
        return $this->Validate();
    }

    public function validationArray(): array
    {
        $this->Validate();
        return $this->response;
    }
}