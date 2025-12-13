<?php

namespace App\Exception;

class BidException extends \Exception
{
    private ?array $metadata = null;

    public function __construct(
        string $message,
        string $code,
        ?array $metadata = null,
        \Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        $this->code = $code;
        $this->metadata = $metadata;
    }

    public function getErrorCode(): string
    {
        return $this->code;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * Convert exception to JSON response array
     */
    public function toArray(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'code' => $this->code,
            'metadata' => $this->metadata
        ];
    }
}
