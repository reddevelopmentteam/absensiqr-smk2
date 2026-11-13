// Modules/ScanQR/resources/assets/js/scanner.js

import { createScanAudio } from './scan-audio.js';
import { createScanLock } from './scan-lock.js';
export { createScanAudio };
export { createScanResultController } from './scan-result.js';
const qrLock = createScanLock();
let generation = 0;
let decoding = false;
window.resetScannerQrLock = () => qrLock.reset();

import { readBarcodes } from 'zxing-wasm/reader';

// =========================
// MODULE STATE
// =========================

let animationFrame = null;
let stream = null; // ← di-reuse, TIDAK di-stop saat destroy
let videoEl = null;
let overlayCanvas = null;
let overlayCtx = null;
let scanCanvas = null;
let scanCtx = null;
let resizeObserver = null;
let isRunning = false;
let lastScanTime = 0;
let isDestroying = false; // ← guard double-call

const SCAN_WIDTH = 640;
const SCAN_HEIGHT = 480;

const INTERVAL_IDLE = 100;
const INTERVAL_ACTIVE = 50;
let scanInterval = INTERVAL_IDLE;

// =========================
// INTERNAL CLEANUP
// Tidak stop stream — reuse untuk reinit cepat
// =========================

function cleanup() {
    isRunning = false;
    generation++;

    if (animationFrame !== null) {
        cancelAnimationFrame(animationFrame);
        animationFrame = null;
    }

    if (videoEl !== null) {
        videoEl.pause();
        videoEl.srcObject = null;
        videoEl = null;
    }

    if (resizeObserver !== null) {
        resizeObserver.disconnect();
        resizeObserver = null;
    }

    overlayCtx = null;
    overlayCanvas = null;
    scanCtx = null;
    scanCanvas = null;
    lastScanTime = 0;
    scanInterval = INTERVAL_IDLE;
}

// =========================
// DESTROY
// =========================

window.destroyScanner = () => {
    // Guard: cegah double-call
    if (isDestroying) return;
    isDestroying = true;

    cleanup();

    const reader = document.getElementById('reader');
    if (reader) reader.innerHTML = '';

    isDestroying = false;
};

window.stopScannerCamera = () => {
    window.destroyScanner();
    if (stream) {
        stream.getTracks().forEach((track) => track.stop());
        stream = null;
    }
};

// =========================
// INIT
// =========================

