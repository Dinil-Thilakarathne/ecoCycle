// Status Item component
class StatusItem extends HTMLElement {
  static get observedAttributes() {
    return ["label", "state", "state-class"];
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
    this.innerHTML = `
      <div class="status-item">
        <span class="status-item__label"></span>
        <div class="tag"></div>
      </div>`;
    this._els = {
      label: this.querySelector(".status-item__label"),
      tag: this.querySelector(".tag"),
    };
  }
  _render() {
    const label = this.getAttribute("label") || "";
    const state = this.getAttribute("state") || "";
    const cls = this.getAttribute("state-class") || "";
    this._els.label.textContent = label;
    this._els.tag.textContent = state;
    this._els.tag.className = "tag " + cls;
  }
}
if (!customElements.get("status-item"))
  customElements.define("status-item", StatusItem);
