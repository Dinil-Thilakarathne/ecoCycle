const ModalManager = (() => {
  let root = null;
  let dialog = null;
  let titleEl = null;
  let descriptionEl = null;
  let bodyEl = null;
  let footerEl = null;
  let closeBtn = null;
  let backdropEl = null;
  let activeConfig = null;

  const defaultOptions = {
    title: "",
    description: "",
    size: "md",
    dismissible: true,
    content: "",
    actions: [],
  };

  function ensureRoot() {
    if (root) return;
    root = document.createElement("div");
    root.className = "modal-layer";
    root.setAttribute("hidden", "hidden");

    root.innerHTML = `
      <div class="modal-layer__backdrop" data-modal-dismiss></div>
      <div class="modal-layer__dialog" role="dialog" aria-modal="true">
        <div class="modal-layer__header">
          <div class="modal-layer__titles">
            <h3 class="modal-layer__title"></h3>
            <p class="modal-layer__description"></p>
          </div>
          <button class="modal-layer__close" type="button" aria-label="Close modal">&times;</button>
        </div>
        <div class="modal-layer__body"></div>
        <div class="modal-layer__footer"></div>
      </div>`;

    dialog = root.querySelector(".modal-layer__dialog");
    titleEl = root.querySelector(".modal-layer__title");
    descriptionEl = root.querySelector(".modal-layer__description");
    bodyEl = root.querySelector(".modal-layer__body");
    footerEl = root.querySelector(".modal-layer__footer");
    closeBtn = root.querySelector(".modal-layer__close");
    backdropEl = root.querySelector("[data-modal-dismiss]");

    closeBtn.addEventListener("click", () => {
      if (activeConfig?.dismissible !== false) {
        close();
      }
    });

    backdropEl.addEventListener("click", () => {
      if (activeConfig?.dismissible !== false) {
        close();
      }
    });

    document.addEventListener("keydown", (event) => {
      if (
        event.key === "Escape" &&
        isOpen() &&
        activeConfig?.dismissible !== false
      ) {
        close();
      }
    });

    document.body.appendChild(root);
  }

  function resetFooter() {
    footerEl.innerHTML = "";
  }

  function renderContent(content) {
    bodyEl.innerHTML = "";
    if (typeof content === "string") {
      bodyEl.innerHTML = content;
      return;
    }
    if (content instanceof Node) {
      bodyEl.appendChild(content);
      return;
    }
    if (Array.isArray(content)) {
      content.forEach((node) => {
        if (node instanceof Node) {
          bodyEl.appendChild(node);
        }
      });
    }
  }

  function setButtonLoading(button, isLoading, label) {
    if (!button) return;
    if (isLoading) {
      if (!button.dataset.originalLabel) {
        button.dataset.originalLabel = button.textContent;
      }
      button.classList.add("is-loading");
      button.disabled = true;
      button.textContent = label || "Please wait...";
    } else {
      button.classList.remove("is-loading");
      button.disabled = false;
      button.textContent = button.dataset.originalLabel || button.textContent;
    }
  }

  function buildActions(actions = []) {
    resetFooter();
    if (!Array.isArray(actions) || actions.length === 0) {
      footerEl.style.display = "none";
      return;
    }

    footerEl.style.display = "flex";
    actions.forEach((action) => {
      const btn = document.createElement("button");
      btn.type = action.type || "button";
      btn.textContent = action.label || "Action";
      const variant = (action.variant || "outline").toLowerCase();
      let variantClass = "btn btn-outline";
      if (variant === "primary") {
        variantClass = "btn btn-primary";
      } else if (variant === "plain") {
        variantClass = "btn btn-outline";
      } else if (variant === "danger") {
        variantClass = "btn btn-danger";
      }
      btn.className = variantClass;

      btn.addEventListener("click", async () => {
        if (typeof action.onClick !== "function") {
          if (action.dismiss !== false) close();
          return;
        }

        const context = {
          body: bodyEl,
          footer: footerEl,
          dialog,
          close,
          setLoading: (isLoading, label) =>
            setButtonLoading(btn, isLoading, label || action.loadingLabel),
        };

        let shouldClose = action.dismiss !== false;
        try {
          const result = action.onClick(context);
          if (result instanceof Promise) {
            setButtonLoading(btn, true, action.loadingLabel);
            await result;
            setButtonLoading(btn, false);
          }
        } catch (error) {
          console.error("Modal action failed", error);
          shouldClose = false;
          setButtonLoading(btn, false);
        }

        if (shouldClose) {
          close();
        }
      });

      footerEl.appendChild(btn);
    });
  }

  function open(options = {}) {
    ensureRoot();
    activeConfig = Object.assign({}, defaultOptions, options);

    if (activeConfig.title) {
      titleEl.textContent = activeConfig.title;
      titleEl.parentElement.style.display = "";
    } else {
      titleEl.textContent = "";
      titleEl.parentElement.style.display = "none";
    }

    if (activeConfig.description) {
      descriptionEl.textContent = activeConfig.description;
      descriptionEl.style.display = "block";
    } else {
      descriptionEl.textContent = "";
      descriptionEl.style.display = "none";
    }

    dialog.dataset.size = activeConfig.size || "md";
    renderContent(activeConfig.content);
    buildActions(activeConfig.actions);

    root.classList.add("visible");
    root.removeAttribute("hidden");
    document.body.classList.add("modal-open");

    return {
      body: bodyEl,
      footer: footerEl,
      dialog,
      close,
    };
  }

  function close() {
    if (!root || !isOpen()) return;
    root.classList.remove("visible");
    root.setAttribute("hidden", "hidden");
    document.body.classList.remove("modal-open");
    activeConfig = null;
    resetFooter();
    bodyEl.innerHTML = "";
  }

  function isOpen() {
    return activeConfig !== null;
  }

  return {
    open,
    close,
    isOpen,
  };
})();

window.Modal = ModalManager;
export default ModalManager;
