## Context

Premier désastre qui touche le son. Tout ce qui suit a été mesuré avant d'être écrit —
c'est la méthode que cette release a payé cher pour adopter.

**Ce qui est disponible** : `AudioWorklet`, sans permission, quantum de 128 échantillons,
avec toute la chaîne Web Audio derrière — Convolver, WaveShaper, filtres, compresseur,
oscillateurs. Une première mesure l'avait déclaré absent : le test interrogeait le mauvais
objet, il est bien là.

**Le morceau est joignable, de bout en bout.** jPlayer crée un `<audio id="jp_audio_0">`.
En production le fichier est servi par `www.musiqueapproximative.net/tracks/…`, donc par le
même hôte que la page, et il porte `access-control-allow-origin: *`.
`createMediaElementSource()` fonctionne sans rien changer.

**Les scripts d'une recette sont chargés en `<script src>` classique** — `addJavascript()`
de symfony 1, sans `type="module"`. Le processeur du worklet se charge séparément, par
`audioWorklet.addModule()` depuis le script principal, et les fichiers d'un désastre sont
servis en 200 avec `application/javascript` — vérifié sur deux d'entre eux.

**Les sélecteurs de la page, relevés et non supposés** : `article h1` porte l'artiste,
`article h2` le titre du morceau. Un `h1` nu attraperait aussi la barre latérale — la page
en compte **trois**. Et `.title` est le nom du site, pas celui du morceau : c'est ce que vise
`mangelettres`, contrairement à ce que le packet de la story affirmait.

## Goals / Non-Goals

**Goals:**

- Que le premier désastre sonore existe, et qu'il soit compris comme voulu
- Que le visiteur qui refuse le mouvement soit entendu
- Que rien de l'infrastructure ne change

**Non-Goals:**

- Lier l'intensité à l'âge du morceau — story 35
- Toute mémoire d'une visite à l'autre — story 36
- Modifier le lecteur, le tirage, le cache

## Decisions

### Wow et flutter par ligne à retard, et non par `playbackRate`

`playbackRate` sur l'élément audio serait plus simple et donnerait un résultat faux : il
modifie la vitesse globale, audible comme un ralenti, pas comme une bande fatiguée. Ce qui
fait la bande usée est une instabilité *continue et petite* autour de la vitesse nominale.

Une ligne à retard dont la longueur oscille produit exactement cela : un retard qui varie
est une hauteur qui varie. Deux oscillateurs superposés — lent pour le *wow*, rapide pour le
*flutter* — et une interpolation entre échantillons, faute de quoi la modulation crépite.

C'est ce qui impose l'`AudioWorklet` : le traitement doit être continu et sur le fil audio.
Sur le fil principal, `ScriptProcessorNode` produirait des craquements — entendus comme une
panne du site, c'est-à-dire l'inverse du but.

### Les contrepoints partagent le signal, ils ne l'imitent pas

C'est la décision de fond, et la spécification la rend obligatoire.

Deux animations parallèles réglées sur les mêmes fréquences seraient perçues comme deux
événements simultanés. Ce qui fait la démonstration, c'est que l'œil et la main confirment
*ce que l'oreille entend*, au même instant. La valeur de modulation ressort donc du
processeur par son `MessagePort` et pilote les deux autres couches.

Le `MessagePort` plutôt qu'un `AnalyserNode` : on veut la valeur du modulateur, pas
l'amplitude du signal. L'analyseur donnerait le volume de la musique, qui n'a aucun rapport.

### La main : inverser la proposition plutôt que la trahir

L'intention était de ralentir le curseur comme la musique. **Aucune API ne le permet** — la
liste des candidats est vide. Le seul moyen est `requestPointerLock`, qui exige un geste,
masque le curseur, affiche une bannière et capture la souris dans la page. Pour un désastre
c'est disqualifiant : ça ne surprend pas, ça séquestre.

Masquer le curseur pour en dessiner un qui traîne est écarté aussi : les clics atterrissent
où est le *vrai* pointeur, et un curseur dessiné qui ment sur le point de clic est un bug.

Retenu : la page répond au pointeur avec le retard flottant. Le curseur n'est pas ralenti,
c'est la page qui met du temps à le remarquer — ce qui prolonge la métaphore au lieu de la
contredire. Une bande usée ne ralentit pas la main, elle répond mollement.

### Le geste de démarrage tombe juste

`AudioContext` démarre suspendu tant que le visiteur n'a rien cliqué. C'est une contrainte
et elle sert : le clic de lecture est précisément le moment où le désastre doit commencer.
Rien à contourner.

## Risks / Trade-offs

**On dégrade la musique d'un contributeur.** C'est la vraie objection, et elle n'est pas
technique. Défigurer une page est une chose ; toucher au morceau que quelqu'un a choisi de
partager en est une autre. D'où une probabilité basse, et le fait que le fichier reste
intact à son adresse.

**Le désastre cesse d'être invisible à une capture d'écran.** C'était sa propriété la plus
neuve — aucun des dix-neuf ne l'avait. Échangée contre un visiteur qui reste. C'est un
arbitrage, pas un gain net.

**Il n'existe pas de `prefers-reduced-motion` pour le son.** Les deux contrepoints tombent
sous ce réglage ; l'altération sonore n'a aucun signal standard pour être refusée. La sortie
doit être fabriquée et documentée, faute de quoi ce désastre est le seul du catalogue auquel
on ne peut pas échapper.

**Un morceau très court ou très calme rendra l'effet illisible.** L'intensité fixe de cette
version ne s'adapte à rien ; c'est assumé, et c'est la raison d'être de la story 35.
