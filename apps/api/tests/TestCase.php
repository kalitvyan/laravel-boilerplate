<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LogicException;
use Tests\Support\Contract\OpenApiContract;

abstract class TestCase extends BaseTestCase
{
    private bool $validatesContract = true;

    /**
     * Только для тестов инфраструктуры на временных роутах, которых нет в спеке.
     */
    protected function withoutContractValidation(): static
    {
        $this->validatesContract = false;

        return $this;
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $response = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);

        if ($this->validatesContract) {
            if ($response->baseRequest === null) {
                throw new LogicException('Test response has no base request to validate against the contract');
            }

            OpenApiContract::assertMatches($response->baseRequest, $response->baseResponse);
        }

        return $response;
    }
}