window.initScanner = async () => {
    // Guard: jangan init kalau masih running
    if (isRunning) return;

    const reader = document.getElementById('reader');
    if (!reader) return;
    const currentGeneration = ++generation;

    // =========================
    // READER CONTAINER
    // =========================

    Object.assign(reader.style, {
        position: 'relative',
        width: '100%',
        height: '100%',
        overflow: 'hidden',
        background: '#000',
    });

    // =========================
    // VIDEO
    // =========================

    videoEl = document.createElement('video');
    videoEl.autoplay = true;
    videoEl.playsInline = true;
    videoEl.muted = true;

    Object.assign(videoEl.style, {
        position: 'absolute',
        top: '0',
        left: '0',
        width: '100%',
        height: '100%',
        objectFit: 'cover',
    });

    // =========================
    // OVERLAY CANVAS
    // =========================

    overlayCanvas = document.createElement('canvas');

    Object.assign(overlayCanvas.style, {
        position: 'absolute',
        top: '0',
        left: '0',
        width: '100%',
        height: '100%',
        pointerEvents: 'none',
        zIndex: '10',
    });

    reader.appendChild(videoEl);
    reader.appendChild(overlayCanvas);

    // =========================
    // SCAN CANVAS (hidden)
    // =========================

    scanCanvas = document.createElement('canvas');
    scanCanvas.width = SCAN_WIDTH;
    scanCanvas.height = SCAN_HEIGHT;

    scanCtx = scanCanvas.getContext('2d', {
        willReadFrequently: true,
        alpha: false,
    });

    overlayCtx = overlayCanvas.getContext('2d');

    // =========================
    // RESIZE OVERLAY
    // =========================

    function syncOverlaySize() {
        if (!overlayCanvas || !reader) return;
        const rect = reader.getBoundingClientRect();
        overlayCanvas.width = rect.width || reader.offsetWidth;
        overlayCanvas.height = rect.height || reader.offsetHeight;
    }

    syncOverlaySize();

    resizeObserver = new ResizeObserver(syncOverlaySize);
    resizeObserver.observe(reader);

    // =========================
    // CAMERA — reuse stream kalau masih aktif
    // Ini yang bikin reinit cepat di webcam murah
    // =========================

    try {
        const streamOk =
            stream !== null &&
            stream.active &&
            stream.getTracks().every((t) => t.readyState === 'live');

        let nextStream = stream;
        if (!streamOk) {
            // Stream belum ada atau sudah mati — minta baru
            if (stream !== null) {
                stream.getTracks().forEach((t) => t.stop());
                stream = null;
            }
            nextStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    // "user" memilih kamera depan pada perangkat mobile.
                    // ideal tetap memberi fallback pada perangkat yang hanya
                    // menyediakan satu kamera.
                    facingMode: { ideal: 'user' },
                    width: { ideal: SCAN_WIDTH },
                    height: { ideal: SCAN_HEIGHT },
                },
                audio: false,
            });
        }
        if (currentGeneration !== generation || !reader.isConnected) {
            if (nextStream && nextStream !== stream) {
                nextStream.getTracks().forEach((track) => track.stop());
            }
            return;
        }
        stream = nextStream;
    } catch (err) {
        console.error('[scanner] Gagal akses kamera:', err);
        return;
    }

    if (currentGeneration !== generation || !reader.isConnected) return;
    videoEl.srcObject = stream;

    try {
        await videoEl.play();
    } catch (err) {
        console.error('[scanner] Gagal play video:', err);
        return;
    }

    if (currentGeneration !== generation || !reader.isConnected) return;

    // =========================
    // SCALE POINT
    // =========================

    function scalePoint(point) {
        const vRatio = SCAN_WIDTH / SCAN_HEIGHT;
        const cRatio = overlayCanvas.width / overlayCanvas.height;

        let dw,
            dh,
            ox = 0,
            oy = 0;

        if (cRatio > vRatio) {
            dw = overlayCanvas.width;
            dh = overlayCanvas.width / vRatio;
            oy = (overlayCanvas.height - dh) / 2;
        } else {
            dh = overlayCanvas.height;
            dw = overlayCanvas.height * vRatio;
            ox = (overlayCanvas.width - dw) / 2;
        }

        return {
            x: (point.x / SCAN_WIDTH) * dw + ox,
            y: (point.y / SCAN_HEIGHT) * dh + oy,
        };
    }

    // =========================
    // DRAW BOX
    // =========================

    function drawBox(location) {
        const pts = [
            scalePoint(location.topLeftCorner),
            scalePoint(location.topRightCorner),
            scalePoint(location.bottomRightCorner),
            scalePoint(location.bottomLeftCorner),
        ];

        overlayCtx.strokeStyle = '#00FF66';
        overlayCtx.lineWidth = 3;
        overlayCtx.lineJoin = 'round';

        overlayCtx.beginPath();
        overlayCtx.moveTo(pts[0].x, pts[0].y);
        pts.slice(1).forEach((p) => overlayCtx.lineTo(p.x, p.y));
        overlayCtx.closePath();
        overlayCtx.stroke();
    }

    // =========================
    // SCAN LOOP
    // =========================

    isRunning = true;

    async function scan(timestamp) {
        if (!isRunning) return;

        animationFrame = requestAnimationFrame(scan);

        if (timestamp - lastScanTime < scanInterval) return;
        if (window.scanned || !videoEl || videoEl.readyState < 4 || decoding) return;

        lastScanTime = timestamp;

        scanCtx.drawImage(videoEl, 0, 0, SCAN_WIDTH, SCAN_HEIGHT);

        const imageData = scanCtx.getImageData(0, 0, SCAN_WIDTH, SCAN_HEIGHT);

        overlayCtx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);

        decoding = true;
        let results;
        try {
            results = await readBarcodes(imageData, {
                tryHarder: true,
                tryDenoise: true,
                tryDownscale: true,
                tryInvert: true,
                tryRotate: true,
                formats: ['QRCode'],
                maxNumberOfSymbols: 1,
            });
        } catch {
            return;
        } finally {
            decoding = false;
        }
        if (!isRunning || window.scanned || currentGeneration !== generation) return;
        const code = results?.[0];

        if (!code || !code.position) {
            qrLock.observe(null, timestamp);
            scanInterval = INTERVAL_IDLE;
            return;
        }

        scanInterval = INTERVAL_ACTIVE;

        drawBox({
            topLeftCorner: code.position.topLeft,
            topRightCorner: code.position.topRight,
            bottomRightCorner: code.position.bottomRight,
            bottomLeftCorner: code.position.bottomLeft,
        });

        if (!window.scanned && qrLock.observe(code.text, timestamp)) {
            window.scanned = true;
            reader.dispatchEvent(
                new CustomEvent('scanStarted', {
                    bubbles: true,
                    detail: { qr: code.text },
                }),
            );
        }
    }

    animationFrame = requestAnimationFrame(scan);
};
