## Why

Le site s'appelle *Musique Approximative*. Dix-neuf désastres, et aucun n'a jamais pris ce
titre au mot.

Celui-ci rend la lecture littéralement approximative : la hauteur du son se met à flotter,
comme une bande magnétique passée trop de fois. Du *wow* — une ondulation lente autour de
0,5–2 Hz — et du *flutter*, un tremblement vers 6–12 Hz, obtenus par une ligne à retard
modulée, un retard qui varie étant une hauteur qui varie.

**Deux propriétés qu'aucun des dix-neuf n'a.**

Il ne vient pas du titre du morceau. Douze des dix-neuf illustrent un mot — le titre dit
« poisson », un poisson arrive. Celui-ci vient du nom du site.

Et il touche **le morceau lui-même**. Le relevé du 2026-08-19 l'a établi : les désastres
décorent la page autour de la musique sans jamais entrer dedans. Web Audio est à zéro sur
dix-neuf, alors qu'il est entièrement disponible et sans permission.

**Ce qu'il doit provoquer** : le doute quelques secondes, puis la compréhension que c'était
voulu. Le doute seul ne suffit pas — sur un site de musique, qui croit le son cassé ferme
l'onglet, et le désastre se retourne contre le morceau qu'il accompagne. D'où deux
contrepoints, sur la même modulation.

## What Changes

- **Un processeur `AudioWorklet`** appliquant wow et flutter au signal du morceau. Mesuré
  disponible, sans permission, quantum de 128 échantillons, intensité exposée en
  `AudioParam` pour qu'elle s'automatise à la fréquence audio.

- **Le branchement sur le lecteur.** jPlayer crée un `<audio id="jp_audio_0">` ; en
  production le fichier est servi par le même hôte que la page avec
  `access-control-allow-origin: *`. `createMediaElementSource()` fonctionne sans rien
  changer à l'infrastructure — vérifié de bout en bout.

- **Le contrepoint visuel**, piloté par la même modulation, pas par une animation parallèle :
  une déformation minuscule du titre du morceau. Une seule cause, deux sens.

- **Le contrepoint tactile** : la page répond au pointeur avec le même retard flottant. *Le
  curseur n'est pas ralenti — la page met du temps à le remarquer.* Aucune API ne permet de
  ralentir un curseur ; `requestPointerLock` séquestre et est écarté.

- **Le respect de `prefers-reduced-motion`** pour les deux contrepoints. Aucun des dix-neuf
  ne lit ce signal aujourd'hui.

**Hors périmètre** : lier l'intensité à l'âge du morceau (story 35) ; toute mémoire d'une
visite à l'autre (story 36). L'intensité est fixe ici.

## Capabilities

### New Capabilities

- `desastre-sonore` : ce que le site s'autorise à faire au signal audio d'un morceau, et ce
  qu'il doit au visiteur quand il le fait.

### Modified Capabilities

- `desastres` : un désastre peut désormais agir sur le son et non seulement sur la page.

## Impact

- `src/web/desastres/bande-usee/` — le script et le processeur de worklet
- `src/apps/frontend/config/desastres/{recettes,regles}/` — la recette et la règle
- `src/apps/frontend/config/desastres.yml` — les deux imports
- Aucun changement du lecteur, du tirage, du cache ni de l'infrastructure
