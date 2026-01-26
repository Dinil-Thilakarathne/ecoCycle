(function () {
  // Create style once
  const style = document.createElement("style");
  style.textContent = `
  #toast-container { position: fixed; right: 16px; bottom: 16px; z-index: 9999; display:flex; flex-direction:column; gap:8px; }
  .toast { background: rgba(0,0,0,0.85); color: #fff; padding: 10px 14px; border-radius: 6px; box-shadow: 0 6px 18px rgba(0,0,0,0.15); font-size: 14px; display:flex; align-items:center; gap:8px; }
  .toast.success { background: linear-gradient(90deg,#2ecc71,#27ae60); }
  .toast.error { background: linear-gradient(90deg,#e74c3c,#c0392b); }
  .toast .toast-close { margin-left: 8px; cursor: pointer; opacity: 0.9; }
  `;
  document.head.appendChild(style);

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
      message
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
