<?php

use App\Platform\Shared\Support\Env;

/*
|--------------------------------------------------------------------------
| Application identity — build provenance
|--------------------------------------------------------------------------
|
| Laravel 12's skeleton ships NO config/app.php: name, env, debug, url, key and the rest come from
| the framework's own defaults (vendor/laravel/framework/config/app.php). Illuminate's
| LoadConfiguration merges a partial file like this one OVER that base, so declaring three keys here
| adds them without displacing anything the framework provides.
|
| Only the build-provenance keys belong here. Anything the framework already defines is deliberately
| absent — re-declaring it would mean two places to change and one of them would eventually be wrong.
|
| WHY THESE KEYS EXIST
|
| `config('app.version')` was read in two places (the /api/v1/health payload and the Filament panel)
| and resolved to NULL in all of them, so both fell back to a hardcoded '1.0.0-rc.1' string that had
| no relationship to what was actually deployed. With ten to thirty customer instances in the field,
| "which build is this academy running?" is a question that has to be answerable from outside the box.
| GET /api/v1/version answers it from these values.
|
| They are populated at image build time (apps/api/Dockerfile ARG -> ENV, supplied by CI), which is
| the only moment the commit is known. Reading them through config — never env() at runtime — is what
| keeps them correct once `config:cache` has run.
|
*/

return [

    // Human-facing release identifier.
    //
    // ONE chain, in precedence order, so there is never a second place to look:
    //   APP_VERSION   — an explicit operator override in .env (the key `.env.production.example` has
    //                   always shipped; before this file existed nothing read it at all)
    //   APP_RELEASE   — the tag or sha- ref baked in at image build time by CI
    //   the literal    — an un-tagged local build, reported truthfully rather than as empty
    'version' => Env::string('APP_VERSION', fn (): string => Env::string('APP_RELEASE', '1.0.0-rc.1')),

    // The exact commit the image was built from. 'unknown' is an honest answer for a local build;
    // a wrong SHA would be worse than no SHA.
    'build_sha' => Env::string('APP_BUILD_SHA', 'unknown'),

    // When the image was built (ISO-8601). Optional; empty when CI did not supply it.
    'built_at' => Env::string('APP_BUILT_AT', ''),

];
