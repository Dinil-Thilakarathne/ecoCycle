# Web Components Architecture Guide

This project uses **native Web Components** to build reusable, framework‑agnostic UI elements (like React components) without adding a heavy front‑end library.

## Goals

- Reusable, declarative, attribute-driven components
- Minimal JavaScript, no build step required (can evolve later)
- Preserve existing global CSS (no sudden styling regressions)
- Encapsulate patterns: stats cards, activity feeds, status indicators, buttons (future)
- Allow progressive enhancement (HTML works even if JS fails)

---

## Current Components

| Tag               | File                                    | Purpose                                           | Shadow DOM | Notes                                            |
| ----------------- | --------------------------------------- | ------------------------------------------------- | ---------- | ------------------------------------------------ |
| `<feature-card>`  | `public/js/components/feature-card.js`  | KPI / metric card with title, value, change badge | No         | Attribute driven; infers success/danger styling  |
| `<activity-card>` | `public/js/components/activity-card.js` | Wrapper card with header + list region            | No         | Preserves nested child elements (pseudo-slot)    |
| `<activity-item>` | `public/js/components/activity-item.js` | Single activity feed line                         | No         | Simple text fields (action / detail / time)      |
| `<status-item>`   | `public/js/components/status-item.js`   | System health / status row                        | No         | `state-class` maps to existing tag color classes |

> All are registered globally via `core.js` and loaded in the base layout.

---

## File Locations

```
public/
  js/
    components/
      core.js              # Imports & registers common components
      feature-card.js
      activity-card.js
      activity-item.js
      status-item.js
```

---

## Global Registration (core bundle)

`core.js` imports component scripts:

```js
import "./feature-card.js";
import "./activity-card.js";
import "./activity-item.js";
import "./status-item.js";
```

Included once in `layouts/app.php`:

```html
<script type="module" src="/js/components/core.js"></script>
```

Add any new globally available component by appending an import to `core.js`.

---

## Component Design Pattern

Each component follows a **lightweight pattern**:

1. No Shadow DOM (for now) → existing CSS classes keep working.
2. `observedAttributes` array defines reactive attributes.
3. `connectedCallback()` calls `_renderSkeleton()` **once** then `_render()`.
4. Attributes changed later trigger `attributeChangedCallback()` → `_render()`.
5. DOM element references cached in `this._els`.
6. Safe textual content assigned via `textContent` (avoid XSS).
7. Optional logic for styling decisions (e.g., positive/negative changes).

### Example (`feature-card` excerpt)

```js
class FeatureCard extends HTMLElement {
  static get observedAttributes() {
    return ["title", "value", "icon", "change", "period", "change-negative"];
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
    /* inject base markup & cache refs */
  }
  _render() {
    /* read attributes & update DOM */
  }
}
```

---

## Why No Shadow DOM (Yet)

| Reason              | Benefit                                 |
| ------------------- | --------------------------------------- |
| Rapid migration     | Existing CSS selectors keep working     |
| Minimal boilerplate | Easier to onboard contributors          |
| Theming             | Global cascade & utility classes usable |

**Trade‑off:** No native slots or style scoping. Where a slot-like area was needed (`activity-card`), we _preserve_ original children before rewriting the container.

We can migrate selectively to Shadow DOM later for isolation (see Roadmap).

---

## Attribute API Reference

### `<feature-card>`

| Attribute         | Required | Description                                               |
| ----------------- | -------- | --------------------------------------------------------- |
| `title`           | ✔        | Heading text                                              |
| `value`           | ✔        | Main metric value                                         |
| `icon`            | ✖        | Font Awesome (or other) class list applied to inner `<i>` |
| `change`          | ✖        | Change label (e.g., `+12%`, `-5%`)                        |
| `change-negative` | ✖        | Force negative styling regardless of sign                 |
| `period`          | ✖        | Small contextual text (default: `from last month`)        |

### `<activity-card>`

