// Feature Card Web Component (no Shadow DOM to preserve existing CSS)
class FeatureCard extends HTMLElement {
  static get observedAttributes() {
    return ["title", "value", "icon", "change", "period", "change-negative"];
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
    this.innerHTML = `
      <div class="feature-card">
        <div class="feature-card__header">
          <h3 class="feature-card__title"></h3>
          <div class="feature-card__icon"><i></i></div>
        </div>
        <p class="feature-card__body"></p>
        <div class="feature-card__footer">
          <div class="tag"><span class="change-val"></span></div>
          <span class="feature-card__period"></span>
        </div>
      </div>`;
    this._els = {
      title: this.querySelector(".feature-card__title"),
      icon: this.querySelector(".feature-card__icon i"),
      value: this.querySelector(".feature-card__body"),
      changeWrap: this.querySelector(".feature-card__footer .tag"),
      changeVal: this.querySelector(".change-val"),
      period: this.querySelector(".feature-card__period"),
    };
  }

  _render() {
    const title = this.getAttribute("title") || "";
    const value = this.getAttribute("value") || "";
    const icon = this.getAttribute("icon") || "";
    const change = this.getAttribute("change") || "";
    const period = this.getAttribute("period") || "from last month";

    this._els.title.textContent = title;
    this._els.value.textContent = value;
    this._els.icon.className = icon;
    this._els.changeVal.textContent = change;
    this._els.period.textContent = period;

    // Apply positive/negative styling if your CSS uses .success / .danger
    const negative =
      this.hasAttribute("change-negative") || /^-/.test(change.trim());
    this._els.changeWrap.className = "tag " + (negative ? "danger" : "success");
  }
}

if (!customElements.get("feature-card")) {
  customElements.define("feature-card", FeatureCard);
}
