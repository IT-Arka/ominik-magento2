<?php

declare(strict_types=1);

namespace Omnik\Core\Helper;

/**
 * Classifies responses returned by Omnik\Core\Model\Http\Client.
 *
 * The HTTP client returns ['fails' => true] for any transport-level problem
 * (HTTP status != 200, cURL/connection error) and null when the JSON body
 * cannot be decoded. Those situations are TRANSIENT and must trigger a retry,
 * not be confused with a valid response that simply lacks the expected data
 * (e.g. a product/seller that genuinely does not exist).
 */
class ApiResponse
{
    /**
     * True when the response represents a transport failure that should be retried.
     *
     * @param array|null $response
     * @return bool
     */
    public function isTransportFailure(?array $response): bool
    {
        if ($response === null) {
            return true;
        }

        return !empty($response['fails']);
    }
}
