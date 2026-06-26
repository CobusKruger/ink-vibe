---
baseline_commit: d3bc55b
---

# Story 6.4: Optional featured image

Status: review

## Story

As a skrywer,
I want to add a featured image,
So that my bydrae has a visual. (FR-20)

## Acceptance Criteria

**Given** the form
**When** I add a featured image
**Then** it is optional and saved with the bydrae.

1. The Skryf form offers an **optional** featured-image file input. Omitting it never blocks submission (the bydrae saves without an image).
2. When a valid image file is uploaded, it is attached to the new bydrae and set as its **featured image** (post thumbnail).
3. A non-image upload, an upload error, or a media-stack failure is **non-fatal**: the bydrae still saves, just without a thumbnail (graceful degradation — no silent loss of the writer's text).

## Tasks / Subtasks

- [x] Task 1: `Ink\Submission\FeaturedImage` (AC: #1, #3)
  - [x] `FIELD` constant; `isPresent($file)` (error-free, non-empty `$_FILES` entry); `isImage($file)` (client MIME starts with `image/` — a UX pre-gate; `media_handle_upload` is the real guard).
- [x] Task 2: wire into the handler (AC: #2, #3)
  - [x] `SubmissionForm::attachFeaturedImage($post_id)` runs after a successful insert: bails silently if no/invalid file; else loads the media stack + `media_handle_upload` (seams) → `set_post_thumbnail`. WP_Error from upload is swallowed (non-fatal).
  - [x] Theme: optional `<input type="file" accept="image/*">` + `enctype="multipart/form-data"` on the form.
- [x] Task 3: tests + gates
  - [x] `FeaturedImageTest` (present/absent/error/non-image); `SubmissionFormTest` extended via seam subclass: image present → upload + thumbnail set; no file / non-image / upload WP_Error → neither (and the bydrae still inserts). All gates green.

## Dev Notes

- **Featured image = post thumbnail** [Source: src/Content/PostTypes.php]: bydrae CPTs `support 'thumbnail'`. Set via `set_post_thumbnail( $post_id, $attachment_id )`.
- **Front-end upload** [Source: project-context.md#escaping]: `media_handle_upload( $field, $post_id )` does the real MIME/type validation against `get_allowed_mime_types()`; the `isImage()` client-MIME check is only a UX pre-gate (client MIME is untrusted). Requires `wp-admin/includes/{file,media,image}.php` — loaded behind a seam so unit tests don't touch the filesystem.
- **Non-fatal** [Source: epics.md#Story 6.4 "optional"]: any failure leaves the bydrae saved without a thumbnail — the writer's text is never lost to an image problem. The form needs `enctype="multipart/form-data"`.
- **Testing**: `isPresent`/`isImage` are pure (tested directly); `attachFeaturedImage` is tested via a subclass overriding the media seams (`ensureMediaStack`, `mediaHandleUpload`) + mocked `set_post_thumbnail`/`is_wp_error`, asserting thumbnail-set on success and `never()` on the bail paths. Conflation-clean.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 6.4]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 6)

### Completion Notes List

- `FeaturedImage` (pure `isPresent`/`isImage` predicates) + `SubmissionForm::attachFeaturedImage()` (media seams `ensureMediaStack`/`mediaHandleUpload`, `set_post_thumbnail`). Runs after a successful insert; bails silently with no/invalid file; swallows upload WP_Error (non-fatal). `Api::formModel()` exposes `field_image`; the theme form gains `enctype="multipart/form-data"` + an optional `<input type="file" accept="image/*">`.
- phpcs: the `$_FILES` read carries a justified `phpcs:ignore` (nonce already verified in handlePost; `media_handle_upload` is the authoritative MIME guard; metadata read through `FeaturedImage`'s `is_scalar` guards).
- Tests 378→384 (+6): present/absent/error/non-image predicates; attach sets thumbnail on success; no-file / non-image / upload-error paths set NO thumbnail (non-fatal). Gates: phpcs/phpstan clean (phpstan run single-threaded in-sandbox via `--debug`), deptrac unchanged (3 pre-existing, Allowed 152), copy:scan no new debt. Conflation-clean.

### File List

- `wp-content/plugins/ink-core/src/Submission/FeaturedImage.php` (NEW)
- `wp-content/plugins/ink-core/src/Submission/SubmissionForm.php` (MOD — attachFeaturedImage + media seams)
- `wp-content/plugins/ink-core/src/Submission/Api.php` (MOD — field_image in model)
- `wp-content/themes/ink-foundation/patterns/skryf.php` (MOD — enctype + file input)
- `tests/Unit/Submission/FeaturedImageTest.php` (NEW)
- `_bmad-output/implementation-artifacts/6-4-optional-featured-image.md` (NEW — this story)
