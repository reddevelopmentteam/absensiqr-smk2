/**
 * Transport WebUSB untuk printer thermal IWARE 58mm (jalur utama, kabel USB).
 *
 * Membutuhkan Chrome/Edge desktop + HTTPS (atau http://localhost) dan
 * user gesture (klik tombol). Tanpa aplikasi/driver print tambahan.
 *
 * Catatan VID: VID IWARE umum belum pasti. Isi ALLOWLIST saat implementasi
 * dari chrome://device-log di mesin kasir (satu nilai literal, bukan tebakan).
 * Bila tidak cocok, panggil dengan filter kosong (hanya di HTTPS/localhost).
 */

const ALLOWLIST = [
    // { vendorId: 0xXXXX, productId: 0xXXXX }, // IWARE — isi dari chrome://device-log
];

const CHUNK = 512;

function filters() {
    return ALLOWLIST.map((d) => ({ ...d }));
}

async function pickDevice() {
    if (!('usb' in navigator)) {
        throw new Error('WebUSB tidak didukung browser ini.');
    }
    // Reconnect tanpa pairing ulang bila pernah diizinkan.
    const known = await navigator.usb.getDevices();
    if (known.length > 0) return known[0];
    // Pairing baru wajib lewat user gesture.
    return await navigator.usb.requestDevice({ filters: filters() });
}

async function findOutEndpoint(device) {
    // Umumnya interface 0, endpoint OUT pertama.
    for (const iface of device.configuration.interfaces) {
        for (const alt of iface.alternates) {
            for (const ep of alt.endpoints) {
                if (ep.direction === 'out') {
                    return {
                        interfaceNumber: iface.interfaceNumber,
                        endpointNumber: ep.endpointNumber,
                    };
                }
            }
        }
    }
    return { interfaceNumber: 0, endpointNumber: 1 };
}

async function sendBytes(bytes) {
    const device = await pickDevice();
    await device.open();
    try {
        if (device.configuration == null) {
            await device.selectConfiguration(1);
        }
        const { interfaceNumber, endpointNumber } =
            await findOutEndpoint(device);
        await device.claimInterface(interfaceNumber);
        try {
            const data =
                bytes instanceof Uint8Array ? bytes : Uint8Array.from(bytes);
            for (let i = 0; i < data.length; i += CHUNK) {
                const chunk = data.slice(i, i + CHUNK);
                await device.transferOut(endpointNumber, chunk);
            }
        } finally {
            await device.releaseInterface(interfaceNumber).catch(() => {});
        }
    } finally {
        await device.close().catch(() => {});
    }
}

window.ThermalUsb = { sendBytes, pickDevice, ALLOWLIST };
