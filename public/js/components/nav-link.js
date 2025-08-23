// Very simple Nav Link component (lite version)
// Goals: minimal logic, just wraps provided children in an <a>, supports href + active class + optional auto-active.
// Usage examples:
//   <nav-link href="/admin">
//     <i class="fa-solid fa-gauge"></i>
//     Dashboard
//   </nav-link>
//   <nav-link href="/reports" active>
//     <i class="fa-solid fa-chart-line"></i>
//     Reports
//   </nav-link>
//   <nav-link href="/settings" auto-active>
//     <span>⚙️</span>
//     Settings
//   </nav-link>
// Features:
//  - Moves all child nodes into an anchor tag.
//  - href attribute sets link target (defaults to '#').
//  - 'active' attribute adds .active class.
//  - 'auto-active' attribute: if current location starts with href (and href !== '#'), adds .active automatically.
//  - Updating 'href' or toggling 'active' later updates anchor.
// Deliberately omits: badge support, label attribute, icon attribute, complex child parsing.

class NavLink extends HTMLElement {
  static get observedAttributes() {
    return ["href", "active"];
  }

  constructor() {
    super();
    this._built = false;
  }

  connectedCallback() {
    if (!this._built) {
      this._build();
      this._built = true;
    }
    this._applyState();
  }

  attributeChangedCallback() {
    if (this._built) this._applyState();
  }

  _build() {
    // Create anchor and move existing nodes inside
    const a = document.createElement("a");
    a.className = "nav-link"; // rely on existing CSS (e.g., .nav-link styles already in project)
    const childNodes = Array.from(this.childNodes);
    childNodes.forEach((n) => a.appendChild(n));
    this.appendChild(a);
    this._a = a;
  }

  _applyState() {
    const href = this.getAttribute("href") || "#";
    this._a.setAttribute("href", href);

    // Active state from attribute
    const hasActive = this.hasAttribute("active");
    this._a.classList.toggle("active", hasActive);

    // Auto-active logic (optional): if element has auto-active attr and path matches
    if (this.hasAttribute("auto-active") && href && href !== "#") {
      try {
        const path = window.location.pathname.replace(/\/$/, "");
        const hrefPath = href.replace(/\/$/, "");
        if (path.startsWith(hrefPath)) {
          this._a.classList.add("active");
        }
      } catch (_) {
        /* ignore */
      }
    }
  }
}

if (!customElements.get("nav-link"))
  customElements.define("nav-link", NavLink);
