<?php

declare(strict_types=1);

namespace Maatify\CloudflareTurnstile\Tests\Support;

use Maatify\CloudflareTurnstile\Service\TurnstileValidation;

final class TestValidation extends TurnstileValidation
{
    /**
     * @param non-empty-string $endpointUrl
     */
    public function __construct(private readonly string $endpointUrl)
    {
        parent::__construct('test-secret');
    }

    protected function endpoint(): string
    {
        return $this->endpointUrl;
    }
}
