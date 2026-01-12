/**
 * Admin Notifications Manager
 * Handles fetching, sending, and rendering notifications
 */

document.addEventListener("DOMContentLoaded", () => {
  fetchNotifications();
  setupNotificationForm();

  // Auto-refresh every 30 seconds
  setInterval(fetchNotifications, 30000);
});

// State
let isSubmitting = false;

/**
 * Fetch recent notifications from the API
 */
async function fetchNotifications() {
  const listContainer = document.getElementById("recent-notifications-list");

  // Only show loading state on first load if empty
  if (!listContainer.hasChildNodes()) {
    listContainer.innerHTML =
      '<div style="padding:2rem;text-align:center;color:var(--neutral-500);">Loading notifications...</div>';
  }

  try {
    const response = await fetch("/api/notifications?limit=10", {
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    });

    if (!response.ok) throw new Error("Failed to load notifications");

    const data = await response.json();
    renderNotifications(data.notifications || []);
  } catch (error) {
    console.error("Error:", error);
    listContainer.innerHTML = `<div style="padding:1rem;color:var(--danger);text-align:center;">Failed to load notifications. <button onclick="fetchNotifications()" class="btn btn-sm btn-outline" style="margin-left:0.5rem">Retry</button></div>`;
  }
}

/**
 * Render the list of notifications
 */
function renderNotifications(notifications) {
  const container = document.getElementById("recent-notifications-list");

  if (!notifications || notifications.length === 0) {
    container.innerHTML = `
            <div style="padding:2rem;text-align:center;color:var(--neutral-500);display:flex;flex-direction:column;align-items:center;gap:0.5rem">
                <i class="fa-regular fa-bell-slash" style="font-size:1.5rem;opacity:0.5"></i>
                <p>No recent notifications found</p>
            </div>
        `;
    return;
  }

  container.innerHTML = notifications
    .map((notification) => {
      const status = notification.status || "sent"; // default to sent if missing

      // Determine Alert Type based on notification type/status
      let alertType = "info";
      if (status === "failed") alertType = "danger";
      else if (notification.type === "alert") alertType = "danger";
      else if (notification.type === "maintenance") alertType = "warning";

      // Determine Status Badge Class
      let statusClass = "secondary";
      if (status === "sent" || status === "read") statusClass = "success";
      else if (status === "pending" || status === "unread")
        statusClass = "warning";
      else if (status === "failed") statusClass = "danger";

      // Format Date
      const date = new Date(notification.created_at || notification.timestamp);
      const formattedDate = date.toLocaleString();

      return `
            <alert-box type="${alertType}" title="${escapeHtml(
        notification.title || "Notification"
      )}" dismissible>
                <p style="margin:0; color: var(--neutral-700); font-size: var(--text-sm);">
                    ${escapeHtml(notification.message)}
                </p>

                <div style="margin-top: var(--space-2); font-size: var(--text-xs); color: var(--neutral-500);">
                    <span>To: ${escapeHtml(
                      notification.recipient_group ||
                        (Array.isArray(notification.recipients)
                          ? notification.recipients.join(", ")
                          : notification.recipients) ||
                        "All"
                    )}</span>
                    &nbsp;&middot;&nbsp;
                    <span>${formattedDate}</span>
                </div>

                <div class="tag ${statusClass} alert-action">
                    ${escapeHtml(
                      status.charAt(0).toUpperCase() + status.slice(1)
                    )}
                </div>
            </alert-box>
        `;
    })
    .join("");
}

/**
 * Setup the notification sending form
 */
function setupNotificationForm() {
  const form = document.getElementById("notificationForm");
  if (!form) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    if (isSubmitting) return;

    const recipient = document.getElementById("recipient").value;
    const notificationType = document.getElementById("notificationType").value;
    const message = document.getElementById("message").value;

    if (!recipient || !notificationType || !message.trim()) {
      showToast("Please fill in all required fields", "error");
      return;
    }

    const title = getNotificationTitle(notificationType);

    try {
      isSubmitting = true;
      const btn = form.querySelector('button[type="submit"]');
      const originalBtnText = btn.innerHTML;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
      btn.disabled = true;

      const response = await fetch("/api/notifications", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        body: JSON.stringify({
          recipient_group: recipient,
          type: notificationType,
          title: title,
          message: message,
        }),
      });

      const result = await response.json();

      if (response.ok) {
        showToast("Notification sent successfully!", "success");
        form.reset();
        fetchNotifications(); // Refresh list
      } else {
        throw new Error(
          result.errors
            ? Object.values(result.errors).flat().join(", ")
            : result.message || "Failed to send"
        );
      }
    } catch (error) {
      console.error("Error sending notification:", error);
      showToast(error.message, "error");
    } finally {
      isSubmitting = false;
      const btn = form.querySelector('button[type="submit"]');
      btn.innerHTML =
        '<i class="fa-solid fa-paper-plane"></i> Send Notification';
      btn.disabled = false;
    }
  });
}

function getNotificationTitle(type) {
  const titles = {
    info: "Information",
    alert: "System Alert",
    system: "System Update",
    maintenance: "Maintenance Notice",
  };
  return titles[type] || "Notification";
}

// Utility to escape HTML to prevent XSS
function escapeHtml(unsafe) {
  if (!unsafe) return "";
  return unsafe
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

// Toast helper (reusing existing or fallback)
function showToast(message, type = "info") {
  if (window.__createToast) {
    window.__createToast(message, type);
  } else {
    alert(`${type.toUpperCase()}: ${message}`);
  }
}
