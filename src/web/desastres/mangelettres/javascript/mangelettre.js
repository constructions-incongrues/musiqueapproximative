document.addEventListener("DOMContentLoaded", (event) => {
  window.DesastreAudio.onReady(function (audio) {
    console.log("[desastres/mangelettres] Loaded");
    gsap.registerPlugin(SplitText);
    let split = SplitText.create(
      window.DesastreOptions.mangelettres.selector || "section.content",
      {
        type: "chars",
        mask: "chars",
        autoSplit: true,
        charsClass: "char++",
      },
    );

    // Animer la disparition progressive
    const target = window.DesastreOptions.mangelettres.target;
    const rate = window.DesastreOptions.mangelettres.rate || 1.0;
    const caseSensitive =
      window.DesastreOptions.mangelettres.case === "sensitive";

    console.log(
      "[desastres/mangelettres] Analyzing",
      split.chars.length,
      "characters",
    );

    // Identifier les lettres à faire disparaître
    const charsToRemove = split.chars.filter((charEl) => {
      const char = charEl.textContent;
      const charToCompare = caseSensitive ? char : char.toLowerCase();
      const isInTarget = target.includes(charToCompare);

      // Si la lettre est dans target, on la marque pour disparition avec une probabilité de "rate"
      if (isInTarget) {
        return Math.random() < rate;
      }
      return false;
    });

    const duration =
      window.DesastreOptions.mangelettres.duration || audio.duration.toFixed(0);
    const stagger = window.DesastreOptions.mangelettres.stagger || 0.02;
    const speed = window.DesastreOptions.mangelettres.speed || 1;

    console.log(
      "[desastres/mangelettres] Will remove",
      charsToRemove.length,
      "characters in",
      audio.duration,
      "seconds",
    );

    gsap.to(charsToRemove, {
      opacity: 0,
      duration: duration / speed,
      stagger: stagger,
      ease: "power2.in",
      onComplete: () => {
        console.log("[desastres/mangelettres] Disappearance complete");
      },
    });
  });

  console.log(
    "[desastres/mangelettres] SplitText initialized with progressive disappearance",
  );
});
