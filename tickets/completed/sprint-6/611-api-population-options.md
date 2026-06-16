# Ticket 611: API Population Options

**Sprint:** 6 — Post-Launch Expansion
**Status:** Proposed
**Owner:** unassigned
**Estimate:** M

---

## Context

Many users of the Toplist block want to keep their affiliate lists dynamically synced with a central database, rather than manually updating or importing CSVs. Currently, the block relies exclusively on static stored pipes/JSON inside `post_content`. We need a mechanism to populate or sync these toplists via an external API.

## Goal

Provide a hook or integration with the WP REST API that allows remote services to automatically update a Toplist's content (via JSON or CSV payloads), or allow the block to dynamically pull its data from an external API endpoint on render/cron.

## Acceptance criteria

- [ ] Expose an authenticated WP REST API endpoint (e.g., `POST /toplist-block/v1/sync/(?P<id>\d+)`) that accepts a JSON array and completely overwrites the list's pipe data.
- [ ] Add an option for "Remote Source URL" in the Toplist editor that, if configured, pulls data via a scheduled Cron task or transient-cached fetch.
- [ ] Ensure any automated sync triggers proper caching invalidation for the frontend block.

## Dependencies

- **Blocks:** 699
- **Blocked by:** none

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
