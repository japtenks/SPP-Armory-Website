// modern.js
document.addEventListener("DOMContentLoaded", () => {
  // === Account Dropdown ===
  const accDrop = document.querySelector(".account-dropdown");
  const accBtn = accDrop?.querySelector("a");
  accBtn?.addEventListener("click", e => {
    e.preventDefault();
    accDrop.classList.toggle("open");
  });
  document.addEventListener("click", e => {
    if (accDrop && !accDrop.contains(e.target)) accDrop.classList.remove("open");
  });

  // === Mobile Menu ===
  const menuBtn  = document.querySelector(".mobile-toggle");
  const menu     = document.querySelector(".mobile-menu");
  const overlay  = document.querySelector(".menu-overlay");
  const closeBtn = document.querySelector(".menu-close");

  const toggleMenu = () => {
    menu.classList.toggle("open");
    overlay.classList.toggle("active");
  };

  menuBtn?.addEventListener("click", e => {
    e.stopPropagation();
    toggleMenu();
  });

  overlay?.addEventListener("click", () => {
    menu.classList.remove("open");
    overlay.classList.remove("active");
  });

  closeBtn?.addEventListener("click", () => {
    menu.classList.remove("open");
    overlay.classList.remove("active");
  });

  // === Accordion submenus in mobile ===
  menu?.querySelectorAll("li").forEach(li => {
    const link = li.querySelector("a");
    const sub  = li.querySelector("ul");
    if (sub && link) {
      li.classList.add("has-sub");
      link.addEventListener("click", e => {
        e.preventDefault();
        li.classList.toggle("open");
      });
    }
  });

  // === Tooltip container ===
  const tooltip = document.getElementById("tooltip");
  const tooltipText = document.getElementById("tooltiptext");
  if (tooltip && tooltipText) {
    document.querySelectorAll("[data-tooltip]").forEach(el => {
      el.addEventListener("mouseenter", () => {
        tooltipText.textContent = el.dataset.tooltip;
        tooltip.style.display = "block";
      });
      el.addEventListener("mousemove", e => {
        tooltip.style.left = e.pageX + 10 + "px";
        tooltip.style.top = e.pageY + 10 + "px";
      });
      el.addEventListener("mouseleave", () => {
        tooltip.style.display = "none";
      });
    });
  }
});
