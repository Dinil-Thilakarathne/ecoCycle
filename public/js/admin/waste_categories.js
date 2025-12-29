/**
 * Waste Categories Management
 * Handles adding/editing categories via ModalManager
 */

const csrfToken = document
  .querySelector('meta[name="csrf-token"]')
  ?.getAttribute("content");

// Helper to show toasts
function showToast(message, type = "info") {
  if (window.__createToast) {
    window.__createToast(message, type);
  } else {
    alert(message);
  }
}

// API Helper
async function apiRequest(endpoint, method, data = null) {
  const headers = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };

  if (csrfToken && method !== "GET") {
    headers["X-CSRF-TOKEN"] = csrfToken;
  }

  const options = {
    method,
    headers,
    body: data ? JSON.stringify(data) : undefined,
  };

  try {
    const response = await fetch(endpoint, options);
    // Handle no content
    if (response.status === 204) return null;

    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.error || result.message || "Request failed");
    }

    return result;
  } catch (error) {
    console.error("API Error:", error);
    throw error;
  }
}

function getFormContent(data = {}) {
  const name = data.name || "";
  const unit = data.unit || "kg";
  const color = data.color || "#2563eb";
  const price = data.price || "0.00";

  const container = document.createElement("div");
  container.innerHTML = `
        <div style="display:grid;gap:1rem;">
            <div>
                <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#374151;">Category Name</label>
                <input type="text" id="cat_name" value="${escapeHtml(
                  name
                )}" placeholder="e.g. Plastic"
                    style="width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:0.375rem;">
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#374151;">Unit</label>
                    <input type="text" id="cat_unit" value="${escapeHtml(
                      unit
                    )}" placeholder="kg"
                        style="width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:0.375rem;">
                </div>
                <div>
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#374151;">Color Code</label>
                    <div style="display:flex;gap:0.5rem;">
                        <input type="color" id="cat_color_picker" value="${escapeHtml(
                          color
                        )}" 
                            style="height:38px;width:50px;padding:0;border:1px solid #d1d5db;"
                            onchange="document.getElementById('cat_color').value = this.value">
                        <input type="text" id="cat_color" value="${escapeHtml(
                          color
                        )}" 
                            style="width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:0.375rem;"
                            onchange="document.getElementById('cat_color_picker').value = this.value">
                    </div>
                </div>
            </div>

            <div style="background:#f0fdf4;padding:1rem;border-radius:0.5rem;border:1px solid #bbf7d0;">
                <label style="display:block;margin-bottom:0.5rem;font-weight:600;color:#166534;">Purchase Price (Per Unit)</label>
                <div style="position:relative;">
                    <span style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:#166534;font-weight:bold;">Rs</span>
                    <input type="number" step="0.01" id="cat_price" value="${escapeHtml(
                      price
                    )}" 
                        style="width:100%;padding:0.5rem 0.5rem 0.5rem 2.5rem;border:1px solid #16a34a;border-radius:0.375rem;color:#166534;font-weight:bold;">
                </div>
                <p style="margin-top:0.5rem;font-size:0.8rem;color:#15803d;">
                    This is the amount paid to customers for each unit of waste collected.
                </p>
            </div>
        </div>
    `;
  return container;
}

function escapeHtml(text) {
  if (!text) return "";
  return text
    .toString()
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function openWasteCategoryModal(existingData = null) {
  const isEdit = !!existingData;
  const title = isEdit ? "Edit Category" : "New Waste Category";
  const formContent = getFormContent(existingData || {});

  // For edits, we need logic to clear the price if user desires,
  // although typically price is always overwritten.

  window.Modal.open({
    title,
    content: formContent,
    size: "md",
    actions: [
      {
        label: "Cancel",
        variant: "plain",
      },
      {
        label: "Save Category",
        variant: "primary",
        dismiss: false,
        onClick: async ({ close, setLoading }) => {
          const name = formContent.querySelector("#cat_name").value.trim();
          const unit = formContent.querySelector("#cat_unit").value.trim();
          const color = formContent.querySelector("#cat_color").value.trim();
          const price =
            parseFloat(formContent.querySelector("#cat_price").value) || 0;

          if (!name) {
            showToast("Category name is required", "error");
            return;
          }

          setLoading(true);

          try {
            const payload = {
              name,
              unit,
              color,
              pricePerUnit: price,
            };

            let response;
            if (isEdit) {
              response = await apiRequest(
                `/api/waste-categories/${existingData.id}`,
                "PUT",
                payload
              );
              showToast("Category updated successfully", "success");
            } else {
              response = await apiRequest(
                "/api/waste-categories",
                "POST",
                payload
              );
              showToast("Category created successfully", "success");
            }

            // Delay reload slightly to allow toast to be seen
            setTimeout(() => window.location.reload(), 1000);
            close();
          } catch (err) {
            showToast(err.message, "error");
            setLoading(false);
          }
        },
      },
    ],
  });
}

// Global exposure for edit button
window.editCategory = function (data) {
  openWasteCategoryModal(data);
};
