<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class ReverbAllowedOrigins
{
    /**
     * @return list<string>
     */
    public static function fromEnvironment(
        ?string $origins,
        string $environment,
        ?string $fallbackOrigin,
    ): array {
        if ($origins === null) {
            $environment = mb_strtolower(mb_trim($environment));

            if (! in_array($environment, ['local', 'testing'], true)) {
                return [];
            }

            $origins = self::hostFromApplicationUrl($fallbackOrigin ?? 'http://localhost');
        }

        $allowedOrigins = array_values(array_unique(array_filter(
            array_map(
                static fn (string $origin): string => mb_strtolower(mb_trim($origin)),
                explode(',', $origins),
            ),
            static fn (string $origin): bool => $origin !== '',
        )));

        if ($allowedOrigins === []) {
            throw new InvalidArgumentException(
                'REVERB_ALLOWED_ORIGINS must contain at least one exact hostname.',
            );
        }

        foreach ($allowedOrigins as $origin) {
            if (! self::isExactHost($origin)) {
                throw new InvalidArgumentException(
                    "Invalid REVERB_ALLOWED_ORIGINS entry [{$origin}]; use exact hostnames without schemes, ports, or wildcards.",
                );
            }
        }

        return $allowedOrigins;
    }

    private static function hostFromApplicationUrl(string $applicationUrl): string
    {
        $scheme = parse_url($applicationUrl, PHP_URL_SCHEME);
        $host = parse_url($applicationUrl, PHP_URL_HOST);

        if (
            ! is_string($scheme)
            || ! in_array(mb_strtolower($scheme), ['http', 'https'], true)
            || ! is_string($host)
            || $host === ''
        ) {
            throw new InvalidArgumentException(
                'APP_URL must be an absolute HTTP or HTTPS URL to derive the local Reverb origin.',
            );
        }

        return $host;
    }

    private static function isExactHost(string $origin): bool
    {
        if ($origin === 'null' || str_contains($origin, '*') || str_ends_with($origin, '.')) {
            return false;
        }

        if (filter_var($origin, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return true;
        }

        if (preg_match('/^\[(.+)]$/', $origin, $matches) === 1) {
            return filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        }

        return filter_var($origin, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }
}
