// In-browser WebP conversion for admin uploads.
//
// Primary encoder: Squoosh's WebP codec (WASM) via @jsquash/webp, bundled
// locally by Vite — no third-party CDN at runtime. Fallback: native
// canvas.toBlob('image/webp'); if the browser can't emit WebP (Safari < 17)
// we fall back to compressed JPEG rather than failing.
//
// Exposed as window.ssbcToWebp(file, opts) so the inline admin-form scripts
// (which live in Blade views) can use it. The WASM chunk is dynamically
// imported on first use, so public pages never pay for it.

const MAX_EDGE = 1920
const WEBP_QUALITY = 80 // jsquash scale 0–100; canvas equivalent 0.80

let jsquashEncode = null // resolved encode() fn, null = not tried, false = unavailable

async function loadJsquash() {
    if (jsquashEncode === null) {
        try {
            const mod = await import('@jsquash/webp/encode')
            jsquashEncode = mod.default
        } catch (e) {
            console.warn('SSBC WebP: WASM encoder unavailable, using canvas fallback', e)
            jsquashEncode = false
        }
    }
    return jsquashEncode
}

function decodeToImage(file) {
    if (window.createImageBitmap) {
        return createImageBitmap(file).catch(() => decodeViaImg(file))
    }
    return decodeViaImg(file)
}

function decodeViaImg(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file)
        const img = new Image()
        img.onload = () => { URL.revokeObjectURL(url); resolve(img) }
        img.onerror = (e) => { URL.revokeObjectURL(url); reject(e) }
        img.src = url
    })
}

function drawScaled(source) {
    const w = source.naturalWidth || source.width
    const h = source.naturalHeight || source.height
    const scale = Math.min(1, MAX_EDGE / Math.max(w, h))
    const canvas = document.createElement('canvas')
    canvas.width = Math.max(1, Math.round(w * scale))
    canvas.height = Math.max(1, Math.round(h * scale))
    canvas.getContext('2d').drawImage(source, 0, 0, canvas.width, canvas.height)
    if (source.close) source.close()
    return { canvas, downscaled: scale < 1 }
}

function canvasToBlob(canvas, type, quality) {
    return new Promise((resolve) => canvas.toBlob(resolve, type, quality))
}

async function encodeCanvas(canvas) {
    // 1. Squoosh WASM encoder
    const encode = await loadJsquash()
    if (encode) {
        try {
            const imageData = canvas.getContext('2d').getImageData(0, 0, canvas.width, canvas.height)
            const buffer = await encode(imageData, { quality: WEBP_QUALITY })
            return { blob: new Blob([buffer], { type: 'image/webp' }), encoder: 'wasm' }
        } catch (e) {
            console.warn('SSBC WebP: WASM encode failed, using canvas fallback', e)
        }
    }
    // 2. Native canvas WebP
    let blob = await canvasToBlob(canvas, 'image/webp', WEBP_QUALITY / 100)
    if (blob && blob.type === 'image/webp') return { blob, encoder: 'canvas' }
    // 3. Browser can't emit WebP → compressed JPEG
    blob = await canvasToBlob(canvas, 'image/jpeg', 0.82)
    if (blob) return { blob, encoder: 'jpeg' }
    return null
}

function webpName(filename, type) {
    const ext = type === 'image/webp' ? '.webp' : '.jpg'
    const base = filename.replace(/\.[^.]+$/, '')
    return (base || 'image') + ext
}

/**
 * Convert an image File to a downscaled (max 1920px) WebP blob.
 * Resolves with { blob, filename, originalSize, size, converted }.
 * If conversion fails or produces a larger file than the original
 * (and no downscaling was needed), the original file is returned.
 */
window.ssbcToWebp = async function (file) {
    const fallback = { blob: file, filename: file.name, originalSize: file.size, size: file.size, converted: false }
    try {
        const source = await decodeToImage(file)
        const { canvas, downscaled } = drawScaled(source)
        const result = await encodeCanvas(canvas)
        if (!result || !result.blob) return fallback
        // Keep whichever file is smaller — unless the original exceeded the
        // max edge, in which case the downscaled version must win.
        if (!downscaled && result.blob.size >= file.size) return fallback
        return {
            blob: result.blob,
            filename: webpName(file.name, result.blob.type),
            originalSize: file.size,
            size: result.blob.size,
            converted: true,
        }
    } catch (e) {
        console.warn('SSBC WebP: conversion failed, uploading original', e)
        return fallback
    }
}

function fmtSize(bytes) {
    if (bytes >= 1024 * 1024) return (bytes / 1024 / 1024).toFixed(1) + ' MB'
    return Math.max(1, Math.round(bytes / 1024)) + ' KB'
}
window.ssbcFmtSize = fmtSize

// Plain (non-async) admin file inputs opt in with data-webp-auto: the picked
// file is converted in place via DataTransfer so the normal form submit
// carries the .webp file, with the size saving shown under the input.
document.addEventListener('change', async (e) => {
    const input = e.target
    if (!input.matches || !input.matches('input[type="file"][data-webp-auto]')) return
    const file = input.files && input.files[0]
    if (!file || !/^image\/(jpeg|png|webp)$/.test(file.type)) return

    let note = input.parentNode.querySelector('.webp-auto-note')
    if (!note) {
        note = document.createElement('p')
        note.className = 'webp-auto-note text-xs mt-1 text-ssbc-sage'
        input.insertAdjacentElement('afterend', note)
    }
    note.textContent = 'Converting to WebP…'

    const result = await window.ssbcToWebp(file)
    if (result.converted && window.DataTransfer) {
        try {
            const dt = new DataTransfer()
            dt.items.add(new File([result.blob], result.filename, { type: result.blob.type }))
            input.files = dt.files
            note.textContent = fmtSize(result.originalSize) + ' → ' + fmtSize(result.size) + ' (WebP)'
            note.classList.add('text-ssbc-green')
            return
        } catch (err) {
            console.warn('SSBC WebP: could not replace input file, original will upload', err)
        }
    }
    note.textContent = 'Uploading original file (' + fmtSize(file.size) + ')'
})
