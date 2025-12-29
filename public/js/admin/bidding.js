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
  const highest = Number(round.currentHighestBid ?? round.current_highest_bid);
  if (Number.isFinite(highest) && highest > 0) return highest;
  const reserve = resolveReservePrice(round);
  return reserve !== null ? reserve : 0;
}

function parseEndTime(raw) {
  if (!raw) return null;
  const candidate =
    raw instanceof Date ? raw : new Date(String(raw).replace(" ", "T"));
  const time = candidate.getTime();
  return Number.isNaN(time) ? null : candidate;
}

function formatTimeRemainingText(endValue) {
  const end = parseEndTime(endValue);
  if (!end) return "N/A";

  const diffSeconds = Math.floor((end.getTime() - Date.now()) / 1000);
  if (diffSeconds <= 0) return "Ended";

  const hours = Math.floor(diffSeconds / 3600);
  const minutes = Math.floor((diffSeconds % 3600) / 60);
  return `${hours}h ${minutes}m`;
}

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
    (item) => String(item.id) === id
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
  const currentBid = formatCurrency(getDisplayBidValue(round));
  const biddingCompany = escapeHtml(round.biddingCompany || "—");
  const timeRemaining = formatTimeRemainingText(round.endTime);
  const status = renderStatusBadge(round.status || "pending");

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
    round.id
  )}')" title="View Details"><i class="fa-solid fa-eye"></i></button>`;

  if ((round.status || "").toLowerCase() !== "completed") {
    if (
      !hasLeadingCompany &&
      !hasBids &&
      (round.status || "").toLowerCase() === "active"
    ) {
      actionsHtml += `<button class="icon-button" onclick="editBiddingRound('${escapeHtml(
        round.id
      )}')" title="Edit Bid Round"><i class="fa-solid fa-edit"></i></button>`;
      actionsHtml += `<button class="icon-button danger" onclick="cancelBiddingRound('${escapeHtml(
        round.id
      )}')" title="Cancel Bid Round"><i class="fa-solid fa-trash"></i></button>`;
    }
  }
  actionsHtml += "</div>";

  return `
        <td class="font-medium">${lotId}</td>
        <td>${wasteCategory}</td>
        <td>${quantity}</td>
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
  if (cells[3])
    cells[3].innerHTML = `<div class="cell-with-icon">${formatCurrency(
      getDisplayBidValue(round)
    )}</div>`;
  if (cells[4]) cells[4].textContent = round.biddingCompany || "—";
  if (cells[5])
    cells[5].innerHTML = `<div class="cell-with-icon"><i class="fa-solid fa-clock"></i> ${formatTimeRemainingText(
      round.endTime
    )}</div>`;
  if (cells[6]) cells[6].innerHTML = renderStatusBadge(round.status);
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
                    <label style="display:block;font-weight:600;margin-bottom:6px;">Quantity</label>
                    <input type="number" name="quantity" min="100" step="100" required style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px;" />
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

  // Wiring defaults
  const categorySelect = form.querySelector('select[name="wasteCategory"]');
  const startingBidInput = form.querySelector('input[name="startingBid"]');
  if (categorySelect && startingBidInput) {
    categorySelect.addEventListener("change", function () {
      const cat = (this.value || "").toString().trim();
      if (!cat) {
        startingBidInput.removeAttribute("min");
        return;
      }
      const minMap = window.__MINIMUM_BIDS || {};
      const minValRaw = minMap[cat.toLowerCase()];
      const minVal =
        typeof minValRaw !== "undefined" ? parseFloat(minValRaw) : NaN;
      if (!isNaN(minVal)) {
        startingBidInput.value = Number(minVal).toFixed(2);
        startingBidInput.setAttribute("min", String(minVal));
      } else {
        startingBidInput.removeAttribute("min");
      }
    });
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
          if (!(quantity > 0))
            errors.push("Quantity must be greater than zero");
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
                `Starting bid must be at least Rs ${Number(minVal).toFixed(2)}`
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
                   round.lotId ?? ""
                 )}" />
                 <input type="hidden" name="wasteCategory" value="${escapeHtml(
                   round.wasteCategory ?? ""
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
                              round.startingBid ?? round.currentHighestBid ?? ""
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
                }
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
                  round.lotId || round.id
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

window.viewBiddingDetails = function (el, biddingId) {
  // Handle overload: viewBiddingDetails(id) or viewBiddingDetails(el, id)
  if (arguments.length === 1) {
    biddingId = el;
    el = null;
  }

  // Find data
  let record = null;
  if (window.__BIDDING_DATA) {
    record = window.__BIDDING_DATA.find(
      (r) => String(r.id) === String(biddingId)
    );
  }

  if (!record && el) {
    // scraping fallback logic omitted for brevity as __BIDDING_DATA should be reliable
    // but if needed we can parse the TR row again
  }

  if (!record) {
    showToast("Details not found in cache", "error");
    return;
  }

  const container = document.createElement("div");
  container.className = "user-modal__grid"; // reusing existing grid class if available, or style inline
  container.style.display = "grid";
  container.style.gridTemplateColumns = "1fr 1fr";
  container.style.gap = "8px 16px";

  const fields = [
    ["Lot ID", record.lotId],
    ["Waste Category", record.wasteCategory],
    ["Quantity", `${record.quantity} ${record.unit}`],
    ["Current Highest Bid", formatCurrency(getDisplayBidValue(record))],
    ["Leading Company", record.biddingCompany || "—"],
    ["Time Remaining", formatTimeRemainingText(record.endTime)],
    ["Status", record.status],
  ];

  container.innerHTML = fields
    .map(
      ([label, val]) => `
        <div style="font-weight:600;color:#374151;">${label}</div>
        <div style="color:#111827;">${escapeHtml(val)}</div>
    `
    )
    .join("");

  Modal.open({
    title: "Bidding Round Details",
    content: container,
    actions: [{ label: "Close", variant: "secondary", dismiss: true }],
  });
};
