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
      var isActive = section.id === target;
      section.classList.toggle("active", isActive);
      section.style.display = isActive ? "block" : "none";
    });

    if (navLinks && navLinks.length) {
      navLinks.forEach(function (link) {
        var isActive = link.dataset.target === target;
        link.classList.toggle("active", isActive);
        link.setAttribute("aria-current", isActive ? "page" : "false");
      });
    }

    if (pageTitle) {
      pageTitle.textContent = titles[target];
    }

    closeSidebar();
    if (window && typeof window.scrollTo === "function") {
      window.scrollTo({ top: 0, behavior: "smooth" });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      if (location.hash) {
        var initial = location.hash.replace("#", "");
        goTo(titles[initial] ? initial : "overview");
      } else {
        goTo("overview");
      }
    });
  } else {
    if (location.hash) {
      var initial = location.hash.replace("#", "");
      goTo(titles[initial] ? initial : "overview");
    } else {
      goTo("overview");
    }
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

  var bookingForm = document.querySelector(".booking-form");
  if (bookingForm) {
    bookingForm.addEventListener("submit", function () {
      alert("Thank you! Our technician will contact you shortly.");
    });
  }

  if (location.hash) {
    var initial = location.hash.replace("#", "");
    if (titles[initial]) {
      goTo(initial);
    } else {
      goTo("overview");
    }
  } else {
    goTo("overview");
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
