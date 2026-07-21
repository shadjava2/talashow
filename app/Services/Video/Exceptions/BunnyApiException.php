<?php

namespace App\Services\Video\Exceptions;

use Illuminate\Http\Client\Response;

class BunnyApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?string $responseBody = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function fromResponse(Response $response): self
    {
        return new self(
            'Erreur API Bunny Stream (HTTP '.$response->status().').',
            $response->status(),
            $response->body()
        );
    }
}
