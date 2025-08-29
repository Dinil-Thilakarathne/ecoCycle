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
    }
    this._render();
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
  _render() {
    this._els.title.textContent = this.getAttribute("title") || "";
    this._els.desc.textContent = this.getAttribute("description") || "";
  }
}
if (!customElements.get("activity-card"))
  customElements.define("activity-card", ActivityCard);
