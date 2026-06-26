<?php

declare(strict_types=1);

namespace Omnik\Core\Test\Unit\Helper;

use Omnik\Core\Helper\ApiResponse;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Omnik\Core\Helper\ApiResponse
 */
class ApiResponseTest extends TestCase
{
    private ApiResponse $apiResponse;

    protected function setUp(): void
    {
        $this->apiResponse = new ApiResponse();
    }

    public function testNullResponseIsTransportFailure(): void
    {
        $this->assertTrue($this->apiResponse->isTransportFailure(null));
    }

    public function testFailsTrueIsTransportFailure(): void
    {
        $this->assertTrue($this->apiResponse->isTransportFailure(['fails' => true]));
    }

    public function testValidResponseIsNotTransportFailure(): void
    {
        $response = ['productData' => ['id' => 'abc123'], 'skus' => []];
        $this->assertFalse($this->apiResponse->isTransportFailure($response));
    }

    public function testEmptyArrayIsNotTransportFailure(): void
    {
        // An empty body is not a transport failure: it is a (possibly invalid) payload
        // that downstream validation should classify as NOT_FOUND, not retry forever.
        $this->assertFalse($this->apiResponse->isTransportFailure([]));
    }

    public function testFailsFalseIsNotTransportFailure(): void
    {
        $this->assertFalse($this->apiResponse->isTransportFailure(['fails' => false]));
    }
}
