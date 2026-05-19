# Getting Started — CEFET-RJ Seminários External API

Programmatic access to seminars, workshops, users, speaker data, locations, and seminar types. JSON over HTTPS, bearer-token authenticated, versioned at `/v1`.

## Base URL

All endpoints are rooted at:

```
https://<your-deployment>/api/external/v1
```

The OpenAPI document for this UI is served at `/api/external/docs.json`.

## Authentication

Send a Sanctum personal access token in the `Authorization` header:

```
Authorization: Bearer sk-XXXXXXXXXXXXXXXX
```

Tokens are issued by an administrator from the admin panel. Each token is bound to an admin or teacher account, has a name, an optional expiry, and a set of fine-grained abilities (e.g. `seminars:read`, `seminars:write`, `workshops:read`, `users:write`, `locations:read`, `seminar-types:write`, `users.speaker-data:read`, `presence-link:read`, `presence-link:write`). A token issued without explicit abilities receives the wildcard `*` and may call every external endpoint.

Authentication outcomes:

- `401 unauthenticated` — missing, malformed, expired, or revoked token.
- `403 forbidden` — token is valid but lacks the ability required by the endpoint, or the underlying user is not allowed to act on the target resource.

## Response Envelope

Single-resource endpoints return:

```json
{ "data": { "id": 123, "name": "..." } }
```

Index endpoints wrap the page in `data` and add a `meta` block:

```json
{
  "data": [ { "id": 1, "name": "..." }, { "id": 2, "name": "..." } ],
  "meta": {
    "current_page": 2,
    "last_page": 5,
    "per_page": 15,
    "total": 67,
    "from": 16,
    "to": 30
  }
}
```

## Pagination

Index endpoints are page-based. Default page size is 15 items.

```
GET /api/external/v1/seminars?page=2
```

Use `meta.current_page` and `meta.last_page` to drive paging. `meta.from` / `meta.to` give the absolute index range of the items in the current page.

## Filtering & Sorting

`GET /api/external/v1/seminars` accepts:

| Param | Meaning |
| ----- | ------- |
| `search` | substring match on `name` |
| `active` | `true` / `false` |
| `scheduled_from` / `scheduled_to` | ISO-8601 datetime range on `scheduled_at` |
| `upcoming` | `true` → shortcut for `scheduled_from = now()` |
| `updated_since` | ISO-8601 datetime; only rows whose `updated_at` is at or after this value |
| `sort` | comma-separated; `-` prefix = descending. Allowed: `scheduled_at`, `name`, `updated_at` |

`GET /api/external/v1/workshops` accepts:

| Param | Meaning |
| ----- | ------- |
| `search` | substring match on workshop name |
| `updated_since` | ISO-8601 datetime |
| `sort` | comma-separated; `-` prefix = descending. Allowed: `name`, `updated_at` |

Other index endpoints accept their own parameter sets — see the per-endpoint pages in this UI for the canonical list.

## Sparse Fieldsets

Trim any resource payload to a specific set of top-level fields with `?fields=`:

```
GET /api/external/v1/seminars/123?fields=id,name,scheduled_at
```

Allowed field names are documented per-resource. Requesting an unknown field returns `422 validation_error`.

## Conditional Requests

Every successful `GET` response carries an `ETag` header (and a `Last-Modified` header where the underlying record exposes a meaningful modification timestamp). Replay them on the next request to skip transferring an unchanged body:

```
If-None-Match: W/"a1b2c3d4e5f6a7b8"
If-Modified-Since: Wed, 01 Apr 2026 12:00:00 GMT
```

A match returns `304 Not Modified` with no body, preserving the original `ETag` (and `Last-Modified` when present).

## Idempotency

`POST` endpoints accept an optional `Idempotency-Key` header so retries after a network failure don't create duplicate resources:

```
POST /api/external/v1/locations
Idempotency-Key: 7f3a9b1c-2e44-4d8a-9b1c-2e444d8a9b1c
Content-Type: application/json

{ "name": "Sala 101", "max_vacancies": 50 }
```

Behavior:

- The key is scoped to the **token that issued the request**, so two integrations may safely use the same key value.
- The original response body, status, and a whitelist of headers (`Content-Type`, `Content-Language`, `Cache-Control`, `Location`, `ETag`, `Last-Modified`) are cached for **24 hours** on the first 2xx response.
- A retry within that window with **the same byte-for-byte body** replays the cached response and adds `Idempotent-Replayed: true`.
- A retry with **a different body** returns `409 idempotency_key_conflict`.
- A retry that arrives while the original is still in flight returns `409 idempotency_concurrent_request` — wait and try again.
- Non-2xx responses are not cached. A request that 4xx'd with a given key may be retried with the same or different body.

