/**
 * IWARE 58mm ESC/POS encoder milik website (tanpa dependensi, boleh offline).
 *
 * Browser tidak bisa mem-bundle OS driver; yang di-bundle di sini adalah
 * driver ESC/POS JavaScript: byte mentah untuk printer thermal ESC/POS
 * (umum untuk IWARE POS58/C58BT) via WebUSB / WebBluetooth.
 *
 * Lebar kertas: 32 kolom mode font-A.
 */

// Perintah dasar (literal, sesuai plan).
const INIT = [0x1b, 0x40]; // ESC @ — reset printer
const ALIGN_LT = [0x1b, 0x61, 0x00]; // ESC a 0 — rata kiri
const ALIGN_CT = [0x1b, 0x61, 0x01]; // ESC a 1 — rata tengah
const CUT = [0x1d, 0x56, 0x01]; // GS V 1 — potong sebagian (partial cut)
const BOLD_ON = [0x1b, 0x45, 0x01]; // ESC E 1 — tebal
const BOLD_OFF = [0x1b, 0x45, 0x00]; // ESC E 0 — normal

const COLS = 32;

/**
 * Printer 58mm IWARE umum tidak dukung UTF-8 penuh; ganti karakter
 * non-ASCII dengan '?' supaya byte selalu terkirim utuh.
 */
function encodeText(str) {
    const safe = String(str ?? '').replace(/[^\x20-\x7e\n]/g, '?');
    return Array.from(new TextEncoder().encode(safe));
}

/** Print + feed n baris: ESC d n. */
function feed(n) {
    const lines = Math.max(0, Math.min(255, Number(n) || 0));
    return [0x1b, 0x64, lines];
}

/** Bungkus teks manual 32 kolom (potong kata bila perlu). */
function wrap32(text) {
    const lines = [];
    for (const raw of String(text ?? '').split('\n')) {
        let line = raw;
        if (line.length === 0) {
            lines.push('');
            continue;
        }
        while (line.length > COLS) {
            let cut = line.lastIndexOf(' ', COLS);
            if (cut <= 0) cut = COLS;
            lines.push(line.slice(0, cut));
            line = line.slice(cut).trimStart();
        }
        lines.push(line);
    }
    return lines;
}

function concat(...parts) {
    const out = [];
    for (const p of parts) {
        if (p == null) continue;
        for (const b of p) out.push(b & 0xff);
    }
    return out;
}

function textLine(str) {
    const out = [];
    for (const line of wrap32(str)) {
        out.push(...encodeText(line), 0x0a);
    }
    return out;
}

/**
 * Perintah QR native printer: GS ( k (bukan raster PNG).
 * Model 2, EC level M (0x31), dot size 10 — 37 modul x 10 dot = 370 dot,
 * pas penuh di kertas 58mm (384 dot), sisa putih kiri-kanan ~1mm.
 */
function qrNative(payload) {
    const data = encodeText(payload);
    const len = data.length + 3;
    const pL = len & 0xff;
    const pH = (len >> 8) & 0xff;
    return concat(
        [0x1d, 0x28, 0x6b, 0x04, 0x00, 0x31, 0x41, 0x32, 0x00], // model 2
        [0x1d, 0x28, 0x6b, 0x03, 0x00, 0x31, 0x43, 0x08], // size 8 (full-width 58mm)
        [0x1d, 0x28, 0x6b, 0x03, 0x00, 0x31, 0x45, 0x31], // EC level M
        [0x1d, 0x28, 0x6b, pL, pH, 0x31, 0x50, 0x30],
        data, // store
        [0x1d, 0x28, 0x6b, 0x03, 0x00, 0x31, 0x51, 0x30], // print
    );
}

window.EscPos = {
    INIT,
    ALIGN_CT,
    ALIGN_LT,
    CUT,
    BOLD_ON,
    BOLD_OFF,
    COLS,
    encodeText,
    feed,
    wrap32,
    concat,
    textLine,
    qrNative,
};
