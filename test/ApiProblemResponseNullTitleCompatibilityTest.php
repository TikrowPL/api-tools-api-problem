<?php

namespace LaminasTest\ApiTools\ApiProblem;

use ErrorException;
use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\ApiProblem\ApiProblemResponse;
use PHPUnit\Framework\TestCase;

class ApiProblemResponseNullTitleCompatibilityTest extends TestCase
{
    /** @dataProvider titleProvider */
    public function testOptionalTitleRetainsResponseContractWithoutNativeDiagnostics(
        int $status,
        ?string $title,
        int $expectedStatus,
        string $expectedReason,
        string $expectedJsonTitle
    ): void {
        set_error_handler(static function ($severity, $message, $file, $line): void {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $problem = new ApiProblem($status, 'Unauthenticated fixture', null, $title);
            $response = new ApiProblemResponse($problem);
            self::assertSame($problem, $response->getApiProblem());
            self::assertSame($expectedStatus, $response->getStatusCode());
            self::assertSame($expectedReason, $response->getReasonPhrase());
            self::assertSame('application/problem+json', $response->getHeaders()->get('content-type')->getFieldValue());
            self::assertSame([
                'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html',
                'title' => $expectedJsonTitle,
                'status' => $expectedStatus,
                'detail' => 'Unauthenticated fixture',
            ], json_decode($response->getContent(), true));
        } finally {
            restore_error_handler();
        }
    }

    public function titleProvider(): array
    {
        return [
            'omitted forbidden title' => [403, null, 403, 'Forbidden', 'Forbidden'],
            'omitted unauthorized title' => [401, null, 401, 'Unauthorized', 'Unauthorized'],
            'empty title' => [403, '', 403, 'Forbidden', ''],
            'whitespace title' => [403, '  ', 403, 'Forbidden', '  '],
            'zero title' => [403, '0', 403, 'Forbidden', '0'],
            'custom title' => [403, '  Custom reason  ', 403, 'Custom reason', '  Custom reason  '],
            'known extended status' => [599, null, 599, 'Network Connect Timeout Error', 'Unknown'],
            'invalid status normalized' => [700, null, 500, 'Internal Server Error', 'Internal Server Error'],
        ];
    }
}
