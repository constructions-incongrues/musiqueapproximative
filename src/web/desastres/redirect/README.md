# Désastre Redirect

## Description

Le désastre "redirect" redirige automatiquement l'utilisateur vers une URL spécifiée après un délai configurable. Ce désastre est utilisé pour des événements spéciaux comme "Spooky Mix" ou les fêtes de Noël.

## Déclenchement

Le désastre peut être déclenché selon différentes conditions. Exemples dans la configuration :

**Spooky Mix :**
```yaml
- query: "query.title == 'Spooky Mix'"
  recettes: [spooky]
  probability: 1.0
```

**Quickos (Noël) :**
```yaml
- query: "context.date.month == '12'"
  recettes: [quickos]
  probability: 0.1

- query: "context.date.month == '12' && (context.date.day == '24' || context.date.day == '25')"
  recettes: [quickos]
  probability: 0.5
```

## Configuration

### Options disponibles

| Option | Type | Requis | Description |
|--------|------|--------|-------------|
| `url` | string | ✅ | URL de destination de la redirection |
| `seconds` | number | ✅ | Délai en secondes avant la redirection |

### Exemples de configuration

**Spooky Mix :**
```yaml
spooky:
  enabled: true
  desastre: redirect
  options:
    url: https://sos.musiqueapproximative.net
    seconds: 3
```

**Quickos (Noël) :**
```yaml
quickos:
  enabled: true
  desastre: redirect
  options:
    url: https://quickoschantenoel.musiqueapproximative.net/?play=1
    seconds: 3
```

## Fonctionnement

1. **Chargement** : Le script vérifie les paramètres URL
2. **Détection noredirect** : Si le paramètre `?noredirect` est présent, la redirection est annulée
3. **DOMContentLoaded** : Attend le chargement complet de la page
4. **Lecture des options** :
   - URL : `window.DesastreOptions.redirect.url`
   - Délai : `window.DesastreOptions.redirect.seconds`
5. **Redirection** : Après le délai spécifié, redirige vers l'URL

## Paramètre noredirect

Pour désactiver la redirection, ajoutez `?noredirect` à l'URL :
```
https://www.musiqueapproximative.net/post/spooky-mix?noredirect
```

Ceci est utile pour :
- Tester la page sans être redirigé
- Déboguer le contenu de la page
- Accéder au contenu malgré la règle de redirection

## Timing

- **Délai par défaut** : Défini dans les options (généralement 3 secondes)
- **Délai minimum** : Aucun (peut être 0 pour redirection immédiate)
- **Conversion** : Le délai en secondes est multiplié par 1000 pour `setTimeout()`

## Fichiers

- `javascript/main.js` : Script principal de redirection
- `stylesheets/main.css` : Styles éventuels (non analysé)
- `README.md` : Cette documentation

## Logs de débogage

```javascript
console.log('[desastres/redirect] loaded');
console.log('[desastres/redirect] noredirect parameter detected, aborting redirect.');
```

## Cas d'usage

### Événements spéciaux
- Redirection vers un site thématique (Halloween, Noël)
- Activation temporaire basée sur la date

### Easter eggs
- Redirection sur certains titres d'émissions
- Blagues ou surprises pour les auditeurs

### Maintenance
- Redirection temporaire vers une page d'information

## Compatibilité

JavaScript vanilla avec APIs standard :
- URLSearchParams : ✅ (tous navigateurs modernes)
- setTimeout : ✅ (tous navigateurs)
- window.location.href : ✅ (tous navigateurs)

Compatible :
- Chrome/Edge : ✅
- Firefox : ✅
- Safari : ✅
- Opera : ✅

## Limitations

- Pas de message d'avertissement avant la redirection
- Pas de compteur visuel du temps restant
- Pas de bouton d'annulation (sauf paramètre `?noredirect` manuel)
- La redirection ne peut pas être annulée une fois lancée
- Pas de validation de l'URL de destination
- Pas de gestion des erreurs si l'URL est invalide
- Le délai ne peut pas être configuré dynamiquement
