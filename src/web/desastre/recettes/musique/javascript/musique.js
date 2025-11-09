console.log("♫");

document.addEventListener("DOMContentLoaded", function() {
     let contentDiv = document.querySelector(".content");

     // Créer 3 rangées
     for (let r = 0; r < 3; r++) {
          // Créer la div "rangée"
          let rangee = document.createElement("div");
          rangee.className = "rangee";

          // Créer les 5 div "ligne" et les ajouter à la rangée
          for (let i = 0; i < 5; i++) {
               let ligne = document.createElement("div");
               ligne.className = "ligne";
               rangee.appendChild(ligne);
          }

          // Créer la div "tempo"
          let tempo = document.createElement("div");
          tempo.className = "tempo";

          // Créer les 4 div "mesure" et les ajouter à la div "tempo"
          for (let j = 0; j < 4; j++) {
               let mesure = document.createElement("div");
               mesure.className = "mesure";
               tempo.appendChild(mesure);
          }

          // Ajouter la div "tempo" à la rangée
          rangee.appendChild(tempo);

          // Attacher la rangée à la div .content
          contentDiv.appendChild(rangee);
     }
});