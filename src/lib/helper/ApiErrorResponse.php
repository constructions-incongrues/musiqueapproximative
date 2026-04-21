<?php

/**
 * JSON API 1.0 compliant error response builder.
 *
 * @see https://jsonapi.org/format/#errors
 */
class ApiErrorResponse
{
  /**
   * Format a single error per JSON API 1.0 spec.
   *
   * @param int    $status  HTTP status code (400, 404, 500, etc.)
   * @param string $title   Short, human-readable error title
   * @param string $detail  Detailed human-readable message (optional)
   * @param array  $source  Error source, e.g. ["pointer" => "/data/attributes/field"] (optional)
   * @return array
   */
  public static function format($status, $title, $detail = '', array $source = [])
  {
    $error = [
      'status' => (string) $status,
      'title'  => $title,
    ];

    if ($detail !== '') {
      $error['detail'] = $detail;
    }

    if (!empty($source)) {
      $error['source'] = $source;
    }

    return ['errors' => [$error]];
  }

  /**
   * Format multiple errors in a single response.
   *
   * Each error in $errors should have: status, title, and optionally detail, source.
   *
   * @param array $errors Array of error definitions
   * @return array
   */
  public static function formatMultiple(array $errors)
  {
    $formatted = [];

    foreach ($errors as $err) {
      $error = [
        'status' => (string) $err['status'],
        'title'  => $err['title'],
      ];

      if (isset($err['detail']) && $err['detail'] !== '') {
        $error['detail'] = $err['detail'];
      }

      if (!empty($err['source'])) {
        $error['source'] = $err['source'];
      }

      $formatted[] = $error;
    }

    return ['errors' => $formatted];
  }
}
