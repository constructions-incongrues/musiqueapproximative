<?php

/**
 * Filter to set JSON API Content-Type header on all JSON responses.
 *
 * Sets Content-Type to application/vnd.api+json per JSON API 1.0 spec.
 *
 * @see https://jsonapi.org/format/#content-negotiation
 */
class JsonApiFilter extends sfFilter
{
  public function execute($filterChain)
  {
    $filterChain->execute();

    $request  = $this->context->getRequest();
    $response = $this->context->getResponse();

    // Only apply to JSON format requests that already have a JSON content type
    $requestFormat = $request->getRequestFormat();
    $contentType   = $response->getContentType();

    if ($requestFormat === 'json' || (is_string($contentType) && stripos($contentType, 'json') !== false)) {
      $response->setContentType('application/vnd.api+json; charset=utf-8');
    }
  }
}
