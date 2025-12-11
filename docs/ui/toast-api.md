# Toast Notification API Guide

The EcoCycle application includes a lightweight, global toast notification system for displaying feedback to users.

## Usage

The toast function is available globally on the `window` object.

```javascript
toast(message, type, timeout);
```

## Parameters

| Parameter | Type   | Default      | Description                                                                  |
| --------- | ------ | ------------ | ---------------------------------------------------------------------------- |
| `message` | string | **Required** | The text content to display in the toast notification. Accepts HTML strings. |
| `type`    | string | `'info'`     | The style of the toast. Options: `'info'`, `'success'`, `'error'`.           |
| `timeout` | number | `4000`       | Duration in milliseconds before the toast automatically closes.              |

## Examples

### Basic Info Toast

```javascript
// Shows a default grey/black info toast for 4 seconds
toast("This is an informational message");
```

### Success Toast

```javascript
// Shows a green success toast
toast("User suspended successfully.", "success");
```

### Error Toast

```javascript
// Shows a red error toast
toast("Failed to save changes.", "error");
```

### Custom Duration

```javascript
// Shows a toast for 10 seconds
toast("Long lasting message...", "info", 10000);
```

## Implementation Details

The toast system is initialized in `public/js/toast.js`. It:

- Injects required CSS styles into the document head automatically.
- Creates a fixed container `#toast-container` at the bottom-right of the viewport.
- Supports stacking multiple toasts.
- Provides a close (×) button for manual dismissal.
