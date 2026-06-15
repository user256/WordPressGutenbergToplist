# Ticket 301: Toplist Block License Class

**Sprint:** 3 — Premium Licensing
**Status:** Not started
**Owner:** unassigned
**Estimate:** L

---

## Context

Premium is sold via portal. Solar-form ships `class-solar-lead-capture-license.php` in premium only; lite build deletes it. Toplist needs the same client: validate license key against portal, cache signed status, respect `recheck_after`.

## Goal

`includes/class-toplist-block-license.php` validates keys against portal and exposes `is_valid()` for premium feature registration.

## Acceptance criteria

- [ ] Class file lives in `toplist-block/includes/` and is listed in `PREMIUM_FILES` (deleted in lite build)
- [ ] `activate`/`validate` calls portal API (`licence-manager/validate` or `/api/v1/validate`) with `product_slug`, `license_key`, `domain`
- [ ] Valid response cached in option with HMAC integrity (pattern from solar-form license class)
- [ ] `is_valid()` returns false when no key, invalid key, or expired
- [ ] File excluded from lite zip (smoke test 204 passes)
- [ ] Invalid license does not white-screen site; local block render still works

## Out of scope

- Admin settings UI (ticket 302)
- Portal backend module (ticket 303)
- Update checker / package downloads

## Dependencies

- **Blocks:** 302, 304
- **Blocked by:** 299
- **External:** Portal API base URL, product_slug decision

## Approach (optional)

Copy structure from `/home/user256/GitRepos/solar-form/solar-lead-capture/includes/class-solar-lead-capture-license.php`; adapt namespaces, option keys, product slug.

## Notes / decisions log

- 

---

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
4. Follow-ups filed as new tickets.
