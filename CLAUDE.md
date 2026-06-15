# Project rules — Toplist Block

Operating rules for this repo. They apply in every session working here.

## Product model

- **Premium canonical source:** `toplist-block/` — edit here only.
- **Lite generated output:** `toplist-block-lite/` — produced by `scripts/build-lite.php`; never hand-edit.
- **Mutually exclusive installs:** lite (WP.org) OR premium (portal), not both.
- **Reference implementation:** `/home/user256/GitRepos/solar-form` (`scripts/build-free.php`, license class, smoke tests).
- **Portal licensing:** `/home/user256/GitRepos/portal` (`licence-manager` module).

## Workflow

1. Read `tickets/overview.md` for active sprint and next pick.
2. Implement one ticket; append decisions to the ticket file.
3. Close by marking `- [x]` in `overview.md`, then run `python process_tickets.py --apply`.
4. Commit messages reference ticket IDs: `tickets: close 101 (monorepo structure)` or `wip 102: add free-vs-premium manifest`.

## Git

- Push completed tickets to remote; do not let finished work sit only locally.
- Do not commit generated `toplist-block-lite/` or zip files.
- Do not commit secrets (`.env`, license keys).

## See also

- `tickets/overview.md` — roadmap
- `tickets/TICKET_TEMPLATE.md` — new ticket format
- `process_tickets.py` — archive completed tickets
