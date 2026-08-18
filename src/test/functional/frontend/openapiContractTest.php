<?php

/**
 * Confronte le contrat OpenAPI au site.
 *
 * Le test ne porte AUCUNE liste de routes : il lit `web/openapi.yaml`, itere sur
 * ses `paths`, et pour chaque reponse declaree fabrique la demande, l'execute et
 * compare. Une seconde liste divergerait de la premiere ; c'est la raison d'etre
 * de ce dispositif.
 *
 * Ce qu'il verifie :
 *   - chaque route declaree existe et repond ;
 *   - son code de statut est celui du contrat ;
 *   - son type de contenu est celui du contrat, compare sur le type de media
 *     seul — le « charset » ne doit pas faire echouer la comparaison ;
 *   - les champs de premier niveau que le schema declare `required` sont
 *     presents dans les reponses JSON.
 *
 * Ce qu'il ne verifie PAS : la structure des corps au-dela du premier niveau,
 * et le fait que le document soit servi en HTTP a son adresse publique. Les
 * deux sont dits dans le contrat lui-meme. Le second a deja mordu : le contrat
 * a ete vert en integration pendant qu'il repondait 404 en ligne.
 *
 * Les valeurs de substitution ci-dessous (slug, md5sum, current, url) viennent
 * des fixtures. Ce n'est pas une liste de routes : c'est de quoi remplir les
 * parametres que le contrat declare obligatoires.
 */

include(dirname(__FILE__).'/../../bootstrap/functional.php');
require_once dirname(__FILE__).'/../../bootstrap/database.php';

$yamlDir = sfConfig::get('sf_symfony_lib_dir').'/yaml/';
require_once $yamlDir.'sfYaml.class.php';
require_once $yamlDir.'sfYamlParser.class.php';
require_once $yamlDir.'sfYamlInline.class.php';

$contrat = sfConfig::get('sf_web_dir').'/openapi.yaml';

if (!file_exists($contrat))
{
  $t = new lime_test(1);
  $t->fail(sprintf(
    "Le contrat est absent de %s. Il n'est plus genere : c'est un fichier versionne,\n".
    'servi tel quel. S\'il manque, c\'est qu\'il a ete supprime ou deplace.',
    $contrat
  ));

  return;
}

$doc = sfYaml::load($contrat);

// Le contrat n'est plus rendu : il est versionne et servi tel quel. L'invariant
// a tenir n'est donc plus « la substitution n'a rien mange », c'est « personne
// ne l'a retransforme en gabarit ». Un « ${...} » ici voudrait dire qu'une
// etape de rendu est revenue — et comme les rendus sont ignores par git alors
// que le deploiement se fait par `git pull`, elle ne produirait rien en ligne.
$brut = file_get_contents($contrat);

if (preg_match('/\$\{[A-Z_]+\}/', $brut, $trouve))
{
  $t = new lime_test(1);
  $t->fail(sprintf(
    "Le contrat porte la variable non substituee %s. Il est servi tel quel :\n".
    'il ne doit contenir aucun motif a rendre. Voir l\'en-tete du fichier.',
    $trouve[0]
  ));

  return;
}

/**
 * Resout une reference locale `#/components/...`.
 */
function contrat_resoudre($noeud, array $doc)
{
  $vus = 0;

  while (is_array($noeud) && isset($noeud['$ref']) && $vus++ < 10)
  {
    $chemin = explode('/', ltrim($noeud['$ref'], '#/'));
    $noeud = $doc;

    foreach ($chemin as $segment)
    {
      if (!isset($noeud[$segment]))
      {
        return null;
      }

      $noeud = $noeud[$segment];
    }
  }

  return $noeud;
}

/**
 * Normalise `x-parametres` : une table, une liste de tables, ou rien.
 */
function contrat_jeux_de_parametres($noeud)
{
  if (!isset($noeud['x-parametres']))
  {
    return array(array());
  }

  $jeux = $noeud['x-parametres'];

  return isset($jeux[0]) && is_array($jeux[0]) ? $jeux : array($jeux);
}

