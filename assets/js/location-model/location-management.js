document.addEventListener("DOMContentLoaded", function () {
  "use strict";

  /* ── Config ──────────────────────────────────────────────── */
  var cfg = window.lebLocMgmtCfg || {};
  var editUrlBase = cfg.editUrlBase || "";
  var defaultSvgPath = cfg.defaultSvgPath || "";

  /* ── State ─────────────────────────────────────────────────── */
  var lebLocState = {
    currentPage: 1,
    perPage: 10,
    totalItems: 0,
    searchTerm: "",
    searchTimer: null,
    isLoading: false,
    selectedIds: [],
  };

  /* ── DOM References ─────────────────────────────────────────── */
  var domCardsList = document.getElementById("leb-loc-cards-list");
  var domPagText = document.getElementById("leb-loc-pagination-text");
  var domPagControls = document.getElementById("leb-loc-page-controls");
  var domTableWrap = document.getElementById("leb-loc-table-wrap");
  var domSearchInput = document.getElementById("leb-loc-search-input");
  var domSearchClear = document.getElementById("leb-loc-search-clear");
  var domSearchWrap = document.getElementById("leb-loc-search-wrap");

  /* ── AJAX Config ────────────────────────────────────────────── */
  var ajaxUrl = typeof LEB_Ajax !== "undefined" ? LEB_Ajax.ajax_url : "";
  var nonce = typeof LEB_Ajax !== "undefined" ? LEB_Ajax.nonce : "";

  /* ── Helper: format datetime ──────────────────────────────── */
  function lebLocFormatDate(dateStr) {
    if (!dateStr) {
      return "—";
    }
    try {
      var d = new Date(dateStr.replace(" ", "T"));
      return d.toLocaleString("en-IN", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
      });
    } catch (e) {
      return dateStr;
    }
  }

  /* ── Helper: XSS-safe text escaping ─────────────────────────── */
  function lebLocEscHtml(text) {
    var div = document.createElement("div");
    div.appendChild(document.createTextNode(String(text || "")));
    return div.innerHTML;
  }

  /* ── Helper: Extract SVG URL from JSON string ──────────────── */
  function lebLocExtractSvgSrc(svgRaw) {
    if (!svgRaw) return defaultSvgPath;
    try {
      var parsed = JSON.parse(svgRaw);
      return parsed.path ? parsed.path : defaultSvgPath;
    } catch (e) {
      return svgRaw; // Fallback for raw URLs
    }
  }

  /* ── Render: Loading overlay ─────────────────────────────── */
  function lebLocShowLoading() {
    if (document.getElementById("leb-loc-loader")) {
      return;
    }
    var overlay = document.createElement("div");
    overlay.id = "leb-loc-loader";
    overlay.className = "leb-loading-overlay";
    overlay.setAttribute("aria-hidden", "true");
    overlay.innerHTML = '<div class="leb-spinner"></div>';
    if (domTableWrap) domTableWrap.appendChild(overlay);
  }

  function lebLocHideLoading() {
    var el = document.getElementById("leb-loc-loader");
    if (el && el.parentNode) {
      el.parentNode.removeChild(el);
    }
  }

  /* ── Render: Empty state ──────────────────────────────────── */
  function lebLocRenderEmpty(message) {
    if (!domCardsList) return;
    domCardsList.innerHTML = [
      '<div class="leb-empty-state">',
      '  <svg class="leb-empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
      '    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>',
      '    <circle cx="12" cy="10" r="3"/>',
      "  </svg>",
      '  <p class="leb-empty-state__title">' + lebLocEscHtml(message) + "</p>",
      "</div>",
    ].join("");
  }

  /* ── Render: Data cards ───────────────────────────────────── */
  function lebLocRenderCards(items) {
    if (!domCardsList) return;
    if (!items || items.length === 0) {
      lebLocRenderEmpty("No locations found.");
      lebLocUpdateBulkBar();
      return;
    }

    var html = "";
    items.forEach(function (row, index) {
      var sno = (lebLocState.currentPage - 1) * lebLocState.perPage + index + 1;
      var editUrl = editUrlBase + encodeURIComponent(row.id);
      var isSelected = lebLocState.selectedIds.includes(parseInt(row.id));

      /* SVG preview cell */
      /* SVG preview cell */
      var finalSvgPath = lebLocExtractSvgSrc(row.svg_path);
      var svgCell =
        '<img class="leb-loc-svg-preview" src="' +
        lebLocEscHtml(finalSvgPath) +
        '" alt="' +
        lebLocEscHtml(row.name) +
        ' icon" width="24" height="24" loading="lazy" onerror="this.onerror=null;this.src=\'' +
        defaultSvgPath +
        "';\">";

      html += [
        '<div class="leb-loc-card" data-id="' + row.id + '">',
        '  <div class="leb-loc-checkbox-wrap">',
        '    <input type="checkbox" class="leb-loc-checkbox leb-loc-item-checkbox" value="' +
          row.id +
          '"' +
          (isSelected ? " checked" : "") +
          ">",
        "  </div>",
        '  <div class="leb-loc-s-no-badge">' + sno + "</div>",
        '  <div class="leb-loc-card-svg-preview">' + svgCell + "</div>",
        '  <div class="leb-loc-card-body">',
        '    <div class="leb-loc-card-row">',
        '      <span class="leb-loc-card-label">Name</span>',
        '      <span class="leb-loc-card-value">' +
          lebLocEscHtml(row.name) +
          "</span>",
        "    </div>",
        '    <div class="leb-loc-card-row">',
        '      <span class="leb-loc-card-label">Slug</span>',
        '      <span class="leb-loc-card-value">' +
          lebLocEscHtml(row.slug) +
          "</span>",
        "    </div>",
        '    <div class="leb-loc-card-row">',
        '      <span class="leb-loc-card-label">Updated</span>',
        '      <span class="leb-loc-card-value">' +
          lebLocEscHtml(lebLocFormatDate(row.updated_at)) +
          "</span>",
        "    </div>",
        "  </div>",
        '  <div class="leb-loc-card-actions">',
        '    <a href="' + editUrl + '" class="leb-loc-edit-btn">',
        '      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
        '        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>',
        '        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
        "      </svg>",
        "      Edit",
        "    </a>",
        '    <button class="leb-loc-delete-btn" data-id="' +
          row.id +
          '" data-name="' +
          lebLocEscHtml(row.name) +
          '">',
        '      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
        '        <polyline points="3 6 5 6 21 6"/>',
        '        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        '        <line x1="10" y1="11" x2="10" y2="17"/>',
        '        <line x1="14" y1="11" x2="14" y2="17"/>',
        "      </svg>",
        "      Delete",
        "    </button>",
        "  </div>",
        "</div>",
      ].join("");
    });
    domCardsList.innerHTML = html;

    /* Bind individual checkboxes */
    domCardsList
      .querySelectorAll(".leb-loc-item-checkbox")
      .forEach(function (cb) {
        cb.addEventListener("change", function () {
          var id = parseInt(this.value);
          if (this.checked) {
            if (!lebLocState.selectedIds.includes(id)) {
              lebLocState.selectedIds.push(id);
            }
          } else {
            lebLocState.selectedIds = lebLocState.selectedIds.filter(
              function (sid) {
                return sid !== id;
              },
            );
          }
          lebLocUpdateBulkBar();
        });
      });

    /* Bind delete buttons */
    domCardsList
      .querySelectorAll(".leb-loc-delete-btn")
      .forEach(function (btn) {
        btn.addEventListener("click", function () {
          lebLocConfirmDelete(
            this.getAttribute("data-id"),
            this.getAttribute("data-name"),
          );
        });
      });

    lebLocUpdateBulkBar();
  }

  /* ── Bulk Actions Support ────────────────────────────────── */
  window.lebLocUpdateBulkBar = function () {
    var bulkBar = document.getElementById("leb-loc-bulk-actions");
    var selectedCount = document.getElementById("leb-loc-selected-count");
    var selectAll = document.getElementById("leb-loc-select-all");
    if (!domCardsList) return;
    var pageChecks = domCardsList.querySelectorAll(".leb-loc-item-checkbox");

    if (bulkBar && selectedCount) {
      if (lebLocState.selectedIds.length > 0) {
        bulkBar.classList.add("leb-active");
        selectedCount.textContent =
          lebLocState.selectedIds.length + " selected";
      } else {
        bulkBar.classList.remove("leb-active");
      }
    }

    if (selectAll) {
      if (pageChecks.length > 0) {
        var allChecked = true;
        pageChecks.forEach(function (cb) {
          if (!cb.checked) {
            allChecked = false;
          }
        });
        selectAll.checked = allChecked;
      } else {
        selectAll.checked = false;
      }
    }
  };

  var domSelectAll = document.getElementById("leb-loc-select-all");
  if (domSelectAll) {
    domSelectAll.addEventListener("change", function (e) {
      var isChecked = e.target.checked;
      if (!domCardsList) return;
      var pageChecks = domCardsList.querySelectorAll(".leb-loc-item-checkbox");
      pageChecks.forEach(function (cb) {
        var id = parseInt(cb.value);
        cb.checked = isChecked;
        if (isChecked) {
          if (!lebLocState.selectedIds.includes(id)) {
            lebLocState.selectedIds.push(id);
          }
        } else {
          lebLocState.selectedIds = lebLocState.selectedIds.filter(
            function (sid) {
              return sid !== id;
            },
          );
        }
      });
      lebLocUpdateBulkBar();
    });
  }

  window.lebLocBulkDelete = function () {
    if (lebLocState.selectedIds.length === 0) {
      return;
    }

    if (typeof LEB_Confirm === "undefined") {
      if (
        confirm(
          "Delete " + lebLocState.selectedIds.length + " selected locations?",
        )
      ) {
        lebLocPerformBulkDelete();
      }
      return;
    }

    LEB_Confirm.show({
      title: "Delete Selected?",
      message:
        "Are you sure you want to delete " +
        lebLocState.selectedIds.length +
        " selected locations? This cannot be undone.",
      confirmText: "Delete All",
      type: "leb-warning",
      onConfirm: function () {
        lebLocPerformBulkDelete();
      },
    });
  };

  function lebLocPerformBulkDelete() {
    var formData = new FormData();
    formData.append("action", "leb_loc_bulk_delete_locations");
    formData.append("nonce", nonce);
    lebLocState.selectedIds.forEach(function (id) {
      formData.append("ids[]", id);
    });

    lebLocShowLoading();
    fetch(ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        lebLocHideLoading();
        if (data.success) {
          if (typeof LEB_Toaster !== "undefined") {
            LEB_Toaster.show(data.data.message || "Batch deleted.", "success");
          }
          lebLocState.selectedIds = [];
          lebLocFetchLocations();
        } else {
          if (typeof LEB_Toaster !== "undefined") {
            LEB_Toaster.show(
              data.data.message || "Bulk delete failed.",
              "error",
            );
          }
        }
      })
      .catch(function () {
        lebLocHideLoading();
        if (typeof LEB_Toaster !== "undefined") {
          LEB_Toaster.show("Network error during bulk delete.", "error");
        }
      });
  }

  /* ── Render: Pagination ──────────────────────────────────── */
  function lebLocRenderPagination(total, page, perPage) {
    var totalPages = Math.max(1, Math.ceil(total / perPage));
    var start = total === 0 ? 0 : (page - 1) * perPage + 1;
    var end = Math.min(page * perPage, total);

    if (domPagText) {
      domPagText.textContent = "Showing " + start + "–" + end + " of " + total;
    }

    var html = "";
    html +=
      '<button class="leb-loc-pg-btn" id="leb-loc-pg-prev" aria-label="Previous page"' +
      (page <= 1 ? " disabled" : "") +
      ">";
    html +=
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>';
    html += "</button>";

    var windowStart = Math.max(1, page - 2);
    var windowEnd = Math.min(totalPages, windowStart + 4);
    windowStart = Math.max(1, windowEnd - 4);

    for (var i = windowStart; i <= windowEnd; i++) {
      html +=
        '<button class="leb-loc-pg-btn' +
        (i === page ? " leb-loc-pg-active" : "") +
        '" data-page="' +
        i +
        '">' +
        i +
        "</button>";
    }

    html +=
      '<button class="leb-loc-pg-btn" id="leb-loc-pg-next" aria-label="Next page"' +
      (page >= totalPages ? " disabled" : "") +
      ">";
    html +=
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>';
    html += "</button>";

    if (domPagControls) {
      domPagControls.innerHTML = html;

      domPagControls
        .querySelectorAll(".leb-loc-pg-btn[data-page]")
        .forEach(function (btn) {
          btn.addEventListener("click", function () {
            lebLocState.currentPage = parseInt(
              this.getAttribute("data-page"),
              10,
            );
            lebLocFetchLocations();
          });
        });
    }

    var prevBtn = document.getElementById("leb-loc-pg-prev");
    var nextBtn = document.getElementById("leb-loc-pg-next");

    if (prevBtn) {
      prevBtn.addEventListener("click", function () {
        if (lebLocState.currentPage > 1) {
          lebLocState.currentPage--;
          lebLocFetchLocations();
        }
      });
    }
    if (nextBtn) {
      nextBtn.addEventListener("click", function () {
        if (lebLocState.currentPage < totalPages) {
          lebLocState.currentPage++;
          lebLocFetchLocations();
        }
      });
    }
  }

  /* ── Deletion Logic ──────────────────────────────────────── */
  function lebLocConfirmDelete(id, name) {
    if (typeof LEB_Confirm === "undefined") {
      if (confirm('Are you sure you want to delete "' + name + '"?')) {
        lebLocPerformDelete(id);
      }
      return;
    }
    LEB_Confirm.show({
      title: "Delete Location?",
      message:
        'Are you sure you want to delete "' +
        name +
        '"? This action is irreversible.',
      confirmText: "Delete Now",
      cancelText: "Cancel",
      type: "leb-warning",
      onConfirm: function () {
        lebLocPerformDelete(id);
      },
    });
  }

  function lebLocPerformDelete(id) {
    var formData = new FormData();
    formData.append("action", "leb_loc_delete_location");
    formData.append("nonce", nonce);
    formData.append("id", id);

    lebLocShowLoading();
    fetch(ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        lebLocHideLoading();
        if (data.success) {
          if (typeof LEB_Toaster !== "undefined") {
            LEB_Toaster.show(
              data.data.message || "Deleted successfully.",
              "success",
            );
          }
          lebLocFetchLocations();
        } else {
          if (typeof LEB_Toaster !== "undefined") {
            LEB_Toaster.show(data.data.message || "Failed to delete.", "error");
          }
        }
      })
      .catch(function () {
        lebLocHideLoading();
        if (typeof LEB_Toaster !== "undefined") {
          LEB_Toaster.show("Network error. Please try again.", "error");
        }
      });
  }

  /* ── AJAX Fetch ──────────────────────────────────────────── */
  function lebLocFetchLocations() {
    if (lebLocState.isLoading) {
      return;
    }
    lebLocState.isLoading = true;
    lebLocShowLoading();

    var formData = new FormData();
    formData.append("action", "leb_loc_get_locations");
    formData.append("nonce", nonce);
    formData.append("search", lebLocState.searchTerm);
    formData.append("page", lebLocState.currentPage);
    formData.append("per_page", lebLocState.perPage);

    fetch(ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        lebLocHideLoading();
        lebLocState.isLoading = false;

        if (data.success && data.data) {
          var result = data.data;
          lebLocState.totalItems = result.total;
          lebLocRenderCards(result.items);
          lebLocRenderPagination(
            result.total,
            lebLocState.currentPage,
            lebLocState.perPage,
          );
        } else {
          lebLocRenderEmpty("Failed to load locations.");
          if (typeof LEB_Toaster !== "undefined") {
            LEB_Toaster.show(
              data.data && data.data.message
                ? data.data.message
                : "Error loading locations.",
              "error",
            );
          }
        }
      })
      .catch(function () {
        lebLocHideLoading();
        lebLocState.isLoading = false;
        lebLocRenderEmpty("Network error. Please try again.");
      });
  }

  /* ── Search Logic ────────────────────────────────────────── */
  function lebLocUpdateClearBtn() {
    if (!domSearchInput || !domSearchClear) return;
    if (domSearchInput.value.length > 0) {
      domSearchClear.classList.add("leb-loc-clear-visible");
    } else {
      domSearchClear.classList.remove("leb-loc-clear-visible");
    }
  }

  if (domSearchInput) {
    domSearchInput.addEventListener("input", function () {
      lebLocUpdateClearBtn();
      clearTimeout(lebLocState.searchTimer);
      var val = this.value.trim();
      if (val.length === 0 || val.length >= 2) {
        lebLocState.searchTimer = setTimeout(function () {
          lebLocState.searchTerm = val;
          lebLocState.currentPage = 1;
          lebLocFetchLocations();
        }, 350);
      }
    });

    domSearchInput.addEventListener("focus", function () {
      if (domSearchWrap) domSearchWrap.classList.add("leb-loc-search-focused");
    });
    domSearchInput.addEventListener("blur", function () {
      if (domSearchWrap)
        domSearchWrap.classList.remove("leb-loc-search-focused");
    });
  }

  if (domSearchClear) {
    domSearchClear.addEventListener("click", function () {
      if (domSearchInput) domSearchInput.value = "";
      lebLocUpdateClearBtn();
      lebLocState.searchTerm = "";
      lebLocState.currentPage = 1;
      lebLocFetchLocations();
      if (domSearchInput) domSearchInput.focus();
    });
  }

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && domSearchInput) {
      domSearchInput.value = "";
      lebLocUpdateClearBtn();
      lebLocState.searchTerm = "";
      lebLocState.currentPage = 1;
      lebLocFetchLocations();
    }
  });

  /* ── Bootstrap ───────────────────────────────────────────── */
  if (domCardsList) {
    lebLocFetchLocations();
  }
});
