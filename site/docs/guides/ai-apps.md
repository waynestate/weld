---
title: "Building an AI or LLM app | WELD docs"
description: "Building an AI or LLM app on WELD: server-side model calls, streaming answers, storing embeddings on MySQL 5.6, per-user rate limits and logging."
url: https://weld.apps.wayne.edu/docs/guides/ai-apps.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Building an AI or LLM app [#](https://weld.apps.wayne.edu/docs/guides/ai-apps.html#building-an-ai-or-llm-app)

WELD is a working base for an app that talks to a language model: sign-in, sessions, security headers and the checks are already welded in. This page lists the parts that need your attention when the model does the talking. Every rule below comes from the stack's own sources; where behavior depends on your platform, it says so.

## Call the model server-side [#](https://weld.apps.wayne.edu/docs/guides/ai-apps.html#call-the-model-server-side)

The browser cannot reach a model API. HELMET's CSP allows `connect-src` only for the app's own origin and the WayneUI CDN (`vendor/waynestate/helmet/src/Headers/SecurityHeaders.php:70`), so a fetch from the page to any other host fails. Make the call from PHP instead, and keep the credential where the page never sees it:

- Put the key in an environment variable and read it in a config file, the way WELD reads every other setting. `config/worker.php:16` shows the pattern: `'mode' => env('QUEUE_WORKER_MODE', 'none')`.
- Never name it `VITE_*`. Vite copies every `VITE_` variable into the built JavaScript: `.env.example:94` feeds `VITE_APP_NAME` from `${APP_NAME}`, and the page reads it back at `resources/js/components/AppHeader.vue:23`. A model key in the bundle is a public key.
- Keep TLS verification on. CREED's handbook, the code chapter, forbids disabling it ([handbook/code.md:70-78](https://github.com/waynestate/creed/blob/main/handbook/code.md)), and Pinstripe's banned-call rules fail CI on `withoutVerifying()`, `CURLOPT_SSL_VERIFYPEER` set to false and the rest.

A minimal shape, with the key out of git and out of the bundle:

```php
// config/model.php
return [
    'endpoint' => env('MODEL_ENDPOINT'),
    'api_key' => env('MODEL_API_KEY'),
];
```

```php
$response = Http::withToken(config('model.api_key'))
    ->timeout(60)
    ->post(config('model.endpoint'), ['messages' => $messages]);
```

## Stream the answer [#](https://weld.apps.wayne.edu/docs/guides/ai-apps.html#stream-the-answer)

A long answer should not sit in memory until it is complete. Laravel ships both halves of the tool:

- `response()->stream($callback)` sends a chunk as it is produced; with a generator callback it echoes each chunk and flushes after it (`vendor/laravel/framework/src/Illuminate/Routing/ResponseFactory.php:213,222-228`).
- `response()->eventStream($callback)` does the same as Server-Sent Events, adding `Content-Type: text/event-stream`, `Cache-Control: no-cache` and `X-Accel-Buffering: no`, and flushing after every message (`:146,170-174,198-202`).

Two limits shape how far a stream can run under Citadel's Apache mod_php:

- `output_buffering = 4096` (`deploy/.squire/php.ini:6`) keeps the buffer at 4 KB, so each flush hands over a small unit instead of holding the whole answer.
- `max_execution_time = 30` (`deploy/.squire/php.ini:18`) ends a request that runs longer than 30 seconds. A generation that takes that long needs its work split, or a different limit on your platform: check on your app.

Remember the auth side too: an unauthenticated `text/event-stream` request gets a redirect, not a 401, and `EventSource` cannot follow it ([SESAME and fetch](https://weld.apps.wayne.edu/docs/guides/app-structure.html#sesame-and-fetch)).

## Store what you learn [#](https://weld.apps.wayne.edu/docs/guides/ai-apps.html#store-what-you-learn)

If the app keeps embeddings or model output, it lands in the same shared MySQL 5.6 everything else uses:

- There is no vector column type and no JSON column type on 5.6. CREED's handbook, the databases chapter, says store JSON as `text` or `longText` with Eloquent `array` or `json` casts and filter in PHP ([handbook/databases.md:58-60](https://github.com/waynestate/creed/blob/main/handbook/databases.md)); PROCTOR's `mysql56` check enforces it.
- WELD already keeps index keys under 5.6's 767-byte cap with `Schema::defaultStringLength(191)` (`app/Providers/AppServiceProvider.php:32`), and your new tables inherit it.
- Nearest-neighbor search then runs in PHP over the stored rows. That is the price of 5.6; if it stops being cheap, that is a design conversation, not a column type.

## Rate-limit per user [#](https://weld.apps.wayne.edu/docs/guides/ai-apps.html#rate-limit-per-user)

Model calls cost money and tokens. Laravel's `RateLimiter` facade (`vendor/laravel/framework/src/Illuminate/Support/Facades/RateLimiter.php:27`) counts attempts per key; key it on the signed-in user's username, which SESAME's `Identity` carries (`vendor/waynestate/sesame/src/Identity.php:24`):

```php
use Illuminate\Support\Facades\RateLimiter;
use Waynestate\Sesame\Sesame;

$user = app(Sesame::class)->user();

if (! RateLimiter::attempt('model:'.$user->username, 30)) {
    abort(429);
}
```

Thirty calls a minute per user is a placeholder, not a policy: pick the number your budget allows.

## Log like everything else [#](https://weld.apps.wayne.edu/docs/guides/ai-apps.html#log-like-everything-else)

CREED has no AI-specific logging rules, and the general ones apply:

- Logs and diagnostic output must not contain credentials or personally identifiable information such as Social Security numbers, including logged exceptions (CREED's handbook, the secrets chapter, [handbook/secrets.md:17](https://github.com/waynestate/creed/blob/main/handbook/secrets.md)). A prompt carrying a student record is personal data: log the size and outcome of a model call, not its full text.
- Logs are rotated or removed at a set interval; on Citadel the platform rotates container logs (CREED's handbook, the production chapter, [handbook/production.md:24](https://github.com/waynestate/creed/blob/main/handbook/production.md)). WELD sends everything to `php://stderr` (`LOG_CHANNEL=stderr`, [Configuration](https://weld.apps.wayne.edu/docs/reference/configuration.html)), which is the container log on Citadel.

## Related [#](https://weld.apps.wayne.edu/docs/guides/ai-apps.html#related)

- [Security](https://weld.apps.wayne.edu/docs/guides/security.html#the-effective-csp)
- [App structure](https://weld.apps.wayne.edu/docs/guides/app-structure.html#sesame-and-fetch)
- [Configuration](https://weld.apps.wayne.edu/docs/reference/configuration.html)
