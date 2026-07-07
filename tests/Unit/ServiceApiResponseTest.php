<?php

namespace Tests\Unit;

use App\Services\Lock\ServiceApiResponse;
use Tests\TestCase;

class ServiceApiResponseTest extends TestCase
{
    public function test_success_wraps_data_in_envelope(): void
    {
        $res  = (new ServiceApiResponse())->success(['a' => 1], 'ok');
        $body = $res->getData(true);

        $this->assertSame(200, $res->getStatusCode());
        $this->assertTrue($body['success']);
        $this->assertSame('ok', $body['message']);
        $this->assertSame(['a' => 1], $body['data']);
    }

    public function test_error_validation_uses_error_block(): void
    {
        $res  = (new ServiceApiResponse())->errorValidation(['email' => ['required']]);
        $body = $res->getData(true);

        $this->assertSame(422, $res->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertSame('GEN-VALIDATION', $body['error']['code']);
        $this->assertSame(['email' => ['required']], $body['error']['details']);
    }
}
