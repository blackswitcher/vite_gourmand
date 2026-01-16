var bouton = document.querySelector(".bouton");
var menu = document.querySelector(".menu");

window.menuToggle = function () {
  menu.classList.toggle("active");
  bouton.classList.toggle("active");
};

// gestion du modal

const openMapBtn = document.getElementById("openMapBtn");
const mapModal = document.getElementById("mapModal");
const closeMapBtn = document.querySelector("#mapModal .modal_close");

if (openMapBtn && mapModal && closeMapBtn) {
  // ouvrir
  openMapBtn.addEventListener("click", () => {
    mapModal.classList.add("open");
  });

  //fermer en cliquant sur la croix

  closeMapBtn.addEventListener("click", () => {
    mapModal.classList.remove("open");
  });

  // fermer si on clique en dehors
  mapModal.addEventListener("click", (e) => {
    if (e.target === mapModal) {
      mapModal.classList.remove("open");
    }
  });
}

// gestion de la fonction Prix

const prixMaxInput = document.getElementById("prixMax");
const prixMaxValue = document.getElementById("prixMaxValue");

// il s'agit de ma value de départ
prixMaxValue.textContent = prixMaxInput.value;

//mise a jour en temps reel

prixMaxInput.addEventListener("input", () => {
  prixMaxValue.textContent = prixMaxInput.value;
});

// gestion du carroussel

// definition des variables

const slide = document.querySelectorAll(".slideModal");
const btnPrev = document.querySelector(".btnPrevious");
const btnNext = document.querySelector(".btnNext");
let currentIndex = 0;

function carroussel() {
  slide.forEach((element) => element.classList.remove("active"));
  slide[currentIndex].classList.add("active");
  btnNext.addEventListener("click", () => {
    currentIndex++; // on avance
    if (currentIndex >= slide.length) {
      // si on dépasse le dernier
      currentIndex = 0; // on revient au début
    }
    carroussel(); // on met à jour l'affichage
  });

  btnPrev.addEventListener("click", () => {
    currentIndex--; // on recule
    if (currentIndex < 0) {
      // si on passe avant le 1er
      currentIndex = slide.length - 1; // on va au dernier
    }
    carroussel(); // on met à jour l'affichage
  });
}

// gestion du modal
function afficherMenu() {
  const menuModal = document.getElementById("menuModal");
  const modalClose = document.querySelector(".modal_close");
  const modal = document.querySelector(".modal_overlay");

  //gerer l'ouverture de mon modal
  if (modal && modalClose && menuModal) {
    menuModal.addEventListener("click", () => {
      modal.classList.remove("hidden");
    });
    // gerer la fermeture de mon modal
    modalClose.addEventListener("click", () => {
      modal.classList.add("hidden");
    });

    // gere la fermeture si on clique en dehors du modal
    modal.addEventListener("click", (e) => {
      if (e.target === modal) {
        modal.classList.add("hidden");
      }
    });
  }
}
//appel de ma function ^^
afficherMenu();

//------------------------------GESTION DU MODAL CONNEXION --------------------------
function initModalConnexion() {
  const btnModalConnexion = document.getElementById("modalConnexion");
  const modalOverlay = document.querySelector(".modal_overlay");
  const btnClose = document.querySelector(".modal_close");

  if (!btnModalConnexion || !modalOverlay || !btnClose) return;

  // OUVRIR
  btnModalConnexion.addEventListener("click", (e) => {
    e.preventDefault(); // important si c'est un <a>
    modalOverlay.classList.remove("hidden");
  });

  // FERMER avec la croix
  btnClose.addEventListener("click", () => {
    modalOverlay.classList.add("hidden");
  });

  // FERMER si clic sur le fond (outside)
  modalOverlay.addEventListener("click", (e) => {
    if (e.target === modalOverlay) {
      modalOverlay.classList.add("hidden");
    }
  });
}

initModalConnexion();

//-------------------------------GESTION CONNEXION/INSCRIPTION-----------

