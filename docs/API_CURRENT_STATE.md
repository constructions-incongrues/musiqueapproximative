# API Current State

> Auto-generated analysis of musiqueapproximative JSON endpoints.  
> Date: 2026-04-20

## Endpoints Overview

| Endpoint | Action | Format | Content-Type |
|----------|--------|--------|--------------|
| `GET /:slug.json` | `post/show` | json | `application/json` |
| `GET /posts.json` | `post/list` | json | `application/json` |
| `GET /posts.json?q=X` | `post/list` (search) | json | `application/json` |
| `GET /posts.json?c=X` | `post/list` (contributor) | json | `application/json` |
| `GET /md5/:md5sum` | `post/md5` | json (inline) | `application/json` |
| `GET /random` | `post/random` | json (inline) | `application/json` |
| `GET /next/:current` | `post/next` | json (inline) | `application/json` |
| `GET /prev/:current` | `post/prev` | json (inline) | `application/json` |
| `GET /oembed` | `post/oembed` | json/xml | `application/json` |

## Response Structures

### `POST /show` (showSuccess.json.php)

Wraps a single post in a `posts` array — references JSON API but does not follow 1.0 spec:

```json
{
  "posts": [
    {
      "id": "<slug>",
      "href": "https://example.com/<slug>.json",
      "body": { "html": "...", "markdown": "..." },
      "track": {
        "href": "https://example.com/tracks/file.mp3",
        "title": "Track Title",
        "author": "Artist Name",
        "md5": "abc123"
      },
      "contributor": {
        "name": "Display Name",
        "slug": "username",
        "href_website": "https://..."
      },
      "links": {
        "post_previous": "https://example.com/prev-slug.json",
        "post_next": "https://example.com/next-slug.json",
        "contributor_playlist": "https://example.com/posts.json?c=username",
        "avatar": "https://example.com/avatars/<id>.png"
      },
      "publish_on": "2024-01-15 12:00:00",
      "created_at": "...",
      "updated_at": "..."
    }
  ]
}
```

### `POST /list` (listSuccess.json.php)

Same structure as show, but with multiple posts:

```json
{
  "posts": [
    { "id": "...", "href": "...", ... },
    { "id": "...", "href": "...", ... }
  ]
}
```

### `POST /md5` (inline JSON)

Returns a single post as raw `toJson()` output (not wrapped in `posts` array):

```json
{ "id": "...", "href": "...", "body": { ... }, "track": { ... }, ... }
```

### `POST /random`, `/next`, `/prev` (inline JSON)

Simplified response with only navigation info:

```json
{
  "url": "/slug",
  "title": "Artist - Track Title"
}
```

### `POST /oembed` (inline JSON)

Standard oEmbed 1.0 format (not JSON API):

```json
{
  "version": 1,
  "type": "rich",
  "provider_name": "MusiqueApproximative",
  "provider_url": "...",
  "height": 220,
  "width": 510,
  "title": "Artist - Track Title",
  "description": "...",
  "html": "<iframe ...></iframe>"
}
```

## Non-Compliance Issues

| Issue | Details |
|-------|---------|
| No `data` key | Responses use `posts` instead of `data` |
| No `type` field | Resources don't declare their type |
| `id` uses slug | JSON API allows string IDs, but inconsistent (slug vs numeric) |
| No `attributes` wrapper | Attributes are at root level of resource |
| No `relationships` | Related resources (contributor, track) are embedded, not linked |
| No `meta` | No pagination metadata |
| No top-level `links` | No self/next/prev at document level |
| Wrong Content-Type | Uses `application/json` instead of `application/vnd.api+json` |
| Single resources in array | Show endpoint wraps single post in `posts[]` |
| Mixed response formats | `/random`, `/next`, `/prev` use different structure |
| No error format | No standardized error responses |

## Data Model (Post.toJson)

Source: `src/lib/model/doctrine/Post.class.php`

The `toJson()` method on the Post model builds the JSON representation by:
1. Converting Doctrine record to array (`parent::toArray()`)
2. Replacing numeric `id` with `slug`
3. Building `href` via Symfony routing
4. Restructuring `body` into `{html, markdown}`
5. Grouping track fields into `track` sub-object
6. Grouping contributor info into `contributor` sub-object
7. Removing internal fields (`sfGuardUser`, `svn_revision`, `is_online`)
8. Adding navigation `links` (previous, next, playlist, avatar)
