console.log('[desastres/fish] Loaded');

let fishDiv = document.createElement('div');
fishDiv.classList.add('fish');
fishDiv.innerHTML = '<iframe src="https://lottie.host/embed/fd9ece5c-3319-49ea-a84a-a42eaba41839/77Hxq4ZgJ4.lottie"></iframe>';
fishDiv.style.position = 'absolute';
fishDiv.style.width = '100px';
fishDiv.style.height = '100px';
document.body.appendChild(fishDiv);

console.log('[desastres/fish] Fish element created and added to body');

document.addEventListener('mousemove', (event) => {
     const x = event.clientX;
     const y = event.clientY;

     fishDiv.style.left = `${x}px`;
     fishDiv.style.top = `${y-20}px`;
});

console.log('[desastres/fish] Mousemove listener registered');