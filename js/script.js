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

document.querySelectorAll(".password-toggle").forEach(function (toggleButton) {
  toggleButton.addEventListener("click", function () {
    const targetId = toggleButton.dataset.target;
    const field = document.getElementById(targetId);

    if (!field) {
      return;
    }

    const isHidden = field.type === "password";
    field.type = isHidden ? "text" : "password";
    toggleButton.textContent = isHidden ? "🙈" : "👁";
    toggleButton.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
  });
});
