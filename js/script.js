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
