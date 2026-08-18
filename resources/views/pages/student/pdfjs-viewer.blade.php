<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $file->original_name ?? 'Document' }}</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            background: #f4f6fb;
        }

        #viewerContainer {
            height: 100vh;
            overflow-y: auto;
            padding: 16px;
            box-sizing: border-box;
            scroll-behavior: smooth;
        }

        .pdf-page {
            width: fit-content;
            margin: 0 auto 16px auto;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.16);
            background: #ffffff;
        }

        .pdf-page canvas {
            display: block;
        }

        .viewer-status {
            font-family: Arial, sans-serif;
            color: #1f2937;
            font-size: 14px;
            text-align: center;
            padding: 20px;
        }

        #no-right-click {
            user-select: none;
            -webkit-user-select: none;
        }
    </style>
</head>
<body id="no-right-click" oncontextmenu="return false;">
    <div id="viewerContainer" aria-label="PDF document viewer">
        <div id="viewerStatus" class="viewer-status">Loading document...</div>
    </div>

    <script type="module">
        import * as pdfjsLib from 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.7.76/pdf.min.mjs';

        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/4.7.76/pdf.worker.min.mjs';

        const fileId = {{ (int) $file->id }};
        const subtopicId = {{ (int) $file->board_exam_subtopic_id }};
        const pdfUrl = @json($pdfUrl);
        const viewerContainer = document.getElementById('viewerContainer');
        const viewerStatus = document.getElementById('viewerStatus');

        function postPdfProgress(progress) {
            const clamped = Math.max(0, Math.min(100, Math.round(progress || 0)));
            window.parent.postMessage({
                type: 'board-exam-pdf-progress',
                fileId,
                subtopicId,
                progress: clamped,
            }, window.location.origin);
        }

        function bindScrollTracking() {
            const onScroll = () => {
                const maxScrollable = Math.max(1, viewerContainer.scrollHeight - viewerContainer.clientHeight);
                const ratio = Math.max(0, Math.min(1, viewerContainer.scrollTop / maxScrollable));
                postPdfProgress(ratio * 100);
            };

            viewerContainer.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        }

        async function renderPdfDocument() {
            try {
                let pdf = null;

                try {
                    const directLoadingTask = pdfjsLib.getDocument({
                        url: pdfUrl,
                        withCredentials: true,
                    });
                    pdf = await directLoadingTask.promise;
                } catch (directLoadError) {
                    const pdfResponse = await fetch(pdfUrl, {
                        credentials: 'same-origin',
                    });

                    if (!pdfResponse.ok) {
                        throw new Error(`HTTP ${pdfResponse.status}`);
                    }

                    const pdfBytes = new Uint8Array(await pdfResponse.arrayBuffer());
                    const bufferLoadingTask = pdfjsLib.getDocument({ data: pdfBytes });
                    pdf = await bufferLoadingTask.promise;
                }

                viewerStatus.remove();

                for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
                    const page = await pdf.getPage(pageNumber);
                    const baseViewport = page.getViewport({ scale: 1 });
                    const targetWidth = Math.max(320, Math.min(1100, viewerContainer.clientWidth - 48));
                    const scale = targetWidth / baseViewport.width;
                    const viewport = page.getViewport({ scale });

                    const pageWrapper = document.createElement('div');
                    pageWrapper.className = 'pdf-page';

                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');

                    canvas.width = Math.floor(viewport.width);
                    canvas.height = Math.floor(viewport.height);

                    pageWrapper.appendChild(canvas);
                    viewerContainer.appendChild(pageWrapper);

                    await page.render({
                        canvasContext: context,
                        viewport,
                    }).promise;
                }

                bindScrollTracking();
            } catch (error) {
                viewerStatus.innerHTML = `
                    Unable to load this PDF preview.<br>
                    <a href="${pdfUrl}" target="_blank" rel="noopener">Open PDF directly</a>
                `;
            }
        }

        renderPdfDocument();

        // Disable right-click & keyboard save shortcuts
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('keydown', e => {
            if (e.ctrlKey && (e.key === 's' || e.key === 'S' || e.key === 'p' || e.key === 'P')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>