| Attribute     | Required | Description                                                     |
| ------------- | -------- | --------------------------------------------------------------- |
| `title`       | ✔        | Card heading                                                    |
| `description` | ✖        | Subheading / context                                            |
| (children)    | ✔        | One or more `<activity-item>` / `<status-item>` or custom nodes |

### `<activity-item>`

| Attribute | Required | Description                       |
| --------- | -------- | --------------------------------- |
| `action`  | ✔        | Action headline (e.g., "Bid won") |
| `detail`  | ✖        | Secondary info (entity, resource) |
| `time`    | ✖        | Relative timestamp string         |

### `<status-item>`

| Attribute     | Required | Description                                                     |
| ------------- | -------- | --------------------------------------------------------------- |
| `label`       | ✔        | Row label (e.g., `Database`)                                    |
| `state`       | ✔        | Status text (e.g., `Healthy`)                                   |
| `state-class` | ✖        | Existing color/status utility class (e.g., `online`, `warning`) |

---

## Usage Examples

### Feature Cards Loop (PHP)

```php
<?php foreach ($stats as $s): ?>
  <feature-card
    title="<?= htmlspecialchars($s['title']) ?>"
    value="<?= htmlspecialchars($s['value']) ?>"
    icon="<?= htmlspecialchars($s['icon']) ?>"
    change="<?= htmlspecialchars($s['change']) ?>"
    period="from last month"
    <?php if(strpos($s['change'], '-') === 0): ?>change-negative<?php endif; ?>
  ></feature-card>
<?php endforeach; ?>
```

### Activity Section

```html
<activity-card
  title="Recent Activity"
  description="Latest system activities and updates"
>
  <activity-item
    action="New pickup scheduled"
    detail="John Doe"
    time="2 minutes ago"
  ></activity-item>
  <activity-item
    action="Bid won"
    detail="Lot #1234"
    time="5 minutes ago"
  ></activity-item>
</activity-card>
```

### System Health

```html
<activity-card
  title="System Health"
  description="Current system status and performance"
>
  <status-item label="Server" state="Online" state-class="online"></status-item>
  <status-item
    label="Database"
    state="Healthy"
    state-class="healthy"
  ></status-item>
</activity-card>
```

### Dynamic Updates (JS)

```js
// Update a value later
const revenue = document.querySelector('feature-card[title="Monthly Revenue"]');
revenue.setAttribute("value", "$48,010");
revenue.setAttribute("change", "+18%");

// Append a new activity item
const activityCard = document.querySelector(
  'activity-card[title="Recent Activity"]'
);
const item = document.createElement("activity-item");
item.setAttribute("action", "New company registered");
item.setAttribute("detail", "RecycleCo Ltd.");
item.setAttribute("time", "just now");
activityCard.appendChild(item);
```

---

## Creating a New Component (Checklist)

1. **Decide Shadow DOM?** Start without (faster). Use Shadow DOM if name collisions or style bleed become a problem.
2. **File:** `public/js/components/<name>.js`.
3. **Define class:** `class XyzThing extends HTMLElement { ... }`.
4. **Static attributes:** `static get observedAttributes() { return [...] }`.
5. **Skeleton:** Create base markup in `_renderSkeleton()`; cache DOM refs in `this._els`.
6. **Render logic:** In `_render()`, read attributes, mutate DOM.
7. **Registration:** `if(!customElements.get('xyz-thing')) customElements.define('xyz-thing', XyzThing);`
8. **Global include:** Import in `core.js` if widely used; otherwise lazy load via `import()`.
9. **Test:** Add element to a test view and change attributes dynamically.
10. **Document:** Update this guide if API is public.

### Template Snippet

```js
class MyWidget extends HTMLElement {
  static get observedAttributes() {
    return ["foo", "bar"];
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
    this.innerHTML = `<div class="my-widget"><span data-foo></span></div>`;
    this._els = { foo: this.querySelector("[data-foo]") };
  }
  _render() {
    this._els.foo.textContent = this.getAttribute("foo") || "";
  }
}
if (!customElements.get("my-widget"))
  customElements.define("my-widget", MyWidget);
```

---

## Naming Conventions