Key format: up to 200 characters, charset `[A-Za-z0-9._:-]`. Anything else returns `422 validation_error`. UUIDs are recommended.

The header is ignored on `GET`/`HEAD`/`PUT`/`PATCH`/`DELETE`. `PUT` and `DELETE` are already idempotent at the protocol level.

## Errors

All error responses share the same envelope:

```json
{
  "error": "validation_error",
  "message": "Dados inválidos",
  "errors": { "name": ["O campo nome é obrigatório."] }
}
```

`error` is a stable machine-readable code; `message` is a human-readable string; `errors` is only present for `422` validation failures.

| HTTP | Common `error` codes |
| ---- | -------------------- |
| 400  | `invalid_token` |
| 401  | `unauthenticated` |
| 403  | `forbidden` |
| 404  | `not_found` |
| 409  | `conflict`, `workshop_in_use`, `subject_in_use`, `already_registered`, `not_registered`, `seminar_full`, `seminar_expired`, `idempotency_key_conflict`, `idempotency_concurrent_request` |
| 422  | `validation_error` |
| 429  | `rate_limited` |
| 500  | `server_error` |

## Presence Link

Each seminar can have one presence link — a UUID-routed URL attendees open (or scan as a QR code) to register attendance. External integrations can read it, create it, and update its active state or expiry.

### Read

```
GET /api/external/v1/seminars/{slug}/presence-link
```

Required ability: `presence-link:read`. Token holder must also be allowed to view the seminar.

Default response:

```json
{
  "data": {
    "id": 17,
    "uuid": "0a4d8b1c-...-9d8f",
    "active": true,
    "expires_at": "2026-06-15T18:00:00Z",
    "is_expired": false,
    "is_valid": true,
    "url": "https://<your-deployment>/p/0a4d8b1c-...-9d8f",
    "png_url": "https://<your-deployment>/p/0a4d8b1c-...-9d8f.png"
  }
}
```

If the seminar has no presence link configured: `{ "data": null }` with `200 OK`. A missing seminar: `404 not_found`.

For an inline QR PNG, request `?include=qr_code`. The payload gains a `qr_code` field whose value is a `data:image/png;base64,...` URI ready to drop into an `<img src>`. The QR is rendered at scale 20 (matching the admin panel). For lower-bandwidth or print-targeted use cases, fetch `png_url` directly — it serves the raw PNG.

Conditional requests apply: pass `If-Modified-Since` or `If-None-Match` from a previous response and the server replies `304 Not Modified` when neither the seminar nor the presence link has changed since.

### Create (idempotent)

```
POST /api/external/v1/seminars/{slug}/presence-link
```

Required ability: `presence-link:write`. Empty body — the UUID is server-generated, `active` defaults to `true` so the link is usable immediately, and `expires_at` is auto-set to `max(scheduled_at + 4h, now() + 1h)` (the same rule `PATCH { active: true }` applies). The `now + 1h` floor guarantees the freshly-created link is valid for at least an hour, even when the seminar's `scheduled_at` is in the past or null. Call `PATCH .../presence-link` with `{ "active": false }` to deactivate later.

- **First call:** `201 Created` with `{ "message": "Presence link created successfully.", "data": { ... } }`.
- **Subsequent calls:** `200 OK` with `{ "message": "Presence link already exists.", "data": { ... } }` — the existing record is returned unchanged. The endpoint is safe to call defensively.

The standard `Idempotency-Key` header is supported and recommended for retry safety after network failures.

### Update (activate / deactivate / re-expire)

```
PATCH /api/external/v1/seminars/{slug}/presence-link
PUT   /api/external/v1/seminars/{slug}/presence-link
```

Required ability: `presence-link:write`. Body — at least one field required:

| Field | Type | Behavior |
| ----- | ---- | -------- |
| `active` | boolean | Sets the active flag. |
| `expires_at` | ISO-8601 string or `null` | Sets the expiry explicitly. |

**Implicit expiry policy.** When `active` is sent and `expires_at` is **not** sent, the server computes the expiry for you:

- `{ "active": true }` → `expires_at = max(scheduled_at + 4h, now() + 1h)`.
- `{ "active": false }` → `expires_at = null`.

If you send `expires_at` explicitly, that value wins regardless of `active`. Sending only `expires_at` updates the expiry without touching `active`.

Returns `200 OK` with the updated record. If the seminar has no presence link to update, the response is `404 not_found` — call `POST` first to create it. Empty body or unknown-fields-only body → `422 validation_error`.
