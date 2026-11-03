<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Parses the TRUSTED_PROXIES env var into the value trustProxies() expects.
 *
 * The middleware closure in bootstrap/app.php runs before config is loaded,
 * so this helper reads the raw env string and returns either an array of
 * addresses or the wildcard '*'.
 */
class TrustedProxies
{
    /**
     * Citadel's front-end proxy (HA-Proxy). When TRUSTED_PROXIES is unset
     * entirely, we fall back to this so existing Citadel deploys keep
     * working without an explicit env entry.
     */
    public const CITADEL_PROXY = '172.18.0.1';

    /**
     * Turn a TRUSTED_PROXIES env value into a trustProxies() argument.
     *
     * null (unset)   -> [CITADEL_PROXY]  (Citadel default)
     * '' (set empty) -> []               (trust nothing)
     * '*'            -> '*'              (trust every proxy)
     * 'a, b'         -> ['a', 'b']       (trimmed, empties dropped)
     */
    public static function fromEnv(?string $value): array|string
    {
        if ($value === null) {
            return [self::CITADEL_PROXY];
        }

        if ($value === '') {
            return [];
        }

        if ($value === '*') {
            return '*';
        }

        $proxies = [];
        foreach (explode(',', $value) as $proxy) {
            $proxy = trim($proxy);
            if ($proxy !== '') {
                $proxies[] = $proxy;
            }
        }

        return $proxies;
    }
}
