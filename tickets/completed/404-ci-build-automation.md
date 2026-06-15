# Ticket 404: CI / Automated Build Pipeline

**Sprint:** 4 — Distribution & Compliance
**Status:** Done
**Owner:** unassigned
**Estimate:** S

---

## Context

Currently, the roadmap assumes the lite and premium zip files will be built locally via `php scripts/build-lite.php`. Relying on manual local builds introduces the risk of dirty working directories, differing PHP versions, and human error, which could accidentally leak premium code into the lite WP.org distribution.

## Goal

Establish a CI pipeline (e.g., GitHub Actions) to enforce reproducible, clean builds and run the test suite automatically.

## Acceptance criteria

- [ ] CI workflow created that runs on PRs and pushes to main.
- [ ] CI runs the PHPUnit tests (Ticket 403) and the smoke tests (Ticket 204).
- [ ] On release tags (e.g., `v1.0.0`), CI automatically executes the build script and generates both `toplist-block-lite.zip` and `toplist-block-premium.zip` as release artifacts.
- [ ] Build script fails the CI job if any premium files or patterns are detected in the lite output directory.

## Out of scope

- Automated deployment directly to SVN / WP.org repository (can be manual for the first release).

## Dependencies

- **Blocks:** 499
- **Blocked by:** 201, 403
- **External:** none

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
