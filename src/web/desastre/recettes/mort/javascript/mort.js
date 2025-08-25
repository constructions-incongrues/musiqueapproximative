console.log("Mort");

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

// Fonction pour créer les tombes
function creerTombes() {
  const plateau = document.querySelector(".plateau");
  
  if (plateau) {
    // Configuration de la grille adaptée au viewport avec la nouvelle hauteur
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight * 0.6; // 60vh au lieu de 100vh
    const gridSizeX = Math.floor(viewportWidth / 120); // Grille plus fine horizontalement
    const gridSizeY = Math.floor(viewportHeight / 120); // Grille plus fine verticalement
    const cellSizeX = viewportWidth / gridSizeX;
    const cellSizeY = viewportHeight / gridSizeY;
    const positions = [];
    
    // Génération de positions aléatoires uniques avec plus d'espacement
    for (let i = 0; i < 20; i++) {
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
    
    // Création des tombes une par une avec délai plus long
    positions.forEach((pos, index) => {
      setTimeout(() => {
        const tombe = document.createElement("div");
        tombe.className = "tombe";
        tombe.id = "tombe-" + (index + 1);
        
        // Positionnement aléatoire sur la grille
        tombe.style.position = "absolute";
        tombe.style.left = pos.x + "px";
        tombe.style.top = pos.y + "px";
        tombe.style.width = "120px"; // Taille fixe pour les tombes
        tombe.style.height = "auto";
        
        // Création de l'image avec le chemin correct vers le dossier img
        const image = document.createElement("img");
        image.src = `/desastre/recettes/mort/img/cimetiere${index + 1}.png`;
        image.alt = `Cimetiere ${index + 1}`;
        image.className = "image-tombe";
        image.style.width = "100%";
        image.style.height = "100%";
        image.style.objectFit = "contain";
        
        // Gestion des erreurs de chargement d'image
        image.onerror = function () {
          console.log(`Image cimetiere${index + 1}.png non trouvée`);
          this.style.display = "none";
        };
        
        image.onload = function () {
          console.log(`Image cimetiere${index + 1}.png chargée avec succès`);
        };
        
        tombe.appendChild(image);
        
        // Ajout du div à la div plateau
        plateau.appendChild(tombe);
        
      }, index * 800); // Délai de 800ms (0.8 seconde) entre chaque tombe
    });
    
    console.log("20 divs 'tombe' seront créés sur la grille étalée du plateau");
  } else {
    console.error("La div '.plateau' n'a pas été trouvée");
  }
}

// Attendre que le DOM soit chargé puis créer les tombes
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', creerTombes);
} else {
  // Le DOM est déjà chargé
  creerTombes();
}

// Alternative avec une fonction réutilisable
function creerTombesAvecImages(nombre = 20) {
  const plateau = document.querySelector(".plateau");
  
  if (!plateau) {
    console.error("La div '.plateau' n'a pas été trouvée");
    return;
  }
  
  // Suppression des tombes existantes si nécessaire
  const tombesExistantes = plateau.querySelectorAll(".tombe");
  tombesExistantes.forEach((tombe) => tombe.remove());
  
  // Configuration de la grille adaptée au viewport avec la nouvelle hauteur
  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight * 0.6; // 60vh au lieu de 100vh
  const gridSizeX = Math.floor(viewportWidth / 120); // Grille plus fine horizontalement
  const gridSizeY = Math.floor(viewportHeight / 120); // Grille plus fine verticalement
  const cellSizeX = viewportWidth / gridSizeX;
  const cellSizeY = viewportHeight / gridSizeY;
  const positions = [];
  
  // Génération de positions aléatoires uniques avec plus d'espacement
  for (let i = 0; i < nombre; i++) {
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
  
  // Création des tombes une par une avec délai plus long
  positions.forEach((pos, index) => {
    setTimeout(() => {
      const tombe = document.createElement("div");
      tombe.className = "tombe";
      tombe.id = "tombe-" + (index + 1);
      tombe.setAttribute("data-index", index + 1);
      
      // Positionnement aléatoire sur la grille
      tombe.style.position = "absolute";
      tombe.style.left = pos.x + "px";
      tombe.style.top = pos.y + "px";
      tombe.style.width = "120px"; // Taille fixe pour les tombes
      tombe.style.height = "auto";
      
      // Création de l'image avec le chemin correct vers le dossier img
      const image = document.createElement("img");
      image.src = `/desastre/recettes/mort/img/cimetiere${index + 1}.png`;
      image.alt = `Cimetiere ${index + 1}`;
      image.className = "image-tombe";
      image.style.width = "100%";
      image.style.height = "100%";
      image.style.objectFit = "contain";
      
      // Gestion des erreurs de chargement d'image
      image.onerror = function () {
        console.log(`Image cimetiere${index + 1}.png non trouvée`);
        this.style.display = "none";
      };
      
      image.onload = function () {
        console.log(`Image cimetiere${index + 1}.png chargée avec succès`);
      };
      
      tombe.appendChild(image);
      
      // Ajout du div à la div plateau
      plateau.appendChild(tombe);
      
    }, index * 800); // Délai de 800ms (0.8 seconde) entre chaque tombe
  });
  
  console.log(`${nombre} divs 'tombe' seront créés sur la grille étalée du plateau`);
}
