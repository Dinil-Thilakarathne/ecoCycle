class PageHeader extends HTMLElement {
  static get observedAttributes() {
    return ["title", "description"];
  }

  constructor() {
    super();
    this._init = false;
  }

  connectedCallback() {
    if (!this._init) {
      this._build();
      this._init = true;
    }
    this._render();
  }

  attributeChangedCallback() {
    if (this._init) this._render();
  }

  _build() {
    // Preserve existing nested action elements (e.g., buttons) if provided by user
    const actionNodes = Array.from(
      this.querySelectorAll(
        "[data-header-action], .header-actions, button, a.btn"
      )
    );
    this.innerHTML = `
      <div class="page-header">
        <div class="page-header__content">
          <h2 class="page-header__title"></h2>
          <p class="page-header__description"></p>
        </div>
        <div class="page-header__actions" data-actions></div>
      </div>`;
    this._els = {
      title: this.querySelector(".page-header__title"),
      desc: this.querySelector(".page-header__description"),
      actions: this.querySelector("[data-actions]"),
    };
    // Re-append preserved action nodes
    actionNodes.forEach((node) => this._els.actions.appendChild(node));
    if (!actionNodes.length) {
      this._els.actions.style.display = "none";
    }
  }

  _render() {
    const titleAttr = this.getAttribute("title");
    const descAttr = this.getAttribute("description");

    if (titleAttr) this._els.title.textContent = titleAttr;
    else if (!this._els.title.textContent) this._els.title.textContent = "";

    if (descAttr) {
      this._els.desc.textContent = descAttr;
      this._els.desc.style.display = "";
    } else {
      // remove description if not provided
      this._els.desc.remove();
    }
  }
}

if (!customElements.get("page-header"))
  customElements.define("page-header", PageHeader);
