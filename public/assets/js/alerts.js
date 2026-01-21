// 1. Create the Toast configuration (Shared)
var Toast = Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.onmouseenter = Swal.stopTimer;
    toast.onmouseleave = Swal.resumeTimer;
  },
});

// 2. Define the global toast function
// We use 'window.toast' to ensure it's globally accessible without 'const' conflicts
window.toast = function (message, icon = "success") {
  Toast.fire({
    icon: icon,
    title: message,
  });
};
// Reusable AJAX Handler
async function performAjaxAction(url, formData, rowElement = null) {
  try {
    const response = await fetch(url, {
      method: "POST",
      body: formData,
    });
    const result = await response.json();

    if (result.success) {
      toast(result.message);
      if (rowElement) {
        rowElement.style.opacity = "0.5";
        setTimeout(() => location.reload(), 500);
      }
    } else {
      Swal.fire("Error", result.message || "Action failed", "error");
    }
  } catch (err) {
    Swal.fire("Error", "Network error or server crash", "error");
  }
}

// Sync Payment with Bakong
async function syncPayments() {
  const icon = document.getElementById("sync-icon");

  // 1. Show a loading overlay so the user knows to wait
  Swal.fire({
    title: "Syncing with Bakong...",
    text: "Please wait while we fetch the latest payments.",
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading();
      if (icon) icon.classList.add("fa-spin");
    },
  });

  // Add this line temporarily to alerts.js to see the loading for 3 seconds
  //await new Promise((resolve) => setTimeout(resolve, 3000));

  try {
    // 2. Perform the background sync
    const response = await fetch("../bakong_payments/admin_sync_payments.php");
    const data = await response.text();

    // 3. Once finished, replace the loading overlay with the success message
    Swal.fire({
      title: "Sync Complete",
      text: data || "Database is up to date.",
      icon: "success",
      confirmButtonText: "OK",
      confirmButtonColor: "#3b82f6",
    }).then((result) => {
      // 4. ONLY reload after they click "OK"
      if (result.isConfirmed) {
        location.reload();
      }
    });
  } catch (err) {
    // Handle network or server errors
    Swal.fire(
      "Error",
      "Sync failed. Check your internet connection or server status.",
      "error",
    );
  } finally {
    // 5. Always stop the icon spin at the end
    if (icon) icon.classList.remove("fa-spin");
  }
}

// Process Cash Payment
function handleCashPayment(billId, amount, row) {
  Swal.fire({
    title: "Confirm Cash",
    text: `Are you sure you received $${Number(amount).toFixed(2)}?`,
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Yes, Mark as Paid",
    confirmButtonColor: "#10b981",
  }).then((result) => {
    if (result.isConfirmed) {
      const data = new FormData();
      data.append("bill_id", billId);
      data.append("amount", amount);
      performAjaxAction(
        "../bakong_payments/process_cash_payment.php",
        data,
        row,
      );
    }
  });
}

async function ajaxDelete(id, url, rowElement) {
  const isDark = document.documentElement.classList.contains("dark");

  const result = await Swal.fire({
    title: "Are you sure?",
    text: "This action cannot be undone.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#ef4444",
    cancelButtonColor: "#64748b",
    confirmButtonText: "Yes, delete it!",
    background: isDark ? "#1f2937" : "#ffffff",
    color: isDark ? "#ffffff" : "#1f2937",
    customClass: { popup: "rounded-[2rem]" },
  });

  if (result.isConfirmed) {
    const data = new FormData();
    data.append("id", id);

    try {
      const response = await fetch(url + "?action=delete", {
        method: "POST",
        body: data,
      });
      const resData = await response.json();

      if (resData.success) {
        // Professional Animation: Slide and Fade
        rowElement.style.transition = "all 0.6s cubic-bezier(0.4, 0, 0.2, 1)";
        rowElement.style.transform = "translateX(50px)";
        rowElement.style.opacity = "0";

        // Show the toast message
        window.toast(resData.message, "success");

        // Remove from DOM after animation finishes
        setTimeout(() => {
          rowElement.remove();
          // Update the total count badge
          const countBadge = document.getElementById("total-count");
          if (countBadge) {
            const currentCount = document.querySelectorAll(".nat-item").length;
            countBadge.textContent = currentCount + " TOTAL";
          }
        }, 600);
      } else {
        Swal.fire("Error", resData.message, "error");
      }
    } catch (err) {
      Swal.fire("Error", "Network connection failed.", "error");
    }
  }
}

