/**
 * EcoCycle - Main Application JavaScript
 */

// Sidebar responsive toggle
(function () {
  const layout = document.querySelector(".dashboard-layout");
  const sidebar = document.querySelector(".dashboard-sidebar");
  const toggleIcon = document.querySelector(
    ".content-header__title i.fa-square-caret-left"
  );
  if (!layout || !sidebar || !toggleIcon) return;

  const MOBILE_BREAKPOINT = 900; // match CSS media query

  function isMobile() {
    return window.innerWidth <= MOBILE_BREAKPOINT;
  }

  function setMobileState() {
    if (isMobile()) {
      // start collapsed on mobile
      sidebar.classList.add("is-collapsed");
      layout.classList.remove("with-collapsed-sidebar");
    } else {
      sidebar.classList.remove("is-collapsed");
    }
  }

  function toggleSidebar() {
    if (isMobile()) {
      sidebar.classList.toggle("is-collapsed");
      toggleIcon.classList.toggle(
        "sidebar-toggle-active",
        !sidebar.classList.contains("is-collapsed")
      );
      syncBackdrop();
    } else {
      // desktop: toggle collapsed class on layout (rail mode)
      const collapsed = layout.classList.toggle("with-collapsed-sidebar");
      toggleIcon.classList.toggle("sidebar-toggle-active", collapsed);
    }
  }

  // Backdrop (static element in layout)
  const backdrop = document.querySelector(".sidebar-backdrop");
  function syncBackdrop() {
    if (!backdrop) return;
    if (!isMobile()) {
      backdrop.classList.remove("is-active");
      return;
    }
    const open = !sidebar.classList.contains("is-collapsed");
    backdrop.classList.toggle("is-active", open);
  }
  if (backdrop) {
    backdrop.addEventListener("click", () => {
      if (!sidebar.classList.contains("is-collapsed")) {
        sidebar.classList.add("is-collapsed");
        toggleIcon.classList.remove("sidebar-toggle-active");
        syncBackdrop();
      }
    });
  }

  // Close when clicking outside sidebar (mobile open)
  document.addEventListener("mousedown", (e) => {
    if (!isMobile()) return;
    if (sidebar.classList.contains("is-collapsed")) return;
    if (!sidebar.contains(e.target) && e.target !== toggleIcon) {
      sidebar.classList.add("is-collapsed");
      toggleIcon.classList.remove("sidebar-toggle-active");
      syncBackdrop();
    }
  });

  // Initialize
  setMobileState();
  window.addEventListener("resize", setMobileState);
  toggleIcon.addEventListener("click", toggleSidebar);
  window.addEventListener("resize", syncBackdrop);
  syncBackdrop();
})();
