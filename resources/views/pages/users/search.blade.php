@extends(match(auth()->user()?->role) {
    'teacher'     => 'layouts.appTeach',
    'admin'       => 'layouts.appAdmin',
    'superadmin'  => 'layouts.appAdmin',
    'accountancy' => 'layouts.appAcc',
    'educ'        => 'layouts.appEduc',
    'psych'       => 'layouts.app',
    default       => 'layouts.app'
})

@section('title', 'User Search')
@section('page-heading', 'User Search')

@section('content')
<style>
    @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&family=Instrument+Serif:ital@0;1&display=swap');

    .us-wrap {
        max-width: 860px;
        margin: 0 auto;
        padding: 2rem 1rem 4rem;
        font-family: 'DM Sans', sans-serif;
    }

    .us-heading {
        font-family: 'DM Sans', serif;
        font-size: 28px;
        font-weight: 400;
        color: #0f0f0f;
        margin: 0 0 2px;
        letter-spacing: -0.02em;
    }

    .us-sub {
        font-size: 13px;
        color: #888;
        margin: 0 0 2rem;
    }

    .us-filters {
        display: grid;
        grid-template-columns: 1fr 160px;
        gap: 10px;
        margin-bottom: 12px;
    }

    .us-field {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .us-label {
        font-size: 11px;
        font-weight: 500;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #aaa;
    }

    .us-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
    }

    .us-input,
    .us-select {
        height: 40px;
        padding: 0 12px;
        border: 1px solid #e4e4e4;
        border-radius: 8px;
        font-family: 'DM Sans', sans-serif;
        font-size: 14px;
        color: #111;
        background: #fff;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
        appearance: none;
        -webkit-appearance: none;
        width: 100%;
    }

    .us-input { padding-right: 36px; }

    .us-input:focus,
    .us-select:focus {
        border-color: #111;
        box-shadow: 0 0 0 3px rgba(0,0,0,0.06);
    }

    .us-input::placeholder { color: #bbb; }

    .us-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23aaa' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 32px;
    }

    .us-spinner {
        position: absolute;
        right: 10px;
        width: 16px;
        height: 16px;
        border: 2px solid #e4e4e4;
        border-top-color: #999;
        border-radius: 50%;
        animation: us-spin 0.6s linear infinite;
        display: none;
        flex-shrink: 0;
    }

    .us-spinner.visible { display: block; }

    @keyframes us-spin { to { transform: rotate(360deg); } }

    .us-status {
        font-size: 12px;
        color: #aaa;
        min-height: 18px;
        margin-bottom: 1.25rem;
    }

    .us-divider {
        height: 1px;
        background: #f0f0f0;
    }

    .us-table {
        width: 100%;
        border-collapse: collapse;
    }

    .us-table thead th {
        font-size: 11px;
        font-weight: 500;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #bbb;
        padding: 12px 14px;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
    }

    .us-table tbody tr {
        border-bottom: 1px solid #f7f7f7;
        cursor: pointer;
        transition: background 0.1s;
        animation: us-fadeIn 0.15s ease both;
    }

    @keyframes us-fadeIn {
        from { opacity: 0; transform: translateY(4px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .us-table tbody tr:hover { background: #fafafa; }
    .us-table tbody tr:hover .us-row-arrow { opacity: 1; }

    .us-table tbody td {
        padding: 13px 14px;
        vertical-align: middle;
    }

    .us-user-name {
        font-size: 14px;
        font-weight: 500;
        color: #111;
        margin: 0 0 2px;
    }

    .us-user-id {
        font-size: 12px;
        color: #bbb;
        margin: 0;
    }

    .us-program-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 500;
        padding: 3px 9px;
        border-radius: 99px;
        background: #f3f3f3;
        color: #555;
        letter-spacing: 0.02em;
        text-transform: capitalize;
    }

    .us-row-arrow {
        font-size: 16px;
        color: #ccc;
        opacity: 0;
        transition: opacity 0.15s;
        text-align: right;
    }

    .us-empty {
        text-align: center;
        padding: 3rem 0;
        color: #ccc;
        font-size: 14px;
    }

    @media (max-width: 580px) {
        .us-filters { grid-template-columns: 1fr; }
    }
</style>

<div class="us-wrap">
    <h1 class="us-heading">User Search</h1>
    <p class="us-sub">Search for students, teachers, and staff.</p>

    <div class="us-filters">
        <div class="us-field">
            <label class="us-label" for="userSearchInput">Search</label>
            <div class="us-input-wrap">
                <input
                    id="userSearchInput"
                    type="text"
                    class="us-input"
                    placeholder="Name, ID number, or email"
                    autocomplete="off"
                />
                <div class="us-spinner" id="searchSpinner"></div>
            </div>
        </div>

        <div class="us-field">
            <label class="us-label" for="programFilter">Program</label>
            <select id="programFilter" class="us-select">
                <option value="">All programs</option>
                <option value="psych">Psych</option>
                <option value="educ">Educ</option>
                <option value="accountancy">Accountancy</option>
                <option value="teacher">Teacher</option>
                <option value="admin">Admin</option>
            </select>
        </div>
    </div>

    <div class="us-status" id="searchStatus"></div>

    <div class="us-divider"></div>

    <table class="us-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Program</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="usersResultsBody">
            <tr>
                <td colspan="3">
                    <div class="us-empty">Start typing to search users.</div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection

@section('scripts')
<script>
    const apiUrl  = '{{ route('users.search.api') }}';
    const chatUrl = '{{ route('chat.index') }}'; // adjust to your actual chat route name

    function escHtml(str) {
        return String(str ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderResults(results) {
        const $body = $('#usersResultsBody');

        if (!results || results.length === 0) {
            $body.html(`<tr><td colspan="3"><div class="us-empty">No users found.</div></td></tr>`);
            return;
        }

        const rows = results.map((u, i) => `
            <tr data-user-id="${escHtml(u.id)}" style="animation-delay:${i * 30}ms">
                <td>
                    <p class="us-user-name">${escHtml(u.name || u.idnumber)}</p>
                    <p class="us-user-id">${escHtml(u.idnumber)}</p>
                </td>
                <td>
                    <span class="us-program-badge">${escHtml(u.role)}</span>
                </td>
                <td class="us-row-arrow">&#8594;</td>
            </tr>
        `).join('');

        $body.html(rows);
    }

    let debounceTimer = null;

    function doSearch() {
        const q = String($('#userSearchInput').val() || '').trim();

        if (q.length < 1) {
            $('#usersResultsBody').html(`
                <tr><td colspan="3"><div class="us-empty">Start typing to search users.</div></td></tr>
            `);
            $('#searchStatus').text('');
            return;
        }

        const program = $('#programFilter').val();

        $('#searchSpinner').addClass('visible');
        $('#searchStatus').text('');

        $.get(apiUrl, { q, role: program })
            .done(function (res) {
                renderResults(res.results || []);
            })
            .fail(function () {
                $('#searchStatus').text('Search failed. Please try again.');
            })
            .always(function () {
                $('#searchSpinner').removeClass('visible');
            });
    }

    $(document).ready(function () {
        // Realtime — fires 300ms after user stops typing
        $('#userSearchInput').on('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(doSearch, 300);
        });

        // Re-search when program filter changes
        $('#programFilter').on('change', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(doSearch, 150);
        });

        // Open chat on row click
        $(document).on('click', '#usersResultsBody tr[data-user-id]', function () {
            const userId = $(this).data('user-id');
            window.location.href = chatUrl + '?user=' + userId;
        });
    });
</script>
@endsection
