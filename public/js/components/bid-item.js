// Bid Item Web Component
// Renders a bid summary styled like a feature card.
// Attributes:
//  - type: Waste type label (required)
//  - bid: Bid amount string (required)
//  - amount: Waste amount (e.g., "2,500 kg") (required)
//  - status: Status text (e.g., Active, Pending, Closed) (optional)
//  - status-class: Extra class applied to status span to control color (optional)
//  - unwrap: (boolean) If present, the custom element is replaced by its inner markup after initial render
class BidItem extends HTMLElement {
  static get observedAttributes() {
    return ["type", "bid", "amount", "status", "status-class"];
  }

  constructor() {
    super();
    this._initialized = false;
  }

  connectedCallback() {
    if (!this._initialized) {
      this._renderSkeleton();
      this._initialized = true;
      if (this.hasAttribute("unwrap")) {
        this._render();
        const innerRoot = this.firstElementChild; // .feature-card (with bid-item class)
        if (innerRoot) this.replaceWith(innerRoot);
        return;
      }
    }
    this._render();
  }

  attributeChangedCallback() {
    if (this._initialized) this._render();
  }

  _renderSkeleton() {
    // Use feature-card base class to inherit existing styling
    this.innerHTML = `
      <div class="feature-card">
        <div class="bid-header">
          <span class="waste-type" data-el="type"></span>
          <span class="tag" data-el="status"></span>
          </div>
          <div class="bid-details">
          <span class="bid-amount-total" data-el="amount"></span>
          <span class="bid-amount" data-el="bid"></span>
        </div>
      </div>`;
    this._els = {
      type: this.querySelector('[data-el="type"]'),
      bid: this.querySelector('[data-el="bid"]'),
      amount: this.querySelector('[data-el="amount"]'),
      status: this.querySelector('[data-el="status"]'),
      statusWrapper: this.querySelector(".status"),
    };
  }

  _render() {
    const typeAttr = (this.getAttribute("type") || "").trim();
    const bidAttr = (this.getAttribute("bid") || "").trim();
    const amountAttr = (this.getAttribute("amount") || "").trim();
    const statusAttr = (this.getAttribute("status") || "").trim();
    const statusClass = (this.getAttribute("status-class") || "").trim();

    // Basic assignments
    if (this._els.type) this._els.type.textContent = typeAttr;
    if (this._els.bid) this._els.bid.textContent = bidAttr;
    if (this._els.amount) this._els.amount.textContent = amountAttr;

    if (statusAttr) {
      if (this._els.status) this._els.status.textContent = statusAttr;
      // Map status to a color class if no explicit status-class given
      const autoClass = this._mapStatusToClass(statusAttr);
      this._els.status.className = `tag ${statusClass || autoClass}`.trim();
      this._els.status.style.display = "";
    } else if (this._els.status) {
      this._els.status.textContent = "";
      this._els.status.style.display = "none";
    }
  }

  _mapStatusToClass(status) {
    const s = status.toLowerCase();
    if (s.includes("active")) return "success"; // greenish
    if (s.includes("pending")) return "warning";
    if (s.includes("closed") || s.includes("expired") || s.includes("cancel"))
      return "danger";
    return "secondary";
  }
}

if (!customElements.get("bid-item")) {
  customElements.define("bid-item", BidItem);
}
