# Ticket 106: Import File Upload Validation

**Sprint:** 1 — Stabilise & Document (Remedial)
**Status:** Done
**Owner:** unassigned
**Estimate:** S

---

## Context

During a code review of `toplist-block.php`, it was identified that the `toplist_process_bulk_import_csv_file()` and `toplist_handle_import_json()` functions process uploaded files (`$_FILES['toplist_bulk_csv_file']` and `$_FILES['toplist_json_file']`) without explicitly validating the file extension or MIME type. 

While the CSV parsing (`fgetcsv`) and JSON parsing (`json_decode`) will safely fail if fed an invalid file type (like an executable or a PHP script), relying solely on parser failure rather than input boundary validation is a bad security practice and could flag warnings during the WP.org review process.

## Goal

Add explicit file extension and MIME type validation to all file upload handlers in the plugin.

## Acceptance criteria

- [x] In `toplist_handle_import_json()`, verify the uploaded file has a `.json` extension and the correct MIME type (e.g. using `wp_check_filetype()`).
- [x] In `toplist_handle_import_all_csv()`, verify the uploaded file has a `.csv` extension and correct MIME type.
- [x] Reject any files that fail validation and return a clear error message to the user (`wp_safe_redirect` with an appropriate error code).

## Dependencies

- **Blocks:** 199
- **Blocked by:** none

## Definition of done

1. All acceptance criteria checked.
2. Merged to main.
3. Bullet in `tickets/overview.md` marked `[x]`.
