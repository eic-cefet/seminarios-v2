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

Tokens are issued by an administrator from the admin panel. Each token is bound to an admin or teacher account, has a name, an optional expiry, and a set of fine-grained abilities (e.g. `seminars:read`, `seminars:write`, `workshops:read`, `users:write`, `locations:read`, `seminar-types:write`, `users.speaker-data:read`). A token issued without explicit abilities receives the wildcard `*` and may call every external endpoint.

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
| 409  | `conflict`, `workshop_in_use`, `subject_in_use`, `already_registered`, `not_registered`, `seminar_full`, `seminar_expired` |
| 422  | `validation_error` |
| 429  | `rate_limited` |
| 500  | `server_error` |
