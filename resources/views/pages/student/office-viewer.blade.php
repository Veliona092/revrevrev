<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $module->title }}</title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            background: #f4f6fb;
            font-family: 'DM Sans', sans-serif;
        }

        .viewer-shell {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        .viewer-toolbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
        }

        .viewer-toolbar a {
            color: #2563eb;
            font-size: 15px;
            text-decoration: none;
            font-weight: 500;
        }

        .viewer-toolbar a:hover {
            text-decoration: underline;
        }

        .viewer-frame {
            width: 100%;
            height: 100%;
            border: 0;
            background: #fff;
            flex: 1 1 auto;
        }
    </style>
</head>
<body>
    <div class="viewer-shell">
        <div class="viewer-toolbar">
            <a href="{{ $previewFileUrl }}" target="_blank" rel="noopener">Open file directly</a>
        </div>
        <iframe
            class="viewer-frame"
            src="{{ $officeViewerUrl }}"
            title="Document preview"
            loading="eager"
            referrerpolicy="no-referrer"
        ></iframe>
    </div>

    <script>
        (function () {
            const moduleId = {{ (int) $module->id }};
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

            postProgress(10);

            setInterval(function () {
                const elapsedSeconds = Math.floor((Date.now() - startTime) / 1000);

                if (elapsedSeconds >= 180) {
                    postProgress(100);
                    return;
                }

                if (elapsedSeconds >= 120) {
                    postProgress(85);
                    return;
                }

                if (elapsedSeconds >= 60) {
                    postProgress(65);
                    return;
                }

                if (elapsedSeconds >= 30) {
                    postProgress(40);
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

