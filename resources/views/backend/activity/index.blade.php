@extends('backend.master')

@section('title', 'Audit Logs')

@push('style')
<style>
/* ── Premium Audit Log Styles ─────────────────────────────────────── */
.audit-header    { background: linear-gradient(45deg, #800000, #A01010); }

/* Filters */
.audit-filter-bar {
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 1rem 1.2rem;
    margin-bottom: 1.2rem;
}

/* Premium Table */
.audit-table thead th {
    background: #4E342E !important;
    color: #fff !important;
    border: none;
    font-size: .75rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: 14px 12px;
}
.audit-table tbody td {
    vertical-align: middle;
    padding: .7rem .75rem;
    border-bottom: 1px solid #edf2f9;
    color: #2d3748;
}
.audit-table tbody tr:last-child td { border-bottom: none; }
.audit-table tbody tr:hover { background: #f8fafc; }

/* Action badge colours */
.badge-action-created  { background: #d4edda; color: #155724; }
.badge-action-updated  { background: #cce5ff; color: #004085; }
.badge-action-deleted  { background: #f8d7da; color: #721c24; }
.badge-action-login    { background: #e2d9f3; color: #4a235a; }
.badge-action-default  { background: #e2e8f0; color: #4a5568; }

.badge-action {
    padding: .35em .75em;
    border-radius: 20px;
    font-size: .78rem;
    font-weight: 700;
    display: inline-flex; align-items: center; gap: .3rem;
}

/* Expandable JSON panel */
.audit-properties {
    background: #1e1e2e;
    color: #cdd6f4;
    border-radius: 10px;
    font-size: .78rem;
    max-height: 220px;
    overflow-y: auto;
    padding: .8rem 1rem;
}

/* Search bar */
.audit-search-wrap .input-group-text { background: #fff; border-right: none; border-radius: 10px 0 0 10px; }
.audit-search-wrap .form-control     { border-left: none; border-radius: 0 10px 10px 0; }

/* Loading skeleton */
#auditLoadingRow td { padding: 3rem; }

/* Cap warning */
#capWarning { border-radius: 10px; }

/* Pagination — matches backup history maroon style */
#auditPaginator .page-link { border-radius: 6px; margin: 0 2px; color: #800000; }
#auditPaginator .page-item.active .page-link { background-color: #800000 !important; border-color: #800000 !important; color: #fff !important; }
#auditPaginator .page-link:hover { color: #600000; }
</style>
@endpush

@section('content')
<div class="row animate__animated animate__fadeIn">
  <div class="col-12">
    <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden; min-height: 70vh;">

      {{-- ── Header ─────────────────────────────────────────────────────────── --}}
      <div class="card-header audit-header py-3 d-flex align-items-center">
        <h3 class="card-title font-weight-bold text-white mb-0">
          <i class="fas fa-shield-alt mr-2"></i> System Audit Logs
        </h3>
        <div class="ml-auto d-flex align-items-center gap-2">
          <span id="statsTotal" class="badge bg-white shadow-sm px-3 py-2 font-weight-bold" style="border-radius: 8px; color: #800000; font-size: .85rem;">
            Total: {{ number_format($stats['total']) }}
          </span>
        </div>
      </div>

      <div class="card-body p-4">

        {{-- ── Spotlight Search ─────────────────────────────────────────────── --}}
        <div class="audit-search-wrap input-group shadow-sm mb-3">
          <div class="input-group-prepend">
            <span class="input-group-text border-0 pl-3">
              <i class="fas fa-search text-muted"></i>
            </span>
          </div>
          <input type="text" id="auditSearch" class="form-control border-0 apple-input py-3"
                 placeholder="Search by user, description, IP address…" style="font-size: .95rem; box-shadow: none;">
        </div>

        {{-- ── Filter Bar ───────────────────────────────────────────────────── --}}
        <div class="audit-filter-bar">
          <div class="row align-items-end g-2">
            <div class="col-md-2">
              <label class="font-weight-bold small mb-1">From</label>
              <input type="date" id="filterStart" class="form-control form-control-sm" style="border-radius: 8px;"
                     value="{{ now()->subDays(29)->format('Y-m-d') }}">
            </div>
            <div class="col-md-2">
              <label class="font-weight-bold small mb-1">To</label>
              <input type="date" id="filterEnd" class="form-control form-control-sm" style="border-radius: 8px;"
                     value="{{ now()->format('Y-m-d') }}">
            </div>
            <div class="col-md-2">
              <label class="font-weight-bold small mb-1">User</label>
              <select id="filterUser" class="form-control form-control-sm" style="border-radius: 8px;">
                <option value="">All Users</option>
                @foreach($users as $u)
                  <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2">
              <label class="font-weight-bold small mb-1">Action</label>
              <select id="filterAction" class="form-control form-control-sm" style="border-radius: 8px;">
                <option value="">All Actions</option>
                <option value="Created">Created</option>
                <option value="Updated">Updated</option>
                <option value="Deleted">Deleted</option>
                <option value="Login">Login</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="font-weight-bold small mb-1">Module</label>
              <select id="filterModule" class="form-control form-control-sm" style="border-radius: 8px;">
                <option value="">All Modules</option>
                @foreach($modules as $mod)
                  <option value="{{ $mod }}">{{ $mod }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
              <button id="applyFilter" class="btn btn-sm btn-block font-weight-bold shadow-sm"
                      style="background: #800000; color: #fff; border-radius: 8px;">
                <i class="fas fa-filter mr-1"></i> Filter
              </button>
              <button id="clearFilter" class="btn btn-sm btn-light font-weight-bold shadow-sm"
                      style="border-radius: 8px;" title="Clear filters">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>

        {{-- ── Cap Warning ──────────────────────────────────────────────────── --}}
        <div id="capWarning" class="alert alert-warning border-0 shadow-sm py-2 px-3 small d-none mb-3">
          <i class="fas fa-exclamation-triangle mr-1"></i>
          Showing the latest <strong>500 records</strong>. Use the date or action filters to narrow your search.
        </div>

        {{-- ── Table ────────────────────────────────────────────────────────── --}}
        <div class="table-responsive">
          <table class="table audit-table mb-0">
            <thead>
              <tr>
                <th style="width: 130px;">Time</th>
                <th style="width: 150px;">User</th>
                <th style="width: 110px;">Action</th>
                <th style="width: 120px;">Module</th>
                <th>Description</th>
                <th style="width: 80px; text-align: center;">Details</th>
              </tr>
            </thead>
            <tbody id="auditTableBody">
              <tr id="auditLoadingRow">
                <td colspan="6" class="text-center text-muted py-5">
                  <i class="fas fa-spinner fa-spin fa-2x mb-2 text-gray-400"></i><br>
                  Loading logs…
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        {{-- ── Pagination ───────────────────────────────────────────────────── --}}
        <div id="auditPaginator" class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">
          <div id="auditMeta" class="text-muted small"></div>
          <nav><ul class="pagination pagination-sm mb-0" id="auditPages"></ul></nav>
          <div class="d-flex align-items-center gap-2">
            <small class="text-muted font-weight-bold">Per page:</small>
            <select id="perPage" class="form-control form-control-sm" style="width: 70px; border-radius: 6px;">
              <option value="25" selected>25</option>
              <option value="50">50</option>
              <option value="100">100</option>
              <option value="200">200</option>
            </select>
          </div>
        </div>

      </div>{{-- /card-body --}}
    </div>
  </div>
</div>
@endsection

@push('script')
<script>
(function () {
    'use strict';

    const ENDPOINT = '{{ route("backend.admin.activity.logs.data") }}';

    // ── Action badge map ─────────────────────────────────────────────────────
    const ACTION_BADGE = {
        'Created': { cls: 'badge-action-created', icon: 'fa-plus-circle' },
        'Updated': { cls: 'badge-action-updated', icon: 'fa-edit'       },
        'Deleted': { cls: 'badge-action-deleted', icon: 'fa-trash-alt'  },
        'Login'  : { cls: 'badge-action-login',   icon: 'fa-sign-in-alt'},
    };

    let currentPage = 1;
    let searchTimer = null;

    // ── Gather current filter values ─────────────────────────────────────────
    function getParams(page) {
        return {
            page       : page || currentPage,
            per_page   : $('#perPage').val(),
            start_date : $('#filterStart').val(),
            end_date   : $('#filterEnd').val(),
            user_id    : $('#filterUser').val(),
            action     : $('#filterAction').val(),
            module     : $('#filterModule').val(),
            search     : $('#auditSearch').val().trim(),
        };
    }

    // ── Render action badge ──────────────────────────────────────────────────
    function actionBadge(action) {
        const cfg = ACTION_BADGE[action] || { cls: 'badge-action-default', icon: 'fa-dot-circle' };
        return `<span class="badge-action ${cfg.cls}"><i class="fas ${cfg.icon}"></i> ${action}</span>`;
    }

    // ── Render properties toggle button ─────────────────────────────────────
    function propertiesBtn(log) {
        if (!log.properties || (Array.isArray(log.properties) && log.properties.length === 0)) {
            return '<span class="text-muted">—</span>';
        }
        return `<button class="btn btn-xs btn-light border shadow-sm view-props-btn"
                        style="border-radius: 6px; font-size: .75rem;"
                        data-id="${log.id}"
                        data-props='${JSON.stringify(log.properties)}'>
                    <i class="fas fa-eye"></i>
                </button>`;
    }

    // ── Format datetime ──────────────────────────────────────────────────────
    function fmtDate(dt) {
        const d = new Date(dt.replace(' ', 'T'));
        const date = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        const time = d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
        return `${date}<br><small class="text-muted">${time}</small>`;
    }

    // ── Build table rows ─────────────────────────────────────────────────────
    function renderRows(logs) {
        if (!logs.length) {
            return `<tr>
                      <td colspan="6" class="text-center text-muted py-5">
                        <i class="fas fa-search fa-3x mb-3 text-gray-300"></i><br>
                        No audit logs match your filters.
                      </td>
                    </tr>`;
        }

        return logs.map(log => {
            const user    = log.user ? `<strong>${log.user.name}</strong><br><small class="text-muted">${log.ip_address || ''}</small>`
                                     : `<span class="text-muted">System</span>`;
            const detailRow = (log.properties && !Array.isArray(log.properties))
                ? `<tr class="audit-props-row d-none" id="props_${log.id}">
                     <td colspan="6" style="padding: .5rem 1rem;">
                       <pre class="audit-properties">${JSON.stringify(log.properties, null, 2)}</pre>
                     </td>
                   </tr>`
                : '';

            return `<tr>
                      <td>${fmtDate(log.created_at)}</td>
                      <td>${user}</td>
                      <td>${actionBadge(log.action)}</td>
                      <td><span class="font-weight-bold text-secondary small">${log.module || '—'}</span></td>
                      <td>${log.description || '—'}</td>
                      <td class="text-center">${propertiesBtn(log)}</td>
                    </tr>${detailRow}`;
        }).join('');
    }

    // ── Render pagination ────────────────────────────────────────────────────
    function renderPagination(meta) {
        const from  = ((meta.current_page - 1) * meta.per_page) + 1;
        const to    = Math.min(meta.current_page * meta.per_page, meta.total);
        $('#auditMeta').html(`Showing <strong>${from}–${to}</strong> of <strong>${meta.total.toLocaleString()}</strong> logs`);

        let pages = '';
        // Prev
        pages += `<li class="page-item ${meta.current_page <= 1 ? 'disabled' : ''}">
                    <a class="page-link audit-page" data-page="${meta.current_page - 1}" href="#">&laquo;</a>
                  </li>`;
        // Page numbers (condensed)
        const total = meta.last_page;
        const cur   = meta.current_page;
        let shown = [];
        shown.push(1);
        if (cur > 3)  shown.push('…');
        for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) shown.push(i);
        if (cur < total - 2) shown.push('…');
        if (total > 1) shown.push(total);

        shown.forEach(p => {
            if (p === '…') {
                pages += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            } else {
                pages += `<li class="page-item ${p === cur ? 'active' : ''}">
                            <a class="page-link audit-page" data-page="${p}" href="#">${p}</a>
                          </li>`;
            }
        });
        // Next
        pages += `<li class="page-item ${meta.current_page >= meta.last_page ? 'disabled' : ''}">
                    <a class="page-link audit-page" data-page="${meta.current_page + 1}" href="#">&raquo;</a>
                  </li>`;
        $('#auditPages').html(pages);
    }

    // ── Fetch & render ───────────────────────────────────────────────────────
    function loadLogs(page) {
        currentPage = page || 1;
        $('#auditTableBody').html(
            `<tr id="auditLoadingRow"><td colspan="6" class="text-center text-muted py-5">
               <i class="fas fa-spinner fa-spin fa-2x mb-2"></i><br>Loading…
             </td></tr>`
        );
        $('#auditPages').html('');
        $('#auditMeta').html('');

        $.ajax({
            url    : ENDPOINT,
            method : 'GET',
            data   : getParams(currentPage),
            success: function (res) {
                $('#capWarning').toggleClass('d-none', !res.capped);
                $('#auditTableBody').html(renderRows(res.data));
                renderPagination({
                    current_page: res.current_page,
                    last_page   : res.last_page,
                    total       : res.total,
                    per_page    : res.per_page,
                });
            },
            error: function () {
                $('#auditTableBody').html(
                    `<tr><td colspan="6" class="text-center text-danger py-5">
                       <i class="fas fa-exclamation-circle fa-2x mb-2"></i><br>
                       Failed to load logs. Please refresh and try again.
                     </td></tr>`
                );
            }
        });
    }

    // ── Event Listeners ──────────────────────────────────────────────────────
    $(document).ready(function () {

        // Initial load (last 30 days default)
        loadLogs(1);

        // Filter button
        $('#applyFilter').on('click', function () { loadLogs(1); });

        // Clear filters
        $('#clearFilter').on('click', function () {
            $('#filterStart').val('{{ now()->subDays(29)->format("Y-m-d") }}');
            $('#filterEnd').val('{{ now()->format("Y-m-d") }}');
            $('#filterUser, #filterAction, #filterModule').val('');
            $('#auditSearch').val('');
            loadLogs(1);
        });

        // Per-page change
        $('#perPage').on('change', function () { loadLogs(1); });

        // Spotlight search (debounced 400ms)
        $('#auditSearch').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => loadLogs(1), 400);
        });

        // Enter key on search
        $('#auditSearch').on('keydown', function (e) {
            if (e.key === 'Enter') { clearTimeout(searchTimer); loadLogs(1); }
        });

        // Pagination click
        $(document).on('click', '.audit-page', function (e) {
            e.preventDefault();
            const p = $(this).data('page');
            if (p && p !== '…') loadLogs(p);
        });

        // Expandable properties panel
        $(document).on('click', '.view-props-btn', function () {
            const id  = $(this).data('id');
            const row = $(`#props_${id}`);
            row.toggleClass('d-none');
            const icon = $(this).find('i');
            icon.toggleClass('fa-eye fa-eye-slash');
        });
    });
})();
</script>
@endpush
