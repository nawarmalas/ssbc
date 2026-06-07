import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import TextAlign from '@tiptap/extension-text-align'
import TextStyle from '@tiptap/extension-text-style'
import Color from '@tiptap/extension-color'
import Highlight from '@tiptap/extension-highlight'
import Underline from '@tiptap/extension-underline'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import Table from '@tiptap/extension-table'
import TableRow from '@tiptap/extension-table-row'
import TableCell from '@tiptap/extension-table-cell'
import TableHeader from '@tiptap/extension-table-header'
import Placeholder from '@tiptap/extension-placeholder'
import FontFamily from '@tiptap/extension-font-family'
import FontSize from 'tiptap-extension-font-size'

function buildToolbar(wrap, editor, isRtl) {
  const toolbar = wrap.querySelector('.tt-toolbar')
  if (!toolbar) return
  toolbar.innerHTML = ''

  const btn = (label, title, action, isActive) => {
    const b = document.createElement('button')
    b.type = 'button'
    b.innerHTML = label
    b.title = title
    b.className = 'tt-btn' + (isActive ? ' is-active' : '')
    b.addEventListener('click', action)
    return b
  }

  const sep = () => {
    const s = document.createElement('span')
    s.className = 'tt-sep'
    return s
  }

  // Row 1 — font family select
  const ffSel = document.createElement('select')
  ffSel.className = 'tt-select'
  ffSel.title = 'Font Family'
  ;['Default','Arial','Georgia','Times New Roman','Courier New','Cairo','Noto Sans Arabic'].forEach(f => {
    const o = document.createElement('option')
    o.value = f === 'Default' ? '' : f
    o.textContent = f
    ffSel.appendChild(o)
  })
  ffSel.addEventListener('change', () => {
    if (ffSel.value) editor.chain().focus().setFontFamily(ffSel.value).run()
    else editor.chain().focus().unsetFontFamily().run()
  })
  toolbar.appendChild(ffSel)

  // Font size select
  const fsSel = document.createElement('select')
  fsSel.className = 'tt-select'
  fsSel.title = 'Font Size'
  ;['10','11','12','14','16','18','20','22','24','28','32','36'].forEach(s => {
    const o = document.createElement('option')
    o.value = s + 'px'
    o.textContent = s
    fsSel.appendChild(o)
  })
  fsSel.addEventListener('change', () => editor.chain().focus().setFontSize(fsSel.value).run())
  toolbar.appendChild(fsSel)

  // Heading select
  const hSel = document.createElement('select')
  hSel.className = 'tt-select'
  hSel.title = 'Heading'
  ;[['Paragraph','0'],['Heading 1','1'],['Heading 2','2'],['Heading 3','3'],['Heading 4','4']].forEach(([label, level]) => {
    const o = document.createElement('option')
    o.value = level
    o.textContent = label
    hSel.appendChild(o)
  })
  hSel.addEventListener('change', () => {
    const l = parseInt(hSel.value)
    if (l === 0) editor.chain().focus().setParagraph().run()
    else editor.chain().focus().setHeading({ level: l }).run()
  })
  toolbar.appendChild(hSel)
  toolbar.appendChild(sep())

  // Bold, Italic, Underline, Strike
  toolbar.appendChild(btn('<b>B</b>', 'Bold (Ctrl+B)', () => editor.chain().focus().toggleBold().run(), editor.isActive('bold')))
  toolbar.appendChild(btn('<i>I</i>', 'Italic (Ctrl+I)', () => editor.chain().focus().toggleItalic().run(), editor.isActive('italic')))
  toolbar.appendChild(btn('<u>U</u>', 'Underline (Ctrl+U)', () => editor.chain().focus().toggleUnderline().run(), editor.isActive('underline')))
  toolbar.appendChild(btn('<s>S</s>', 'Strikethrough', () => editor.chain().focus().toggleStrike().run(), editor.isActive('strike')))

  // Text color
  const colorInput = document.createElement('input')
  colorInput.type = 'color'
  colorInput.className = 'tt-color-input'
  colorInput.title = 'Text Color'
  colorInput.value = '#000000'
  colorInput.addEventListener('input', () => editor.chain().focus().setColor(colorInput.value).run())
  toolbar.appendChild(colorInput)

  // Highlight
  toolbar.appendChild(btn('✦', 'Highlight', () => editor.chain().focus().toggleHighlight().run(), editor.isActive('highlight')))
  toolbar.appendChild(sep())

  // Alignment
  const alignments = isRtl
    ? [['≡R','right'],['≡C','center'],['≡L','left'],['≡J','justify']]
    : [['≡L','left'],['≡C','center'],['≡R','right'],['≡J','justify']]

  alignments.forEach(([label, align]) => {
    toolbar.appendChild(btn(label, 'Align ' + align, () => editor.chain().focus().setTextAlign(align).run(), editor.isActive({ textAlign: align })))
  })
  toolbar.appendChild(sep())

  // Lists + indent
  toolbar.appendChild(btn('•≡', 'Bullet List', () => editor.chain().focus().toggleBulletList().run(), editor.isActive('bulletList')))
  toolbar.appendChild(btn('1.≡', 'Numbered List', () => editor.chain().focus().toggleOrderedList().run(), editor.isActive('orderedList')))
  toolbar.appendChild(btn('→', 'Indent', () => editor.chain().focus().sinkListItem('listItem').run()))
  toolbar.appendChild(btn('←', 'Outdent', () => editor.chain().focus().liftListItem('listItem').run()))
  toolbar.appendChild(sep())

  // Blockquote, HR
  toolbar.appendChild(btn('"', 'Blockquote', () => editor.chain().focus().toggleBlockquote().run(), editor.isActive('blockquote')))
  toolbar.appendChild(btn('—', 'Horizontal Rule', () => editor.chain().focus().setHorizontalRule().run()))
  toolbar.appendChild(sep())

  // Link
  toolbar.appendChild(btn('🔗', 'Link (Ctrl+K)', () => {
    const url = window.prompt('URL:', editor.getAttributes('link').href || '')
    if (url === null) return
    if (url === '') { editor.chain().focus().unsetLink().run(); return }
    editor.chain().focus().setLink({ href: url }).run()
  }, editor.isActive('link')))

  // Image (by URL)
  toolbar.appendChild(btn('🖼', 'Insert Image', () => {
    const url = window.prompt('Image URL:')
    if (url) editor.chain().focus().setImage({ src: url }).run()
  }))

  // Table
  toolbar.appendChild(btn('⊞', 'Insert Table', () => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()))
  toolbar.appendChild(sep())

  // Undo, Redo, Clear
  toolbar.appendChild(btn('↩', 'Undo (Ctrl+Z)', () => editor.chain().focus().undo().run()))
  toolbar.appendChild(btn('↪', 'Redo (Ctrl+Y)', () => editor.chain().focus().redo().run()))
  toolbar.appendChild(btn('✕', 'Clear Formatting', () => editor.chain().focus().unsetAllMarks().clearNodes().run()))
}

