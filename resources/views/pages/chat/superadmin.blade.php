@extends('layouts.appAdmin')

@section('title', 'Messages')
@section('page-heading', 'Messages')

@section('content')
<style>
    /* ── Shell ── */
    .ch-shell {
        display: grid;
        grid-template-columns: 300px 1fr;
        border: 1px solid #ebebeb;
        border-radius: 12px;
        overflow: hidden;
        height: 78vh;
        min-height: 520px;
        background: #fff;
    }

    /* ── Left panel ── */
    .ch-left {
        display: flex;
        flex-direction: column;
        border-right: 1px solid #f0f0f0;
        overflow: hidden;
    }

    /* ── New chat / search bar ── */
    .ch-new-bar {
        padding: 12px 14px;
        border-bottom: 1px solid #f0f0f0;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .ch-new-label {
        font-size: 11px;
        font-weight: 500;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #bbb;
        margin: 0;
    }

    .ch-search-input {
        width: 100%;
        height: 32px;
        padding: 0 10px;
        border: 1px solid #e4e4e4;
        border-radius: 6px;
        font-family: 'DM Sans', sans-serif;
        font-size: 13px;
        color: #111;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
        background: #fff;
    }

    .ch-search-input:focus {
        border-color: #4A90D9;
        box-shadow: 0 0 0 2px rgba(74, 144, 217, 0.08);
    }

    .ch-search-input::placeholder { color: #bbb; }

    .ch-search-results {
        display: none;
        flex-direction: column;
        border: 1px solid #ebebeb;
        border-radius: 8px;
        overflow: hidden;
        max-height: 180px;
        overflow-y: auto;
        background: #fff;
        box-shadow: 0 4px 16px rgba(0,0,0,0.07);
    }

    .ch-search-results::-webkit-scrollbar { width: 3px; }
    .ch-search-results::-webkit-scrollbar-thumb { background: #e8e8e8; border-radius: 99px; }

    .ch-search-item {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 9px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f7f7f7;
        transition: background 0.1s;
    }

    .ch-search-item:last-child { border-bottom: none; }
    .ch-search-item:hover { background: #f7f7f7; }

    .ch-search-avatar {
        width: 28px; height: 28px; border-radius: 50%;
        background: #0f0f0f; color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 10px; font-weight: 500; flex-shrink: 0;
    }

    .ch-search-name { font-size: 13px; font-weight: 500; color: #111; }
    .ch-search-role { font-size: 11px; color: #aaa; }
    .ch-search-empty { padding: 12px; font-size: 12px; color: #999; text-align: center; }

    /* ── Conversation list ── */
    .ch-convo-head {
        padding: 10px 14px 6px;
        display: flex; align-items: center; justify-content: space-between;
        flex-shrink: 0;
    }

    .ch-convo-label {
        font-size: 11px; font-weight: 500;
        letter-spacing: 0.06em; text-transform: uppercase;
        color: #bbb; margin: 0;
    }

    .ch-count-badge {
        font-size: 11px; background: #f3f3f3; color: #555;
        padding: 2px 8px; border-radius: 99px; font-weight: 500;
    }

    .ch-convo-list { flex: 1; overflow-y: auto; }
    .ch-convo-list::-webkit-scrollbar { width: 4px; }
    .ch-convo-list::-webkit-scrollbar-thumb { background: #e8e8e8; border-radius: 99px; }

    .ch-convo-item {
        display: block; padding: 10px 14px; cursor: pointer;
        text-decoration: none; transition: background 0.1s, border-left-color 0.1s;
        border-left: 2px solid transparent;
        border-bottom: 1px solid #f7f7f7;
    }

    .ch-convo-item:last-child { border-bottom: none; }
    .ch-convo-item:hover { background: #fafafa; border-left-color: #d0e6f5; }
    .ch-convo-item.active { background: #f7f7f7; border-left-color: #4A90D9; }

    .ch-convo-row {
        display: flex; justify-content: space-between;
        align-items: baseline; margin-bottom: 3px;
    }

    .ch-convo-name {
        font-size: 13px; font-weight: 500; color: #111; margin: 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 170px;
    }

    .ch-convo-time  { font-size: 11px; color: #aaa; flex-shrink: 0; }

    .ch-convo-preview {
        font-size: 12px; color: #999; margin: 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    .ch-online-dot {
        display: inline-block; width: 7px; height: 7px; border-radius: 50%;
        background: #4A90D9; margin-right: 4px; vertical-align: middle; flex-shrink: 0;
    }

    /* ── Right / main panel ── */
    .ch-main { display: flex; flex-direction: column; overflow: hidden; }

    .ch-main-head {
        padding: 14px 20px; border-bottom: 1px solid #f0f0f0;
        flex-shrink: 0; display: flex; align-items: center;
        justify-content: space-between; min-height: 60px;
    }

    .ch-main-name   { font-size: 14px; font-weight: 500; color: #111; margin: 0 0 2px; }

    .ch-main-status {
        font-size: 11px; color: #aaa; margin: 0;
        display: flex; align-items: center; gap: 5px;
    }

    .ch-main-status.online { color: #4A90D9; font-weight: 500; }

    .ch-refresh-btn {
        height: 26px; padding: 0 10px; background: transparent;
        border: 1px solid #e4e4e4; border-radius: 6px;
        font-family: 'DM Sans', sans-serif; font-size: 11px; font-weight: 500; color: #555;
        cursor: pointer; transition: background 0.15s, border-color 0.15s, color 0.15s;
    }

    .ch-refresh-btn:hover { border-color: #bbb; color: #111; background: #fafafa; }

    /* ── Messages ── */
    .ch-messages {
        flex: 1; overflow-y: auto; padding: 20px 20px 10px;
        display: flex; flex-direction: column; gap: 4px;
    }

    .ch-messages::-webkit-scrollbar { width: 4px; }
    .ch-messages::-webkit-scrollbar-thumb { background: #e8e8e8; border-radius: 99px; }

    .ch-empty { margin: auto; text-align: center; color: #999; font-size: 13px; }

    .ch-bubble-wrap {
        display: flex; flex-direction: column; max-width: 68%;
        animation: ch-pop 0.15s ease both;
    }

    @keyframes ch-pop {
        from { opacity: 0; transform: translateY(4px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .ch-bubble-wrap.me   { align-self: flex-end;  align-items: flex-end; }
    .ch-bubble-wrap.them { align-self: flex-start; align-items: flex-start; }

    .ch-bubble {
        padding: 9px 12px; border-radius: 12px;
        font-size: 13px; line-height: 1.5;
        word-break: break-word; white-space: pre-wrap;
    }

    .ch-bubble-wrap.me   .ch-bubble { background: #4A90D9; color: #fff; border-bottom-right-radius: 3px; }
    .ch-bubble-wrap.them .ch-bubble { background: #f3f3f3; color: #111; border-bottom-left-radius: 3px; }

    .ch-bubble-meta { font-size: 10px; color: #aaa; margin-top: 3px; padding: 0 2px; }

    /* ── Input bar ── */
    .ch-input-bar {
        padding: 12px 16px; border-top: 1px solid #f0f0f0;
        display: flex; gap: 10px; align-items: flex-end; flex-shrink: 0;
    }

    .ch-textarea {
        flex: 1; min-height: 36px; max-height: 120px; padding: 8px 10px;
        border: 1px solid #e4e4e4; border-radius: 6px;
        font-family: 'DM Sans', sans-serif; font-size: 13px; color: #111;
        resize: none; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s; line-height: 1.5;
    }

    .ch-textarea:focus { border-color: #4A90D9; box-shadow: 0 0 0 2px rgba(74, 144, 217, 0.08); }
    .ch-textarea::placeholder { color: #bbb; }

    .ch-send-btn {
        height: 28px; padding: 0 12px; background: #4A90D9; color: #fff;
        border: none; border-radius: 6px;
        font-family: 'DM Sans', sans-serif; font-size: 12px; font-weight: 500;
        cursor: pointer; flex-shrink: 0; transition: background 0.15s, transform 0.1s;
    }

    .ch-send-btn:hover  { background: #3771b3; }
    .ch-send-btn:active { transform: scale(0.97); }
    .ch-send-btn:disabled { background: #ddd; cursor: not-allowed; }

    .ch-state { text-align: center; padding: 2rem 1rem; color: #999; font-size: 13px; }

    @media (max-width: 680px) {
        .ch-shell { grid-template-columns: 1fr; height: auto; }
        .ch-left  { border-right: none; border-bottom: 1px solid #f0f0f0; height: 280px; }
        .ch-messages { height: 40vh; }
    }
</style>

<div class="ch-shell">

    {{-- ── Left panel ── --}}
    <div class="ch-left">

        {{-- Search / new chat ── --}}
        <div class="ch-new-bar">
            <p class="ch-new-label">New Conversation</p>
            <input type="text" id="userSearchInput" class="ch-search-input" placeholder="Search by name or ID…">
            <div class="ch-search-results" id="userSearchResults"></div>
        </div>

        {{-- Conversation list ── --}}
        <div class="ch-convo-head">
            <p class="ch-convo-label">Conversations</p>
            <span class="ch-count-badge" id="conversationCount">0</span>
        </div>

        <div class="ch-convo-list" id="conversationList">
            <div class="ch-state">Loading…</div>
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
            <textarea id="messageBody" class="ch-textarea" rows="1" placeholder="Write a message…"></textarea>
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
        $('#messageList').html('<div class="ch-state">Loading messages…</div>');
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
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
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
