console.log('[desastres/light] Loaded');

let contenDiv = document.querySelector(".content");

let newDiv = document.createElement("div");
let newDiv2 = document.createElement("div");
let newDiv3 = document.createElement("div");
newDiv.className = "light";
newDiv2.className = "light";
newDiv3.className = "light";

contenDiv.appendChild(newDiv);
contenDiv.appendChild(newDiv2);
contenDiv.appendChild(newDiv3);

console.log('[desastres/light] Created 3 light rays');

let light = document.querySelectorAll(".light");
light.forEach((el, index) => {
  el.animate(
    [
      { transform: "translateX(-100%) rotate(45deg)", opacity: "0.5" },

      { transform: "translateX(800%)", opacity: "0" },
    ],
    {
      duration: 4000,
      iterations: Infinity,
      delay: 10 + index * Math.random(),
      fill: "forwards",
      direction: "normal",
      easing: "ease-in-out",
    }
  );
});

console.log('[desastres/light] Animations started');
