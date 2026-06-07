import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// TipTap rich text editors
import { initTiptap } from './editors/tiptap-news.js'

function initNewsEditors() {
  if (!document.getElementById('content_en') && !document.getElementById('content_ar')) return
  const fields = [
    ['content_en',  { placeholder: 'Write article content here...' }],
    ['content_ar',  { isRtl: true, placeholder: 'اكتب المحتوى هنا...' }],
    ['excerpt_en',  { compact: true, placeholder: 'Short excerpt...' }],
    ['excerpt_ar',  { isRtl: true, compact: true, placeholder: 'ملخص قصير...' }],
  ]
  fields.forEach(([id, opts]) => {
    try { initTiptap(id, opts) } catch (e) { console.error('[TipTap] failed to init #' + id, e) }
  })
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initNewsEditors)
} else {
  initNewsEditors()
}