// NEW & IMPROVED: EDIT/CREATE Modal Handler
function openBillModal(billData = null) {
  // Get data from the hidden JSON scripts in bills.php
  const rooms = JSON.parse(document.getElementById("roomsJson").textContent);
  const rates = JSON.parse(document.getElementById("ratesJson").textContent);
  const nextInvoice = JSON.parse(
    document.getElementById("nextInvoiceJson").textContent || '"INV-0000"',
  );

  // Check if the body or a specific element has the 'dark' class
  const isDark =
    document.body.classList.contains("dark") ||
    document.documentElement.classList.contains("dark");

  Swal.fire({
    title: billData ? "Update Bill" : "Generate New Bill",
    width: "700px",
    // SET COLORS DYNAMICALLY BASED ON THEME
    background: isDark ? "#1f2937" : "#ffffff",
    color: isDark ? "#ffffff" : "#1f2937",
    padding: "1.5rem",
    customClass: {
      container: "swal-modal-custom",
      popup: "rounded-3xl",
    },
    html: `
        <form id="billForm" class="text-left space-y-5 mt-4">
            <input type="hidden" name="bill_id" value="${billData?.bill_id || ""}">
            
            <div class="mb-4">
                <label class="block text-[10px] font-bold uppercase opacity-50 mb-2 ml-1 tracking-widest text-blue-500">Invoice Number</label>
                <input type="text" value="${billData?.invoice_number || nextInvoice}" class="modal-input opacity-50 font-mono" readonly>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-bold uppercase opacity-50 mb-2 ml-1 tracking-widest">Select Room & Tenant</label>
                    <select name="room_id" id="modal_room_id" class="modal-input" required ${billData ? "disabled" : ""} onchange="updatePrevReading(this)">
                        <option value="">-- Choose a Room --</option>
                        ${rooms
                          .map(
                            (r) => `
                            <option value="${r.room_id}" data-old="${r.last_e || 0}" ${billData?.room_id == r.room_id ? "selected" : ""}>
                                Room ${r.room_number} (${r.renter_name})
                            </option>
                        `,
                          )
                          .join("")}
                    </select>
                    ${billData ? `<input type="hidden" name="room_id" value="${billData.room_id}">` : ""}
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase opacity-50 mb-2 ml-1 tracking-widest">Billing Period</label>
                    <input type="date" name="bill_month" class="modal-input" required value="${billData?.bill_month || new Date().toISOString().split("T")[0].slice(0, 8) + "01"}">
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase opacity-50 mb-2 ml-1 tracking-widest">Utility Rate Plan</label>
                    <select name="rate_id" class="modal-input" required>
                        ${rates
                          .map(
                            (rt) => `
                            <option value="${rt.rate_id}" ${billData?.rate_id == rt.rate_id ? "selected" : ""}>
                                $${rt.electric_rate}/kWh | $${rt.water_rate}/unit
                            </option>
                        `,
                          )
                          .join("")}
                    </select>
                </div>

                <div class="bg-yellow-50 dark:bg-yellow-900/10 p-4 rounded-2xl border border-yellow-100 dark:border-yellow-900/20">
                    <label class="block text-[10px] font-bold uppercase opacity-50 mb-2 ml-1 tracking-widest text-yellow-600">Electricity (kWh)</label>
                    <div class="space-y-2">
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-[9px] font-black opacity-40 uppercase">Prev</span>
                            <input type="number" id="modal_old_electric" name="old_electric" class="modal-input pl-12 bg-white/50" value="${billData?.old_electric || 0}" readonly>
                        </div>
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-[9px] font-black opacity-40 uppercase">New</span>
                            <input type="number" step="0.01" name="new_electric" class="modal-input pl-12 border-yellow-300 focus:ring-yellow-500" placeholder="0.00" value="${billData?.new_electric || ""}" required>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/10 p-4 rounded-2xl border border-blue-100 dark:border-blue-900/20">
                    <label class="block text-[10px] font-bold uppercase opacity-50 mb-2 ml-1 tracking-widest text-blue-600">Water Consumption</label>
                    <div class="space-y-2">
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-[9px] font-black opacity-40 uppercase">Units</span>
                            <input type="number" name="water_units" class="modal-input pl-12 border-yellow-300 focus:ring-yellow-500" placeholder="0" value="${billData?.water_units || ""}" required>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <style>
            .modal-input {
                width: 100%; 
                padding: 10px 14px; 
                border-radius: 12px; 
                border: 1px solid ${isDark ? "#374151" : "#e2e8f0"};
                background: ${isDark ? "#111827" : "#ffffff"};
                color: ${isDark ? "#ffffff" : "#1f2937"};
                outline: none;
                transition: all 0.2s;
            }
            .dark .modal-input { background: #1f2937; border-color: #374151; color: white; }
            .modal-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        </style>
    `,
    showCancelButton: true,
    confirmButtonText: billData ? "Update Records" : "Create Invoice",
    confirmButtonColor: "#2563eb",
    preConfirm: async () => {
      const form = document.getElementById("billForm");
      if (!form.checkValidity()) {
        form.reportValidity();
        return false;
      }
      const formData = new FormData(form);
      const response = await fetch("bill_actions.php?action=save", {
        method: "POST",
        body: formData,
      });
      const result = await response.json();
      if (!result.success) {
        Swal.showValidationMessage(result.message);
      }
      return result;
    },
  }).then((result) => {
    if (result.isConfirmed && result.value?.success) {
      toast(result.value.message);
      setTimeout(() => location.reload(), 1000);
    }
  });
}

