<?php

/**
 * Moteur de regles pour les desastres.
 *
 * Ce moteur permet d'evaluer des expressions de regles basees sur :
 * - Les parametres de requete (query.*)
 * - Le contexte temporel (context.date.*)
 * - Des operateurs logiques (&&, ||)
 * - Des regex avec le pattern ~ /pattern/i
 *
 * @package    sfDesastrePlugin
 * @subpackage lib
 * @author     Musique Approximative
 */
class sfDesastreRuleEngine
{
  protected $query = array();
  protected $context = array();

  /**
   * Constructeur
   *
   * @param array $query Parametres de la requete
   * @param array $context Contexte additionnel (par defaut: date courante)
   */
  public function __construct(array $query = array(), array $context = array())
  {
    $this->query = $query;

    // Initialiser le contexte de date par defaut
    if (!isset($context['date'])) {
      $context['date'] = array(
        'day' => date('j'),
        'month' => date('n'),
        'year' => date('Y'),
        'hour' => date('G'),
        'minute' => date('i'),
        'weekday' => date('N'), // 1 (lundi) a 7 (dimanche)
      );
    }

    $this->context = $context;
  }

  /**
   * Evalue une expression de regle
   *
   * @param string $expression L'expression a evaluer
   * @return bool True si la regle correspond
   */
  public function evaluate($expression)
  {
    // Nettoyer l'expression
    $expression = trim($expression);

    if (empty($expression)) {
      return false;
    }

    // Gerer les parentheses pour les sous-expressions SAUF celles dans les regex
    // On match seulement les parentheses qui ne sont PAS entre des /.../ (regex)
    if (preg_match('/^([^\/]*)\(([^)]+)\)([^\/]*)$/', $expression, $matches)) {
      // Verifier qu'il n'y a pas de regex dans la partie matchee
      $before = $matches[1];
      $subExpression = $matches[2];
      $after = $matches[3];

      // Si on a un ~ avant la parenthese, c'est une regex, on ne traite pas
      if (substr(trim($before), -1) === '~') {
        // C'est une regex, on passe
      } else {
        $result = $this->evaluate($subExpression);
        // Remplacer la sous-expression par son resultat
        $expression = $before . ($result ? 'true' : 'false') . $after;
        // Continuer l'evaluation si necessaire
        if (strpos($expression, '||') !== false || strpos($expression, '&&') !== false) {
          return $this->evaluate($expression);
        }
        return $result;
      }
    }

    // Gerer les operateurs logiques OR
    if (strpos($expression, '||') !== false) {
      $parts = explode('||', $expression);
      foreach ($parts as $part) {
        if ($this->evaluate(trim($part))) {
          return true;
        }
      }
      return false;
    }

    // Gerer les operateurs logiques AND
    if (strpos($expression, '&&') !== false) {
      $parts = explode('&&', $expression);
      foreach ($parts as $part) {
        if (!$this->evaluate(trim($part))) {
          return false;
        }
      }
      return true;
    }

    // Gerer les valeurs booleennes litterales
    if ($expression === 'true') {
      return true;
    }
    if ($expression === 'false') {
      return false;
    }

    // Evaluer une condition simple
    return $this->evaluateCondition($expression);
  }

  /**
   * Evalue une condition simple (regex ou egalite)
   *
   * @param string $condition La condition a evaluer
   * @return bool True si la condition est vraie
   */
  protected function evaluateCondition($condition)
  {
    // Regex: query.title ~ /.*pattern.*/i
    if (preg_match('/(.+?)\s*~\s*\/(.+)\/([i]?)/', $condition, $matches)) {
      $variable = trim($matches[1]);
      $pattern = $matches[2];
      $flags = isset($matches[3]) ? $matches[3] : '';

      $value = $this->getVariableValue($variable);

      if ($value === null) {
        return false;
      }

      $regexFlags = '';
      if ($flags === 'i') {
        $regexFlags = 'i';
      }

      return (bool) preg_match('/' . $pattern . '/' . $regexFlags, $value);
    }

    // Egalite: context.date.day == '14'
    if (preg_match('/(.+?)\s*(==|!=|>|<|>=|<=)\s*[\'"]?(.+?)[\'"]?$/', $condition, $matches)) {
      $variable = trim($matches[1]);
      $operator = $matches[2];
      $expectedValue = trim($matches[3], '\'"');

      $actualValue = $this->getVariableValue($variable);

      if ($actualValue === null) {
        return false;
      }

      switch ($operator) {
        case '==':
          return $actualValue == $expectedValue;
        case '!=':
          return $actualValue != $expectedValue;
        case '>':
          return $actualValue > $expectedValue;
        case '<':
          return $actualValue < $expectedValue;
        case '>=':
          return $actualValue >= $expectedValue;
        case '<=':
          return $actualValue <= $expectedValue;
        default:
          return false;
      }
    }

    return false;
  }

  /**
   * Recupere la valeur d'une variable (query.* ou context.*)
   *
   * @param string $variable Le nom de la variable (ex: "query.title", "context.date.day")
   * @return mixed|null La valeur de la variable ou null si non trouvee
   */
  protected function getVariableValue($variable)
  {
    $parts = explode('.', $variable);

    if (count($parts) < 2) {
      return null;
    }

    $scope = array_shift($parts);

    if ($scope === 'query') {
      $data = $this->query;
    } elseif ($scope === 'context') {
      $data = $this->context;
    } else {
      return null;
    }

    // Naviguer dans les tableaux imbriques
    foreach ($parts as $key) {
      if (!isset($data[$key])) {
        return null;
      }
      $data = $data[$key];
    }

    return $data;
  }

  /**
   * Definit les parametres de requete
   *
   * @param array $query
   */
  public function setQuery(array $query)
  {
    $this->query = $query;
  }

  /**
   * Definit le contexte
   *
   * @param array $context
   */
  public function setContext(array $context)
  {
    $this->context = $context;
  }
}
