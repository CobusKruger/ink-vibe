---
baseline_commit: d3bc55b
---

# Story 6.5: Optional audio/video attachment

Status: done

## Review Findings

- [x] [Review][Patch] `MediaAttachment::register()` hooked `init` from within the running `init` dispatch (Edge Case Hunter, MED) — changed to call `registerMeta()` directly (the `Content\FieldSets` house pattern), removing the WP_Hook-iterator fragility. [src/Submission/MediaAttachment.php]

## Story

As a skrywer,
I want to attach audio/video,
So that I can share recorded work. (FR-21)

## Acceptance Criteria

**Given** the form
**When** I add an audio/video attachment
**Then** it is optional and saved with the bydrae.

1. The Skryf form offers an **optional** audio/video file input. Omitting it never blocks submission.
2. When a valid audio or video file is uploaded, it is attached to the new bydrae and its attachment id is stored on the bydrae (a registered `ink_media_attachment` post meta), so the reading layer (Epic 7) can surface it.
3. A non-audio/video upload, an upload error, or a media-stack failure is **non-fatal** (the bydrae still saves; no media meta is written).

## Tasks / Subtasks

- [x] Task 1: extract `Ink\Submission\Upload` (DRY — avoid duplicating 6.4's logic)
  - [x] `isPresent($file)` (error-free, non-empty `$_FILES` entry) + `mimeStartsWith($file, ...$prefixes)` (client-MIME UX pre-gate). Refactor `FeaturedImage` to delegate to it (behaviour unchanged).
- [x] Task 2: `Ink\Submission\MediaAttachment` (AC: #1, #2, #3)
  - [x] `FIELD` + `META_KEY = ink_media_attachment`; `isAudioVideo($file)` via `Upload::mimeStartsWith($file, 'audio/', 'video/')`; `register()` registers the meta on the bydrae CPTs (single, integer, absint, logged-in auth).
- [x] Task 3: wire into the handler + module + theme (AC: #2, #3)
  - [x] `SubmissionForm::attachMedia($post_id)` after a successful insert (mirrors attachFeaturedImage): bail / media-stack / `media_handle_upload` seam → `update_post_meta(META_KEY)`. `Module::register()` registers `MediaAttachment`. Theme: optional `<input type="file" accept="audio/*,video/*">`.
- [x] Task 4: tests + gates
  - [x] `UploadTest` (present/absent/error; mimeStartsWith single + multi prefix); `MediaAttachmentTest` (isAudioVideo; attachMedia sets meta on success, none on no-file/wrong-type/error); `FeaturedImageTest` still green post-refactor. All gates green.

## Dev Notes

- **DRY (retro Action — single source over duplication)**: 6.4's `FeaturedImage::isPresent`/MIME logic is needed verbatim for audio/video. Extract `Upload` rather than copy it (the same lesson as the `is_scalar`/`Scalar` paydown) — `FeaturedImage` delegates, `MediaAttachment` reuses.
- **Storage** [Source: epics.md#Story 6.5 "saved with the bydrae"]: an attachment, not a thumbnail — store the attachment id in `ink_media_attachment` post meta (registered on the bydrae CPTs, `show_in_rest` for the Epic-7 reading layer). `media_handle_upload` is the authoritative MIME guard; `isAudioVideo` is a UX pre-gate.
- **Non-fatal** [Source: epics.md#Story 6.5 "optional"]: any failure leaves the bydrae saved without media meta. Conflation-clean.
- **Testing**: `Upload`/`MediaAttachment` predicates pure; `attachMedia` via the same media-seam subclass pattern as 6.4, mocking `update_post_meta`/`is_wp_error`.

### References

- [Source: _bmad-output/planning-artifacts/epics.md#Story 6.5]

## Dev Agent Record

### Agent Model Used

claude-opus-4-8 (BMAD dev-story loop, Epic 6)

### Completion Notes List

- Extracted `Ink\Submission\Upload` (`isPresent` + `mimeStartsWith(...$prefixes)`) as the single source; refactored `FeaturedImage` to delegate (behaviour unchanged — FeaturedImageTest still green) rather than duplicate the logic (the retro's single-source lesson applied proactively).
- `MediaAttachment` (audio/video predicate + `ink_media_attachment` meta registered on the bydrae CPTs) + `SubmissionForm::attachMedia()` (reuses the media seams; stores the attachment id via `update_post_meta`; non-fatal). `Module::register()` registers it; `Api::formModel()` exposes `field_media`; theme adds an optional `accept="audio/*,video/*"` input.
- Tests 384→391 (+7): Upload present/absent/error + single/multi-prefix MIME; isAudioVideo; attachMedia writes meta on success, none on no-file/wrong-type/error. phpcs/phpstan clean; deptrac unchanged (3 pre-existing, Allowed 152); copy:scan no new debt. Conflation-clean.

### File List

- `wp-content/plugins/ink-core/src/Submission/Upload.php` (NEW)
- `wp-content/plugins/ink-core/src/Submission/MediaAttachment.php` (NEW)
- `wp-content/plugins/ink-core/src/Submission/FeaturedImage.php` (MOD — delegate to Upload)
- `wp-content/plugins/ink-core/src/Submission/SubmissionForm.php` (MOD — attachMedia)
- `wp-content/plugins/ink-core/src/Submission/Module.php` (MOD — register MediaAttachment)
- `wp-content/plugins/ink-core/src/Submission/Api.php` (MOD — field_media)
- `wp-content/themes/ink-foundation/patterns/skryf.php` (MOD — audio/video input)
- `tests/Unit/Submission/UploadTest.php` (NEW)
- `tests/Unit/Submission/MediaAttachmentTest.php` (NEW)
- `_bmad-output/implementation-artifacts/6-5-optional-audio-video-attachment.md` (NEW — this story)
