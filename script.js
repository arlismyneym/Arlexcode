function showMemories() {
    document.getElementById('landing').classList.add('animate__animated', 'animate__fadeOut');
    setTimeout(() => {
        document.getElementById('landing').classList.add('d-none');
        const memSection = document.getElementById('memories');
        memSection.classList.remove('d-none');
        memSection.classList.add('animate__animated', 'animate__fadeIn');
    }, 500);
}

let noCount = 0;
const noBtn = document.getElementById('noBtn');
const sadGif = document.getElementById('sadGif');

function rejectValentine() {
    noCount++;
    sadGif.classList.remove('d-none');
    
    if (noCount === 1) {
        noBtn.innerText = "Are you sure?";
    } else if (noCount === 2) {
        noBtn.innerText = "Really sure??";
    } else if (noCount >= 3) {
        noBtn.innerText = "Please? :(";
        // Make the "Yes" button bigger for every 'No'
        document.querySelector('.btn-success').style.transform = `scale(${1 + (noCount * 0.2)})`;
    }
}

// Optional: Makes the button "jump" away if they try to click it (The classic prank)
function moveNo() {
    if (noCount >= 2) {
        const x = Math.random() * (window.innerWidth - noBtn.offsetWidth);
        const y = Math.random() * (window.innerHeight - noBtn.offsetHeight);
        noBtn.style.position = 'absolute';
        noBtn.style.left = x + 'px';
        noBtn.style.top = y + 'px';
    }
}

function acceptValentine() {
    document.getElementById('mainQuestionArea').classList.add('d-none');
    document.getElementById('successArea').classList.remove('d-none');
    document.getElementById('valentineContent').classList.add('animate__animated', 'animate__heartBeat');
}