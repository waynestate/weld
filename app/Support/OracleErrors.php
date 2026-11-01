<?php

namespace App\Support;

/**
 * Plain sentences for the Oracle errors that trip new developers. Each
 * sentence names the settings to look at, never a value and never the raw
 * message, so it is safe to write to the log.
 */
class OracleErrors
{
    private const DRIVER_MISSING = 'Oracle is set up for this app, but this PHP has no Oracle driver (oci8). Deploy to Citadel, which has it, or use the WSU Sail or Docker image with Oracle.';

    private const CONNECT_NAME = 'Oracle could not find the connect name in ORACLE_TNS. Use host:port/service or a full descriptor.';

    private const SERVICE_UNAVAILABLE = 'The Oracle listener answered but that service is not available. Check ORACLE_SERVICE_NAME; the database may be in maintenance.';

    private const NO_LISTENER = 'There is no Oracle listener at that address. Check the host and port; the database may be down.';

    private const REFUSED = 'Oracle refused the username or password. Check ORACLE_USERNAME and ORACLE_PASSWORD.';

    private const GRANT = 'The login worked, but this account lacks permission on that table or view (ORA-%d). Ask the data owner for the grant.';

    /**
     * The plain sentence for this exception, or null when it is not one
     * of the common Oracle cases.
     */
    public static function describe(\Throwable $e): ?string
    {
        if ($e instanceof \Error && self::isDriverMissing($e->getMessage())) {
            return self::DRIVER_MISSING;
        }

        if (preg_match('/ORA-0*(12154|12514|12541|1017|1031|942)\b/i', $e->getMessage(), $matches) === 1) {
            return match ((int) $matches[1]) {
                12154 => self::CONNECT_NAME,
                12514 => self::SERVICE_UNAVAILABLE,
                12541 => self::NO_LISTENER,
                1017 => self::REFUSED,
                default => sprintf(self::GRANT, (int) $matches[1]),
            };
        }

        return null;
    }

    /**
     * The driver is missing when PHP throws for an Oracle symbol: the
     * Yajra OCI_DEFAULT constant, or an undefined oci_connect / OCI
     * function or constant.
     */
    private static function isDriverMissing(string $message): bool
    {
        if (str_contains($message, 'OCI_DEFAULT')) {
            return true;
        }

        $undefinedSymbol = str_contains($message, 'undefined function')
            || str_contains($message, 'undefined constant')
            || str_contains($message, 'Undefined function')
            || str_contains($message, 'Undefined constant');

        return $undefinedSymbol
            && (str_contains($message, 'oci_connect') || str_contains($message, 'OCI'));
    }
}
