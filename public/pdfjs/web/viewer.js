PDFViewerApplication.eventBus.on('scrollmodechanged', () => {
    let page = PDFViewerApplication.pdfViewer.currentPageNumber;
    let totalPages = PDFViewerApplication.pdfDocument.numPages;
    let percent = Math.round((page / totalPages) * 100);
    window.parent.postMessage({ type: 'pdfjs_scroll', percent, page }, '*');
});