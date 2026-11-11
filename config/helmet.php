<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| HELMET: this app's overrides only
|--------------------------------------------------------------------------
|
| Every HELMET setting lives in the package, in
| vendor/waynestate/helmet/config/helmet.php, which HELMET's service
| provider merges under this file. Read that file for the full list and
| the HELMET_* environment variables that set each value. Keeping the
| defaults in the package means a composer update brings new settings and
| fixes in (the Playbook lookup for HELMET_PRESET, for one), instead of a
| stale copy here hiding them.
|
| Laravel merges one level deep: a key set here replaces the package's
| value for that key whole, so override a key only when the environment
| variable cannot do it, and copy the package's whole value for that key
| when you do.
|
| "policy_path" The CREED policy.json the environment ribbon reads to show a
|               version warning for the running PHP and Laravel. The package
|               default is none; this app requires waynestate/creed, so it
|               points at the copy Composer installs, unless
|               HELMET_POLICY_PATH names another file.
|
| "error_pages" A new app answers every 4xx with HELMET's fixed page, so an
|               abort() message never reaches the visitor; the package leaves
|               it off for old apps, and HELMET_ERROR_PAGES=false turns it
|               off here too.
|
| "proxies"     The older TRUSTED_PROXIES variable, kept as an alias for
|               HELMET_PROXIES for apps that set it before HELMET arrived.
|               It is applied ONLY when HELMET_PROXIES itself is unset: then
|               this file returns the parsed list (comma-separated, trimmed,
|               empties dropped; a set-but-empty or whitespace-only value
|               trusts nobody) and it replaces the package's value whole.
|               When HELMET_PROXIES is set, this file returns no proxies key
|               at all, so HELMET's own rules stay in force: HELMET_PROXIES
|               wins, and the HELMET_PRESET platform preset can still fill
|               the list. The old wildcard `*` is not supported through the
|               alias; use HELMET_PROXIES with a CIDR such as 0.0.0.0/0
|               instead. And unset no longer defaults to 172.18.0.1,
|               Citadel's proxy: the address now comes from HELMET_PROXIES
|               in .env.example, or from the preset.
|
*/

$policyPath = env('HELMET_POLICY_PATH');
if (! is_string($policyPath) || $policyPath === '') {
    $defaultPolicyPath = base_path('vendor/waynestate/creed/policy/policy.json');
    $policyPath = is_file($defaultPolicyPath) ? $defaultPolicyPath : null;
}

$config = [
    'policy_path' => $policyPath,

    // New apps answer every 4xx with HELMET's fixed page, so an abort()
    // message never reaches the visitor. HELMET_ERROR_PAGES=false turns
    // it off; JSON clients, validation, auth redirects and 5xx stay
    // with Laravel either way.
    'error_pages' => filter_var(env('HELMET_ERROR_PAGES', true), FILTER_VALIDATE_BOOL),
];

// The older TRUSTED_PROXIES variable, applied only when HELMET_PROXIES is
// unset, so the newer name always wins and the package's own rules
// (including HELMET_PRESET) stay in force when the app has moved on.
$helmetProxies = env('HELMET_PROXIES');
$trustedProxies = env('TRUSTED_PROXIES');

if ($helmetProxies === null && is_string($trustedProxies)) {
    // Parse the alias the way HELMET parses HELMET_PROXIES: split on commas,
    // trim each entry, drop the empties. A set-but-empty or whitespace-only
    // value keeps the empty list, so an explicit empty trusts nobody. The
    // old wildcard `*` is not supported here; use HELMET_PROXIES with a
    // CIDR such as 0.0.0.0/0 instead.
    $aliases = [];
    foreach (explode(',', $trustedProxies) as $entry) {
        $entry = trim($entry);
        if ($entry !== '') {
            $aliases[] = $entry;
        }
    }
    $config['proxies'] = $aliases;
}

return $config;
