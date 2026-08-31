# Local test environment — running the suites

Written after the Round 1 / Round 2 Phase A work, where every one of these cost real time to
rediscover. If `composer install` or `php artisan test` behaves oddly, read this first.

---

## 1. `composer install` writes nothing on Windows

`laravel/horizon` requires `ext-pcntl` and `ext-posix`. **Neither exists on Windows PHP at any
version**, so the platform check fails, and Composer exits **without creating `vendor/` at all** —
with no obvious error unless you read the whole output.

```bash
composer install --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix
```

Both extensions are used only by the Horizon **worker daemon**, never by the test suite or by
`artisan` commands the suite runs, so ignoring them locally is safe. Linux/CI is unaffected and must
not use these flags.

The alternative is to run the suite inside the `api` container (`Dockerfile.dev`, PHP 8.3 on Linux),
which has both extensions.

## 2. The backing services

```bash
docker compose up -d postgres redis
```

Host ports are deliberately offset (**Postgres 55432**, **Redis 6380**) so they do not clash with
other projects. `phpunit.xml` pins only the database NAME (`helbaron_test`); host, port, user and
password come from the ambient environment, so the same config works from the host and from inside
the container.

**Container-name collision.** A stack created before this repo's compose project name existed may
leave containers named `helbaron-postgres` / `helbaron-redis` registered under a *different* compose
project. `docker compose up` then fails with `Conflict. The container name "/helbaron-postgres" is
already in use` instead of reusing them. Either `docker start helbaron-postgres` directly, or remove
the stale containers and let compose recreate them under this project.

## 3. `--env=testing` does NOT switch the database

There is no `.env.testing`. `php artisan migrate --env=testing` therefore reads plain `.env` and runs
against the **development** database (`helbaron`), not `helbaron_test`.

The test database is selected by `phpunit.xml`'s `DB_DATABASE` override, which applies only when
PHPUnit/Pest is the entry point. So:

- `php artisan test` — correct database, always.
- `php artisan migrate` / `migrate:fresh` — **development** database. `migrate:fresh` will drop every
  table in it. Do not reach for `--env=testing` expecting protection; it gives none.

## 4. Parallel runs, and the one setting that makes them safe

```bash
php artisan test                     # always trustworthy
php artisan test --parallel          # safe ONLY with the compose lock setting below
```

**Root cause, measured.** Earlier revisions of this document blamed missing worker databases and
"long batches dropping the connection". Both were wrong — they were symptoms. The actual cause is a
PostgreSQL shared-lock-table overflow:

`RefreshDatabase` runs `migrate:fresh`, which drops all **189 tables `CASCADE` in one transaction**.
The public schema holds **~988 relations** (tables plus their indexes, sequences and constraints), so
each drop takes ~1000 locks. Postgres does not cap locks per transaction; it sizes a single shared
lock table for the whole server:

```
max_locks_per_transaction x (max_connections + max_prepared_transactions)
= 64 x 100 = 6400 slots        # stock settings
```

One serial run needs ~1000 and fits. Eight parallel workers each dropping their own schema need
~8000 and overflow it:

```
SQLSTATE[53200] Out of memory: ERROR: out of shared memory
HINT: You might need to increase max_locks_per_transaction.
```

The overflow lands in whichever tests happen to collide, so it presents as an intermittent flake with
a **different victim list every run** — which is why it was previously mistaken for several unrelated
problems. Measured on identical code: **9 failures at 8 workers, 26 at 4 workers, 0 serially.**

**The fix is committed**, in `docker-compose.yml`: the postgres service runs with
`max_locks_per_transaction=1024` (102400 slots, ~17 MB). Recreate the container to pick it up:

```bash
docker compose up -d --force-recreate postgres
docker exec helbaron-postgres psql -U helbaron -Atc 'show max_locks_per_transaction'   # expect 1024
```

If you see `out of shared memory`, that is the check that failed — it is never a code defect.

## 4b. For a full-suite number, run it in ONE invocation

```bash
php artisan test                     # the whole suite, one command, one total
```

Do **not** run the suite in chunks and add up the totals. That practice cost a real verification
failure: a Phase E gate reported "1834 passed, 0 failed" as if it were the whole suite, when the
Admin→Branding chunk — 450 tests, ~20% of the run — had simply not been executed. Nothing flagged
it, because a chunk that is never run produces no output to notice. The totals only looked wrong to
someone who added up the per-chunk numbers from two phases and compared them.

A single invocation cannot omit a chunk. If you must chunk (for a timeout), print the per-directory
counts and reconcile them against the previous run's before reporting a total.

## 5. Line endings

`.gitattributes` mandates `eol=lf`, but Git for Windows ships `core.autocrlf=true` at the **system**
level, which checks every file out as CRLF. Pint then reports ~1300 files needing `line_ending`
fixes, drowning any real finding.

```bash
git config core.autocrlf false
# then convert the working tree; this is diff-neutral because the repo already stores LF
```

Do **not** "fix" it by running Pint across the repo — that rewrites over a thousand files and buries
whatever you were actually reviewing.

Note that a handful of `apps/web` files are stored in Git with CRLF (they predate `.gitattributes`),
so converting *those* is a real change. They want a dedicated `git add --renormalize` commit of their
own, kept separate from feature work.
