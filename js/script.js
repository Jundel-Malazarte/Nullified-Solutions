const button = document.getElementById("bookBtn");

if (button) {
  button.addEventListener("click", () => {
    alert("Thank you! Our technician will contact you shortly.");
  });
}

const menu = document.querySelector("#navMenu");
const toggle = document.querySelector(".menu-toggle");

if (menu && toggle) {
  toggle.addEventListener("click", () => {
    menu.classList.toggle("show");
    toggle.setAttribute("aria-expanded", menu.classList.contains("show"));
  });
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

  button.addEventListener("click", function () {
    field.type = field.type === "password" ? "text" : "password";
    syncIconState();
  });
}

document.querySelectorAll(".password-toggle").forEach(function (button) {
  setupPasswordToggle(button);
});
