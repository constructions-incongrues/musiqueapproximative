console.log('[desastres/danse] Loaded');

document.addEventListener("DOMContentLoaded", () => {
     const audioElement = document.querySelector(".jp-jplayer audio");
  const headerEl = document.querySelector("header");
  const infosEl = document.querySelector(".infos");
  const contentEl = document.querySelector(".content-text");

  if (!audioElement || !headerEl || !infosEl || !contentEl) {
    console.warn(
      "[Danse] Impossible de trouver l'audio ou les éléments '.infos', 'header', '.content'."
    );
    return;
  }

  let animationId = null;

  const audioContext = new AudioContext();
  const analyserNode = audioContext.createAnalyser();
  analyserNode.fftSize = 2048;

  const sourceNode = audioContext.createMediaElementSource(audioElement);
  sourceNode.connect(analyserNode);
  analyserNode.connect(audioContext.destination);

  const frequencyData = new Uint8Array(analyserNode.frequencyBinCount);

  const animate = () => {
    analyserNode.getByteFrequencyData(frequencyData);

    let lowEnergy = 0;
    let midEnergy = 0;
    let highEnergy = 0;

    let lowCount = 0;
    let midCount = 0;
    let highCount = 0;

    const third = Math.floor(frequencyData.length / 3);
    const twoThird = third * 2;

    for (let i = 0; i < frequencyData.length; i++) {
      const value = frequencyData[i];
      if (i < third) {
        lowEnergy += value;
        lowCount++;
      } else if (i < twoThird) {
        midEnergy += value;
        midCount++;
      } else {
        highEnergy += value;
        highCount++;
      }
    }

    const normalise = (energy, count) =>
      count === 0 ? 0 : energy / (count * 255);

    const bassLevel = normalise(lowEnergy, lowCount);
    const midLevel = normalise(midEnergy, midCount);
    const trebleLevel = normalise(highEnergy, highCount);

    headerEl.style.transform = `scale(${1 + bassLevel * 0.25})`;
    headerEl.style.boxShadow = `0 0 ${10 + bassLevel * 40}px rgba(255, 51, 102, ${0.15 + bassLevel * 0.5
      })`;

    infosEl.style.transform = `translateY(${midLevel * -20}px) scale(${1 +
      midLevel * 0.2})`;
    infosEl.style.filter = `brightness(${1 + midLevel * 0.4})`;

    contentEl.style.transform = `scale(${1 + trebleLevel * 0.15})`;
    contentEl.style.boxShadow = `0 0 ${12 + trebleLevel * 30}px rgba(51, 102, 255, ${0.1 + trebleLevel * 0.45
      })`;

    animationId = requestAnimationFrame(animate);
  };

  const startAnimation = () => {
    if (audioContext.state === "suspended") {
      audioContext.resume();
    }

    if (!animationId) {
      animationId = requestAnimationFrame(animate);
    }
  };

  audioElement.addEventListener("play", startAnimation);
  audioElement.addEventListener("pause", () => {
    if (animationId) {
      cancelAnimationFrame(animationId);
      animationId = null;
    }
  });

  document.addEventListener(
    "click",
    () => {
      if (audioContext.state === "suspended") {
        audioContext.resume();
      }
    },
    { once: true }
  );
});

