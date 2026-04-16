(function () {
  // Toast styles are now imported via CSS
  const container = document.createElement("div");
  container.id = "toast-container";
  document.body.appendChild(container);

  window.__createToast = function (message, type = "info", timeout = 4000) {
    const t = document.createElement("div");
    t.className =
      "toast " +
      (type === "success" ? "success" : type === "error" ? "error" : "");
    t.setAttribute("role", "status");
    t.innerHTML = `<div class="toast-message">${String(
      message,
    )}</div><div class="toast-close">&times;</div>`;
    container.appendChild(t);

    const remove = () => {
      t.style.transition = "opacity 200ms";
      t.style.opacity = "0";
      setTimeout(() => t.remove(), 220);
    };
    const closeBtn = t.querySelector(".toast-close");
    if (closeBtn) closeBtn.addEventListener("click", remove);

    setTimeout(remove, timeout);
    return t;
  };

  // Alias for easier usage
  window.toast = window.__createToast;
})();
