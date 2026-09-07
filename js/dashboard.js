/* ===========================================================
   NULLIFIED SOLUTIONS — CLIENT DASHBOARD (front-end only)
   Everything here runs in the browser. There is no backend yet:
   bookings, orders and settings live in memory/localStorage
   for this session and are lost on refresh unless noted.
   Wire this up to your real API when it's ready.
=========================================================== */

(function () {
  "use strict";

  /* ---------- user (demo) ---------- */
  var user = {
    name: localStorage.getItem("ns_userName") || "Jandel Malazarte",
    email: localStorage.getItem("ns_userEmail") || "jandelmalazarte@gmail.com",
  };

  function initials(name) {
    return name
      .split(" ")
      .filter(Boolean)
      .slice(0, 2)
      .map(function (n) { return n[0].toUpperCase(); })
      .join("");
  }

  document.getElementById("userName").textContent = user.name;
  document.getElementById("userEmail").textContent = user.email;
  document.getElementById("userAvatar").textContent = initials(user.name);
  document.getElementById("settingsAvatar").textContent = initials(user.name);
  document.getElementById("settingsName").value = user.name;
  document.getElementById("settingsEmail").value = user.email;

  /* ---------- sidebar navigation ---------- */
  var sections = document.querySelectorAll(".dash-section");
  var navLinks = document.querySelectorAll(".sidebar-nav a");
  var pageTitle = document.getElementById("pageTitle");
  var sidebar = document.getElementById("sidebar");
  var backdrop = document.getElementById("sidebarBackdrop");

  var titles = {
    overview: "Dashboard",
    book: "Book a Repair",
    bookings: "My Bookings",
    premium: "Premium Accounts",
    software: "Software Store",
    settings: "Account Settings",
  };

  function goTo(target) {
    if (!titles[target]) return;

    sections.forEach(function (s) {
      s.classList.toggle("active", s.id === target);
    });
    navLinks.forEach(function (a) {
      a.classList.toggle("active", a.dataset.target === target);
    });
    pageTitle.textContent = titles[target];
    closeSidebar();
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  navLinks.forEach(function (a) {
    a.addEventListener("click", function (e) {
      e.preventDefault();
      goTo(a.dataset.target);
    });
  });

  document.querySelectorAll(".nav-jump").forEach(function (btn) {
    btn.addEventListener("click", function () {
      goTo(btn.dataset.target);
    });
  });

  /* handle deep-link like dashboard.html#book */
  if (location.hash) {
    var initial = location.hash.replace("#", "");
    if (titles[initial]) goTo(initial);
  }

  /* ---------- mobile sidebar toggle ---------- */
  function openSidebar() {
    sidebar.classList.add("open");
    backdrop.classList.add("show");
  }
  function closeSidebar() {
    sidebar.classList.remove("open");
    backdrop.classList.remove("show");
  }
  document.getElementById("sidebarToggle").addEventListener("click", function () {
    sidebar.classList.contains("open") ? closeSidebar() : openSidebar();
  });
  backdrop.addEventListener("click", closeSidebar);

  /* ---------- toast helper ---------- */
  var toast = document.getElementById("dashToast");
  var toastText = document.getElementById("dashToastText");
  var toastTimer;
  function showToast(message) {
    toastText.textContent = message;
    toast.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () {
      toast.classList.remove("show");
    }, 2800);
  }

  /* ---------- bookings data (in memory for this session) ---------- */
  var bookingSeed = [
    { id: "NS-1042", device: "Laptop — ASUS Vivobook", issue: "Screen Replacement", date: "2026-09-10", status: "confirmed" },
    { id: "NS-1039", device: "Phone — Samsung A54", issue: "Battery Replacement", date: "2026-09-05", status: "progress" },
    { id: "NS-1021", device: "Laptop — Lenovo IdeaPad", issue: "Virus Removal", date: "2026-08-22", status: "completed" },
  ];
  var bookings = bookingSeed.slice();
  var bookingCounter = 1043;

  var statusLabel = {
    pending: "Pending",
    confirmed: "Confirmed",
    progress: "In Progress",
    completed: "Completed",
    cancelled: "Cancelled",
  };

  function pill(status) {
    return '<span class="status-pill status-' + status + '">' + statusLabel[status] + "</span>";
  }

  function renderBookings() {
    var body = document.getElementById("bookingsBody");
    var recent = document.getElementById("recentActivityBody");
    body.innerHTML = "";
    recent.innerHTML = "";

    bookings.forEach(function (b) {
      var canCancel = b.status === "pending" || b.status === "confirmed";
      var row = document.createElement("tr");
      row.innerHTML =
        "<td>" + b.id + "</td>" +
        "<td>" + b.device + "</td>" +
        "<td>" + b.issue + "</td>" +
        "<td>" + b.date + "</td>" +
        "<td>" + pill(b.status) + "</td>" +
        "<td>" + (canCancel
          ? '<button type="button" class="row-btn cancel" data-id="' + b.id + '">Cancel</button>'
          : "—") + "</td>";
      body.appendChild(row);
    });

    bookings.slice(0, 4).forEach(function (b) {
      var row = document.createElement("tr");
      row.innerHTML =
        "<td>" + b.id + "</td>" +
        "<td>" + b.device + "</td>" +
        "<td>" + b.issue + "</td>" +
        "<td>" + b.date + "</td>" +
        "<td>" + pill(b.status) + "</td>";
      recent.appendChild(row);
    });

    body.querySelectorAll(".row-btn.cancel").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var id = btn.dataset.id;
        var booking = bookings.find(function (b) { return b.id === id; });
        if (booking && confirm("Cancel booking " + id + "?")) {
          booking.status = "cancelled";
          renderBookings();
          updateStats();
          showToast("Booking " + id + " cancelled.");
        }
      });
    });
  }

  function updateStats() {
    var active = bookings.filter(function (b) { return b.status === "pending" || b.status === "confirmed" || b.status === "progress"; }).length;
    var completed = bookings.filter(function (b) { return b.status === "completed"; }).length;
    document.getElementById("statActive").textContent = active;
    document.getElementById("statCompleted").textContent = completed;
  }

  renderBookings();
  updateStats();

  /* ---------- booking form ---------- */
  document.getElementById("bookingForm").addEventListener("submit", function (e) {
    e.preventDefault();
    var form = e.target;
    var device = form.deviceType.value + " — " + form.model.value;

    bookingCounter += 1;
    bookings.unshift({
      id: "NS-" + bookingCounter,
      device: device,
      issue: form.issueType.value,
      date: form.date.value || "TBD",
      status: "pending",
    });

    renderBookings();
    updateStats();
    form.reset();
    showToast("Booking request sent — we'll confirm your appointment shortly.");
    goTo("bookings");
  });

  /* ---------- store buy buttons (premium accounts + software) ---------- */
  document.querySelectorAll(".buy-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var item = btn.dataset.item;
      showToast(item + " added — we'll include this with your next booking.");
    });
  });

  /* ---------- settings form ---------- */
  document.getElementById("settingsForm").addEventListener("submit", function (e) {
    e.preventDefault();
    var form = e.target;
    var name = form.fullName.value.trim() || user.name;
    var email = form.email.value.trim() || user.email;

    localStorage.setItem("ns_userName", name);
    localStorage.setItem("ns_userEmail", email);

    document.getElementById("userName").textContent = name;
    document.getElementById("userEmail").textContent = email;
    document.getElementById("userAvatar").textContent = initials(name);
    document.getElementById("settingsAvatar").textContent = initials(name);

    form.password.value = "";
    showToast("Account settings saved.");
  });

  /* ---------- logout ---------- */
  function logout() {
    if (!confirm("Log out of your Nullified Solutions account?")) return;
    localStorage.removeItem("ns_userName");
    localStorage.removeItem("ns_userEmail");
    window.location.href = "login.html";
  }
  document.getElementById("logoutBtn").addEventListener("click", logout);
  document.getElementById("logoutBtn2").addEventListener("click", logout);
})();