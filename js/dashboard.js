(function () {
  "use strict";

  var sections = document.querySelectorAll(".dash-section");
  var navLinks = document.querySelectorAll(".sidebar-nav a");
  var pageTitle = document.getElementById("pageTitle");
  var sidebar = document.getElementById("sidebar");
  var backdrop = document.getElementById("sidebarBackdrop");
  var toggleButton = document.getElementById("sidebarToggle");

  var titles = {
    overview: "Dashboard",
    book: "Book a Repair",
    bookings: "My Bookings",
    pricing: "Repair Pricing",
    premium: "Premium Accounts",
    software: "Software Store",
    payments: "Payments",
    settings: "Account Settings",
  };

  function closeSidebar() {
    if (sidebar) {
      sidebar.classList.remove("open");
    }
    if (backdrop) {
      backdrop.classList.remove("show");
    }
  }

  function goTo(target) {
    if (!titles[target]) return;

    sections.forEach(function (section) {
      section.classList.toggle("active", section.id === target);
    });

    navLinks.forEach(function (link) {
      link.classList.toggle("active", link.dataset.target === target);
    });

    if (pageTitle) {
      pageTitle.textContent = titles[target];
    }

    closeSidebar();
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  navLinks.forEach(function (link) {
    link.addEventListener("click", function (event) {
      event.preventDefault();
      goTo(link.dataset.target);
    });
  });

  document.querySelectorAll(".nav-jump").forEach(function (button) {
    button.addEventListener("click", function () {
      goTo(button.dataset.target);
    });
  });

  if (location.hash) {
    var initial = location.hash.replace("#", "");
    if (titles[initial]) {
      goTo(initial);
    }
  }

  if (toggleButton && sidebar && backdrop) {
    toggleButton.addEventListener("click", function () {
      sidebar.classList.toggle("open");
      backdrop.classList.toggle("show");
    });

    backdrop.addEventListener("click", closeSidebar);
  }

  var menu = document.querySelector("#navMenu");
  var menuToggle = document.querySelector(".menu-toggle");
  if (menu && menuToggle) {
    menuToggle.addEventListener("click", function () {
      menu.classList.toggle("show");
      menuToggle.setAttribute("aria-expanded", menu.classList.contains("show"));
    });
  }
})();
