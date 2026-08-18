@extends($layout ?? 'layouts.app')

@section('title', 'Messages')
@section('page-heading', 'Messages')

@section('content')
<style>
    .ch-shell {
        --ch-accent: #0f6e56;
        --ch-accent-soft: #d7f0e8;
        --ch-accent-shadow: rgba(15, 110, 86, 0.18);
        --ch-head-bg: linear-gradient(135deg, #fbfefc 0%, #f2f8f5 100%);
        --ch-send-hover: #095447;
        --ch-sidebar-bg: #f8fbfa;

        display: grid;
        grid-template-columns: 380px 1fr;
        border: 1px solid #e7ecea;
        border-radius: 20px;
        overflow: hidden;
        height: 82vh;
        min-height: 620px;
        background: #fff;
        box-shadow: 0 16px 34px rgba(16, 24, 40, 0.08);
    }

    .ch-shell.is-teacher {
        --ch-accent: #245e55;
        --ch-accent-soft: #d8ece8;
        --ch-accent-shadow: rgba(36, 94, 85, 0.2);
        --ch-head-bg: linear-gradient(135deg, #f7fbfa 0%, #ecf4f2 100%);
        --ch-send-hover: #1a4840;
        --ch-sidebar-bg: #f5faf9;
    }

    .ch-shell.is-admin {
        --ch-accent: #1f4f8f;
        --ch-accent-soft: #dbe8fb;
        --ch-accent-shadow: rgba(31, 79, 143, 0.18);
        --ch-head-bg: linear-gradient(135deg, #f8fbff 0%, #edf4fd 100%);
        --ch-send-hover: #173d6e;
        --ch-sidebar-bg: #f6f9fd;
    }

    .ch-shell.is-student {
        --ch-accent: #3a4180;
        --ch-accent-soft: #e1e5fb;
        --ch-accent-shadow: rgba(58, 65, 128, 0.2);
        --ch-head-bg: linear-gradient(135deg, #fafbff 0%, #f2f4ff 100%);
        --ch-send-hover: #2d3159;
        --ch-sidebar-bg: #f7f9ff;
    }

    .ch-left {
        display: flex;
        flex-direction: column;
        border-right: 1px solid #e8eeec;
        overflow: hidden;
        background: var(--ch-sidebar-bg);
    }

    .ch-new-bar {
        padding: 18px;
        border-bottom: 1px solid #e7ecea;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .ch-new-label {
        font-size: 13px;
        font-weight: 500;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #79858f;
        margin: 0;
    }

    .ch-search-input {
        width: 100%;
        height: 46px;
        padding: 0 16px;
        border: 1px solid #d7dfdc;
        border-radius: 999px;
        font-family: 'DM Sans', sans-serif;
        font-size: 16px;
        font-weight: 500;
        color: #111827;
        outline: none;
        transition: border-color 0.18s, box-shadow 0.18s, background-color 0.18s;
        background: #fff;
    }

    .ch-search-input:focus {
        border-color: var(--ch-accent);
        box-shadow: 0 0 0 4px var(--ch-accent-shadow);
        background: #ffffff;
    }

    .ch-search-input::placeholder {
        color: #9aa6b0;
        font-weight: 400;
    }

    .ch-search-results {
        display: none;
        flex-direction: column;
        border: 1px solid #e0e7e4;
        border-radius: 14px;
        overflow: hidden;
        max-height: 260px;
        overflow-y: auto;
        background: #fff;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
    }

    .ch-search-results::-webkit-scrollbar {
        width: 4px;
    }

    .ch-search-results::-webkit-scrollbar-thumb {
        background: #d8e0dc;
        border-radius: 999px;
    }

    .ch-search-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 15px;
        cursor: pointer;
        border-bottom: 1px solid #f1f4f3;
        transition: background-color 0.15s;
    }

    .ch-search-item:last-child {
        border-bottom: none;
    }

    .ch-search-item:hover {
        background: #f8fbfa;
    }

    .ch-search-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: #0f172a;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 500;
        flex-shrink: 0;
    }

    .ch-search-name {
        font-size: 16px;
        font-weight: 500;
        color: #0f172a;
    }

    .ch-search-role {
        font-size: 13px;
        color: #8b95a1;
    }

    .ch-search-empty {
        padding: 14px;
        font-size: 14px;
        color: #8b95a1;
        text-align: center;
    }

    .ch-convo-head {
        padding: 16px 18px 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    .ch-convo-label {
        font-size: 13px;
        font-weight: 500;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #8a95a0;
        margin: 0;
    }

    .ch-count-badge {
        font-size: 13px;
        background: #eaf1ee;
        color: #46525d;
        padding: 4px 11px;
        border-radius: 999px;
        font-weight: 500;
    }

    .ch-convo-list {
        flex: 1;
        overflow-y: auto;
        padding: 4px 10px 10px;
    }

    .ch-convo-list::-webkit-scrollbar {
        width: 4px;
    }

    .ch-convo-list::-webkit-scrollbar-thumb {
        background: #d8e0dc;
        border-radius: 999px;
    }

    .ch-convo-item {
        display: block;
        padding: 14px;
        cursor: pointer;
        text-decoration: none;
        transition: transform 0.14s, background-color 0.14s, box-shadow 0.14s;
        border-radius: 14px;
        border: 1px solid transparent;
        margin-bottom: 8px;
    }

    .ch-convo-item:hover {
        background: #ffffff;
        border-color: #e2e9e6;
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06);
    }

    .ch-convo-item.active {
        background: #ffffff;
        border-color: var(--ch-accent-soft);
        box-shadow: inset 0 0 0 1px var(--ch-accent-soft), 0 10px 22px rgba(15, 23, 42, 0.08);
    }

    .ch-convo-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 10px;
        margin-bottom: 6px;
    }

    .ch-convo-name {
        font-size: 16px;
        font-weight: 500;
        color: #0f172a;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 220px;
    }

    .ch-convo-time {
        font-size: 13px;
        color: #88939f;
        flex-shrink: 0;
        font-weight: 500;
    }

    .ch-convo-preview {
        font-size: 14px;
        color: #74808c;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ch-online-dot {
        display: inline-block;
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: var(--ch-accent);
        margin-right: 7px;
        vertical-align: middle;
        box-shadow: 0 0 0 3px var(--ch-accent-soft);
    }

    .ch-main {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #ffffff;
    }

    .ch-main-head {
        padding: 18px 24px;
        border-bottom: 1px solid #e7ecea;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 76px;
        background: var(--ch-head-bg);
    }

    .ch-main-name {
        font-size: 20px;
        font-weight: 500;
        color: #0f172a;
        margin: 0 0 2px;
    }

    .ch-main-status {
        font-size: 14px;
        color: #718091;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 5px;
        font-weight: 500;
    }

    .ch-main-status.online {
        color: var(--ch-accent);
        font-weight: 500;
    }

    .ch-refresh-btn {
        height: 38px;
        padding: 0 16px;
        background: #fff;
        border: 1px solid #d8e0dc;
        border-radius: 999px;
        font-family: 'DM Sans', sans-serif;
        font-size: 14px;
        font-weight: 500;
        color: #3e4b58;
        cursor: pointer;
        transition: border-color 0.15s, color 0.15s, box-shadow 0.15s;
    }

    .ch-refresh-btn:hover {
        border-color: var(--ch-accent);
        color: var(--ch-accent);
        box-shadow: 0 4px 12px var(--ch-accent-shadow);
    }

    .ch-messages {
        flex: 1;
        overflow-y: auto;
        padding: 26px 26px 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: radial-gradient(circle at top right, #fafefd 0%, #ffffff 42%);
    }

    .ch-messages::-webkit-scrollbar {
        width: 5px;
    }

    .ch-messages::-webkit-scrollbar-thumb {
        background: #d8e0dc;
        border-radius: 999px;
    }

    .ch-empty {
        margin: auto;
        text-align: center;
        color: #81909e;
        font-size: 16px;
        font-weight: 500;
    }

    .ch-bubble-wrap {
        display: flex;
        flex-direction: column;
        max-width: 76%;
        animation: ch-pop 0.18s ease both;
    }

    @keyframes ch-pop {
        from {
            opacity: 0;
            transform: translateY(4px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .ch-bubble-wrap.me {
        align-self: flex-end;
        align-items: flex-end;
    }

    .ch-bubble-wrap.them {
        align-self: flex-start;
        align-items: flex-start;
    }

    .ch-bubble {
        padding: 13px 16px;
        border-radius: 18px;
        font-size: 17px;
        line-height: 1.6;
        word-break: break-word;
        white-space: pre-wrap;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
    }

    .ch-bubble-wrap.me .ch-bubble {
        background: var(--ch-accent);
        color: #fff;
        border-bottom-right-radius: 5px;
        box-shadow: 0 10px 20px var(--ch-accent-shadow);
    }

    .ch-bubble-wrap.them .ch-bubble {
        background: #eef3f1;
        color: #1f2937;
        border-bottom-left-radius: 5px;
    }

    .ch-shell.is-student .ch-bubble-wrap.them .ch-bubble,
    .ch-shell.is-admin .ch-bubble-wrap.them .ch-bubble {
        background: #eceffe;
    }

    .ch-bubble-meta {
        font-size: 13px;
        color: #7d8a97;
        margin-top: 4px;
        padding: 0 3px;
        font-weight: 500;
    }

    .ch-input-bar {
        padding: 16px 20px;
        border-top: 1px solid #e7ecea;
        display: flex;
        gap: 12px;
        align-items: flex-end;
        flex-shrink: 0;
        background: #ffffff;
    }

    .ch-textarea {
        flex: 1;
        min-height: 48px;
        max-height: 150px;
        padding: 12px 14px;
        border: 1px solid #d7dfdc;
        border-radius: 12px;
        font-family: 'DM Sans', sans-serif;
        font-size: 16px;
        color: #111827;
        resize: none;
        outline: none;
        transition: border-color 0.18s, box-shadow 0.18s;
        line-height: 1.5;
    }

    .ch-textarea:focus {
        border-color: var(--ch-accent);
        box-shadow: 0 0 0 4px var(--ch-accent-shadow);
    }

    .ch-textarea::placeholder {
        color: #9aa6b0;
    }

    .ch-send-btn {
        height: 46px;
        min-width: 92px;
        padding: 0 18px;
        background: var(--ch-accent);
        color: #fff;
        border: none;
        border-radius: 12px;
        font-family: 'DM Sans', sans-serif;
        font-size: 16px;
        font-weight: 500;
        letter-spacing: 0.03em;
        cursor: pointer;
        flex-shrink: 0;
        transition: background-color 0.15s, transform 0.1s, box-shadow 0.15s;
        box-shadow: 0 8px 16px var(--ch-accent-shadow);
    }

    .ch-send-btn:hover {
        background: var(--ch-send-hover);
    }

    .ch-send-btn:active {
        transform: scale(0.98);
    }

    .ch-send-btn:disabled {
        background: #ced6d2;
        box-shadow: none;
        cursor: not-allowed;
    }

    .ch-state {
        text-align: center;
        padding: 2rem 1rem;
        color: #81909e;
        font-size: 16px;
        font-weight: 500;
    }

    @media (max-width: 920px) {
        .ch-shell {
            grid-template-columns: 320px 1fr;
        }

        .ch-bubble-wrap {
            max-width: 80%;
        }
    }

    @media (max-width: 700px) {
        .ch-shell {
            grid-template-columns: 1fr;
            height: auto;
            min-height: 0;
        }

        .ch-left {
            border-right: none;
            border-bottom: 1px solid #e7ecea;
            height: 290px;
        }

        .ch-main-head {
            padding: 16px 18px;
        }

        .ch-messages {
            height: 42vh;
            padding: 18px;
        }

        .ch-input-bar {
            padding: 12px 14px;
        }
    }
</style>

<div class="ch-shell {{ ($chatTheme ?? 'student') === 'teacher' ? 'is-teacher' : (($chatTheme ?? 'student') === 'admin' ? 'is-admin' : 'is-student') }}">

    {{-- ── Left panel ── --}}
    <div class="ch-left">

        {{-- Search / new chat ── --}}
        <div class="ch-new-bar">
            <p class="ch-new-label">New Conversation</p>
            <input type="text" id="userSearchInput" class="ch-search-input" placeholder="Search by name or ID...">
            <div class="ch-search-results" id="userSearchResults"></div>
        </div>

        {{-- Conversation list ── --}}
        <div class="ch-convo-head">
            <p class="ch-convo-label">Conversations</p>
            <span class="ch-count-badge" id="conversationCount">0</span>
        </div>

        <div class="ch-convo-list" id="conversationList">
            <div class="ch-state">Loading...</div>
        </div>

    </div>

    {{-- ── Main panel ── --}}
    <div class="ch-main">
        <div class="ch-main-head">
            <div>
                <p class="ch-main-name" id="activeChatTitle">Select a conversation</p>
                <p class="ch-main-status" id="activeChatStatus"></p>
            </div>
            <button class="ch-refresh-btn" id="refreshBtn" type="button">Refresh</button>
        </div>

        <div class="ch-messages" id="messageList">
            <div class="ch-empty">No conversation selected.</div>
        </div>

        <div class="ch-input-bar">
            <input type="hidden" id="activeChatId" value="">
            <textarea id="messageBody" class="ch-textarea" rows="1" placeholder="Write a message..."></textarea>
            <button class="ch-send-btn" id="sendBtn" type="button" disabled>Send</button>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    const currentUserId   = {{ (int) auth()->id() }};
    const csrfToken       = '{{ csrf_token() }}';
    const urlParams       = new URLSearchParams(window.location.search);
    const autoStartUserId = urlParams.get('user');

    let activeChatId   = null;
    let activeChatMeta = {};
    let lastMessageId  = 0;
    let pollInterval   = null;
    let searchTimer    = null;

    function escHtml(str) {
        return String(str ?? '')
            .replaceAll('&','&amp;').replaceAll('<','&lt;')
            .replaceAll('>','&gt;').replaceAll('"','&quot;')
            .replaceAll("'","&#039;");
    }

    function fmtTime(iso) {
        if (!iso) return '';
        return new Date(iso).toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' });
    }

    function initials(name) {
        return String(name || '?').split(' ').map(w => w[0]).join('').slice(0,2).toUpperCase();
    }

    function renderBubble(m) {
        const isMe = Number(m.sender_id) === Number(currentUserId);
        const cls  = isMe ? 'me' : 'them';
        const name = isMe ? 'You' : escHtml(m.sender?.name || 'User');
        return `<div class="ch-bubble-wrap ${cls}">
                    <div class="ch-bubble">${escHtml(m.body)}</div>
                    <div class="ch-bubble-meta">${name} · ${escHtml(fmtTime(m.created_at))}</div>
                </div>`;
    }

    function scrollBottom() {
        const el = document.getElementById('messageList');
        if (el) el.scrollTop = el.scrollHeight;
    }

    function setActiveChat(chatId, meta) {
        activeChatId   = chatId;
        activeChatMeta = meta || {};
        lastMessageId  = 0;
        $('#activeChatId').val(chatId);
        $('#activeChatTitle').text(meta.name || 'Conversation');
        $('#sendBtn').prop('disabled', false);
        const $s = $('#activeChatStatus');
        if (meta.isOnline) {
            $s.html('<span class="ch-online-dot"></span>Online').addClass('online');
        } else {
            $s.text(meta.lastSeenLabel || '').removeClass('online');
        }
        $('#messageList').html('<div class="ch-state">Loading messages...</div>');
    }

    /* ── User search ── */
    $('#userSearchInput').on('input', function () {
        const q = $(this).val().trim();
        clearTimeout(searchTimer);
        if (!q) { $('#userSearchResults').hide().html(''); return; }

        searchTimer = setTimeout(function () {
            $.get('{{ route('users.search.api') }}', { q })
                .done(function (data) {
                    const results = data.results || [];
                    if (!results.length) {
                        $('#userSearchResults').html('<div class="ch-search-empty">No users found.</div>').css('display','flex');
                        return;
                    }
                    const html = results.map(u => `
                        <div class="ch-search-item" data-user-id="${u.id}" data-name="${escHtml(u.text||u.name||'')}">
                            <div class="ch-search-avatar">${escHtml(initials(u.text||u.name||'?'))}</div>
                            <div>
                                <div class="ch-search-name">${escHtml(u.text||u.name||'')}</div>
                                <div class="ch-search-role">${escHtml(u.role||'')}</div>
                            </div>
                        </div>`).join('');
                    $('#userSearchResults').html(html).css('display','flex').css('flex-direction','column');
                });
        }, 280);
    });

    $(document).on('click', '.ch-search-item[data-user-id]', function () {
        const userId = $(this).data('user-id');
        const name   = $(this).data('name');
        $('#userSearchInput').val('');
        $('#userSearchResults').hide().html('');

        $.post('/chat/conversations', { _token: csrfToken, target_user_id: userId })
            .done(function (res) {
                if (res.success) {
                    loadConversations(function (chats) {
                        const found = chats.find(c => Number(c.chat_id) === Number(res.chat_id));
                        if (found) {
                            const other = found.other_user || {};
                            setActiveChat(found.chat_id, {
                                name:          other.name || other.idnumber || name,
                                isOnline:      other.is_online,
                                lastSeenLabel: other.last_seen_label,
                            });
                            loadMessages(true);
                            startPolling();
                        }
                    });
                }
            }).fail(xhr => alert(xhr.responseJSON?.message || 'Unable to start chat.'));
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.ch-new-bar').length) $('#userSearchResults').hide();
    });

    /* ── Conversations ── */
    function loadConversations(callback) {
        $.get('{{ url('/chat/conversations') }}')
            .done(function (res) {
                const chats = res.chats || [];
                $('#conversationCount').text(chats.length);

                if (!chats.length) {
                    $('#conversationList').html('<div class="ch-state">No conversations yet.</div>');
                    if (typeof callback === 'function') callback([]);
                    return;
                }

                const html = chats.map(c => {
                    const other    = c.other_user || {};
                    const name     = other.name || other.idnumber || 'User';
                    const isActive = activeChatId && Number(c.chat_id) === Number(activeChatId);
                    const dot      = other.is_online ? '<span class="ch-online-dot"></span>' : '';

                    return `<a class="ch-convo-item ${isActive ? 'active' : ''}" href="#"
                               data-chat-id="${c.chat_id}"
                               data-name="${escHtml(name)}"
                               data-is-online="${other.is_online ? '1' : '0'}"
                               data-last-seen="${escHtml(other.last_seen_label || '')}">
                                <div class="ch-convo-row">
                                    <p class="ch-convo-name">${dot}${escHtml(name)}</p>
                                    <span class="ch-convo-time">${escHtml(fmtTime(c.last_message?.created_at))}</span>
                                </div>
                                <p class="ch-convo-preview">${escHtml(c.last_message?.body || '')}</p>
                            </a>`;
                }).join('');

                $('#conversationList').html(html);
                if (typeof callback === 'function') callback(chats);
            })
            .fail(() => $('#conversationList').html('<div class="ch-state" style="color:#e24b4a">Failed to load.</div>'));
    }

    /* ── Messages ── */
    function loadMessages(reset) {
        if (!activeChatId) return;
        $.get(`/chat/conversations/${activeChatId}/messages`, { after_id: reset ? 0 : lastMessageId })
            .done(function (res) {
                const msgs = res.messages || [];
                if (reset) {
                    $('#messageList').html(msgs.length ? msgs.map(renderBubble).join('') : '<div class="ch-empty">No messages yet. Say hello!</div>');
                    scrollBottom();
                } else if (msgs.length) {
                    $('#messageList').append(msgs.map(renderBubble).join(''));
                    scrollBottom();
                }
                if (msgs.length) lastMessageId = Number(msgs[msgs.length - 1].id);
            })
            .fail(() => { if (reset) $('#messageList').html('<div class="ch-state" style="color:#e24b4a">Failed to load.</div>'); });
    }

    /* ── Send ── */
    function sendMessage() {
        const body = String($('#messageBody').val() || '').trim();
        if (!body || !activeChatId) return;
        $('#sendBtn').prop('disabled', true);

        $.post(`/chat/conversations/${activeChatId}/messages`, { _token: csrfToken, body })
            .done(function (res) {
                if (res.success && res.message) {
                    lastMessageId = Number(res.message.id);
                    $('#messageBody').val('').trigger('input');
                    $('#messageList').append(renderBubble(res.message));
                    scrollBottom();
                }
            })
            .fail(() => alert('Message failed to send.'))
            .always(() => { $('#sendBtn').prop('disabled', false); $('#messageBody').focus(); });
    }

    /* ── Auto-start ── */
    function autoStartChat(userId) {
        $.post('/chat/conversations', { _token: csrfToken, target_user_id: userId })
            .done(function (res) {
                if (res.success) {
                    loadConversations(function (chats) {
                        const found = chats.find(c => Number(c.chat_id) === Number(res.chat_id));
                        if (found) {
                            const other = found.other_user || {};
                            setActiveChat(found.chat_id, {
                                name:          other.name || other.idnumber || 'User',
                                isOnline:      other.is_online,
                                lastSeenLabel: other.last_seen_label,
                            });
                            loadMessages(true);
                            startPolling();
                        }
                    });
                }
            });
    }

    function startPolling() {
        stopPolling();
        pollInterval = setInterval(() => { if (!document.hidden) loadMessages(false); }, 2500);
    }

    function stopPolling() {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = null;
    }

    $(document).ready(function () {

        $(document).on('click', '.ch-convo-item[data-chat-id]', function (e) {
            e.preventDefault();
            const $el = $(this);
            setActiveChat($el.data('chat-id'), {
                name:          $el.data('name'),
                isOnline:      $el.data('is-online') === 1 || $el.data('is-online') === '1',
                lastSeenLabel: $el.data('last-seen'),
            });
            loadMessages(true);
            startPolling();
        });

        $('#refreshBtn').on('click', () => { loadConversations(); loadMessages(true); });
        $('#sendBtn').on('click', sendMessage);

        $('#messageBody').on('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        });

        $('#messageBody').on('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 150) + 'px';
        });

        if (autoStartUserId) {
            autoStartChat(autoStartUserId);
        } else {
            loadConversations(function (chats) {
                if (chats.length > 0) {
                    const first = chats[0];
                    const other = first.other_user || {};
                    setActiveChat(first.chat_id, {
                        name:          other.name || other.idnumber || 'User',
                        isOnline:      other.is_online,
                        lastSeenLabel: other.last_seen_label,
                    });
                    loadMessages(true);
                    startPolling();
                }
            });
        }
    });
</script>
@endsection