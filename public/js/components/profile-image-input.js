class ProfileImageInput extends HTMLElement {
  static get observedAttributes() {
    return ["label", "name", "help", "default-src", "value", "required"];
  }

  constructor() {
    super();
    this._initialized = false;
    this._defaultPreview = "";
  }

  connectedCallback() {
    if (!this._initialized) {
      this._renderSkeleton();
      this._bindEvents();
      this._initialized = true;
    }
    this._syncFromAttributes();
  }

  attributeChangedCallback() {
    if (this._initialized) {
      this._syncFromAttributes();
    }
  }

  _renderSkeleton() {
    this.innerHTML = `
      <div class="profile-image-input">
        <label class="profile-image-input__label"></label>
        <div class="profile-image-input__body">
          <div class="profile-image-input__preview" aria-live="polite">
            <img class="profile-image-input__img" alt="Profile photo preview" />
          </div>
          <div class="profile-image-input__actions">
            <button type="button" class="btn btn-outline profile-image-input__trigger">Choose photo</button>
            <button type="button" class="profile-image-input__clear" aria-label="Remove selected photo">Remove</button>
          </div>
          <input class="profile-image-input__file" type="file" accept="image/*" />
        </div>
        <p class="profile-image-input__help" aria-hidden="true"></p>
      </div>
    `;

    this._els = {
      label: this.querySelector(".profile-image-input__label"),
      img: this.querySelector(".profile-image-input__img"),
      trigger: this.querySelector(".profile-image-input__trigger"),
      clear: this.querySelector(".profile-image-input__clear"),
      file: this.querySelector(".profile-image-input__file"),
      help: this.querySelector(".profile-image-input__help"),
    };
  }

  _bindEvents() {
    this._els.trigger.addEventListener("click", () => {
      this._els.file.click();
    });

    this._els.clear.addEventListener("click", () => {
      this._els.file.value = "";
      this._updatePreview(this._defaultPreview);
      this.dispatchEvent(
        new CustomEvent("file-cleared", {
          bubbles: true,
        })
      );
    });

    this._els.file.addEventListener("change", (event) => {
      const file = event.target.files && event.target.files[0];
      if (!file) {
        this._updatePreview(this._defaultPreview);
        return;
      }

      const reader = new FileReader();
      reader.onload = () => {
        this._updatePreview(reader.result);
      };
      reader.readAsDataURL(file);

      this.dispatchEvent(
        new CustomEvent("file-selected", {
          detail: { file },
          bubbles: true,
        })
      );
    });
  }

  _syncFromAttributes() {
    const label = this.getAttribute("label") || "Profile photo";
    const name = this.getAttribute("name") || "profile_photo";
    const help =
      this.getAttribute("help") ||
      "Optional. JPG, PNG, GIF, or WEBP up to 2 MB.";
    const defaultSrc =
      this.getAttribute("default-src") || "/assets/logo-icon.png";
    const existingValue = this.getAttribute("value") || "";
    const required = this.hasAttribute("required");

    this._defaultPreview = defaultSrc;

    this._els.label.textContent = label;
    this._els.file.name = name;
    this._els.file.required = required;
    this._els.help.textContent = help;
    this._els.help.style.display = help ? "" : "none";

    const previewSource = existingValue || defaultSrc;
    this._updatePreview(previewSource);
  }

  _updatePreview(src) {
    this._els.img.src = src || this._defaultPreview;
    const isDefault = !src || src === this._defaultPreview;
    this._els.clear.classList.toggle("is-disabled", isDefault);
    this._els.clear.disabled = isDefault;
  }
}

if (!customElements.get("profile-image-input")) {
  customElements.define("profile-image-input", ProfileImageInput);
}
