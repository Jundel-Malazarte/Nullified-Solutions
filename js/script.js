const button = document.getElementById("bookBtn");

if (button) {
  button.addEventListener("click", () => {
    alert("Thank you! Our technician will contact you shortly.");
  });
}

function initializeHeaderReveal() {
  const header = document.querySelector(".site-header");

  if (!header) {
    return;
  }

  requestAnimationFrame(function () {
    header.classList.add("is-ready");
  });
}

function initializeMenuToggle() {
  const menu = document.querySelector("#navMenu");
  const toggle = document.querySelector(".menu-toggle");

  if (menu && toggle) {
    toggle.addEventListener("click", () => {
      menu.classList.toggle("show");
      toggle.setAttribute("aria-expanded", menu.classList.contains("show"));
    });
  }
}

function initializeTypingAnimation() {
  const typingTarget = document.querySelector(".typing-text");

  if (!typingTarget) {
    return;
  }

  const phrases = (typingTarget.dataset.text || "Computer & Phone Repair|Laptop & Tablet Repair|Fast Tech Repair")
    .split("|")
    .map(function (phrase) {
      return phrase.trim();
    })
    .filter(Boolean);

  let phraseIndex = 0;
  let letterIndex = 0;
  let isDeleting = false;

  typingTarget.textContent = "";

  const typeLoop = function () {
    const currentPhrase = phrases[phraseIndex] || phrases[0];

    if (!isDeleting) {
      letterIndex += 1;
      typingTarget.textContent = currentPhrase.slice(0, letterIndex);

      if (letterIndex < currentPhrase.length) {
        setTimeout(typeLoop, 110);
        return;
      }

      isDeleting = true;
      setTimeout(typeLoop, 1200);
      return;
    }

    letterIndex -= 1;
    typingTarget.textContent = currentPhrase.slice(0, letterIndex);

    if (letterIndex > 0) {
      setTimeout(typeLoop, 80);
      return;
    }

    isDeleting = false;
    phraseIndex = (phraseIndex + 1) % phrases.length;
    typingTarget.textContent = "";
    letterIndex = 0;
    setTimeout(typeLoop, 180);
  };

  setTimeout(typeLoop, 250);
}

function setupPasswordToggle(button) {
  const targetId = button.dataset.target;
  const field = document.getElementById(targetId);
  const icon = button.querySelector("i");

  if (!field || !button || !icon) {
    return;
  }

  const syncIconState = function () {
    const isPasswordHidden = field.type === "password";
    icon.classList.remove("fa-eye", "fa-eye-slash");
    icon.classList.add(isPasswordHidden ? "fa-eye" : "fa-eye-slash");
    button.setAttribute("aria-label", isPasswordHidden ? "Show password" : "Hide password");
  };

  syncIconState();

  button.addEventListener("click", function (event) {
    event.preventDefault();
    event.stopPropagation();
    field.type = field.type === "password" ? "text" : "password";
    syncIconState();
  });
}

document.addEventListener("DOMContentLoaded", function () {
  initializeHeaderReveal();
  initializeMenuToggle();
  initializeTypingAnimation();

  document.querySelectorAll(".password-toggle").forEach(function (button) {
    setupPasswordToggle(button);
  });
});
