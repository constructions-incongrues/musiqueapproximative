console.log('[desastres/postillons] Loaded');

// Vérifier que les options sont présentes
if (!window.DesastreOptions || !window.DesastreOptions.postillons) {
  console.error('[desastres/postillons] ERROR: Options not found in window.DesastreOptions.postillons');
  console.log('[desastres/postillons] Available options:', window.DesastreOptions);
} else {
  console.log('[desastres/postillons] Options loaded:', window.DesastreOptions.postillons);

  // Options avec valeurs par défaut
  const options = {
    imageFolder: window.DesastreOptions.postillons.imageFolder || '/desastres/postillons/img/default',
    imageBaseName: window.DesastreOptions.postillons.imageBaseName || 'image',
    imageExtension: window.DesastreOptions.postillons.imageExtension || 'png',
    imageCount: window.DesastreOptions.postillons.imageCount || 20,
    imageSize: window.DesastreOptions.postillons.imageSize || 120,
    delay: window.DesastreOptions.postillons.delay || 800
  };

  console.log('[desastres/postillons] Using options:', options);

  // Création de la div plateau
  let body = document.querySelector("body");
  let newDiv = document.createElement("div");
  newDiv.className = "plateau";
  newDiv.style.position = "absolute";
  newDiv.style.width = "100%";
  newDiv.style.height = "60vh";
  newDiv.style.zIndex = "1000";
  newDiv.style.bottom = "0";

  body.appendChild(newDiv);

  console.log('[desastres/postillons] Plateau created at bottom (60vh height)');

  // Fonction pour créer les postillons
  function creerpostillons() {
    console.log('[desastres/postillons] Starting image creation');
    const plateau = document.querySelector(".plateau");

    if (plateau) {
      // Configuration de la grille adaptée au viewport avec la nouvelle hauteur
      const viewportWidth = window.innerWidth;
      const viewportHeight = window.innerHeight * 0.6;
      const gridSizeX = Math.floor(viewportWidth / options.imageSize);
      const gridSizeY = Math.floor(viewportHeight / options.imageSize);
      const cellSizeX = viewportWidth / gridSizeX;
      const cellSizeY = viewportHeight / gridSizeY;
      const positions = [];

      // Génération de positions aléatoires uniques avec plus d'espacement
      for (let i = 0; i < options.imageCount; i++) {
      let newPos;
      let attempts = 0;
      do {
        newPos = {
          x: Math.floor(Math.random() * gridSizeX) * cellSizeX,
          y: Math.floor(Math.random() * gridSizeY) * cellSizeY
        };
        attempts++;
        // Éviter les positions trop proches et assurer une meilleure répartition
      } while (positions.some(pos =>
        Math.abs(pos.x - newPos.x) < cellSizeX * 2 &&
        Math.abs(pos.y - newPos.y) < cellSizeY * 2
      ) && attempts < 200);

      positions.push(newPos);
    }

    // Création des postillons une par une avec délai plus long
    positions.forEach((pos, index) => {
      setTimeout(() => {
        const postillon = document.createElement("div");
        postillon.className = "postillon";
        postillon.id = "postillon-" + (index + 1);

        // Positionnement aléatoire sur la grille
        postillon.style.position = "absolute";
        postillon.style.left = pos.x + "px";
        postillon.style.top = pos.y + "px";
        postillon.style.width = options.imageSize + "px";
        postillon.style.height = "auto";

        // Création de l'image avec le chemin configuré
        const image = document.createElement("img");
        const imagePath = `${options.imageFolder}/${options.imageBaseName}${index + 1}.${options.imageExtension}`;
        image.src = imagePath;
        image.alt = `${options.imageBaseName} ${index + 1}`;
        image.className = "image-postillon";
        image.style.width = "100%";
        image.style.height = "100%";
        image.style.objectFit = "contain";

        // Gestion des erreurs de chargement d'image
        image.onerror = function () {
          console.error(`[desastres/postillons] ERROR: Image not found: ${imagePath}`);
          this.style.display = "none";
        };

        image.onload = function () {
          console.log(`[desastres/postillons] Image loaded: ${imagePath}`);
        };

        postillon.appendChild(image);

        // Ajout du div à la div plateau
        plateau.appendChild(postillon);

      }, index * options.delay);
    });

    console.log(`[desastres/postillons] ${options.imageCount} tombs will be created progressively (${options.delay}ms delay)`);
  } else {
    console.error("[desastres/postillons] ERROR: .plateau div not found");
  }
}

  // Attendre que le DOM soit chargé puis créer les postillons
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', creerpostillons);
  } else {
    // Le DOM est déjà chargé
    creerpostillons();
  }
}
