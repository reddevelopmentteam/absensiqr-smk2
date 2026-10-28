/**
 * Transport WebBluetooth untuk printer thermal IWARE varian BT.
 * Hanya aktif bila navigator.bluetooth ada. Jalur USB kabel tetap utama.
 *
 * Membutuhkan Chrome/Edge + HTTPS wajib, dan user gesture.
 */

const PRINTER_SERVICE_UUIDS = [
    // Isi UUID service printer dari mesin kasir bila pairing butuh filter service.
    // Contoh umum SPP/BLE-write — biarkan daftar ini dan sesuaikan satu nilai literal.
];

const NAME_PREFIXES = ['IWARE'];
const CHUNK = 128;

async function pickCharacteristic() {
    if (!('bluetooth' in navigator)) {
        throw new Error('WebBluetooth tidak didukung browser ini.');
    }
    const request = {
        filters: NAME_PREFIXES.map((namePrefix) => ({ namePrefix })),
        optionalServices:
            PRINTER_SERVICE_UUIDS.length > 0
                ? [...PRINTER_SERVICE_UUIDS]
                : ['battery_service'],
    };
    const device = await navigator.bluetooth.requestDevice(request);
    const server = await device.gatt.connect();
    // Cari karakteristik write pertama di service yang cocok.
    const svcList =
        PRINTER_SERVICE_UUIDS.length > 0
            ? [await server.getPrimaryService(PRINTER_SERVICE_UUIDS[0])]
            : await server.getPrimaryServices();
    for (const svc of svcList) {
        const chars = await svc.getCharacteristics();
        const writable = chars.find(
            (c) =>
                c.properties.writeWithResponse ||
                c.properties.writeWithoutResponse,
        );
        if (writable) return { device, char: writable };
    }
    device.gatt.disconnect();
    throw new Error('Karakteristik tulis printer tidak ditemukan.');
}

async function sendBytes(bytes) {
    const { device, char } = await pickCharacteristic();
    try {
        const data =
            bytes instanceof Uint8Array ? bytes : Uint8Array.from(bytes);
        for (let i = 0; i < data.length; i += CHUNK) {
            const chunk = data.slice(i, i + CHUNK);
            if (char.properties.writeWithResponse) {
                await char.writeValueWithResponse(chunk);
            } else {
                await char.writeValueWithoutResponse(chunk);
            }
        }
    } finally {
        device.gatt.disconnect();
    }
}

function isSupported() {
    return 'bluetooth' in navigator;
}

window.ThermalBt = { sendBytes, isSupported };