export function initTiptap(textareaId, { isRtl = false, compact = false, placeholder = '' } = {}) {
  const textarea = document.getElementById(textareaId)
  if (!textarea) return

  const wrap = document.createElement('div')
  wrap.className = 'tt-wrap' + (isRtl ? ' tt-rtl' : '')

  const toolbar = document.createElement('div')
  toolbar.className = 'tt-toolbar'

  const editorEl = document.createElement('div')
  editorEl.className = 'tt-content' + (compact ? ' tt-compact' : '')
  if (isRtl) {
    editorEl.setAttribute('dir', 'rtl')
    editorEl.style.fontFamily = "'Cairo', 'Noto Sans Arabic', Arial, sans-serif"
  }

  wrap.appendChild(toolbar)
  wrap.appendChild(editorEl)
  textarea.insertAdjacentElement('afterend', wrap)
  textarea.style.display = 'none'

  const extensions = [
    StarterKit,
    TextAlign.configure({ types: ['heading', 'paragraph'], defaultAlignment: isRtl ? 'right' : 'left' }),
    TextStyle,
    Color,
    Highlight.configure({ multicolor: true }),
    Underline,
    Link.configure({ openOnClick: false, autolink: true }),
    Image.configure({ inline: false, allowBase64: false }),
    Table.configure({ resizable: true }),
    TableRow,
    TableHeader,
    TableCell,
    FontFamily,
    FontSize,
    Placeholder.configure({ placeholder }),
  ]

  const editor = new Editor({
    element: editorEl,
    extensions,
    content: textarea.value || '',
    editorProps: {
      handlePaste(view, event) {
        const items = Array.from(event.clipboardData?.items || [])
        const imageItem = items.find(i => i.type.startsWith('image/'))
        if (imageItem) {
          event.preventDefault()
          const file = imageItem.getAsFile()
          const fd = new FormData()
          fd.append('image', file)
          const csrf = document.querySelector('meta[name="csrf-token"]')?.content || ''
          fetch('/admin/upload-image', { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrf } })
            .then(r => r.json())
            .then(data => {
              if (data.url) editor.chain().focus().setImage({ src: data.url }).run()
            })
            .catch(() => {})
          return true
        }
        return false
      },
    },
    onUpdate() {
      textarea.value = editor.getHTML()
    },
    onSelectionUpdate() {
      buildToolbar(wrap, editor, isRtl)
    },
    onCreate() {
      buildToolbar(wrap, editor, isRtl)
    },
  })

  // Sync on form submit
  const form = textarea.closest('form')
  if (form) {
    form.addEventListener('submit', () => { textarea.value = editor.getHTML() }, { once: false })
  }

  return editor
}