- Custom element names **must contain a dash**: `feature-card`, `status-item`.
- Attribute names: kebab-case (`state-class`, `change-negative`).
- Internal variables: camelCase in JS.
- Class names reused from existing CSS to avoid duplication.

---

## Performance Considerations

| Aspect     | Guidance                                                                                 |
| ---------- | ---------------------------------------------------------------------------------------- |
| Network    | Components are tiny; a shared `core.js` keeps requests low.                              |
| Parsing    | Keep logic in `_render()` minimal. Avoid heavy loops in connectedCallback.               |
| Re-renders | Only mutate changed subtrees (current pattern already efficient).                        |
| Lazy load  | For future heavy components use `import('/js/components/heavy-chart.js')` conditionally. |

---

## Progressive Enhancement

If JS fails to load: the custom elements will appear as _unknown tags_ and not render their internal template. To provide baseline fallback:

- Optionally server-render an HTML fallback inside the element, and only replace it on initialization.
- Or add a `<noscript>` section if critical.

---

## Common Pitfalls & Solutions

| Issue                              | Cause                                               | Fix                                               |
| ---------------------------------- | --------------------------------------------------- | ------------------------------------------------- |
| Children disappeared (earlier bug) | Overwriting `innerHTML` without preserving children | Capture & re-append nodes (as in `activity-card`) |
| Styling mismatch                   | Classes changed in component markup                 | Keep existing class names or map via config       |
| No reactivity                      | Attribute not in `observedAttributes`               | Add to array & handle in `_render()`              |
| XSS risk                           | Using `innerHTML` with unsanitized input            | Use `textContent` unless markup is sanitized      |

---

## Migration Strategy (Legacy Markup → Component)

1. Identify repeated pattern.
2. Extract dynamic values → choose attribute names.
3. Replace inner dynamic markup with placeholders.
4. Write component using those attributes.
5. Swap every instance in PHP templates with the new custom tag.
6. Keep original CSS until confident; later, consolidate styles.

---

## Roadmap Ideas

- Optional Shadow DOM variants for isolation.
- State / event emitting standard (e.g., dispatch `featurecard:click`).
- Accessibility audits (ARIA roles where needed, e.g., interactive cards = button semantics).
- Testing harness (Jest + @web/test-runner or simple browser smoke test script).
- Build step (esbuild/Vite) if component count or size grows significantly.

---

## Troubleshooting Quick Table

| Symptom                          | Quick Check                                                     |
| -------------------------------- | --------------------------------------------------------------- |
| Component not rendering          | Is `core.js` loaded? Are there console errors?                  |
| Attributes ignored               | In `observedAttributes`? Spelled correctly?                     |
| Children missing (activity-card) | Using older cached JS? Hard refresh (Ctrl+F5).                  |
| Wrong color badge                | Does your CSS define the `danger` / `success` / status classes? |
| Layout flash (unstyled)          | Ensure CSS loads before components (already true in layout).    |

---

## Contributing Steps

1. Create branch: `feat/ui-<name>`.
2. Add component JS file.
3. Import in `core.js` (or lazy load).
4. Add usage example to a view or a sandbox HTML (optional).
5. Update this guide if new public component.
6. Submit PR with screenshots.

---

## FAQ

**Q: Can I pass HTML inside a component?**  
Yes—place child nodes inside tags like `<activity-card> ... </activity-card>`. They will be preserved (for components designed to keep children).

**Q: How do I style internal parts from outside?**  
Currently: directly target class selectors (no Shadow DOM). If we adopt Shadow DOM later, we’ll expose `part="..."` attributes.

**Q: How to transform an attribute into a boolean flag?**  
Presence-only: `<feature-card change-negative></feature-card>` → check via `this.hasAttribute('change-negative')`.

---

## Summary

This Web Component system gives you a scalable, low‑overhead, framework‑free way to build a consistent UI. Extend carefully, keep components focused, and prefer declarative attributes over manual DOM manipulation.

> Have ideas for additional patterns (e.g., tabs, modal, dropdown)? Add them following this guide and update the table above.