// Logic to auto-fill old electricity reading in modal
function updatePrevReading(selectElement) {
  const selectedOption = selectElement.options[selectElement.selectedIndex];
  const oldReading = selectedOption.getAttribute("data-old") || 0;
  const oldInput = document.getElementById("modal_old_electric");
  if (oldInput) oldInput.value = oldReading;
}

// NEW: EDIT/CREATE Renter Modal Handler
function openRenterModal(renterData = null) {
  // 1. Fetch dependencies from hidden JSON script in renters.php
  // You must add <script id="nationalitiesJson" type="application/json"><?php echo json_encode($nationalities); ?></script> to renters.php
  const nationalities = JSON.parse(
    document.getElementById("nationalitiesJson").textContent || "[]",
  );

  const isDark =
    document.body.classList.contains("dark") ||
    document.documentElement.classList.contains("dark");

  const themeColor = renterData ? "#10b981" : "#3b82f6"; // Emerald for Edit, Blue for Create

  Swal.fire({
    title: renterData ? "Edit Renter Profile" : "Register New Renter",
    width: "700px",
    background: isDark ? "#1f2937" : "#ffffff",
    color: isDark ? "#ffffff" : "#1f2937",
    padding: "1.5rem",
    customClass: {
      popup: "rounded-[2.5rem]",
    },
    html: `
        <form id="renterForm" class="text-left space-y-5 mt-6 px-2">
            <input type="hidden" name="id" value="${renterData?.renter_id || ""}">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black uppercase opacity-40 mb-2 tracking-[0.2em] ml-1">Full Legal Name</label>
                    <input type="text" name="name" class="modal-input font-bold" placeholder="John Doe" value="${renterData?.renter_name || ""}" required>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase opacity-40 mb-2 tracking-[0.2em] ml-1">Gender</label>
                    <select name="gender" class="modal-input font-bold">
                        <option value="Male" ${renterData?.gender === "Male" ? "selected" : ""}>Male</option>
                        <option value="Female" ${renterData?.gender === "Female" ? "selected" : ""}>Female</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase opacity-40 mb-2 tracking-[0.2em] ml-1">Nationality</label>
                    <select name="nat_id" class="modal-input font-bold" required>
                        <option value="" disabled ${!renterData ? "selected" : ""}>Select Origin</option>
                        ${nationalities
                          .map(
                            (n) => `
                            <option value="${n.nat_id}" ${renterData?.nat_id == n.nat_id ? "selected" : ""}>
                                ${n.nat_name}
                            </option>
                        `,
                          )
                          .join("")}
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase opacity-40 mb-2 tracking-[0.2em] ml-1">Mobile Phone</label>
                    <input type="text" name="mobile" class="modal-input font-bold" placeholder="012 345 678" value="${renterData?.mobile_phone || ""}" required>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase opacity-40 mb-2 tracking-[0.2em] ml-1 text-sky-500">Telegram Handle</label>
                    <input type="text" name="telegram" class="modal-input font-bold" placeholder="@username" value="${renterData?.telegram || ""}">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black uppercase opacity-40 mb-2 tracking-[0.2em] ml-1">Permanent Address</label>
                    <textarea name="address" rows="3" class="modal-input font-medium resize-none leading-relaxed" placeholder="Street, City, Province...">${renterData?.renter_address || ""}</textarea>
                </div>
            </div>
        </form>

        <style>
            .modal-input {
                width: 100%; 
                padding: 14px 18px; 
                border-radius: 18px; 
                border: 2px solid ${isDark ? "#374151" : "#f1f5f9"};
                background: ${isDark ? "#111827" : "#f8fafc"};
                color: inherit;
                outline: none;
                font-size: 0.9rem;
                transition: all 0.2s;
            }
            .modal-input:focus { 
                border-color: ${themeColor}; 
                background: ${isDark ? "#111827" : "#ffffff"};
                box-shadow: 0 0 0 4px ${themeColor}15;
            }
        </style>
    `,
    showCancelButton: true,
    confirmButtonText: renterData ? "Save Changes" : "Register Renter",
    confirmButtonColor: themeColor,
    cancelButtonColor: isDark ? "#4b5563" : "#94a3b8",
    reverseButtons: true,
    preConfirm: async () => {
      const form = document.getElementById("renterForm");
      if (!form.checkValidity()) {
        form.reportValidity();
        return false;
      }

      const formData = new FormData(form);
      // Change action based on presence of renterData
      const actionParam = renterData ? "edit" : "create";

      try {
        const response = await fetch(
          `renter_actions.php?action=${actionParam}`,
          {
            method: "POST",
            body: formData,
          },
        );
        const result = await response.json();

        if (!result.success) {
          throw new Error(result.message || "Submit failed");
        }
        return result;
      } catch (error) {
        Swal.showValidationMessage(`Error: ${error.message}`);
      }
    },
  }).then((result) => {
    if (result.isConfirmed && result.value?.success) {
      window.toast(result.value.message);
      setTimeout(() => location.reload(), 1000);
    }
  });
}