/**
 * Le type de media seul, sans parametres (« charset », notamment).
 */
function contrat_type_de_media($contentType)
{
  $type = trim(strtolower(current(explode(';', (string) $contentType))));

  return $type;
}

/**
 * Les noms de parametres que le contrat declare obligatoires pour une operation.
 */
function contrat_parametres_obligatoires(array $operation, array $doc)
{
  $noms = array();

  if (!isset($operation['parameters']))
  {
    return $noms;
  }

  foreach ($operation['parameters'] as $parametre)
  {
    $parametre = contrat_resoudre($parametre, $doc);

    if ($parametre && !empty($parametre['required']))
    {
      $noms[] = $parametre['name'];
    }
  }

  return $noms;
}

// Valeurs tirees des fixtures pour les parametres obligatoires du contrat.
//
// Le morceau retenu doit avoir un voisin de CHAQUE cote : le contrat declare un
// 200 sur /posts/next comme sur /posts/prev, et un morceau sans suivant ou sans
// precedent y servirait le 404, rendant la reponse declaree inatteignable.
// La relation de voisinage n'est pas une simple chaine chronologique — on
// interroge donc le modele plutot que de la deduire.
$table = Doctrine_Core::getTable('Post');
$morceau = null;

foreach ($table->createQuery('p')
  ->where('p.is_online = 1 AND p.publish_on <= NOW()')
  ->andWhere("p.slug IS NOT NULL AND p.slug != '' AND p.track_md5 IS NOT NULL")
  ->orderBy('p.publish_on DESC')
  ->execute() as $candidat)
{
  $suivant = $table->getNextPost($candidat, array());
  $precedent = $table->getPreviousPost($candidat, array());

  if ($suivant && $suivant->slug && $precedent && $precedent->slug)
  {
    $morceau = $candidat;
    break;
  }
}

if (!$morceau)
{
  $t = new lime_test(1);
  $t->fail(
    "Aucun morceau publie des fixtures n'a un voisin de chaque cote : les reponses\n".
    'que le contrat declare sur /posts/next et /posts/prev ne peuvent pas etre exercees.'
  );

  return;
}

$valeurs = array(
  'slug'    => $morceau->getSlug(),
  'md5sum'  => $morceau->getTrackMd5(),
  'current' => $morceau->getId(),
  'url'     => 'http://localhost/post/'.$morceau->getSlug(),
);

// Denombrer les verifications avant d'ouvrir lime : le contrat en decide.
$demandes = array();

foreach ($doc['paths'] as $chemin => $operations)
{
  foreach ($operations as $methode => $operation)
  {
    if ('get' !== $methode)
    {
      continue;
    }

    foreach ($operation['responses'] as $statut => $reponse)
    {
      $contenus = isset($reponse['content']) ? $reponse['content'] : array('' => array());

      foreach ($contenus as $typeDeclare => $contenu)
      {
        $jeuxReponse = contrat_jeux_de_parametres($reponse);
        $jeuxContenu = contrat_jeux_de_parametres(is_array($contenu) ? $contenu : array());

        foreach ($jeuxReponse as $jeuR)
        {
          foreach ($jeuxContenu as $jeuC)
          {
            $demandes[] = array(
              'chemin'     => $chemin,
              'statut'     => (int) $statut,
              'type'       => $typeDeclare,
              'imposes'    => array_merge($jeuR, $jeuC),
              'obligatoires' => contrat_parametres_obligatoires($operation, $doc),
              'schema'     => isset($contenu['schema']) ? contrat_resoudre($contenu['schema'], $doc) : null,
            );
          }
        }
      }
    }
  }
}

// Deux assertions par demande (statut, type), plus une d'analysabilite par
// reponse JSON, plus une par schema JSON verifiable.
$attendues = 0;

foreach ($demandes as $demande)
{
  // Statut toujours ; type seulement si la reponse declare un contenu — une
  // redirection n'en declare pas, et son type ne veut rien dire.
  $attendues += '' === $demande['type'] ? 1 : 2;

  if (false !== strpos($demande['type'], 'json'))
  {
    $attendues++;

    if (!empty($demande['schema']['required']))
    {
      $attendues++;
    }
  }
}

