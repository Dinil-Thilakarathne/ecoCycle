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
      // If 'unwrap' attribute present, render once then replace this custom element with inner markup
      if (this.hasAttribute("unwrap")) {
        this._render();
        const innerRoot = this.firstElementChild; // .feature-card div
        if (innerRoot) {
          this.replaceWith(innerRoot);
        }
        return; // Stop further lifecycle (no dynamic updates post-unwrap)
      }
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
      changeWrap: this.querySelector(".feature-card__footer"),
      changeWrapTag: this.querySelector(".feature-card__footer .tag"),
      changeVal: this.querySelector(".change-val"),
      period: this.querySelector(".feature-card__period"),
    };
  }

  _render() {
    const titleAttr = (this.getAttribute("title") || "").trim();
    const valueAttr = (this.getAttribute("value") || "").trim();
    const iconAttr = (this.getAttribute("icon") || "").trim();
    const changeAttr = (this.getAttribute("change") || "").trim();
    const periodAttr = (this.getAttribute("period") || "").trim();

    // Header related elements
    const headerEl = this.querySelector(".feature-card__header");
    const iconWrapper = headerEl
      ? headerEl.querySelector(".feature-card__icon")
      : null;

    // Update / remove title
    if (titleAttr) {
      if (this._els.title) this._els.title.textContent = titleAttr;
    } else if (this._els.title) {
      this._els.title.remove();
      this._els.title = null;
    }

    // Update / remove icon (remove entire wrapper if empty)
    if (iconAttr) {
      if (this._els.icon) this._els.icon.className = iconAttr;
    } else if (iconWrapper) {
      iconWrapper.remove();
      this._els.icon = null;
    }

    // If both title and icon wrapper gone, remove header
    if (
      headerEl &&
      !this._els.title &&
      !headerEl.querySelector(".feature-card__icon")
    ) {
      headerEl.remove();
    }

    // Update / remove value body
    if (valueAttr) {
      if (this._els.value) this._els.value.textContent = valueAttr;
    } else if (this._els.value) {
      this._els.value.remove();
      this._els.value = null;
    }

    // Footer & change/period
    const footerEl = this.querySelector(".feature-card__footer");
    const periodEl = this._els.period; // span
    const tagWrapper = this._els.changeWrapTag; // .tag

    // Change value handling
    if (changeAttr) {
      if (this._els.changeVal) this._els.changeVal.textContent = changeAttr;
      if (tagWrapper) {
        const negative =
          this.hasAttribute("change-negative") || /^-/.test(changeAttr);
        tagWrapper.className = "tag " + (negative ? "danger" : "success");
        tagWrapper.style.display = "";
      }
    } else if (tagWrapper) {
      // remove the change tag if no change provided
      tagWrapper.remove();
      this._els.changeVal = null;
    }

    // Period handling (remove if empty AND attribute explicitly empty)
    if (periodAttr) {
      if (periodEl) periodEl.textContent = periodAttr;
    } else if (this.hasAttribute("period") && periodEl) {
      // attribute set but empty
      periodEl.remove();
      this._els.period = null;
    }

    // Remove footer if neither change tag nor period remains
    if (
      footerEl &&
      !footerEl.querySelector(".tag") &&
      !footerEl.querySelector(".feature-card__period")
    ) {
      footerEl.remove();
    }
  }
}

if (!customElements.get("feature-card")) {
  customElements.define("feature-card", FeatureCard);
}
