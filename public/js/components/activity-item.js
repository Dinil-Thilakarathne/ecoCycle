// Activity Item component
class ActivityItem extends HTMLElement {
  static get observedAttributes() {
    return ["action", "detail", "time"];
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
    if (this.hasAttribute("unwrap")) {
      this._render();
      const innerRoot = this.firstElementChild; // .activity-item div
      if (innerRoot) {
        this.replaceWith(innerRoot);
      }
      return; // Stop further lifecycle (no dynamic updates post-unwrap)
    }
    this._render();
  }
  attributeChangedCallback() {
    if (this._init) this._render();
  }
  _renderSkeleton() {
    this.innerHTML = `
      <div class="activity-item">
        <div class="activity-item__content">
          <p class="activity-item__title"></p>
          <p class="activity-item__subtitle"></p>
        </div>
        <p class="activity-item__time"></p>
      </div>`;
    this._els = {
      title: this.querySelector(".activity-item__title"),
      subtitle: this.querySelector(".activity-item__subtitle"),
      time: this.querySelector(".activity-item__time"),
    };
  }
  _render() {
    this._els.title.textContent = this.getAttribute("action") || "";
    this._els.subtitle.textContent = this.getAttribute("detail") || "";
    this._els.time.textContent = this.getAttribute("time") || "";
  }
}
if (!customElements.get("activity-item"))
  customElements.define("activity-item", ActivityItem);
