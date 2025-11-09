<?php

/**
 * Filtre pour injecter les options des desastres dans le HTML apres le rendu.
 *
 * Ce filtre s'execute apres que le template ait ete rendu et injecte
 * les options JavaScript et CSS dans le HTML final.
 *
 * @package    sfDesastrePlugin
 * @subpackage filter
 * @author     Musique Approximative
 */
class sfDesastreFilter extends sfFilter
{
  /**
   * Execute le filtre
   *
   * @param sfFilterChain $filterChain La chaine de filtres
   */
  public function execute($filterChain)
  {
    // Executer d'abord les autres filtres (et rendre le template)
    $filterChain->execute();

    // Recuperer la reponse
    $response = $this->context->getResponse();

    // Verifier si on a des options de desastres a injecter depuis les attributs utilisateur
    $user = $this->context->getUser();
    $jsCode = $user->getAttribute('desastre_options_js', null);
    $cssCode = $user->getAttribute('desastre_options_css', null);

    if (!$jsCode && !$cssCode) {
      return;
    }

    // Recuperer le contenu de la reponse
    $content = $response->getContent();

    if (empty($content)) {
      return;
    }

    // Verifier que c'est du HTML
    $contentType = $response->getContentType();
    if ($contentType !== 'text/html' && strpos($contentType, 'html') === false) {
      return;
    }

    // Injecter le code avant la fermeture du head
    if (strpos($content, '</head>') !== false) {
      $inject = '';
      if ($cssCode) {
        $inject .= $cssCode . "\n";
      }
      if ($jsCode) {
        $inject .= $jsCode . "\n";
      }

      $content = str_replace('</head>', $inject . '</head>', $content);
      $response->setContent($content);
    }
  }
}
