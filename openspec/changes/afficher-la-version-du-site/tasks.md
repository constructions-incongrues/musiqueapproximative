# Tâches

Pas de `design.md` : ni module, ni dépendance, ni schéma. Une dizaine de lignes de gabarit.

## 1. Se servir de ce qui existe

- [x] 1.1 Constater que `VersionFilter` pose déjà `app_version` depuis `src/VERSION`, et que
  `layout.php` s'en sert **uniquement** comme casse-cache d'assets. La valeur est déjà dans
  la page, sous la forme `?v=1.11.0` ; elle n'est simplement jamais montrée.
- [x] 1.2 Ne rien charger de plus : pas de lecture de fichier, pas d'appel réseau, pas de
  requête à GitHub. Une page qui interroge une forge pour afficher son propre numéro de
  version est une page qui tombe quand la forge tombe.

## 2. L'afficher

- [x] 2.1 Poser la ligne sous les crédits, où se trouvent déjà le dépôt et la licence.
- [x] 2.2 Lier vers `releases/tag/v<version>`, la notice de cette version précise et non la
  liste — c'est ce qui répond à « qu'est-ce que je consulte ».
- [x] 2.3 Traiter le cas sans `src/VERSION` : afficher « Version de développement » et lier
  la liste des publications. **Ne pas fabriquer un lien vers une étiquette inexistante** —
  un lien mort dit moins que pas de lien.
- [x] 2.4 Aucun style ajouté : la ligne hérite de la barre latérale. Vérifié —
  16 px, `rgb(187,187,187)`, Rambla, comme le texte voisin.

## 3. Vérification

- [x] 3.1 Cas nominal : la page rend « Version 1.11.0 — notes de version », et le lien sort
  en `…/releases/tag/v1.11.0`.
- [x] 3.2 **La cible du lien existe** : elle répond `200`. Un lien vers une notice de
  publication qu'on n'a pas vérifiée est un lien mort en puissance.
- [x] 3.3 Cas dégradé : `src/VERSION` retiré, la page rend « Version de développement — notes
  de version » et le lien sort en `…/releases`. Fichier restauré après l'essai.
- [x] 3.4 `php -l` sur le gabarit.

### Vérification manuelle — après la mise en ligne

- [ ] 3.5 Ouvrir le site et vérifier que la version affichée est bien celle qui vient d'être
  publiée, et non la précédente. C'est le seul point que le développement ne peut pas
  montrer : `src/VERSION` n'y bouge qu'à la fusion de la PR de release.
