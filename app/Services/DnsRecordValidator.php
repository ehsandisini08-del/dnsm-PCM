<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class DnsRecordValidator
{
    /**
     * Validate DNS record data and throw ValidationException if invalid.
     *
     * @param  array{type: string, name: string, content: string, ttl?: int|null, prio?: int|null, flags?: int|null, tag?: string|null, value?: string|null, weight?: int|null, port?: int|null, target?: string|null}  $data
     *
     * @throws ValidationException
     */
    public static function validate(array $data): void
    {
        $type = strtoupper($data['type'] ?? '');

        if (! in_array($type, PowerDNSService::SUPPORTED_TYPES, true)) {
            throw ValidationException::withMessages([
                'type' => "Unsupported record type [{$type}].",
            ]);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'ttl' => ['nullable', 'integer', 'min:1', 'max:2147483647'],
        ];

        switch ($type) {
            case 'A':
                $rules['content'] = [
                    'required',
                    'ip',
                    function ($attribute, $value, $fail) {
                        if (! filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            $fail('The content must be a valid IPv4 address for type A.');
                        }
                    },
                ];
                break;

            case 'AAAA':
                $rules['content'] = [
                    'required',
                    'ip',
                    function ($attribute, $value, $fail) {
                        if (! filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                            $fail('The content must be a valid IPv6 address for type AAAA.');
                        }
                    },
                ];
                break;

            case 'CNAME':
            case 'NS':
            case 'PTR':
                $rules['content'] = [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) use ($type) {
                        $target = rtrim(trim($value), '.');
                        if (filter_var($target, FILTER_VALIDATE_IP)) {
                            $fail("The content for type {$type} must be a hostname, not an IP address.");
                        } elseif (! self::isValidHostname($target)) {
                            $fail("The content for type {$type} must be a valid hostname.");
                        }
                    },
                ];
                break;

            case 'MX':
                $rules['prio'] = ['required', 'integer', 'min:0', 'max:65535'];
                $rules['content'] = [
                    'required',
                    'string',
                    'max:255',
                    function ($attribute, $value, $fail) {
                        $target = rtrim(trim($value), '.');
                        if (filter_var($target, FILTER_VALIDATE_IP)) {
                            $fail('The MX target must be a hostname (e.g. mail.domain.com), not an IP address.');
                        } elseif (! self::isValidHostname($target)) {
                            $fail('The MX target must be a valid hostname.');
                        }
                    },
                ];
                break;

            case 'TXT':
                $rules['content'] = ['required', 'string', 'max:65535'];
                break;

            case 'SRV':
                $rules['prio'] = ['required', 'integer', 'min:0', 'max:65535'];
                $rules['content'] = [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        // Format: "weight port target"
                        $parts = preg_split('/\s+/', trim($value));
                        if (count($parts) !== 3) {
                            $fail('SRV content must follow the format: "<weight> <port> <target>" (e.g. "10 5060 sip.example.com.")');

                            return;
                        }
                        [$weight, $port, $target] = $parts;
                        if (! is_numeric($weight) || (int) $weight < 0 || (int) $weight > 65535) {
                            $fail('SRV weight must be an integer between 0 and 65535.');
                        }
                        if (! is_numeric($port) || (int) $port < 1 || (int) $port > 65535) {
                            $fail('SRV port must be an integer between 1 and 65535.');
                        }
                        $cleanTarget = rtrim(trim($target), '.');
                        if (! self::isValidHostname($cleanTarget)) {
                            $fail('SRV target must be a valid hostname.');
                        }
                    },
                ];
                break;

            case 'CAA':
                $rules['content'] = [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        // Format: "<flags> <tag> "<value>""
                        if (! preg_match('/^(\d+)\s+([a-zA-Z0-9]+)\s+"?(.*?)"?$/', trim($value), $matches)) {
                            $fail('CAA content must follow the format: <flags> <tag> "<value>" (e.g. 0 issue "letsencrypt.org")');

                            return;
                        }
                        $flags = (int) $matches[1];
                        $tag = strtolower($matches[2]);
                        if ($flags < 0 || $flags > 255) {
                            $fail('CAA flags must be between 0 and 255.');
                        }
                        if (! in_array($tag, ['issue', 'issuewild', 'iodef', 'contactemail', 'contactphone'], true)) {
                            $fail("CAA tag must be one of: issue, issuewild, iodef, contactemail, contactphone. Got [{$tag}].");
                        }
                    },
                ];
                break;

            case 'SOA':
                $rules['content'] = [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        $parts = preg_split('/\s+/', trim($value));
                        if (count($parts) !== 7) {
                            $fail('SOA record content must have 7 components: <primary_ns> <hostmaster> <serial> <refresh> <retry> <expire> <minimum>');

                            return;
                        }
                        for ($i = 2; $i <= 6; $i++) {
                            if (! is_numeric($parts[$i]) || (int) $parts[$i] < 0) {
                                $fail('SOA serial and timer values must be positive integers.');
                            }
                        }
                    },
                ];
                break;
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Check if a string is a valid hostname/domain.
     */
    public static function isValidHostname(string $hostname): bool
    {
        $hostname = rtrim(trim($hostname), '.');

        if (empty($hostname) || strlen($hostname) > 253) {
            return false;
        }

        // Must match standard DNS hostname regex
        return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]$/i', $hostname);
    }

    /**
     * Format and normalize record content according to PowerDNS conventions.
     */
    public static function normalizeContent(string $type, string $content): string
    {
        $type = strtoupper($type);
        $content = trim($content);

        switch ($type) {
            case 'CNAME':
            case 'NS':
            case 'PTR':
            case 'MX':
                return str_ends_with($content, '.') ? $content : $content.'.';

            case 'TXT':
                // Wrap in double quotes if not already wrapped
                if (! str_starts_with($content, '"') && ! str_ends_with($content, '"')) {
                    return '"'.$content.'"';
                }

                return $content;

            case 'CAA':
                if (preg_match('/^(\d+)\s+([a-zA-Z0-9]+)\s+"?(.*?)"?$/', $content, $matches)) {
                    $flags = (int) $matches[1];
                    $tag = strtolower($matches[2]);
                    $val = trim($matches[3], '"');

                    return "{$flags} {$tag} \"{$val}\"";
                }

                return $content;

            case 'SRV':
                $parts = preg_split('/\s+/', $content);
                if (count($parts) === 3) {
                    $target = str_ends_with($parts[2], '.') ? $parts[2] : $parts[2].'.';

                    return "{$parts[0]} {$parts[1]} {$target}";
                }

                return $content;

            default:
                return $content;
        }
    }
}
