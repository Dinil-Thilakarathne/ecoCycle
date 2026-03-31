// Activity Card Web Component (no Shadow DOM to keep existing CSS)
class ActivityCard extends HTMLElement {
  static get observedAttributes() {
    return ["title", "description"];
  }
  constructor() {
    super();
    this._init = false;
  }
  connectedCallback() {
    if (!this._init) {
      this._renderSkeleton();
      this._init = true;
      if (this.hasAttribute("unwrap")) {
        this.unwrap();
        return;
      }
    }
    this._render();
  }

  unwrap() {
    this._render();
    const innerRoot = this.firstElementChild;
    if (innerRoot) {
      this.replaceWith(innerRoot);
    }
  }
  attributeChangedCallback() {
    if (this._init) this._render();
  }
  _renderSkeleton() {
    // Preserve any existing child nodes (e.g., <activity-item>, <status-item>)
    const preservedChildren = Array.from(this.childNodes);

    this.innerHTML = `
      <div class="activity-card">
        <div class="activity-card__header">
          <h3 class="activity-card__title"></h3>
          <p class="activity-card__description"></p>
        </div>
        <div class="activity-card__content" data-items></div>
      </div>`;

    this._els = {
      title: this.querySelector(".activity-card__title"),
      desc: this.querySelector(".activity-card__description"),
      items: this.querySelector("[data-items]"),
    };

    // Re-append preserved children into the items container
    preservedChildren.forEach((node) => this._els.items.appendChild(node));
  }
  // ...existing code...
  _render() {
    const titleAttr = (this.getAttribute("title") || "").trim();
    const descAttr = (this.getAttribute("description") || "").trim();

    // Current element refs from skeleton (may become null after removal)
    let titleEl = this._els.title;
    let descEl = this._els.desc;
    const headerEl = this.querySelector(".activity-card__header");

    // Update / remove title
    if (titleAttr) {
      if (titleEl) titleEl.textContent = titleAttr;
    } else if (titleEl) {
      titleEl.remove();
      this._els.title = null;
      titleEl = null;
    }

    // Update / remove description
    if (descAttr) {
      if (descEl) descEl.textContent = descAttr;
    } else if (descEl) {
      descEl.remove();
      this._els.desc = null;
      descEl = null;
    }

    // Remove header if both absent
    if (headerEl && !titleAttr && !descAttr) {
      headerEl.remove();
    }

    // Optionally remove empty items container (kept from your previous logic)
    if (this._els.items && this._els.items.childNodes.length === 0) {
      this._els.items.remove();
      this._els.items = null;
    }
  }
}
if (!customElements.get("activity-card"))
  customElements.define("activity-card", ActivityCard);
