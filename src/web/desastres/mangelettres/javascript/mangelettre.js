document.addEventListener("DOMContentLoaded", (event) => {
    console.log("[desastres/mangelettres] Loaded");
    gsap.registerPlugin(SplitText)

    SplitText.create('div.grid-container', {
        "type": "chars",
        "mask": "chars",
        "autoSplit": false,
        "charsClass": "char++",
        "prepareText": (texte, el) => {
            const target = window.DesastreOptions.mangelettres.target;
            const rate = window.DesastreOptions.mangelettres.rate || 1.0;
            const caseSensitive = window.DesastreOptions.mangelettres.case === 'sensitive';

            // Ne garder que les lettres qui ne sont PAS dans target (on mange les lettres de target)
            // Utiliser rate pour échantillonner aléatoirement la suppression
            const filteredText = texte.split('').filter(char => {
                // Comparer en fonction de la sensibilité à la casse
                const charToCompare = caseSensitive ? char : char.toLowerCase();
                const isInTarget = target.includes(charToCompare);

                // Si la lettre est dans target, on la supprime avec une probabilité de "rate"
                if (isInTarget) {
                    return Math.random() > rate; // Supprime si random <= rate
                }
                return true; // Garde les autres lettres
            }).join('');

            console.log(`[desastres/mangelettres] Original: "${texte}" -> Filtered: "${filteredText}" (rate: ${rate}, case: ${caseSensitive ? 'sensitive' : 'insensitive'})`);
            return filteredText;
        }
    });

    console.log('[desastres/mangelettres] SplitText initialized with prepareText filter');
});
