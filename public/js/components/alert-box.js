// Alert Box Web Component (no Shadow DOM to preserve existing global CSS)
// Follows project pattern: observedAttributes, _renderSkeleton, preserve children
class AlertBox extends HTMLElement {
  static get observedAttributes() {
    return ["type", "dismissible", "title"];
  }

  constructor() {
    super();
    this._initialized = false;
  }

  connectedCallback() {
    if (!this._initialized) {
      this._renderSkeleton();
      this._initialized = true;
    }
    this._render();
  }

  attributeChangedCallback() {
    if (this._initialized) this._render();
  }

  _renderSkeleton() {
    // Preserve any existing child nodes (e.g., custom markup passed in)
    const preserved = Array.from(this.childNodes);

    this.innerHTML = `
      <div class="alert-box" role="alert">
        <div class="alert-box__inner">
          <div class="alert-box__content">
            <strong class="alert-box__title"></strong>
            <div class="alert-box__message" data-message></div>
          </div>
          <div class="alert-box__actions" data-actions></div>
        </div>
      </div>`;

    this._els = {
      root: this.querySelector(".alert-box"),
      title: this.querySelector(".alert-box__title"),
      message: this.querySelector("[data-message]"),
      actions: this.querySelector("[data-actions]"),
    };

    // If the author passed inline children, move them into message or actions.
    // If a child has attribute "action" or class "alert-action", treat it as an action control.
    preserved.forEach((node) => {
      if (node.nodeType === Node.ELEMENT_NODE) {
        const el = /** @type {Element} */ (node);
        if (
          el.hasAttribute("action") ||
          el.classList.contains("alert-action")
        ) {
          this._els.actions.appendChild(node);
        } else {
          this._els.message.appendChild(node);
        }
      } else if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) {
        // Plain text goes into the message
        this._els.message.appendChild(node);
      }
    });

    // Hide actions container if empty
    if (!this._els.actions.children.length) {
      this._els.actions.style.display = "none";
    }
  }

  _render() {
    const type = this.getAttribute("type") || "info";
    const title = this.getAttribute("title") || "";
    const dismissible = this.hasAttribute("dismissible");

    // Apply type class (e.g., info, success, warning, danger)
    this._els.root.className = "alert-box alert-box--" + type;

    // Title
    if (title) {
      this._els.title.textContent = title;
      this._els.title.style.display = "";
    } else {
      this._els.title.textContent = "";
      this._els.title.style.display = "none";
    }

    // Dismissible
    if (dismissible) {
      // Add a close button if not already provided
      if (!this._els.actions.querySelector(".alert-box__close")) {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "icon-button alert-box__close";
        btn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        btn.addEventListener("click", () => this._handleClose());
        // show actions container and append close button
        this._els.actions.style.display = "";
        this._els.actions.appendChild(btn);
      }
    } else {
      // If not dismissible, remove close button if present
      const close = this._els.actions.querySelector(".alert-box__close");
      if (close) close.remove();
      if (!this._els.actions.children.length)
        this._els.actions.style.display = "none";
    }
  }

  _handleClose() {
    // Simple hide for now; could dispatch event for parent to remove
    this.style.display = "none";
    this.dispatchEvent(new CustomEvent("alert:closed", { bubbles: true }));
  }
}

if (!customElements.get("alert-box")) {
  customElements.define("alert-box", AlertBox);
}
