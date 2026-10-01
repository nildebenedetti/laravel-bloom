# 0015. Local disk for image uploads

- **Status:** Accepted
- **Date:** 2026-09-22
- **Area:** Infrastructure
- **Affects:** `config/filesystems.php`, `app/Http/Controllers/Api/RecordController.php`, `app/Http/Controllers/Admin/RecordController.php`, `.env`

## Context

A record can have one illustrative image, with alt text for accessibility
(`records.image_path` and `records.image_alt`, added by
`2026_09_16_083953_add_alt_text_column_to_records_table.php`). Records appear in three
places — the public Meadow, the owner's Prism, and the admin backoffice — so the image
has to be servable to anonymous visitors, which rules out a private disk.

The alternatives were S3 (with the `public` disk pointing at it), a dedicated image CDN,
or the local filesystem. Bloom is a single-server application with no object storage
account, and `FILESYSTEM_DISK=public` is already the default.

## Decision

We store images on the **`public` disk** — the default from `FILESYSTEM_DISK=public` in
`.env` — under a single `records/` directory, and store only the **relative path** in
`records.image_path`.

```php
// Api\RecordController::store()
$validated['image_path'] = $request->file('image')->store('records');
```

```php
// Admin\RecordController::store()
$image_path = Storage::putFile('records', $data['image_path']);
$newRecord->image_path = $image_path;
```

Laravel's filesystem serves `storage/app/public` at `/storage/...` through the
`public/storage` symlink (`php artisan storage:link`, already created in
`public/`).

**Upload constraints are declared in one place only** —
`Api\StoreRecordRequest` ([ADR-0013](0013-form-requests-as-the-validation-boundary.md)):

```php
'image'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],  // 2 MB
'image_alt' => ['nullable', 'string', 'max:255'],
```

`image_path` is `text`, not `varchar(255)`, so the column will not truncate a long
generated path. `records.title` is the only `string(200)`.

Note that the two surfaces use **different field names for the same file** — the API
takes `image`, the backoffice takes `image_path`. This is not stored in a config file
anywhere; it is a convention held only by the two controllers and their forms.

## Consequences

### Positive

- No infrastructure to provision. `storage:link` is the whole setup.
- `Storage::putFile()` generates a collision-free hashed filename, so user-supplied
  filenames never reach the filesystem — no path traversal, no overwrites.
- `image_alt` is nullable and validated to 255, matching the column, so the
  accessibility affordance was designed in from the start rather than bolted on.
- Deleting the file is one `Storage::delete($record->image_path)`.

### Negative

- **Images are unscaled and unprocessed.** No thumbnail generation, no resizing, no
  EXIF stripping, no format conversion. A user uploading a 2 MB 6000×4000 JPEG gets
  exactly that, served full-size to every Meadow visitor. There is no `<picture>` or
  `srcset` strategy because there is only one rendition.
- **The two write paths disagree on the disk.** The admin controller uses the default
  disk implicitly (`Storage::putFile`, `Storage::delete`); the API controller
  references `Storage::disk('records')`, and **no disk named `records` is configured**.
  See *Drift*.
- **Orphaned files accumulate.** Deleting a record does not reliably delete its image,
  and replacing an image may or may not delete the old one. Nothing sweeps the disk.
- **Only `image_alt` is validated on the API; the backoffice accepts any file** with no
  mime-type or size check at all, because that path has no Form Request.
- The 2 MB ceiling is a request-level validation, not a server-level one. Behind a
  misconfigured `post_max_size` or a proxy, a larger upload fails with a PHP error
  rather than a validation message.

### Neutral / follow-on

- [ADR-0009](0009-json-api-shaped-api-resources.md) — `image_path` and `image_alt` are
  emitted as attributes.
- [ADR-0013](0013-form-requests-as-the-validation-boundary.md) — where the mime and
  size rules live.

## Drift

- **`Storage::disk('records')` refers to a disk that does not exist.** It is used four
  times in `app/Http/Controllers/Api/RecordController.php` (lines 106, 107, 137, 138).
  `config/filesystems.php` defines only the framework defaults — there is no `records`
  key in `disks`. Every one of those calls throws
  `InvalidArgumentException: Disk [records] does not have a configured driver.`
  Because the enclosing conditionals guard on `$record->image_path` being non-empty, the
  bug only fires for records that actually have an image — so it is invisible in a
  seed-data walkthrough where no record has one.
- **Image replacement is dead code on the API path.**
  `Api\RecordController::update()` checks `$request->hasFile('iamge')` — note the typo,
  `'iamge'` — so the condition is never true and the new image is never stored, the old
  one is never deleted, and `$validated['image_path']` is never set.
- **`Admin\RecordController::destroy()` never deletes the file.** It tests
  `if ($record->image)`, but there is no `image` attribute on the model — the column is
  `image_path`. The attribute resolves to `null`, the condition is always false, and
  every deleted record leaves its image on disk.
- **Replacing an image on the backoffice path deletes before it stores.** `update()`
  calls `Storage::delete($record->image_path)` and then `Storage::putFile()`. If the
  upload fails validation at the PHP level, the old image is already gone.
- **`image_alt` is stored but never rendered as an `alt` attribute by the API.** It is
  emitted as an attribute of `RecordResource`, but `image_path` is also emitted as a
  bare relative path with no corresponding absolute URL, so a client must construct the
  public URL itself.
