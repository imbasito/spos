const fs = require('fs');
const { mdToPdf } = require('md-to-pdf');

(async () => {
  try {
    const pdf = await mdToPdf('SPOS_USER_MANUAL.md', {
      dest: 'SPOS_USER_MANUAL.pdf',
      pdf_options: {
        format: 'A4',
        margin: { top: '20mm', bottom: '20mm', left: '15mm', right: '15mm' },
        printBackground: true,
        displayHeaderFooter: true,
        headerTemplate: '<div style="font-size:10px;text-align:center;width:100%;">SPOS User Manual v1.0.5</div>',
        footerTemplate: '<div style="font-size:10px;text-align:center;width:100%;"><span class="pageNumber"></span> / <span class="totalPages"></span></div>'
      },
      stylesheet: 'https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.1.0/github-markdown.min.css'
    });
    console.log('PDF created successfully!');
  } catch (e) {
    console.error('Error:', e);
  }
})();