function ongletConnexion() {
  const btnConnexion = document.getElementById("btnConnexion");
  const btnInscription = document.getElementById("btnInscription");
  const ongletInscription = document.querySelector(".inscription");

  if (btnConnexion && btnInscription && ongletInscription) {
    btnInscription.addEventListener("click", () => {
      ongletInscription.classList.remove("hidden");
      ongletInscription.classList.add("is-active");
    });

    btnConnexion.addEventListener("click", () => {
      ongletInscription.classList.remove("is-active");
      ongletInscription.classList.add("hidden");
    });
  }
}

ongletConnexion();

function verifInscription() {
  const MDPVerif = document.getElementById("MDPVerif");
  const adresse = document.getElementById("adresse");
  const codePostal = document.getElementById("codePostal");
  const telephone = document.getElementById("telephone");
  const btnInscription = document.getElementById("btnInscription");
  const btnConnexion = document.getElementById("btnConnexion");

  const donnees = [MDPVerif, adresse, codePostal, telephone];

  if (!donnees.every(Boolean)) return;

  btnInscription.addEventListener("click", () => {
    [MDPVerif, adresse, codePostal, telephone].forEach(
      (el) => (el.required = true)
    );
  });

  btnConnexion.addEventListener("click", () => {
    [MDPVerif, adresse, codePostal, telephone].forEach(
      (el) => (el.required = false)
    );
  });
}
verifInscription();

function verifNewMDP() {
  const MDP = document.getElementById("MDP");
  const MDPVerif = document.getElementById("MDPVerif");
  const btnInscription = document.getElementById("btnInscription");
  const btnConnexion = document.getElementById("btnConnexion");
  const submitBtn = document.getElementById("submitBtn");
  const blocInscription = document.querySelector(".inscription");

  if (!MDP || !MDPVerif || !submitBtn || !blocInscription) return;

  // change le texte du bouton
  function setMode(mode) {
    if (mode === "inscription") {
      submitBtn.textContent = "S'inscrire";
    } else {
      submitBtn.textContent = "Se connecter";
      MDPVerif.setCustomValidity("");
      submitBtn.disabled = false;
    }
  }

  // vérification live des mots de passe
  function checkMDP() {

    const isInscription = blocInscription.classList.contains("is-active");

    if(!isInscription){
      MDPVerif.setCustomValidity("");
      submitBtn.disabled = false;
      return;
    }

// antispam

if (MDPVerif.VALUE.length === 0){
  MDPVerif.setCustomValidity("");
  submitBtn.disabled =false; 
  return;
} 

if (MDP !== MDPVerif){
  MDPVerif.setCustomValidity("Les mots de passe sont différents")
  submitBtn.disabled = true;

  MDPVerif.reportValidity();
} else {
  submitBtn.disabled = false ;
}
  }
  // écoute en temps réel
  MDP.addEventListener("input", checkMDP);
  MDPVerif.addEventListener("input", checkMDP);

  // switch des onglets
  btnInscription.addEventListener("click", () => {
    setMode("inscription");
    checkMDP();
  });

  btnConnexion.addEventListener("click", () => {
    setMode("connexion");
  });

  // init au chargement
  setMode(blocInscription.classList.contains("is-active") ? "inscription" : "connexion");
}

verifNewMDP();


// Gestion des cupcakes notes 

const cupcakes = document.querySelectorAll(".cupcake");
const noteInput = document.getElementById("note");
const ratingText = document.getElementById("ratingText");

let current = 0;

function paint(value) {
  cupcakes.forEach((c) => {
    const v = parseInt(c.dataset.value, 10);
    c.classList.toggle("active", v <= value);
  });
  ratingText.textContent = value > 0 ? `Note : ${value}/5` : "Clique pour noter";
}

cupcakes.forEach((cupcake) => {
  cupcake.addEventListener("mouseenter", () => {
    paint(parseInt(cupcake.dataset.value, 10));
  });

  cupcake.addEventListener("click", () => {
    current = parseInt(cupcake.dataset.value, 10);
    noteInput.value = current;
    paint(current);
  });
});

document.querySelector(".rating").addEventListener("mouseleave", () => {
  paint(current);
});