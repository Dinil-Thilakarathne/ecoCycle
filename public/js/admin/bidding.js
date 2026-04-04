/**
 * Bidding Management Admin Logic
 * Handles modal operations using the shared ModalManager.
 */

// --- Constants & Helpers ---

const BIDDING_STATUS_BADGES = {
  active: '<div class="tag online">Active</div>',
  completed: '<div class="tag assigned">Completed</div>',
  awarded: '<div class="tag assigned">Awarded</div>',
  cancelled: '<div class="tag warning">Cancelled</div>',
};

function escapeHtml(value) {
  return String(value ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function renderStatusBadge(status) {
  const key = (status || "").toString().toLowerCase();
  if (key in BIDDING_STATUS_BADGES) {
    return BIDDING_STATUS_BADGES[key];
  }
  return (
    '<div class="tag secondary">' + escapeHtml(status || "Pending") + "</div>"
  );
}

function formatCurrency(value) {
  const num = Number(value);
  if (Number.isNaN(num)) {
    return "Rs 0.00";
  }
  return (
    "Rs " +
    num.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  );
}

function resolveReservePrice(round) {
  if (!round) return null;
  const directReserve = round.reservePrice ?? round.reserve_price;
  const reserveNumber = Number(directReserve);
  if (Number.isFinite(reserveNumber) && reserveNumber > 0) return reserveNumber;

  const starting = Number(round.startingBid ?? round.starting_bid);
  const quantity = Number(round.quantity);

  if (
    Number.isFinite(starting) &&
    Number.isFinite(quantity) &&
    quantity > 0 &&
    starting > 0
  ) {
    return starting * quantity;
  }
  return null;
}

function getDisplayBidValue(round) {
  if (!round) return 0;
  // ONLY return the current highest bid if it exists and is > 0
  const highest = Number(round.currentHighestBid ?? round.current_highest_bid);
  if (Number.isFinite(highest) && highest > 0) return highest;

  // Do NOT fallback to reserve price for "Current Highest" column
  // The column should show "-" if no bids are placed.
  return 0;
}

/**
 * Returns the effective status of a round, correcting 'active' to 'completed'
 * when the end time has already passed. This prevents stale status from being
 * displayed on the client even before the page is refreshed.
 */
function getEffectiveStatus(round) {
  if (!round) return "active";
  const status = (round.status || "active").toLowerCase();
  if (status !== "active") return status;

  // If still 'active' in the record, check if end time has passed
  const end = parseEndTime(round.endTime ?? round.end_time);
  if (end && end.getTime() <= Date.now()) {
    return "completed";
  }
  return status;
}

function parseEndTime(raw) {
  if (!raw) return null;
  const candidate =
    raw instanceof Date ? raw : new Date(String(raw).replace(" ", "T"));
  const time = candidate.getTime();
  return Number.isNaN(time) ? null : candidate;
}

window.formatTimeRemainingText = function (endValue) {
  const end = parseEndTime(endValue);
  if (!end) return "N/A";

  const diffSeconds = Math.floor((end.getTime() - Date.now()) / 1000);
  if (diffSeconds <= 0) return "Ended";

  const hours = Math.floor(diffSeconds / 3600);
  const minutes = Math.floor((diffSeconds % 3600) / 60);
  return `${hours}h ${minutes}m`;
};
var formatTimeRemainingText = window.formatTimeRemainingText;

function formatDateTimeForInput(value) {
  const date = parseEndTime(value);
  if (!date) return "";
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  const hours = String(date.getHours()).padStart(2, "0");
  const minutes = String(date.getMinutes()).padStart(2, "0");
  return `${year}-${month}-${day}T${hours}:${minutes}`;
}

function toSqlDateTimeLocal(value) {
  if (!value) return null;
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return null;
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  const hours = String(date.getHours()).padStart(2, "0");
  const minutes = String(date.getMinutes()).padStart(2, "0");
  const seconds = String(date.getSeconds()).padStart(2, "0");
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

/**
 * Toast helper wrapper
 */
function showToast(message, type = "info") {
  if (typeof window.__createToast === "function") {
    window.__createToast(message, type, 5000);
  } else {
    alert((type === "error" ? "Error: " : "") + message);
  }
}

// --- API & State Management ---

async function apiRequest(url, options = {}) {
  const opts = Object.assign({ headers: {} }, options);
  if (
    opts.body &&
    !(opts.body instanceof FormData) &&
    typeof opts.body === "object"
  ) {
    opts.headers["Content-Type"] =
      opts.headers["Content-Type"] || "application/json";
    opts.body = JSON.stringify(opts.body);
  }
  opts.headers["X-Requested-With"] = "XMLHttpRequest";

  const response = await fetch(url, opts);
  let payload = null;
  try {
    payload = await response.json();
  } catch (err) {
    payload = null;
  }

  if (!response.ok || (payload && payload.success === false)) {
    const message =
      payload && payload.message
        ? payload.message
        : `Request failed (${response.status})`;
    let detail = "";
    if (payload && payload.errors) {
      detail = Object.values(payload.errors).join("\n");
    }
    throw new Error(detail ? `${message}\n${detail}` : message);
  }
  return payload || {};
}

function syncBiddingCache(round) {
  if (!Array.isArray(window.__BIDDING_DATA)) {
    window.__BIDDING_DATA = [];
  }
  const id = String(round.id);
  const index = window.__BIDDING_DATA.findIndex(
    (item) => String(item.id) === id,
  );
  if (index >= 0) {
    window.__BIDDING_DATA[index] = round;
  } else {
    window.__BIDDING_DATA.push(round);
  }
}

async function fetchBiddingRound(roundId) {
  try {
    const response = await apiRequest(`/api/bidding/rounds/${roundId}`);
    const round = response.round;
    if (round) syncBiddingCache(round);
    return round;
  } catch (error) {
    console.error("Failed to fetch bidding round:", error);
    return null;
  }
}

// --- DOM Rendering ---

function renderBiddingRow(round) {
  const lotId = escapeHtml(round.lotId || round.id || "");
  const wasteCategory = escapeHtml(round.wasteCategory || "");
  const quantity =
    escapeHtml(String(round.quantity || "")) +
    " " +
    escapeHtml(round.unit || "");
  const currentBidVal = getDisplayBidValue(round);
  const currentBid =
    currentBidVal > 0
      ? formatCurrency(currentBidVal)
      : '<span style="color:#9ca3af;">-</span>';

  const biddingCompany = escapeHtml(round.biddingCompany || "—");
  const timeRemaining = formatTimeRemainingText(round.endTime);
  const effectiveStatus = getEffectiveStatus(round);
  const status = renderStatusBadge(effectiveStatus);

  const hasLeadingCompany = !!(
    round.leadingCompanyId ||
    (round.biddingCompany &&
      String(round.biddingCompany).trim() !== "" &&
      round.biddingCompany !== "—")
  );
  const startingBid = Number(round.startingBid || 0);
  const currentHighest = Number(round.currentHighestBid || 0);
  const hasBids = currentHighest > startingBid;

  let actionsHtml = '<div class="action-buttons">';
  actionsHtml += `<button class="icon-button" onclick="viewBiddingDetails(this,'${escapeHtml(
    round.id,
  )}')" title="View Details"><i class="fa-solid fa-eye"></i></button>`;

  if (effectiveStatus !== "completed") {
    if (!hasLeadingCompany && !hasBids && effectiveStatus === "active") {
      actionsHtml += `<button class="icon-button" onclick="editBiddingRound('${escapeHtml(
        round.id,
      )}')" title="Edit Bid Round"><i class="fa-solid fa-edit"></i></button>`;
      actionsHtml += `<button class="icon-button danger" onclick="cancelBiddingRound('${escapeHtml(
        round.id,
      )}')" title="Cancel Bid Round"><i class="fa-solid fa-trash"></i></button>`;
    }
  }
  actionsHtml += "</div>";

  return `
        <td class="font-medium">${lotId}</td>
        <td>${wasteCategory}</td>
        <td>${quantity}</td>
        <td><div class="cell-with-icon">${formatCurrency(startingBid)}</div></td>
        <td><div class="cell-with-icon">${currentBid}</div></td>
        <td>${biddingCompany}</td>
        <td><div class="cell-with-icon"><i class="fa-solid fa-clock"></i> ${timeRemaining}</div></td>
        <td>${status}</td>
        <td>${actionsHtml}</td>
    `;
}

function updateBiddingRow(round) {
  const row = document.querySelector(`tr[data-id="${round.id}"]`);
  if (!row) return;

  const cells = row.querySelectorAll("td");
  if (cells[0]) cells[0].textContent = round.lotId || "";
  if (cells[1]) cells[1].textContent = round.wasteCategory || "";
  if (cells[2])
    cells[2].textContent = `${round.quantity || ""} ${round.unit || ""}`;

  // Update starting bid at index 3
  if (cells[3])
    cells[3].innerHTML = `<div class="cell-with-icon">${formatCurrency(round.startingBid || 0)}</div>`;

  // Update current highest bid at index 4
  if (cells[4]) {
    const val = getDisplayBidValue(round);
    cells[4].innerHTML = `<div class="cell-with-icon">${val > 0 ? formatCurrency(val) : '<span style="color:#9ca3af;">-</span>'}</div>`;
  }

  // Update leading company at index 5
  if (cells[5]) cells[5].textContent = round.biddingCompany || "—";

  // Update time remaining at index 6
  if (cells[6])
    cells[6].innerHTML = `<div class="cell-with-icon"><i class="fa-solid fa-clock"></i> ${formatTimeRemainingText(
      round.endTime,
    )}</div>`;

  // Update status at index 7
  if (cells[7])
    cells[7].innerHTML = renderStatusBadge(getEffectiveStatus(round));
}

function removeBiddingRow(roundId) {
  const row = document.querySelector(`tr[data-id="${roundId}"]`);
  if (!row) return;

  row.style.transition = "opacity 0.3s ease-out";
  row.style.opacity = "0";
  setTimeout(() => {
    row.remove();
    const tbody = document.querySelector(".data-table tbody");
    if (tbody && tbody.querySelectorAll("tr:not([data-empty])").length === 0) {
      const emptyRow = document.createElement("tr");
      emptyRow.setAttribute("data-empty", "true");
      emptyRow.innerHTML = `
                <td colspan="8" style="text-align: center; padding: 1rem; color: #6b7280;">
                    No bidding rounds found.
                </td>
            `;
      tbody.appendChild(emptyRow);
    }
  }, 300);
}

// --- Action Handlers (Public API) ---

window.createNewLot = function () {
  const categories = Array.isArray(window.__WASTE_CATEGORIES)
    ? window.__WASTE_CATEGORIES
    : [];

  // Create form element
  const container = document.createElement("div");
  container.innerHTML = `
        <form id="createLotForm">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;">Waste Category</label>
                    <select name="wasteCategory" required style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px;">
                        <option value="">Select category</option>
                        ${categories
                          .map((c) => `<option value="${c}">${c}</option>`)
                          .join("")}
                    </select>
                </div>
                 <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;">Available Quantity</label>
                    <input type="text" id="availableQtyDisplay" readonly value="-" 
                        style="width:100%;padding:8px;border:1px solid #e5e7eb;background-color:#f9fafb;border-radius:4px;color:#6b7280;" />
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;">Quantity</label>
                    <input type="number" name="quantity" min="100" step="1" required style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px;" />
                     <small id="qtyHint" style="color:#6b7280;font-size:0.8em;"></small>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;">Unit</label>
                    <select name="unit" required style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px;">
                        <option value="kg">kg</option>
                        <option value="tons">tons</option>
                    </select>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;">Starting Bid (Rs)</label>
                    <input type="number" name="startingBid" min="0" step="0.01" required style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px;" />
                    <small style="color:#6b7280;font-size:0.8em;">Auto-calculated based on quantity & market price</small>
                </div>
                <div>
                    <label style="display:block;font-weight:600;margin-bottom:6px;">End Time</label>
                    <input type="datetime-local" name="endTime" required style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px;" />
                </div>
            </div>
        </form>
    `;

  const form = container.querySelector("form");
  // Set min date for datetime-local
  const dateInput = form.querySelector('input[name="endTime"]');
  if (dateInput) {
    dateInput.min = new Date().toISOString().slice(0, 16);
  }

  // Wiring defaults & Availability Check
  const categorySelect = form.querySelector('select[name="wasteCategory"]');
  const startingBidInput = form.querySelector('input[name="startingBid"]');
  const quantityInput = form.querySelector('input[name="quantity"]');
  const availableDisplay = container.querySelector("#availableQtyDisplay");
  const qtyHint = container.querySelector("#qtyHint");
  const unitSelect = form.querySelector('select[name="unit"]');

  if (categorySelect) {
    categorySelect.addEventListener("change", async function () {
      const cat = (this.value || "").toString().trim();

      // Reset dependent fields
      quantityInput.value = "";
      quantityInput.removeAttribute("max");
      qtyHint.textContent = "";
      availableDisplay.value = "Checking...";

      if (!cat) {
        startingBidInput.removeAttribute("min");
        availableDisplay.value = "-";
        return;
      }

      // Check Availability API
      try {
        const res = await apiRequest(
          `/api/bidding/availability?categoryName=${encodeURIComponent(cat)}`,
        );
        if (res.success) {
          const avail = parseFloat(res.available);
          const unit = res.unit || "kg";

          // Store pricing info on the element for easy access
          quantityInput.dataset.pricePerUnit = res.pricePerUnit || 0;
          quantityInput.dataset.markupPercentage = res.markupPercentage || 0;

          if (avail > 0) {
            availableDisplay.value = `${avail.toLocaleString()} ${unit}`;
          } else {
            availableDisplay.value = "No waste available";
          }

          if (avail > 0) {
            quantityInput.disabled = false;
            quantityInput.setAttribute("max", avail);
            quantityInput.classList.remove("not-allowed");

            qtyHint.style.color = "#6b7280";
            qtyHint.textContent = `Max: ${avail}`;

            // Enable
            if (unitSelect) unitSelect.disabled = false;
            startingBidInput.disabled = false;
          } else {
            quantityInput.value = "";
            quantityInput.setAttribute("max", 0);
            quantityInput.disabled = true;
            quantityInput.classList.add("not-allowed");

            // Disable
            if (unitSelect) unitSelect.disabled = true;
            startingBidInput.disabled = true;
          }

          // Ensure unit matches if possible or warn?
          if (unitSelect) unitSelect.value = unit;
        }
      } catch (e) {
        console.error("Availability check failed", e);
        availableDisplay.value = "Error";
      }

      // Min Bid Logic removed or replaced by auto-calc
      // We will add an event listener to quantityInput to recalculate bid
      calculateStartingBid();
    });

    quantityInput.addEventListener("input", calculateStartingBid);

    function calculateStartingBid() {
      const qty = parseFloat(quantityInput.value) || 0;
      const price = parseFloat(quantityInput.dataset.pricePerUnit) || 0;
      const markup = parseFloat(quantityInput.dataset.markupPercentage) || 0;

      if (qty > 0 && price > 0) {
        // Formula: Quantity * (Price + (Price * Markup / 100))
        // This assumes markup is a percentage added to the base price
        const unitPriceWithMarkup = price + (price * markup) / 100;
        const total = qty * unitPriceWithMarkup;

        startingBidInput.value = total.toFixed(2);
      } else {
        startingBidInput.value = "";
      }
    }
  }

  Modal.open({
    title: "Create New Lot",
    content: container,
    actions: [
      { label: "Cancel", variant: "secondary", dismiss: true },
      {
        label: "Create Lot",
        variant: "primary",
        onClick: async (context) => {
          const fd = new FormData(form);
          const wasteCategory = (fd.get("wasteCategory") || "")
            .toString()
            .trim();
          const quantity = parseFloat(fd.get("quantity")) || 0;
          const unit = (fd.get("unit") || "").toString().trim();
          const startingBid = parseFloat(fd.get("startingBid")) || 0;
          const endTimeRaw = fd.get("endTime");

          // Validation
          const errors = [];
          if (!wasteCategory) errors.push("Waste category is required");

          if (quantityInput.disabled) {
            errors.push(
              "Cannot create lot: No waste available for this category.",
            );
            // Throw immediately to stop
          } else {
            if (!(quantity > 0))
              errors.push("Quantity must be greater than zero");

            // Max check
            const maxQty = parseFloat(quantityInput.getAttribute("max"));
            if (quantityInput.hasAttribute("max") && quantity > maxQty) {
              errors.push(
                `Quantity cannot exceed available amount (${maxQty})`,
              );
            }
          }

          if (!unit) errors.push("Unit is required");
          if (!(startingBid >= 0))
            errors.push("Starting bid must be zero or more");
          if (!endTimeRaw) errors.push("End time is required");

          const endTimeDate = endTimeRaw ? new Date(endTimeRaw) : null;
          if (
            endTimeRaw &&
            (!endTimeDate || Number.isNaN(endTimeDate.getTime()))
          ) {
            errors.push("End time is invalid");
          } else if (endTimeDate && endTimeDate <= new Date()) {
            errors.push("End time must be in the future");
          }

          if (wasteCategory) {
            const minMap = window.__MINIMUM_BIDS || {};
            const minVal = parseFloat(minMap[wasteCategory.toLowerCase()]);
            if (!isNaN(minVal) && startingBid < minVal) {
              errors.push(
                `Starting bid must be at least Rs ${Number(minVal).toFixed(2)}`,
              );
            }
          }

          if (errors.length) {
            showToast(errors.join("\n"), "error");
            // Return false (implied) to prevent closing if handled manually,
            // but ModalManager keeps open on exception. Throwing is easier.
            throw new Error("Validation Failed");
          }

          try {
            const payload = {
              wasteCategory,
              quantity,
              unit,
              startingBid,
              endTime: toSqlDateTimeLocal(endTimeRaw),
            };

            const response = await apiRequest("/api/bidding/rounds", {
              method: "POST",
              body: payload,
            });

            // Handle success
            const created = response.round || {};
            // Add to table
            window.__BIDDING_DATA = window.__BIDDING_DATA || [];
            window.__BIDDING_DATA.unshift(created);

            const tbody = document.querySelector(".data-table tbody");
            if (tbody) {
              // Remove empty state row if exists
              const emptyRow = tbody.querySelector('tr[data-empty="true"]');
              if (emptyRow) {
                emptyRow.remove();
              }

              const tr = document.createElement("tr");
              tr.setAttribute("data-id", created.id);
              tr.innerHTML = renderBiddingRow(created);
              tbody.insertBefore(tr, tbody.firstChild);
            }

            showToast("New lot created", "success");
            context.close();
          } catch (err) {
            showToast(err.message, "error");
            // Rethrow to keep modal open and stop loading spinner
            throw err;
          }
        },
      },
    ],
  });
};

window.editBiddingRound = async function (roundId) {
  try {
    const round = await fetchBiddingRound(roundId);
    if (!round) {
      showToast("Bidding round not found.", "error");
      return;
    }

    if (round.status && round.status.toLowerCase() !== "active") {
      showToast("Only active bidding rounds can be edited.", "warning");
      return;
    }

    const dateVal = formatDateTimeForInput(round.endTime);
    const container = document.createElement("div");
    container.innerHTML = `
            <form>
                 <input type="hidden" name="lotId" value="${escapeHtml(
                   round.lotId ?? "",
                 )}" />
                 <input type="hidden" name="wasteCategory" value="${escapeHtml(
                   round.wasteCategory ?? "",
                 )}" />
                <div style="display:grid;gap:1rem;">
                    <div>
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Quantity</label>
                        <input type="number" name="quantity" min="1" step="1" required
                            value="${escapeHtml(round.quantity ?? "")}"
                            style="width:100%;padding:0.5rem;border:2px solid #d1d5db;border-radius:6px;" />
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Starting Bid (Rs)</label>
                        <input type="number" name="startingBid" min="0" step="0.01" required
                            value="${escapeHtml(
                              round.startingBid ??
                                round.currentHighestBid ??
                                "",
                            )}"
                            style="width:100%;padding:0.5rem;border:2px solid #d1d5db;border-radius:6px;" />
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600;">End Time</label>
                        <input type="datetime-local" name="endTime" required min="${new Date()
                          .toISOString()
                          .slice(0, 16)}"
                            value="${escapeHtml(dateVal)}"
                            style="width:100%;padding:0.5rem;border:2px solid #d1d5db;border-radius:6px;" />
                    </div>
                </div>
            </form>
        `;

    const form = container.querySelector("form");

    Modal.open({
      title: `Edit Bidding Round - ${escapeHtml(round.lotId || round.id)}`,
      content: container,
      actions: [
        { label: "Cancel", variant: "secondary", dismiss: true },
        {
          label: "Save Changes",
          variant: "primary",
          onClick: async (context) => {
            const fd = new FormData(form);
            const quantity = parseFloat(fd.get("quantity"));
            const startingBid = parseFloat(fd.get("startingBid"));
            const endTimeRaw = fd.get("endTime");

            if (!Number.isFinite(quantity) || quantity <= 0) {
              showToast("Quantity must be positive", "error");
              throw new Error("Validation");
            }
            if (!endTimeRaw) {
              showToast("End time required", "error");
              throw new Error("Validation");
            }

            try {
              const payload = {
                lotId: fd.get("lotId"),
                wasteCategory: fd.get("wasteCategory"),
                quantity,
                startingBid,
                unit: round.unit || "kg", // preserve unit
                endTime: toSqlDateTimeLocal(endTimeRaw),
              };

              const response = await apiRequest(
                `/api/bidding/rounds/${roundId}`,
                {
                  method: "PUT",
                  body: payload,
                },
              );

              const updated = response.round;
              if (updated) {
                syncBiddingCache(updated);
                updateBiddingRow(updated);
              }
              showToast("Bidding round updated.", "success");
              context.close();
            } catch (err) {
              showToast(err.message, "error");
              throw err;
            }
          },
        },
      ],
    });
  } catch (err) {
    showToast(err.message, "error");
  }
};

window.cancelBiddingRound = async function (roundId) {
  try {
    const round = await fetchBiddingRound(roundId);
    if (!round) return;

    const container = document.createElement("div");
    container.innerHTML = `
            <p style="margin:0 0 0.75rem 0;line-height:1.5;color:#374151;">
                Are you sure you want to cancel bidding round <strong>${escapeHtml(
                  round.lotId || round.id,
                )}</strong>?
            </p>
            <p style="margin:0 0 0.75rem 0;color:#6b7280;font-size:0.9rem;">
                This will immediately end the bidding process.
            </p>
            <p style="margin:0;color:#dc2626;font-size:0.85rem;font-weight:600;">
                ⚠️ This action cannot be undone.
            </p>
        `;

    Modal.open({
      title: "Cancel Bidding Round",
      content: container,
      size: "sm",
      actions: [
        { label: "Keep Active", variant: "secondary", dismiss: true },
        {
          label: "Cancel Round",
          variant: "danger",
          onClick: async (context) => {
            try {
              await apiRequest(`/api/bidding/rounds/${roundId}`, {
                method: "DELETE",
                body: { reason: "Cancelled by administrator" },
              });

              removeBiddingRow(roundId);
              showToast("Bidding round cancelled.", "success");
              context.close();
            } catch (err) {
              showToast(err.message, "error");
              throw err;
            }
          },
        },
      ],
    });
  } catch (err) {
    showToast(err.message, "error");
  }
};

window.viewBiddingDetails = async function (el, biddingId) {
  // Handle overload: viewBiddingDetails(id) or viewBiddingDetails(el, id)
  if (arguments.length === 1) {
    biddingId = el;
    el = null;
  }

  // Initial Modal with Loading State
  const modalContext = Modal.open({
    title: "Bidding Round Details",
    content:
      '<div style="padding:20px;text-align:center;">Loading details...</div>',
    actions: [{ label: "Close", variant: "secondary", dismiss: true }],
  });

  try {
    // Fetch fresh data including bids
    const data = await apiRequest(`/api/bidding/rounds/${biddingId}`);
    console.log(data);
    if (!data || !data.round) {
      throw new Error("Details could not be loaded.");
    }

    const record = data.round;
    const bids = data.bids || [];

    console.log(record);

    // Build Details Grid
    const gridContainer = document.createElement("div");
    gridContainer.className = "user-modal__grid";
    gridContainer.style.display = "grid";
    gridContainer.style.gridTemplateColumns = "1fr 1fr";
    gridContainer.style.gap = "8px 16px";
    gridContainer.style.marginBottom = "24px";

    const fields = [
      ["Lot ID", record.lotId],
      ["Waste Category", record.wasteCategory],
      ["Quantity", `${record.quantity} ${record.unit}`],
      ["Current Highest Bid", formatCurrency(getDisplayBidValue(record))],
      ["Leading Company", record.biddingCompany || "—"],
      ["Time Remaining", formatTimeRemainingText(record.endTime)],
      ["Status", renderStatusBadge(record.status)],
    ];

    gridContainer.innerHTML = fields
      .map(
        ([label, val]) => `
            <div style="font-weight:600;color:#374151;">${label}</div>
            <div style="color:#111827;">${String(val).includes("<div") ? val : escapeHtml(val)}</div>
        `,
      )
      .join("");

    // Build Bids Table
    const bidsSection = document.createElement("div");
    bidsSection.innerHTML = `
        <h4 style="margin:0 0 12px 0;font-size:1.1rem;color:#111827;border-bottom:1px solid #e5e7eb;padding-bottom:8px;">Recent Bids</h4>
      `;

    if (bids.length === 0) {
      bidsSection.innerHTML += `<div style="color:#6b7280;font-style:italic;">No bids placed yet.</div>`;
    } else {
      const table = document.createElement("table");
      table.style.width = "100%";
      table.style.borderCollapse = "collapse";
      table.style.fontSize = "0.9rem";

      let rowsHtml = `
            <thead>
                <tr style="border-bottom:2px solid #e5e7eb;text-align:left;">
                    <th style="padding:8px;font-weight:600;color:#374151;">Company</th>
                    <th style="padding:8px;font-weight:600;color:#374151;">Bid Amount</th>
                    <th style="padding:8px;font-weight:600;color:#374151;">Time</th>
                </tr>
            </thead>
            <tbody>
          `;

      bids.forEach((bid) => {
        const isWinner = bid.isWinner
          ? '<span style="color:#fff;background:#10b981;font-size:0.7em;padding:2px 6px;border-radius:99px;margin-left:6px;">WINNER</span>'
          : "";
        const dateStr = new Date(bid.createdAt).toLocaleString();
        rowsHtml += `
                <tr style="border-bottom:1px solid #f3f4f6;">
                    <td style="padding:8px;">${escapeHtml(bid.companyName)} ${isWinner}</td>
                    <td style="padding:8px;font-family:monospace;font-weight:600;">${formatCurrency(bid.amount)}</td>
                    <td style="padding:8px;color:#6b7280;">${escapeHtml(dateStr)}</td>
                </tr>
              `;
      });

      rowsHtml += `</tbody>`;
      table.innerHTML = rowsHtml;
      bidsSection.appendChild(table);
    }

    // Invoice section (for awarded rounds)
    const invoice = data.invoice || null;
    let invoiceSection = null;
    if (invoice) {
      const statusLabel =
        {
          pending: "Awaiting Payment Reference",
          processing: "Reference Submitted — Awaiting Confirmation",
          completed: "Payment Confirmed ✅",
          failed: "Payment Failed",
        }[invoice.status] || invoice.status;

      const color =
        invoice.status === "completed"
          ? "#10b981"
          : invoice.status === "processing"
            ? "#f59e0b"
            : "#6b7280";

      invoiceSection = document.createElement("div");
      invoiceSection.style.cssText =
        "margin-top:16px;padding:12px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;";
      invoiceSection.innerHTML = `
        <h4 style="margin:0 0 8px 0;font-size:1rem;color:#111827;">Invoice Status</h4>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 12px;font-size:0.9rem;">
          <span style="color:#6b7280;">Invoice ID:</span><span>${escapeHtml(String(invoice.id))}</span>
          <span style="color:#6b7280;">Amount:</span><span style="font-weight:600;">${formatCurrency(invoice.amount)}</span>
          <span style="color:#6b7280;">Status:</span><span style="font-weight:600;color:${color};">${escapeHtml(statusLabel)}</span>
          ${invoice.txnId ? `<span style="color:#6b7280;">Reference:</span><span style="font-family:monospace;">${escapeHtml(invoice.txnId)}</span>` : ""}
        </div>
      `;
    }

    // Combine Content
    const contentWrapper = document.createElement("div");
    contentWrapper.appendChild(gridContainer);
    contentWrapper.appendChild(bidsSection);
    if (invoiceSection) contentWrapper.appendChild(invoiceSection);

    // --- Build action buttons ---
    const modalActions = [
      { label: "Close", variant: "secondary", dismiss: true },
    ];

    const roundStatus = (record.status || "").toLowerCase();
    const hasLeadingCompany = !!(
      record.leadingCompanyId || record.biddingCompany
    );

    // Award Winner button — active round with a leading company
    if (roundStatus === "active" && hasLeadingCompany) {
      modalActions.unshift({
        label: "🏆 Award Winner",
        variant: "primary",
        onClick: async (ctx) => {
          const companyId =
            record.leadingCompanyId ||
            (bids.find((b) => b.isWinner || b.isLeading) || {}).companyId ||
            null;

          if (!companyId) {
            showToast("Could not determine the leading company ID.", "error");
            throw new Error("Missing companyId");
          }

          try {
            await apiRequest("/api/bidding/approve", {
              method: "POST",
              body: { biddingId: record.id, companyId: Number(companyId) },
            });

            // Move row from active to history section visually
            const activeRow = document.querySelector(
              `tr[data-id="${escapeHtml(String(record.id))}"]`,
            );
            if (activeRow) activeRow.remove();

            showToast(
              `Winner awarded! Invoice created for ${record.biddingCompany || "leading company"}.`,
              "success",
            );
            ctx.close();
          } catch (err) {
            showToast(err.message || "Failed to award winner.", "error");
            throw err;
          }
        },
      });
    }



    modalContext.close(); // Close loading modal

    Modal.open({
      title: "Bidding Round Details",
      content: contentWrapper,
      size: "md", // wider for table
      actions: modalActions,
    });
  } catch (err) {
    modalContext.close();
    showToast(err.message, "error");
  }
};

// --- Bid History Log Functions ---

/**
 * Load and display bid history
 * @param {string|null} roundId - Optional round ID to filter by
 */
window.loadBidHistory = async function (roundId = null) {
  const container = document.getElementById("bidLogContainer");
  const filterSelect = document.getElementById("roundFilter");

  if (!container) return;

  try {
    // Show loading state
    container.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem;"></i>
                <p style="margin-top: 1rem;">Loading bid history...</p>
            </div>
        `;

    // Build API URL
    const url = roundId
      ? `/api/bidding/bid-history?roundId=${encodeURIComponent(roundId)}`
      : "/api/bidding/bid-history";

    const response = await apiRequest(url);

    if (!response.success) {
      throw new Error(response.message || "Failed to load bid history");
    }

    const bids = response.bids || [];
    const rounds = response.rounds || [];

    // Populate filter dropdown (only on initial load)
    if (!roundId && filterSelect && rounds.length > 0) {
      filterSelect.innerHTML = `
                <option value="">All Rounds</option>
                ${rounds
                  .map(
                    (r) =>
                      `<option value="${escapeHtml(r.id)}">${escapeHtml(r.lotId)} - ${escapeHtml(r.name)}</option>`,
                  )
                  .join("")}
            `;

      // Add change listener
      filterSelect.onchange = function () {
        window.loadBidHistory(this.value || null);
      };
    }

    // Render bid log
    if (bids.length === 0) {
      container.innerHTML = `
                <div style="text-align: center; padding: 3rem; color: #6b7280;">
                    <i class="fa-solid fa-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                    <p style="margin-top: 1rem; font-size: 1.1rem;">No bids found</p>
                    <p style="font-size: 0.9rem; color: #9ca3af;">Try selecting a different round or wait for companies to place bids.</p>
                </div>
            `;
      return;
    }

    // Render timeline
    container.innerHTML = `
            <div class="bid-timeline">
                ${bids.map((bid) => renderBidLogEntry(bid)).join("")}
            </div>
        `;
  } catch (error) {
    console.error("Failed to load bid history:", error);
    container.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #ef4444;">
                <i class="fa-solid fa-exclamation-triangle" style="font-size: 2rem;"></i>
                <p style="margin-top: 1rem;">Failed to load bid history</p>
                <p style="font-size: 0.9rem; color: #6b7280;">${escapeHtml(error.message)}</p>
            </div>
        `;
  }
};

/**
 * Render a single bid log entry
 * @param {Object} bid - Bid object
 * @returns {string} HTML string
 */
function renderBidLogEntry(bid) {
  const date = new Date(bid.createdAt);
  const timeStr = date.toLocaleTimeString("en-US", {
    hour: "2-digit",
    minute: "2-digit",
  });
  const dateStr = date.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
  });

  const winnerBadge = bid.isWinner
    ? '<span class="winner-badge">WINNER</span>'
    : "";

  return `
        <div class="bid-log-entry">
            <div class="bid-timestamp">
                <div class="time">${escapeHtml(timeStr)}</div>
                <div class="date">${escapeHtml(dateStr)}</div>
            </div>
            <div class="bid-content">
                <div class="bid-company">${escapeHtml(bid.companyName)}</div>
                <div class="bid-details">
                    bid <strong>${formatCurrency(bid.amount)}</strong> on 
                    <em>${escapeHtml(bid.lotId)}: ${escapeHtml(bid.roundName)}</em>
                    ${winnerBadge}
                </div>
            </div>
        </div>
    `;
}
