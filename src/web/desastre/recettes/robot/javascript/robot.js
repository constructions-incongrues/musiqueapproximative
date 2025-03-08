let robotLetter = document.querySelector('article h2');
let phrase = robotLetter.textContent;

// Utilisation de la méthode replace pour ajouter un span autour de chaque lettre du mot "robot"
let newPhrase = phrase.replace(/robot/gi, (match) => {
     return match.split('').map(letter => `<span class="letter-wrapper"><span class="letter">${letter}</span></span>`).join('');
});

// Remplacer le contenu de l'élément h2 avec la nouvelle phrase
robotLetter.innerHTML = newPhrase;
let lettersWrapper = document.querySelectorAll('.letter-wrapper');
lettersWrapper.forEach((wrapper, index) => {
     wrapper.style.display = 'inline-block';
     wrapper.style.overflow = 'hidden';
     // wrapper.style.border = '2px solid black';
     // wrapper.style.boxShadow = 'inset 0px 1px 7px rgb(0, 0, 0)';
     // wrapper.style.backgroundColor = '#29f23f';
});
let letters = document.querySelectorAll('h2 .letter');
// console.log(letters);
window.onload = function() {
     for(let i = 0; i < letters.length; i+=2) {
          letters[i].style.display = 'block';
               letters[i].animate([
                    {
                         transform: 'translateX(-10px) perspective(40cm) rotateX(0deg) rotateY(-60deg)',
                         opacity: 1
                    },
                    {
                         transform: 'translateX(20px) perspective(40cm) rotateX(0deg) rotateY(60deg)',
                         opacity: 1
                    },
                    {
                         transform: 'translateX(30px) perspective(40cm) rotateX(0deg) rotateY(60deg)',
                         opacity: 0
                    },
                    {    transform: 'translateX(-10px) perspective(40cm) rotateX(0deg) rotateY(-60deg)',
                         opacity: 0
                     }

               ], {
                    duration: 5000,
                    iterations: Infinity,
                    
                    direction: 'normal',
               });
          
     }

     for (let i = 1; i < letters.length; i += 2) {
          letters[i].style.display = 'inline-block';
          letters[i].style.transform = 'translateY(-50px) perspective(40cm) rotateX(-60deg) rotateY(0deg)';
          setTimeout(() => {
               letters[i].animate([
                    {
                         transform: 'translateY(-50px) perspective(40cm) rotateX(-60deg) rotateY(0deg)',
                         opacity: 1
                    },
                    {
                         transform: 'translateY(30px) perspective(40cm) rotateX(60deg) rotateY(0deg)',
                         opacity: 1
                    },
                    {
                         transform: 'translateY(50px) perspective(40cm) rotateX(-60deg) rotateY(0deg)',
                         opacity: 0
                    },
                    { transform: 'translateY(-10px) perspective(40cm) rotateX(-60deg) rotateY(0deg)' },
                    {
                         transform: 'translateY(0)',
                         opacity: 0
                    }
               ], {
                    duration: 5000,
                    iterations: Infinity,
                    direction: 'normal',
               });
          }, 2000);
     }
}

// console.log(newPhrase);