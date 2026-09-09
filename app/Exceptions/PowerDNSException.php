<?php

namespace App\Exceptions;

use Exception;

class PowerDNSException extends Exception
{
    public static function zoneNotFound(string|int $zone): self
    {
        return new self("DNS Zone [{$zone}] not found.");
    }

    public static function recordNotFound(int $recordId): self
    {
        return new self("DNS Record [ID: {$recordId}] not found.");
    }

    public static function invalidRecordType(string $type): self
    {
        return new self("Unsupported DNS Record type [{$type}].");
    }

    public static function apiError(string $message, int $code = 0, ?\Throwable $previous = null): self
    {
        return new self("PowerDNS API Error: {$message}", $code, $previous);
    }

    public static function databaseError(string $message, ?\Throwable $previous = null): self
    {
        return new self("PowerDNS Database Error: {$message}", 0, $previous);
    }

    public static function syncFailed(string $zone, string $reason): self
    {
        return new self("Failed to sync zone [{$zone}]: {$reason}");
    }
}
