import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// TipTap rich text editors
import { initTiptap } from './editors/tiptap-news.js'

if (document.getElementById('content_en') || document.getElementById('content_ar')) {
  initTiptap('content_en', { placeholder: 'Write article content here...' })
  initTiptap('content_ar', { isRtl: true, placeholder: 'اكتب المحتوى هنا...' })
  initTiptap('excerpt_en', { compact: true, placeholder: 'Short excerpt...' })
  initTiptap('excerpt_ar', { isRtl: true, compact: true, placeholder: 'ملخص قصير...' })
}
