/**
 * Builder payload cetak per surat dari data yang sudah ada di Blade.
 * Murni: tanpa query/route/controller tambahan.
 */

function center(title) {
    const E = window.EscPos;
    return E.concat(
        E.ALIGN_CT,
        E.BOLD_ON,
        E.textLine(title),
        E.BOLD_OFF,
        E.ALIGN_LT,
    );
}

function rule() {
    return window.EscPos.textLine('-'.repeat(window.EscPos.COLS));
}

/**
 * Surat Izin Keluar: judul rata tengah + QR native dari string yang sama
 * dengan $qrCode (murid.uuid . '>' . izin.uuid). Bukan raster PNG.
 */
function buildIzinKeluarBytes(qrCodeString) {
    const E = window.EscPos;
    return E.concat(
        E.INIT,
        center('SURAT IZIN KELUAR'),
        E.textLine(''),
        E.ALIGN_CT,
        E.qrNative(String(qrCodeString ?? '')),
        E.feed(2),
        E.ALIGN_LT,
        rule(),
        E.textLine(''),
        E.feed(3),
        E.CUT,
    );
}

/**
 * Surat Izin Telat: teks saja, mirror blok HTML (nama + alasan wrap 32
 * kolom + garis + blok ttd tanggal/jam WITA + pengawas).
 */
function buildIzinTelatBytes({ nama, alasan, tanggal, jam, pengawas }) {
    const E = window.EscPos;
    return E.concat(
        E.INIT,
        center('SURAT IZIN TELAT'),
        E.textLine(''),
        E.BOLD_ON,
        E.textLine('Nama :'),
        E.BOLD_OFF,
        E.textLine(nama),
        E.textLine(''),
        E.BOLD_ON,
        E.textLine('Alasan Terlambat :'),
        E.BOLD_OFF,
        E.textLine(alasan),
        E.textLine(''),
        rule(),
        E.textLine(''),
        E.ALIGN_CT,
        E.textLine(tanggal),
        E.textLine(`${jam} WITA`),
        E.textLine('Pengawas'),
        E.textLine(''),
        E.textLine(''),
        E.textLine(''),
        E.textLine('................'),
        E.textLine(`(${pengawas})`),
        E.ALIGN_LT,
        E.feed(3),
        E.CUT,
    );
}

window.SuratPayload = { buildIzinKeluarBytes, buildIzinTelatBytes };
