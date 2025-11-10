document.addEventListener("DOMContentLoaded", (event) => {
    console.log("[desastres/mangelettres] Loaded");
    gsap.registerPlugin(SplitText)

    SplitText.create('section.content', {
        "type": "chars",
        "mask": "chars",
        "autoSplit": true,
        "charsClass": "char++",
        onSplit(self) {
            const target = window.DesastreOptions.mangelettres.target;
            const rate = window.DesastreOptions.mangelettres.rate || 1.0;
            const caseSensitive = window.DesastreOptions.mangelettres.case === 'sensitive';
            const duration = window.DesastreOptions.mangelettres.duration || 2;
            const stagger = window.DesastreOptions.mangelettres.stagger || 0.02;

            console.log('[desastres/mangelettres] Analyzing', self.chars.length, 'characters');

            // Identifier les lettres à faire disparaître
            const charsToRemove = self.chars.filter(charEl => {
                const char = charEl.textContent;
                const charToCompare = caseSensitive ? char : char.toLowerCase();
                const isInTarget = target.includes(charToCompare);

                // Si la lettre est dans target, on la marque pour disparition avec une probabilité de "rate"
                if (isInTarget) {
                    return Math.random() < rate;
                }
                return false;
            });

            console.log('[desastres/mangelettres] Will remove', charsToRemove.length, 'characters progressively');

            // Animer la disparition progressive
            if (charsToRemove.length > 0) {
                gsap.to(charsToRemove, {
                    opacity: 0,
                    duration: duration,
                    stagger: stagger,
                    ease: "power2.in",
                    onComplete: () => {
                        console.log('[desastres/mangelettres] Disappearance complete');
                    }
                });
            }

            // Vérifier si onSplit est configuré pour une animation supplémentaire
            if (window.DesastreOptions.mangelettres && window.DesastreOptions.mangelettres.onSplit) {
                console.log('[desastres/mangelettres] Applying additional onSplit animation');
                return gsap.from(
                    self.chars,
                    window.DesastreOptions.mangelettres.onSplit
                );
            }
        }
    });

    console.log('[desastres/mangelettres] SplitText initialized with progressive disappearance');
});
