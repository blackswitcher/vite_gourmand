/////////////////////////////////////////////////////////////////////////////
//                          MODAL MENU                                     //
/////////////////////////////////////////////////////////////////////////////

//mes variables
const ouvrirModalMenu = document.getElementById("ouvrirModalMenu");
const modalMenu = document.getElementById("modalMenu");
const fermerModalMenu = document.getElementById("fermerModalMenu");

// on vérifie que les 3 éléments existent bien avant d'ajouter les événements
if (ouvrirModalMenu && modalMenu && fermerModalMenu) {
  // clic sur le bouton "Ajouter un menu"
  // on affiche le modal en retirant la classe hidden
  ouvrirModalMenu.addEventListener("click", function () {
    modalMenu.classList.remove("hidden");
  });

  // clic sur le bouton X on referme le modal
  fermerModalMenu.addEventListener("click", function () {
    modalMenu.classList.add("hidden");
  });

  // clic sur le fond sombre du modal
  // si on clique en dehors de la fenêtre, on ferme aussi
  modalMenu.addEventListener("click", function (event) {
    if (event.target === modalMenu) {
      modalMenu.classList.add("hidden");
    }
  });
}


/////////////////////////////////////////////////////////////////////////////
//                              MODAL AVIS                                 //
/////////////////////////////////////////////////////////////////////////////

const boutonAvis = document.querySelectorAll('.btnAvis');
const modalAvis = document.getElementById('modalAvis');
const fermerModalAvis = document.getElementById('fermerModalAvis');
const commandeIdAvis = document.getElementById('commande_id_avis');

// je parcourt les boutons avis 
boutonAvis.forEach((bouton) => {
  bouton.addEventListener('click', () =>{
    // je vais lire l'id de la commande que j'ai stocker ds data-commande
    const commandeId = bouton.dataset.commandeId;

    commandeIdAvis.value = commandeId;

    // j'affiche mon modal 
    modalAvis.classList.remove('hidden');
  });
});

// je gere ma fermeture du modal ici 
if (fermerModalAvis && modalAvis) {
  fermerModalAvis.addEventListener('click', () => {
    modalAvis.classList.add('hidden');
  });
}

//////////////////////////////////////////////////////////////////////////////
//                              MODAL EMPLOYER                              //
//////////////////////////////////////////////////////////////////////////////


const boutonOuvrir = document.getElementById('ouvrirModalEmploye');
const boutonFermer = document.getElementById('fermerModalEmploye');
const modalEmploye = document.getElementById('modalEmploye');


if (boutonOuvrir && boutonFermer && modalEmploye){
  boutonOuvrir.addEventListener('click', function(){
    modalEmploye.classList.remove('hidden');
  });

  boutonFermer.addEventListener('click', function() {
    modalEmploye.classList.add('hidden');
  });
}






//------------------------------------ GESTION MODAL MAP ---------------------
function initMapModal(){

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
}

initMapModal();

// gestion de la fonction Prix
function initPrix(){

const prixMaxInput = document.getElementById("prixMax");
const prixMaxValue = document.getElementById("prixMaxValue");

// il s'agit de ma value de départ
prixMaxValue.textContent = prixMaxInput.value;

//mise a jour en temps reel

prixMaxInput.addEventListener("input", () => {
  prixMaxValue.textContent = prixMaxInput.value;
});
}
initMapModal();

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
  const actionForm = document.getElementById("actionForm");

  if (btnConnexion && btnInscription && ongletInscription) {
    btnInscription.addEventListener("click", () => {
      ongletInscription.classList.remove("hidden");
      ongletInscription.classList.add("is-active");

      if(actionForm) {
        actionForm.value="inscription";
      }
    });

    btnConnexion.addEventListener("click", () => {
      ongletInscription.classList.remove("is-active");
      ongletInscription.classList.add("hidden");

      if(actionForm) {
        actionForm.value="connexion";
      }

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
}
  // vérification des mots de passe + confirmation
  function checkMDP(){
    const isInscription = blocInscription.classList.contains("is-active");
    const mdpValue = MDP.value.trim();
    const mdpVerifValue = MDPVerif.value.trim();

    if(!isInscription){
      MDPVerif.setCustomValidity("");
      submitBtn.disabled=false;
      return;
    }
    if(mdpVerifValue.length === 0) {
      MDPVerif.setCustomValidity("");
      submitBtn.disabled =false;
      return;
    }
    if(mdpValue !==mdpVerifValue){
      MDPVerif.setCustomValidity("les mots de passe ne sont pas identiques");
      submitBtn.disabled = true;
    } else {
      MDPVerif.setCustomValidity("");
      submitBtn.disabled = false;
    }
  }
checkMDP();

// Gestion des cupcakes notes 

const cupcakes = document.querySelectorAll(".cupcake");
const noteInput = document.getElementById("note");
const ratingText = document.getElementById("ratingText");
const rating = document.querySelector(".rating");
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

const ratingElement = document.querySelector(".rating");

if (ratingElement){
  ratingElement.addEventListener("mouseleave", () => {
    paint(current);
  });
}


