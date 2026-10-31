<?php

declare(strict_types=1);

namespace Maatify\Turnstile\Tests\Support;

use Maatify\Turnstile\TurnstileRequestCall;

final class TestCall extends TurnstileRequestCall
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