$t = new lime_test($attendues);
$t->diag(sprintf('Contrat : %d routes, %d demandes a verifier', count($doc['paths']), count($demandes)));

$navigateur = new sfBrowser();

foreach ($demandes as $demande)
{
  // Un parametre entre dans la demande s'il est impose par « x-parametres », ou
  // si le contrat le declare obligatoire. Sa valeur vient du jeu impose, sinon
  // des fixtures. Rien d'autre n'est ajoute.
  $parametres = $demande['imposes'];

  foreach ($demande['obligatoires'] as $nom)
  {
    if (!array_key_exists($nom, $parametres) && isset($valeurs[$nom]))
    {
      $parametres[$nom] = $valeurs[$nom];
    }
  }

  $chemin = $demande['chemin'];
  $requete = array();

  foreach ($parametres as $nom => $valeur)
  {
    if (false !== strpos($chemin, '{'.$nom.'}'))
    {
      $chemin = str_replace('{'.$nom.'}', rawurlencode($valeur), $chemin);
    }
    else
    {
      $requete[$nom] = $valeur;
    }
  }

  // Les parametres de chemin restants sont toujours obligatoires : les remplir
  // depuis les fixtures.
  if (preg_match_all('/\{(\w+)\}/', $chemin, $trouves))
  {
    foreach ($trouves[1] as $nom)
    {
      $chemin = str_replace('{'.$nom.'}', isset($valeurs[$nom]) ? rawurlencode($valeurs[$nom]) : '', $chemin);
    }
  }

  $url = $chemin.($requete ? '?'.http_build_query($requete) : '');

  try
  {
    $navigateur->get($url);
    $reponse = $navigateur->getResponse();
    $statutServi = $reponse->getStatusCode();
    $typeServi = contrat_type_de_media($reponse->getContentType());
    $corps = $reponse->getContent();
  }
  catch (Throwable $e)
  {
    // Une TypeError n'est pas une Exception : sans ce catch, elle tue le script
    // et lime ne rapporte qu'un compte de tests manquants, sans dire ou.
    $statutServi = sprintf('%s: %s', get_class($e), $e->getMessage());
    $typeServi = $statutServi;
    $corps = '';
  }

  $t->is($statutServi, $demande['statut'], sprintf(
    'GET %s : statut %s, le contrat annonce %s',
    $url, $statutServi, $demande['statut']
  ));

  if ('' !== $demande['type'])
  {
    $typeAttendu = contrat_type_de_media($demande['type']);

    $t->is($typeServi, $typeAttendu, sprintf(
      'GET %s : type « %s », le contrat annonce « %s »',
      $url, $typeServi, $typeAttendu
    ));
  }

  if (false === strpos($demande['type'], 'json'))
  {
    continue;
  }

  // Une reponse annoncee JSON doit d'abord se laisser lire. Le contrat ne le
  // disait nulle part, et c'est precisement ce qui a manque : `/posts?format=json`
  // a servi en production un document invalide — un antislash non echappe, ne
  // d'un `html_entity_decode()` applique apres `json_encode()` — pendant que
  // cette suite le declarait conforme, parce qu'elle ne regardait que les cles.
  $decode = json_decode($corps, true);

  $t->ok(null !== $decode, sprintf(
    'GET %s : le corps est du JSON analysable (%s)',
    $url, json_last_error_msg()
  ));

  if (empty($demande['schema']['required']))
  {
    continue;
  }

  $manquants = array();

  foreach ($demande['schema']['required'] as $champ)
  {
    if (!is_array($decode) || !array_key_exists($champ, $decode))
    {
      $manquants[] = $champ;
    }
  }

  $t->is(implode(', ', $manquants), '', sprintf(
    'GET %s : champs de premier niveau declares mais absents : %s',
    $url, $manquants ? implode(', ', $manquants) : 'aucun'
  ));
}
