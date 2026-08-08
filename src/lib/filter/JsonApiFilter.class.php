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

    if (null === $request || null === $response) {
      return;
    }

    // Deux surfaces servent du JSON qui n'est pas du JSON:API et dont le
    // Content-Type est impose par leur propre specification :
    //  - le module rest, qui parle Subsonic ;
    //  - l'action oembed, la specification oEmbed exigeant application/json.
    // Les reecrire en application/vnd.api+json casse leurs consommateurs.
    $module = $this->context->getModuleName();

    // Requete non attribuable a un module : on ne reecrit rien plutot que de
    // reecrire a l'aveugle.
    if (null === $module || '' === $module) {
      return;
    }

    if ('rest' === $module || 'oembed' === $request->getParameter('action')) {
      return;
    }

    // Only apply to JSON format requests that already have a JSON content type
    $requestFormat = $request->getRequestFormat();
    $contentType   = $response->getContentType();

    if ($requestFormat === 'json' || (is_string($contentType) && stripos($contentType, 'json') !== false)) {
      $response->setContentType('application/vnd.api+json; charset=utf-8');
    }
  }
}
