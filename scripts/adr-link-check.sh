#!/usr/bin/env bash
# ADR reference check (Sprint 0 / A1-S04; config-driven since Sprint 1 / A2-S01). ASCII-only.
#
# Architecture-sensitive PRs MUST reference an ADR (101_EXECUTION_RULES section 7 + 17).
# The watched paths are NOT hardcoded here: they are loaded from config/architecture/adr-watch.yaml.
# This script fails a PR that changes a watched file WITHOUT either:
#   (a) an "ADR-XX" reference in the PR description, or
#   (b) an added/updated ADR file under docs/adr/.
# Documentation-only PRs are ignored (no watched file matched).
#
# Inputs (env):
#   BASE_REF        git ref to diff against (default: origin/main)
#   PR_BODY         the pull-request description
#   CHANGED_FILES   optional newline-separated file list (overrides git; used for local testing)
#   ADR_WATCH_FILE  optional path to the watch config (default: config/architecture/adr-watch.yaml)

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BASE_REF="${BASE_REF:-origin/main}"
PR_BODY="${PR_BODY:-}"
WATCH_FILE="${ADR_WATCH_FILE:-$ROOT/config/architecture/adr-watch.yaml}"

if [ ! -f "$WATCH_FILE" ]; then
  echo "::error::ADR watch config not found: $WATCH_FILE"
  exit 2
fi

# Load watch patterns (YAML list items under 'patterns:'), stripping the leading dash and quotes.
patterns="$(grep -E "^[[:space:]]*-[[:space:]]" "$WATCH_FILE" \
  | sed -E "s/^[[:space:]]*-[[:space:]]*//; s/^'//; s/'[[:space:]]*$//; s/^\"//; s/\"[[:space:]]*$//")"

if [ -z "$patterns" ]; then
  echo "::error::No patterns found in $WATCH_FILE"
  exit 2
fi

# Determine changed files.
if [ -n "${CHANGED_FILES:-}" ]; then
  files="$CHANGED_FILES"
else
  files="$(git diff --name-only "${BASE_REF}...HEAD" 2>/dev/null \
    || git diff --name-only HEAD~1 2>/dev/null \
    || true)"
fi

# Collect changed files that match ANY watched pattern.
arch_files=""
while IFS= read -r pattern; do
  [ -z "$pattern" ] && continue
  matched="$(printf '%s\n' "$files" | grep -E "$pattern" || true)"
  if [ -n "$matched" ]; then
    arch_files="$(printf '%s\n%s' "$arch_files" "$matched")"
  fi
done <<EOF
$patterns
EOF
arch_files="$(printf '%s\n' "$arch_files" | grep -v '^$' | sort -u || true)"

if [ -z "$arch_files" ]; then
  echo "OK: no architecture-sensitive changes detected; ADR reference not required."
  exit 0
fi

# Satisfied by an explicit ADR reference in the PR body.
if printf '%s' "$PR_BODY" | grep -Eiq 'ADR-[0-9]{2,}'; then
  echo "OK: PR description references an ADR."
  exit 0
fi

# Satisfied by adding/updating an ADR file.
if printf '%s\n' "$files" | grep -Eq '^docs/adr/.*\.md$'; then
  echo "OK: PR adds or updates an ADR under docs/adr/."
  exit 0
fi

# Not satisfied -> fail with a clear, GitHub-annotated message.
echo "::error::Architecture-sensitive change detected without an ADR reference."
echo ""
echo "Architecture-sensitive files changed (matched config/architecture/adr-watch.yaml):"
printf '  - %s\n' $arch_files
echo ""
echo "Per docs/implementation/101_EXECUTION_RULES.md (section 7), a PR touching"
echo "Providers / Ports / Adapters / Contracts, Deptrac config, PHPStan architecture"
echo "rules, context boundaries, provider wiring, or tenancy foundation MUST reference an ADR."
echo ""
echo "To satisfy this check, do ONE of:"
echo "  1. Add 'ADR-XX' (e.g. ADR-07) to the PR description, referencing the decision"
echo "     recorded in docs/adr/INDEX.md; or"
echo "  2. Add/update an ADR file under docs/adr/ if this introduces a NEW decision."
exit 1