// Ask the user for confirmation before logging out
function confirmLogout(username) {
  const isDark =
    document.body.classList.contains("dark") ||
    document.documentElement.classList.contains("dark");

  Swal.fire({
    title: "Sign Out?",
    // Using the name we passed from PHP
    html: `Goodbye, <b>${username}</b>! <br>Are you sure you want to end your session?`,
    icon: "question", // Using 'question' icon for a softer feel
    showCancelButton: true,
    confirmButtonColor: "#ef4444",
    cancelButtonColor: "#6b7280",
    confirmButtonText: "Yes, Sign Out",
    cancelButtonText: "Stay",
    background: isDark ? "#1f2937" : "#ffffff",
    color: isDark ? "#ffffff" : "#1f2937",
  }).then((result) => {
    if (result.isConfirmed) {
      // Show a quick "Logging out..." message before the redirect
      Swal.fire({
        title: "Logging out...",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        },
      });

      // Redirect to the logout PHP file
      window.location.href = "../public/auth/logout.php";
    }
  });
}

// auto-logout feature with a warning (Idle Timer, Logout Timer)
// Use 'var' or a window check to prevent "Already Declared" errors
if (typeof IDLE_TIME_LIMIT === "undefined") {
  var IDLE_TIME_LIMIT = 1 * 60 * 1000; // 1 Minute
  var WARNING_SECONDS = 10;
  var idleTimer;
  var warningTimer;
}

function resetTimers() {
  // console.log("Activity detected - Resetting 5-minute timer");
  clearTimeout(idleTimer);
  clearInterval(warningTimer); // Clear interval specifically for the countdown

  // Start the 5-minute countdown
  idleTimer = setTimeout(showTimeoutWarning, IDLE_TIME_LIMIT);
}

// Show the SweetAlert Warning
function showTimeoutWarning() {
  let countdown = WARNING_SECONDS;
  const isDark =
    document.body.classList.contains("dark") ||
    document.documentElement.classList.contains("dark");

  Swal.fire({
    title: "Session Expiring",
    html: `You have been idle for a while. Logging out in <b style="color: #ef4444;">${countdown}</b> seconds.`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Stay Logged In",
    cancelButtonText: "Logout Now",
    background: isDark ? "#1f2937" : "#ffffff",
    color: isDark ? "#ffffff" : "#1f2937",
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
      // Update the countdown text every 1 second
      warningTimer = setInterval(() => {
        countdown--;
        if (countdown <= 0) {
          clearInterval(warningTimer);
          // Redirect to logout - Adjust path as needed (../auth/logout.php)
          window.location.href = "auth/logout.php?reason=timeout";
        } else {
          const b = Swal.getHtmlContainer().querySelector("b");
          if (b) b.textContent = countdown;
        }
      }, 1000);
    },
  }).then((result) => {
    if (result.isConfirmed) {
      clearInterval(warningTimer);
      resetTimers(); // Reset the 5-minute clock

      // Keep the PHP session alive on the server
      fetch("auth/refresh_session.php").catch((err) =>
        console.log("Refresh failed"),
      );

      if (typeof toast === "function") {
        toast("Session extended", "success");
      }
    } else if (result.dismiss === Swal.DismissReason.cancel) {
      window.location.href = "auth/logout.php";
    }
  });
}

// Event listeners to detect activity
// Ensure the event listeners are only added once
if (!window.idleEventsInitialized) {
  ["mousedown", "mousemove", "keypress", "scroll", "touchstart"].forEach(
    (name) => {
      document.addEventListener(name, resetTimers, true);
    },
  );
  window.idleEventsInitialized = true;
}

// Initialize on page load
resetTimers();
