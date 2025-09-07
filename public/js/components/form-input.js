class FormInput extends HTMLElement {
  static get observedAttributes() {
    return [
      "label",
      "name",
      "type",
      "value",
      "placeholder",
      "required",
      "help",
      "error",
      "disabled",
      "class",
    ];
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
      <div class="form-input">
        <label class="form-input__label"></label>
        <div class="form-input__control">
          <input class="form-input__field" />
          <slot name="after"></slot>
        </div>
        <p class="form-input__help" aria-hidden="true"></p>
        <p class="form-input__error" aria-hidden="true"></p>
      </div>
    `;

    this._els = {
      wrapper: this.querySelector(".form-input"),
      label: this.querySelector(".form-input__label"),
      input: this.querySelector(".form-input__field"),
      help: this.querySelector(".form-input__help"),
      error: this.querySelector(".form-input__error"),
    };

    // propagate native input events as custom events
    this._els.input.addEventListener("input", (e) => {
      this.dispatchEvent(
        new CustomEvent("value-changed", {
          detail: { value: e.target.value },
          bubbles: true,
        })
      );
    });
  }

  _render() {
    const label = this.getAttribute("label") || "";
    const name = this.getAttribute("name") || "";
    const type = this.getAttribute("type") || "text";
    const value = this.getAttribute("value") || "";
    const placeholder = this.getAttribute("placeholder") || "";
    const required = this.hasAttribute("required");
    const help = this.getAttribute("help") || "";
    const error = this.getAttribute("error") || "";
    const disabled = this.hasAttribute("disabled");

    if (label) {
      this._els.label.textContent = label;
      this._els.label.style.display = "";
    } else {
      this._els.label.style.display = "none";
    }

    this._els.input.type = type;
    this._els.input.name = name;
    this._els.input.placeholder = placeholder;
    this._els.input.value = value;
    this._els.input.disabled = disabled;
    this._els.input.required = required;

    // accessibility
    if (name) {
      const id = `input_${name}`;
      this._els.input.id = id;
      this._els.label.htmlFor = id;
    }

    // help and error text
    if (help) {
      this._els.help.textContent = help;
      this._els.help.style.display = "";
    } else {
      this._els.help.style.display = "none";
    }

    if (error) {
      this._els.error.textContent = error;
      this._els.error.style.display = "";
      this._els.wrapper.classList.add("has-error");
      this._els.input.setAttribute("aria-invalid", "true");
    } else {
      this._els.error.style.display = "none";
      this._els.wrapper.classList.remove("has-error");
      this._els.input.removeAttribute("aria-invalid");
    }

    // allow passing classes via attribute
    const extra = this.getAttribute("class") || "";
    this._els.wrapper.className = `form-input ${extra}`.trim();
  }

  // public API
  get value() {
    return this._els.input.value;
  }

  set value(v) {
    this.setAttribute("value", v);
    if (this._els && this._els.input) this._els.input.value = v;
  }

  focus() {
    this._els.input.focus();
  }
}

if (!customElements.get("form-input")) {
  customElements.define("form-input", FormInput);
}
