<?php

/**
 * JSON API 1.0 compliant response builder.
 *
 * @see https://jsonapi.org/format/
 */
class ApiResponse
{
  /**
   * Build a single resource object per JSON API spec.
   *
   * @param string $id       Resource identifier
   * @param string $type     Resource type (e.g. "posts", "users")
   * @param array  $attributes  Resource attributes (id and type are excluded)
   * @param array  $relationships Optional relationships
   * @param array  $links     Optional resource-level links
   * @return array
   */
  public static function resource($id, $type, array $attributes, array $relationships = [], array $links = [])
  {
    // Remove id and type from attributes per spec
    unset($attributes['id'], $attributes['type']);

    $resource = [
      'type'       => $type,
      'id'         => (string) $id,
      'attributes' => $attributes,
    ];

    if (!empty($relationships)) {
      $resource['relationships'] = $relationships;
    }

    if (!empty($links)) {
      $resource['links'] = $links;
    }

    return $resource;
  }

  /**
   * Wrap a single resource in a JSON API top-level document.
   *
   * @param array $data   A resource object (from ::resource())
   * @param array $meta   Optional top-level meta
   * @param array $links  Optional top-level links
   * @return array
   */
  public static function data($data, array $meta = [], array $links = [])
  {
    $response = ['data' => $data];

    if (!empty($meta)) {
      $response['meta'] = $meta;
    }

    if (!empty($links)) {
      $response['links'] = $links;
    }

    return $response;
  }

  /**
   * Wrap a collection of resources in a JSON API top-level document.
   *
   * @param array $items  Array of resource objects (from ::resource())
   * @param array $meta   Optional top-level meta (total, limit, offset, etc.)
   * @param array $links  Optional top-level links (self, next, prev, etc.)
   * @return array
   */
  public static function collection(array $items, array $meta = [], array $links = [])
  {
    $response = ['data' => array_values($items)];

    if (!empty($meta)) {
      $response['meta'] = $meta;
    }

    if (!empty($links)) {
      $response['links'] = $links;
    }

    return $response;
  }
}
