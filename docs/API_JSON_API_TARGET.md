# JSON API 1.0 Target Structure

> Target structure for musiqueapproximative API compliance with [JSON API 1.0](https://jsonapi.org/format/).  
> Date: 2026-04-20

## Content-Type

All JSON responses MUST use:

```
Content-Type: application/vnd.api+json; charset=utf-8
```

Implemented via `JsonApiFilter` registered in `filters.yml`.

## Single Resource: `GET /:slug.json`

```json
{
  "data": {
    "type": "posts",
    "id": "my-post-slug",
    "attributes": {
      "body": {
        "html": "<p>Post content</p>",
        "markdown": "Post content"
      },
      "publish_on": "2024-01-15 12:00:00",
      "created_at": "2024-01-15 12:00:00",
      "updated_at": "2024-01-15 12:00:00"
    },
    "relationships": {
      "track": {
        "data": { "type": "tracks", "id": "md5-hash" }
      },
      "contributor": {
        "data": { "type": "users", "id": "username" }
      }
    },
    "links": {
      "self": "https://example.com/my-post-slug.json"
    }
  },
  "included": [
    {
      "type": "tracks",
      "id": "md5-hash",
      "attributes": {
        "title": "Track Title",
        "author": "Artist Name",
        "href": "https://example.com/tracks/file.mp3"
      }
    },
    {
      "type": "users",
      "id": "username",
      "attributes": {
        "name": "Display Name",
        "href_website": "https://user-website.com"
      },
      "links": {
        "playlist": "https://example.com/posts.json?c=username",
        "avatar": "https://example.com/avatars/42.png"
      }
    }
  ],
  "links": {
    "self": "https://example.com/my-post-slug.json",
    "prev": "https://example.com/previous-slug.json",
    "next": "https://example.com/next-slug.json"
  }
}
```

## Collection: `GET /posts.json`

```json
{
  "data": [
    {
      "type": "posts",
      "id": "post-slug-1",
      "attributes": { ... },
      "relationships": { ... },
      "links": {
        "self": "https://example.com/post-slug-1.json"
      }
    },
    {
      "type": "posts",
      "id": "post-slug-2",
      "attributes": { ... },
      "relationships": { ... },
      "links": {
        "self": "https://example.com/post-slug-2.json"
      }
    }
  ],
  "meta": {
    "total": 1234
  },
  "links": {
    "self": "https://example.com/posts.json"
  }
}
```

### With search: `GET /posts.json?q=query`

Same structure, plus search meta:

```json
{
  "data": [ ... ],
  "meta": {
    "total": 5,
    "query": "search term"
  },
  "links": {
    "self": "https://example.com/posts.json?q=search+term"
  }
}
```

### By contributor: `GET /posts.json?c=username`

```json
{
  "data": [ ... ],
  "meta": {
    "total": 42,
    "contributor": "username"
  },
  "links": {
    "self": "https://example.com/posts.json?c=username"
  }
}
```

## Navigation: `GET /random`, `/next/:id`, `/prev/:id`

These lightweight endpoints return minimal data for player navigation:

```json
{
  "data": {
    "type": "posts",
    "id": "post-slug",
    "attributes": {
      "title": "Artist - Track Title"
    },
    "links": {
      "self": "https://example.com/post-slug"
    }
  }
}
```

## Error Responses

### 404 Not Found

```json
{
  "errors": [
    {
      "status": "404",
      "title": "Not Found",
      "detail": "No post found with slug 'nonexistent'"
    }
  ]
}
```

### 400 Bad Request

```json
{
  "errors": [
    {
      "status": "400",
      "title": "Bad Request",
      "detail": "Missing required parameter: slug"
    }
  ]
}
```

### 500 Internal Server Error

```json
{
  "errors": [
    {
      "status": "500",
      "title": "Internal Server Error"
    }
  ]
}
```

### 422 Validation Error (multiple errors)

```json
{
  "errors": [
    {
      "status": "422",
      "title": "Validation Error",
      "detail": "Title is required",
      "source": { "pointer": "/data/attributes/title" }
    },
    {
      "status": "422",
      "title": "Validation Error",
      "detail": "Body must not be empty",
      "source": { "pointer": "/data/attributes/body" }
    }
  ]
}
```

## oEmbed: `GET /oembed` (NOT JSON API)

The oEmbed endpoint follows the [oEmbed spec](https://oembed.com/), not JSON API.
It should remain unchanged — this is an industry-standard format used by third-party consumers.

## Migration Path (Phase 3)

| Current | Target | Helper |
|---------|--------|--------|
| `{ "posts": [...] }` | `{ "data": [...] }` | `ApiResponse::collection()` |
| Flat resource object | `{ "type": "posts", "id": "...", "attributes": {...} }` | `ApiResponse::resource()` |
| Embedded sub-objects | `relationships` + `included` | `ApiResponse::resource()` per related type |
| No meta | `"meta": { "total": N }` | `ApiResponse::data($data, $meta)` |
| No top-level links | `"links": { "self": "..." }` | `ApiResponse::data($data, [], $links)` |
| `application/json` | `application/vnd.api+json` | `JsonApiFilter` (already active) |
| No error format | `{ "errors": [...] }` | `ApiErrorResponse::format()` |

## Infrastructure Classes

| File | Class | Purpose |
|------|-------|---------|
| `src/lib/helper/ApiResponse.php` | `ApiResponse` | Build JSON API resources, data, collections |
| `src/lib/helper/ApiErrorResponse.php` | `ApiErrorResponse` | Build JSON API error responses |
| `src/lib/filter/JsonApiFilter.class.php` | `JsonApiFilter` | Auto-set `application/vnd.api+json` header |
