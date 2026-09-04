<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $module->title }}</title>
    <style>
        :root {
            color-scheme: light;
            --page-bg: #eef4f8;
            --panel-bg: #ffffff;
            --panel-border: #dbe4ec;
            --text-main: #1f2937;
            --text-muted: #5b6472;
            --accent: #2563eb;
            --accent-soft: #dbeafe;
            --error-bg: #fff3f2;
            --error-border: #f4c7c3;
            --error-text: #9f3a32;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
            background: linear-gradient(180deg, #f7fafc 0%, var(--page-bg) 100%);
            color: var(--text-main);
            font-family: 'DM Sans', sans-serif;
        }

        body {
            min-height: 100vh;
        }

        .viewer-shell {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .viewer-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.92);
            border-bottom: 1px solid var(--panel-border);
            backdrop-filter: blur(12px);
        }

        .viewer-title {
            min-width: 0;
        }

        .viewer-title h1 {
            margin: 0;
            font-size: 16px;
            font-weight: 500;
            color: var(--text-main);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .viewer-title p {
            margin: 2px 0 0;
            font-size: 14px;
            color: var(--text-muted);
        }

        .viewer-link {
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 14px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
        }

        .viewer-link:hover {
            background: #c8ddff;
        }

        .viewer-main {
            flex: 1 1 auto;
            padding: 24px;
        }

        .docx-card {
            max-width: 960px;
            margin: 0 auto;
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .docx-status {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            border-bottom: 1px solid var(--panel-border);
            background: #f8fbfd;
            font-size: 15px;
            color: var(--text-muted);
        }

        .docx-status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #93c5fd;
            flex-shrink: 0;
            animation: pulse 1.4s ease-in-out infinite;
        }

        .docx-status.ready .docx-status-dot {
            background: #34a853;
            animation: none;
        }

        .docx-status.error {
            background: var(--error-bg);
            color: var(--error-text);
            border-bottom-color: var(--error-border);
        }

        .docx-status.error .docx-status-dot {
            background: #d14334;
            animation: none;
        }

        .docx-content {
            min-height: 70vh;
            padding: 40px 48px;
            background: #ffffff;
            overflow-wrap: anywhere;
        }

        .docx-content > *:first-child {
            margin-top: 0;
        }

        .docx-content img {
            max-width: 100%;
            height: auto;
        }

        .docx-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .docx-content th,
        .docx-content td {
            border: 1px solid #d7e0e8;
            padding: 10px 12px;
            vertical-align: top;
        }

        .docx-content p,
        .docx-content li {
            line-height: 1.7;
            color: var(--text-main);
        }

        .docx-content h1,
        .docx-content h2,
        .docx-content h3,
        .docx-content h4,
        .docx-content h5,
        .docx-content h6 {
            color: #0f172a;
            line-height: 1.25;
        }

        .docx-empty {
            display: none;
            padding: 24px 18px;
            border-top: 1px solid var(--panel-border);
            background: #fbfdff;
            font-size: 15px;
            color: var(--text-muted);
        }

        @keyframes pulse {
            0%,
            100% {
                opacity: 0.5;
                transform: scale(1);
            }

            50% {
                opacity: 1;
                transform: scale(1.15);
            }
        }

        @media (max-width: 768px) {
            .viewer-toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .viewer-link {
                width: 100%;
            }

            .viewer-main {
                padding: 14px;
            }

            .docx-content {
                padding: 24px 18px;
            }
        }
    </style>
</head>
<body>
    <div class="viewer-shell">
        <div class="viewer-toolbar">
            <div class="viewer-title">
                <h1>{{ $module->title }}</h1>
                <p>DOCX preview rendered inside Reviso.</p>
            </div>
            <a class="viewer-link" href="{{ $docxUrl }}" target="_blank" rel="noopener">Open file directly</a>
        </div>

        <main class="viewer-main">
            <section class="docx-card">
                <div class="docx-status" id="docx-status">
                    <span class="docx-status-dot" aria-hidden="true"></span>
                    <span id="docx-status-text">Loading document preview...</span>
                </div>
                <article class="docx-content" id="docx-content"></article>
                <div class="docx-empty" id="docx-empty">
                    Some advanced DOCX formatting may not render exactly the same in the browser preview. Use â€œOpen file directlyâ€ if you need the original file.
                </div>
            </section>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mammoth@1.8.0/mammoth.browser.min.js"></script>
    <script>
        (function () {
            const moduleId = {{ (int) $module->id }};
            const docxUrl = @json($docxUrl);
            const statusElement = document.getElementById('docx-status');
            const statusTextElement = document.getElementById('docx-status-text');
            const contentElement = document.getElementById('docx-content');
            const emptyNoticeElement = document.getElementById('docx-empty');
            const startTime = Date.now();
            let lastProgress = 0;

            function postProgress(progress) {
                const clamped = Math.max(0, Math.min(100, Math.round(progress)));
                if (clamped <= lastProgress) {
                    return;
                }

                lastProgress = clamped;
                window.parent.postMessage({
                    type: 'pdf-scroll-progress',
                    moduleId,
                    progress: clamped,
                }, window.location.origin);
            }

            function setStatus(text, state) {
                statusTextElement.textContent = text;
                statusElement.classList.remove('ready', 'error');

                if (state) {
                    statusElement.classList.add(state);
                }
            }

            async function renderDocument() {
                setStatus('Loading document preview...', null);
                postProgress(10);

                const response = await fetch(docxUrl, {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('The document could not be loaded.');
                }

                postProgress(35);

                const arrayBuffer = await response.arrayBuffer();
                const result = await mammoth.convertToHtml({ arrayBuffer: arrayBuffer });

                contentElement.innerHTML = result.value;
                emptyNoticeElement.style.display = 'block';
                setStatus('Document preview ready.', 'ready');
                postProgress(100);

                if (result.messages.length > 0) {
                    console.warn('DOCX preview notes:', result.messages);
                }
            }

            renderDocument().catch(function (error) {
                console.error(error);
                setStatus('This DOCX preview could not be rendered in the browser.', 'error');
                contentElement.innerHTML = '<p>Please use the direct-open link to view or download the original file.</p>';
                emptyNoticeElement.style.display = 'none';
                postProgress(20);
            });

            setInterval(function () {
                const elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);

                if (lastProgress >= 100) {
                    return;
                }

                if (elapsedSeconds >= 120) {
                    postProgress(90);
                    return;
                }

                if (elapsedSeconds >= 60) {
                    postProgress(70);
                    return;
                }

                if (elapsedSeconds >= 30) {
                    postProgress(50);
                }
            }, 5000);

            // Cross-tab formal assessment listener (gentle delay to prevent crash)
            function handleAssessmentLock() {
                document.body.innerHTML = `
                    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;background:#111827;color:#fff;font-family:'DM Sans',Arial,sans-serif;text-align:center;padding:20px;box-sizing:border-box;">
                        <div style="font-size:3rem;margin-bottom:16px;">🔒</div>
                        <h2 style="font-size:22px;margin:0 0 8px;font-weight:600;">Document Locked</h2>
                        <p style="color:#9ca3af;max-width:420px;line-height:1.5;font-size:14.5px;">This lecture document has been locked because a Formal Assessment was started in another tab. Please complete or submit your assessment first.</p>
                    </div>
                `;
            }

            try {
                if ('BroadcastChannel' in window) {
                    const channel = new BroadcastChannel('formal_assessment_sync_channel');
                    channel.onmessage = (e) => {
                        if (e.data && e.data.event === 'assessment_started') {
                            setTimeout(handleAssessmentLock, 800);
                        } else if (e.data && e.data.event === 'assessment_ended') {
                            setTimeout(() => window.location.reload(), 800);
                        }
                    };
                }
            } catch (e) {}

            window.addEventListener('storage', (e) => {
                if (e.key === 'formal_assessment_sync' && e.newValue) {
                    try {
                        const parsed = JSON.parse(e.newValue);
                        if (parsed.event === 'assessment_started') {
                            setTimeout(handleAssessmentLock, 800);
                        } else if (parsed.event === 'assessment_ended') {
                            setTimeout(() => window.location.reload(), 800);
                        }
                    } catch (err) {}
                }
            });
        })();
    </script>
</body>
</html>
