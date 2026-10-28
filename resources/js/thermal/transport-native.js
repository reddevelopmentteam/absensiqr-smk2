/**
 * Transport native untuk aplikasi Android Absensi (WebView).
 *
 * System WebView tidak mendukung WebUSB maupun WebBluetooth — keduanya
 * `in navigator` bernilai false di dalam aplikasi. Sebagai gantinya byte
 * ESC/POS dikirim lewat `window.ThermalPrinter` (JavascriptInterface
 * native, Bluetooth Classic/SPP) yang dipasang MainActivity.
 *
 * Di browser biasa file ini tetap aman di-load: isSupported() false dan
 * sendBytes() menolak dengan pesan yang jelas.
 */

function bridge() {
    return typeof window.ThermalPrinter !== 'undefined'
        ? window.ThermalPrinter
        : null;
}

function isSupported() {
    try {
        const b = bridge();
        return b != null && b.isSupported();
    } catch (e) {
        return false;
    }
}

function toBase64(bytes) {
    const data = bytes instanceof Uint8Array ? bytes : Uint8Array.from(bytes);
    let bin = '';
    for (let i = 0; i < data.length; i += 0x8000) {
        bin += String.fromCharCode.apply(null, data.subarray(i, i + 0x8000));
    }
    return btoa(bin);
}

/**
 * Kirim byte ke printer. address opsional (MAC "AA:BB:CC:DD:EE:FF");
 * kosong = otomatis (nama IWARE dulu, lalu bonded pertama).
 */
async function sendBytes(bytes, address) {
    const b = bridge();
    if (b == null || !isSupported()) {
        throw new Error('Aplikasi Absensi tidak terpasang/di luar aplikasi.');
    }
    if (!b.hasPermission()) {
        // Minta izin sekali; kirim setelah user menyetujui.
        await new Promise((resolve, reject) => {
            const done = () => {
                window.removeEventListener('thermal-permission-granted', done);
                resolve();
            };
            const timer = window.setTimeout(() => {
                window.removeEventListener('thermal-permission-granted', done);
                reject(new Error('Izin Bluetooth belum diberikan.'));
            }, 60000);
            window.addEventListener(
                'thermal-permission-granted',
                () => {
                    window.clearTimeout(timer);
                    done();
                },
                { once: true },
            );
            try {
                b.requestPermission();
            } catch (e) {
                window.clearTimeout(timer);
                window.removeEventListener('thermal-permission-granted', done);
                reject(e);
            }
        });
    }
    const raw = b.print(toBase64(bytes), address || '');
    let res;
    try {
        res = JSON.parse(raw);
    } catch (e) {
        throw new Error('Respons printer tidak valid.');
    }
    if (!res.ok) {
        const err = new Error(res.error || 'Gagal mencetak.');
        if (res.needPermission) {
            try {
                b.requestPermission();
            } catch (ignored) {
                // Pesan error utama yang dilaporkan.
            }
        }
        throw err;
    }
}

function pairedPrinters() {
    const b = bridge();
    if (b == null) return [];
    try {
        return JSON.parse(b.pairedPrinters() || '[]');
    } catch (e) {
        return [];
    }
}

window.ThermalNative = { isSupported, sendBytes, pairedPrinters };
