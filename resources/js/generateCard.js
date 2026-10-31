import { jsPDF } from "jspdf";
import { createContext, destroyContext, domToCanvas } from "modern-screenshot";

const CONFIG = {
    CARD: {
        horizontal: {
            width: 85.6,
            height: 53.98
        }
    },
    PDF: {
        format: "a4",
        unit: "mm",
        orientation: "portrait",
        imageFormat: "JPEG",
        // 0.75 scale still gives roughly 200 DPI on an ID-card-sized PDF,
        // while reducing raster pixels and JPEG work substantially.
        jpegQuality: 0.82,
        backgroundColor: "#ffffff",
        compression: "FAST",
        margin: {
            min: 5,
            max: 12
        },
        gap: {
            min: 3,
            max: 5
        }
    },
    CACHE: {
        canvasLimit: 2
    },
    FONT: {
        timeoutMs: 3000
    },
    TEMPLATE: {
        route: (orientation, model = "standar") => (model === "standar"
            ? `/mpanel/card-template/${orientation}`
            : `/mpanel/card-template/${orientation}?model=${encodeURIComponent(model)}`),
        selector: ".student-card",
        scale: 0.75,
        logoUrl: "/assets/img/logo_smkn_2.png",
        backLogoUrl: "/assets/img/logo-belakang.png",
        motifUrl: "/assets/img/motif-kartu.png",
        fullMotifWidth: 204,
        fullMotifHeight: 324,
        frameWidth: 1000,
        frameHeight: 1000,
        transparentPixel: "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=="
    },
    TEXT: {
        studentName: 40,
        className: 28,
        major: 28,
        nisn: 20
    }
};

const formatBirthDate = (value) => {
    if (!value) return "";

    const date = value instanceof Date ? value : new Date(value);

    if (Number.isNaN(date.getTime())) return String(value ?? "");

    return date.toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" });
};
const templateCache = new Map();
const templateRequestCache = new Map();
const canvasCache = new Map();
const canvasCacheKeys = new WeakMap();
const jpegCache = new WeakMap();
const qrSvgCache = new Map();
const qrSvgRequestCache = new Map();
const photoPrefetchCache = new Map();
const photoFailureCache = new Set();
const renderedCanvasRegistry = new Set();
let whiteCanvas = null;
let whiteCanvasContext = null;

const cacheLimit = (cache, limit, onEvict) => {
    while (cache.size > limit) {
        const oldestKey = cache.keys().next().value;
        const oldestValue = cache.get(oldestKey);

        cache.delete(oldestKey);

        if (typeof onEvict === "function") {
            onEvict(oldestValue, oldestKey);
        }
    }
};

const revokeObjectUrl = (url) => {
    if (typeof url === "string" && url.startsWith("blob:")) {
        URL.revokeObjectURL(url);
    }
};

const normalizeOrientation = (orientation) => (
    orientation === "vertical" ? "vertical" : "horizontal"
);

const BACK_KEY_SUFFIX = "-belakang";

const isBackKey = (key) => typeof key === "string" && key.endsWith(BACK_KEY_SUFFIX);

const baseOrientationOf = (key) => normalizeOrientation(
    isBackKey(key) ? key.slice(0, -BACK_KEY_SUFFIX.length) : key
);

const backKeyOf = (orientation) => `${normalizeOrientation(orientation)}${BACK_KEY_SUFFIX}`;

const getCardSize = (orientation = "horizontal") => {
    const base = CONFIG.CARD.horizontal;

    if (normalizeOrientation(orientation) === "vertical") {
        return {
            width: base.height,
            height: base.width
        };
    }

    return {
        width: base.width,
        height: base.height
    };
};

const truncateText = (value, maxLength) => {
    const text = String(value ?? "").trim();

    if (text.length <= maxLength) return text;

    return `${text.slice(0, Math.max(0, maxLength - 3))}...`;
};

const sanitizeFileName = (value) => String(value ?? "")
    .trim()
    .replace(/[\\?%*:|"<>]/g, "-")
    .replace(/\s+/g, "-")
    .replace(/-+/g, "-")
    .replace(/^[-.]+|[-.]+$/g, "");

const buildStudentCardFileName = (murid) => {
    const className = murid?.rombel?.nama_lengkap ?? "Kelas";
    const name = murid?.nama ?? "Murid";
    const nisn = murid?.nisn ?? "NISN";
    const sanitized = sanitizeFileName(`${className}-${name}-${nisn}`) || "kartu-pelajar";

    return `${sanitized}.pdf`;
};

const blobToBytes = async (blob) => {
    if (!blob) return null;

    if (typeof blob.arrayBuffer === "function") {
        return new Uint8Array(await blob.arrayBuffer());
    }

    return await new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onload = () => resolve(new Uint8Array(reader.result));
        reader.onerror = () => reject(new Error("Unable to convert blob to bytes."));
        reader.readAsArrayBuffer(blob);
    });
};

const createSvgObjectUrl = (svgString) => {
    if (!svgString) return null;

    return URL.createObjectURL(
        new Blob([svgString], { type: "image/svg+xml" })
    );
};

const canvasToBlob = (canvas, type, quality) => new Promise((resolve) => {
    if (typeof canvas.toBlob !== "function") {
        resolve(null);
        return;
    }

    canvas.toBlob(resolve, type, quality);
});

const fetchText = async (url, options = {}) => {
    const response = await fetch(url, options);

    if (!response.ok) {
        throw new Error(`Unable to fetch ${url}: ${response.status} ${response.statusText}`);
    }

    return response.text();
};

const toAbsoluteUrl = (url) => {
    if (!url) return null;

    try {
        return new URL(url, window.location.origin).href;
    } catch {
        return url;
    }
};

const getImageSource = (url) => {
    if (!url) return null;
    if (String(url).startsWith("data:")) return url;

    return toAbsoluteUrl(url);
};

// Warms the browser's HTTP cache for a student's photo ahead of time so that,
// by the time renderTemplateCanvas() actually needs it, the image is already
// downloaded and decoding can happen instantly instead of blocking on a
// network round trip.
const prefetchPhoto = (murid) => {
    const src = getImageSource(murid?.image_path ?? null);

    if (!src || src.startsWith("data:") || photoFailureCache.has(src)) return;

    const existingEntry = photoPrefetchCache.get(src);

    if (existingEntry?.promise) return existingEntry.promise;

    const entry = {
        objectUrl: null,
        promise: null
    };

    entry.promise = (async () => {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 8000);

        try {
            const response = await fetch(src, {
                cache: "force-cache",
                mode: "cors",
                signal: controller.signal
            });

            if (!response.ok) {
                throw new Error(`Unable to fetch image ${src}: ${response.status} ${response.statusText}`);
            }

            const blob = await response.blob();

            const objectUrl = URL.createObjectURL(blob);

            if (photoPrefetchCache.get(src) !== entry) {
                revokeObjectUrl(objectUrl);
                return null;
            }

            entry.objectUrl = objectUrl;

            return objectUrl;
        } finally {
            clearTimeout(timeoutId);
        }
    })().catch(() => {
        photoFailureCache.add(src);
        if (entry.objectUrl) {
            revokeObjectUrl(entry.objectUrl);
            entry.objectUrl = null;
        }

        return null;
    }).finally(() => {
        if (photoPrefetchCache.get(src) === entry) {
            cacheLimit(photoPrefetchCache, 8, (oldEntry) => {
                revokeObjectUrl(oldEntry?.objectUrl);
            });
        }
    });

    photoPrefetchCache.set(src, entry);
    cacheLimit(photoPrefetchCache, 8, (oldEntry) => {
        revokeObjectUrl(oldEntry?.objectUrl);
    });

    return entry.promise;
};

const waitForFrameLoad = (frame) => new Promise((resolve) => {
    frame.addEventListener("load", resolve, { once: true });
});

const waitForImage = async (image) => {
    if (!image) return false;

    if (image.complete && image.naturalWidth > 0) return true;

    try {
        if (typeof image.decode === "function") {
            await image.decode();
            return image.naturalWidth > 0;
        }
    } catch {
        // Fall through to load/error events when decode is not reliable.
    }

    // The image may finish loading between the initial check and decode()
    // rejecting. Re-check it before subscribing to events that have already
    // fired; otherwise callers needlessly wait for the 600 ms timeout.
    if (image.complete) return image.naturalWidth > 0;

    return await new Promise((resolve) => {
        image.addEventListener("load", () => resolve(image.naturalWidth > 0), { once: true });
        image.addEventListener("error", () => resolve(false), { once: true });
    });
};

const withTimeout = async (promise, timeoutMs) => {
    let timeoutId = null;

    try {
        return await Promise.race([
            promise,
            new Promise((resolve) => {
                timeoutId = setTimeout(resolve, timeoutMs);
            })
        ]);
    } finally {
        clearTimeout(timeoutId);
    }
};

const yieldToBrowser = async () => {
    if (globalThis.scheduler?.yield) {
        await globalThis.scheduler.yield();
        return;
    }

    if (typeof window.requestIdleCallback === "function") {
        await new Promise((resolve) => {
            window.requestIdleCallback(() => resolve(), { timeout: 16 });
        });

        return;
    }

    await new Promise((resolve) => {
        if (document.hidden || typeof requestAnimationFrame !== "function") {
            setTimeout(resolve, 0);
            return;
        }

        requestAnimationFrame(() => resolve());
    });
};

const shouldYieldAfterCard = ({ cardsSinceYield, lastYieldAt }) => {
    const elapsedMs = performance.now() - lastYieldAt;

    // Rendering a card normally takes longer than 24 ms, so the previous
    // condition yielded after virtually every card. Keep the interface
    // responsive while amortizing the scheduler/frame cost across a small
    // batch of cards.
    return cardsSinceYield >= 4
        || (cardsSinceYield >= 2 && elapsedMs >= 100);
};

const encodeSvgDataUrl = (svgString) => {
    if (!svgString) return "";

    return createSvgObjectUrl(svgString);
};

const getWhiteCanvas = (width, height) => {
    if (!whiteCanvas) {
        whiteCanvas = document.createElement("canvas");
        whiteCanvasContext = whiteCanvas.getContext("2d", {
            alpha: false,
            willReadFrequently: false
        });
    }

    if (!whiteCanvasContext) {
        throw new Error("Unable to create canvas context for PDF export.");
    }

    if (whiteCanvas.width !== width) whiteCanvas.width = width;
    if (whiteCanvas.height !== height) whiteCanvas.height = height;

    return {
        canvas: whiteCanvas,
        context: whiteCanvasContext
    };
};
const roundCanvasCorners = (canvas, radiusPx) => {
    if (!canvas || !radiusPx || radiusPx <= 0) return canvas;

    const rounded = document.createElement("canvas");

    rounded.width = canvas.width;
    rounded.height = canvas.height;

    const ctx = rounded.getContext("2d");

    if (!ctx || typeof ctx.roundRect !== "function") return canvas;

    // Sudut kartu transparan di luar radius; JPEG tidak punya alpha sehingga
    // area itu jadi putih dan menyatu dengan halaman putih. Isi dulu dengan
    // warna background PDF agar sudut kartu terlihat utuh saat ditempel.
    ctx.fillStyle = CONFIG.PDF.backgroundColor;
    ctx.fillRect(0, 0, rounded.width, rounded.height);
    ctx.beginPath();
    ctx.roundRect(0, 0, canvas.width, canvas.height, radiusPx);
    ctx.clip();
    ctx.drawImage(canvas, 0, 0);

    return rounded;
};

const canvasToJpegBytes = async (canvas, quality = CONFIG.PDF.jpegQuality) => {
    if (!canvas) return null;

    let bytes = null;
    // domToCanvas() already renders with a solid white background. Encoding
    // the result directly avoids an additional full-size canvas allocation and
    // drawImage() pass for every card.
    const blob = await canvasToBlob(canvas, "image/jpeg", quality);

    if (blob) {
        bytes = await blobToBytes(blob);
    } else if (typeof canvas.toDataURL === "function") {
        const dataUrl = canvas.toDataURL("image/jpeg", quality);
        const response = await fetch(dataUrl);

        bytes = new Uint8Array(await response.arrayBuffer());
    } else if (typeof canvas.convertToBlob === "function") {
        const convertedBlob = await canvas.convertToBlob({
            type: "image/jpeg",
            quality
        });

        bytes = await blobToBytes(convertedBlob);
    } else {
        throw new Error("Unable to convert canvas to JPEG bytes.");
    }

    jpegCache.set(canvas, bytes);

    return bytes;
};

const createRenderCacheKey = ({ orientation, murid, qrSvg, model = "standar" }) => JSON.stringify({
    orientation: normalizeOrientation(orientation),
    model,
    uuid: murid?.uuid ?? "",
    image: murid?.image_path ?? "",
    name: murid?.nama ?? "",
    className: murid?.rombel?.nama_lengkap ?? "",
    major: murid?.rombel?.jurusan?.nama ?? "",
    nisn: murid?.nisn ?? "",
    qrSvg: qrSvg ?? ""
});

const cacheRenderedCanvas = (key, canvas) => {
    if (!key || !canvas) return;

    canvasCache.set(key, canvas);
    canvasCacheKeys.set(canvas, key);
    renderedCanvasRegistry.add(canvas);

    while (canvasCache.size > CONFIG.CACHE.canvasLimit) {
        const oldestKey = canvasCache.keys().next().value;
        const oldestCanvas = canvasCache.get(oldestKey);

        canvasCache.delete(oldestKey);
        renderedCanvasRegistry.delete(oldestCanvas);
    }
};

const releaseRenderedCanvas = (canvas) => {
    const key = canvasCacheKeys.get(canvas);

    if (key) canvasCache.delete(key);

    renderedCanvasRegistry.delete(canvas);
};

const createPdfDocument = () => new jsPDF({
    format: CONFIG.PDF.format,
    orientation: CONFIG.PDF.orientation,
    unit: CONFIG.PDF.unit,
    compress: true
});

const calculateGridAxis = ({ pageLength, cardLength, minMargin, maxMargin, minGap, maxGap }) => {
    let best = null;

    for (let count = 1; count <= 20; count += 1) {
        const remaining = pageLength - (count * cardLength);
        if (remaining < minMargin * 2) break;

        const rawGap = count > 1
            ? Math.min(maxGap, Math.max(minGap, (remaining - minMargin * 2) / (count - 1)))
            : 0;
        const margin = (pageLength - (count * cardLength) - ((count - 1) * rawGap)) / 2;

        if (margin < minMargin) continue;

        best = {
            count,
            gap: rawGap,
            margin: Math.min(maxMargin, margin)
        };
    }

    return best ?? {
        count: 1,
        gap: 0,
        margin: Math.max(minMargin, (pageLength - cardLength) / 2)
    };
};

const getPdfGrid = (doc, orientation = "horizontal") => {
    const size = getCardSize(orientation);
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const columnAxis = calculateGridAxis({
        pageLength: pageWidth,
        cardLength: size.width,
        minMargin: CONFIG.PDF.margin.min,
        maxMargin: CONFIG.PDF.margin.max,
        minGap: CONFIG.PDF.gap.min,
        maxGap: CONFIG.PDF.gap.max
    });
    const rowAxis = calculateGridAxis({
        pageLength: pageHeight,
        cardLength: size.height,
        minMargin: CONFIG.PDF.margin.min,
        maxMargin: CONFIG.PDF.margin.max,
        minGap: CONFIG.PDF.gap.min,
        maxGap: CONFIG.PDF.gap.max
    });

    return {
        rows: rowAxis.count,
        columns: columnAxis.count,
        pageWidth,
        pageHeight,
        card: size,
        margin: {
            x: columnAxis.margin,
            y: rowAxis.margin
        },
        gap: {
            x: columnAxis.gap,
            y: rowAxis.gap
        },
        positions: Array.from({ length: rowAxis.count * columnAxis.count }, (_, index) => {
            const row = Math.floor(index / columnAxis.count);
            const column = index % columnAxis.count;

            return {
                x: columnAxis.margin + (column * (size.width + columnAxis.gap)),
                y: rowAxis.margin + (row * (size.height + rowAxis.gap))
            };
        })
    };
};

const addCardToPdf = async ({ pdf, canvas, orientation = "horizontal", position }) => {
    if (!pdf) throw new Error("PDF document is required.");
    if (!canvas) throw new Error("Canvas is required to add a card to PDF.");

    const size = getCardSize(orientation);
    const jpegBytes = await canvasToJpegBytes(canvas);

    if (!jpegBytes) throw new Error("Unable to create PDF image from canvas.");

    pdf.addImage(
        jpegBytes,
        CONFIG.PDF.imageFormat,
        position.x,
        position.y,
        size.width,
        size.height,
        undefined,
        CONFIG.PDF.compression
    );

    releaseRenderedCanvas(canvas);
};

class TemplateLoader {
    constructor({
        templateCacheStore = templateCache
    } = {}) {
        this.templateCache = templateCacheStore;
        this.sessions = new Map();
        this.sessionRequests = new Map();
        this.logoDataUrlPromise = null;
        this.backLogoDataUrlPromise = null;
        this.motifDataUrlPromise = null;
    }
    async fetchTemplate(orientation, { model = "standar", retries = 2 } = {}) {
        const key = isBackKey(orientation) ? orientation : normalizeOrientation(orientation);
        const cacheKey = `${model}:${key}`;

        if (this.templateCache.has(cacheKey)) {
            return this.templateCache.get(cacheKey);
        }

        if (templateRequestCache.has(cacheKey)) {
            return templateRequestCache.get(cacheKey);
        }

        const request = (async () => {
            let lastError = null;

            for (let attempt = 0; attempt <= retries; attempt += 1) {
                try {
                    const template = await fetchText(CONFIG.TEMPLATE.route(key, model), {
                        cache: "no-cache"
                    });

                    // Server baru sudah injeksi motif; server lama masih
                    // serving file mentah dengan placeholder __MOTIF_DATA__
                    // — tambal di client agar kartu tetap bermotif.
                    return await this.withClientMotifFallback(template);
                } catch (error) {
                    lastError = error;

                    // eslint-disable-next-line no-await-in-loop
                    await new Promise((resolve) => setTimeout(resolve, 400 * (attempt + 1)));
                }
            }

            throw lastError ?? new Error(`Unable to fetch template for ${key}.`);
        })();

        templateRequestCache.set(cacheKey, request);

        try {
            const template = await request;

            this.templateCache.set(cacheKey, template);

            return template;
        } catch (error) {
            templateRequestCache.delete(cacheKey);
            throw error;
        } finally {
            if (templateRequestCache.get(cacheKey) === request) {
                templateRequestCache.delete(cacheKey);
            }
        }
    }

    async withClientMotifFallback(template) {
        if (!template || !template.includes("__MOTIF_DATA__")) return template;

        try {
            this.motifDataUrlPromise ??= (async () => {
                const response = await fetch(CONFIG.TEMPLATE.motifUrl, { cache: "force-cache" });

                if (!response.ok) throw new Error(`Motif fetch failed: ${response.status}`);

                const blob = await response.blob();

                return await new Promise((resolve, reject) => {
                    const reader = new FileReader();

                    reader.onload = () => resolve(reader.result);
                    reader.onerror = () => reject(new Error("Unable to read motif blob."));
                    reader.readAsDataURL(blob);
                });
            })();

            const motifDataUrl = await this.motifDataUrlPromise;

            return template.replaceAll("__MOTIF_DATA__", motifDataUrl);
        } catch {
            this.motifDataUrlPromise = null;

            return template;
        }
    }

    createFontMarkup() {
        return "";
        /*
        const stylesheetLinks = CONFIG.FONT.stylesheets.map((href) => (
            `<link rel="stylesheet" href="${href}">`
        )).join("");

        return [
            '<link rel="preconnect" href="https://fonts.googleapis.com">',
            '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>',
            stylesheetLinks
        ].join("");
        */
    }

    createInitialTemplateHtml(template) {
        const html = template
            .replaceAll("__LOGO__", CONFIG.TEMPLATE.transparentPixel)
            .replaceAll("__PHOTO__", CONFIG.TEMPLATE.transparentPixel)
            .replaceAll("__QR__", CONFIG.TEMPLATE.transparentPixel);
        const fontMarkup = this.createFontMarkup();

        if (html.includes("</head>")) {
            return html.replace("</head>", `${fontMarkup}</head>`);
        }

        return `${fontMarkup}${html}`;
    }

    createFrame() {
        const iframe = document.createElement("iframe");

        iframe.setAttribute("aria-hidden", "true");
        iframe.style.position = "fixed";
        iframe.style.left = "-10000px";
        iframe.style.top = "0";
        iframe.style.width = `${CONFIG.TEMPLATE.frameWidth}px`;
        iframe.style.height = `${CONFIG.TEMPLATE.frameHeight}px`;
        iframe.style.border = "0";
        iframe.style.opacity = "0";
        iframe.style.pointerEvents = "none";
        // Without an explicit z-index, this fixed-position offscreen frame
        // follows default DOM-order stacking and can end up rendered above
        // other fixed/absolute UI (modals, dropdowns, sidebars) in some
        // browsers/layouts, even though it's shifted far off-screen.
        // Pinning it to the bottom of the stack prevents that.
        iframe.style.zIndex = "-1";

        document.body.appendChild(iframe);

        return iframe;
    }

    findTextNode(doc, token) {
        const textFilter = doc.defaultView?.NodeFilter?.SHOW_TEXT ?? 4;
        const walker = doc.createTreeWalker(doc.body, textFilter);
        let node = walker.nextNode();

        while (node) {
            if (node.nodeValue.includes(token)) return node;
            node = walker.nextNode();
        }

        return null;
    }

    findImage(doc, token, altPattern) {
        return doc.querySelector(`img[src="${token}"]`)
            ?? Array.from(doc.querySelectorAll("img")).find((image) => (
                altPattern.test(image.getAttribute("alt") ?? "")
            ))
            ?? null;
    }

    collectSessionRefs(doc) {
        return {
            logoImage: this.findImage(doc, "__LOGO__", /logo/i),
            photoImage: this.findImage(doc, "__PHOTO__", /foto|photo/i),
            qrImage: this.findImage(doc, "__QR__", /qr/i),
            studentNameNode: this.findTextNode(doc, "__STUDENT_NAME__"),
            classNameNode: this.findTextNode(doc, "__KELAS__"),
            majorNode: this.findTextNode(doc, "__JURUSAN__"),
            birthDateNode: this.findTextNode(doc, "__TANGGAL_LAHIR__"),
            nisnNode: this.findTextNode(doc, "__NISN__")
        };
    }

    async waitForFonts(doc) {
        return undefined;
        /*
        // Previously this walked every element in the card with getComputedStyle()
        // to "discover" font families. That forces a synchronous style
        // recalculation over the whole subtree on every session creation, which
        // is pure main-thread cost for no real benefit (the templates only ever
        // use the families already declared in CONFIG.FONT.families). Loading
        // only the known families/weights is just as correct and cheaper.
        const fontSet = doc?.fonts;

        if (!fontSet) return;

        const loads = CONFIG.FONT.families.flatMap((family) => (
            CONFIG.FONT.weights.map((weight) => fontSet.load(`${weight} 16px "${family}"`))
        ));

        await withTimeout(Promise.allSettled(loads), CONFIG.FONT.timeoutMs);
        await withTimeout(fontSet.ready, CONFIG.FONT.timeoutMs);
        */
    }
    async createSession(orientation, { model = "standar" } = {}) {
        const key = isBackKey(orientation) ? orientation : normalizeOrientation(orientation);
        const sessionKey = `${model}:${key}`;
        const template = await this.fetchTemplate(key, { model });
        const frame = this.createFrame();
        const loadPromise = waitForFrameLoad(frame);

        frame.srcdoc = this.createInitialTemplateHtml(template);
        await loadPromise;

        const doc = frame.contentDocument;
        const card = doc?.querySelector(CONFIG.TEMPLATE.selector);

        if (!doc || !card) {
            frame.remove();
            throw new Error(`Card template is missing "${CONFIG.TEMPLATE.selector}".`);
        }

        const session = {
            frame,
            doc,
            card,
            isBack: isBackKey(key),
            refs: this.collectSessionRefs(doc),
            cardRadiusPx: parseFloat(doc?.defaultView?.getComputedStyle(card)?.borderRadius) || 0
        };

        await this.waitForFonts(doc);

        this.sessions.set(sessionKey, session);

        return session;
    }

    async getSession(orientation, { model = "standar" } = {}) {
        const key = isBackKey(orientation) ? orientation : normalizeOrientation(orientation);
        const sessionKey = `${model}:${key}`;
        const session = this.sessions.get(sessionKey);

        if (session?.frame?.isConnected) return session;

        if (this.sessionRequests.has(sessionKey)) {
            return this.sessionRequests.get(sessionKey);
        }

        const request = this.createSession(key, { model })
            .finally(() => {
                if (this.sessionRequests.get(sessionKey) === request) {
                    this.sessionRequests.delete(sessionKey);
                }
            });

        this.sessionRequests.set(sessionKey, request);

        return request;
    }

    async setImageSource(image, source, { retries = 1, timeoutMs = 5000 } = {}) {
        if (!image) return;

        const fallbackSource = CONFIG.TEMPLATE.transparentPixel;

        if (!source || photoFailureCache.has(source)) {
            if (image.getAttribute("src") !== fallbackSource) {
                image.removeAttribute("crossorigin");
                image.decoding = "async";
                image.setAttribute("src", fallbackSource);
                await waitForImage(image);
            }

            return;
        }

        for (let attempt = 0; attempt <= retries; attempt += 1) {
            if (source.startsWith("blob:")) {
                image.removeAttribute("crossorigin");
            } else if (!source.startsWith("data:")) {
                image.crossOrigin = "anonymous";
            } else {
                image.removeAttribute("crossorigin");
            }

            image.decoding = "async";
            image.setAttribute("src", source);

            // eslint-disable-next-line no-await-in-loop
            const loaded = await withTimeout(waitForImage(image), timeoutMs);

            if (loaded) return;

            // Timeout murni (gambar belum selesai, bukan error): beri jeda
            // singkat lalu cek naturalWidth sekali lagi sebelum memutuskan
            // retry/poison. Ini menangani decode() lambat untuk PNG besar
            // (logo 363KB) tanpa membanjiri server dengan request retry.
            if (image.naturalWidth > 0) return;

            if (image.complete) break;

            // Masih loading setelah timeout: retry sekali dengan URL segar
            // untuk mengalahkan cache browser yang macet. Source TIDAK di-
            // poison di sini — lambat kali ini belum tentu rusak.
            if (attempt < retries && !source.startsWith("data:") && !source.startsWith("blob:")) {
                const separator = source.includes("?") ? "&" : "?";

                image.removeAttribute("src");

                // eslint-disable-next-line no-await-in-loop
                await new Promise((resolve) => setTimeout(resolve, 300 * (attempt + 1)));

                image.decoding = "async";
                image.setAttribute("src", `${source}${separator}retry=${attempt + 1}`);

                // eslint-disable-next-line no-await-in-loop
                const retryLoaded = await withTimeout(waitForImage(image), timeoutMs);

                if (retryLoaded || image.naturalWidth > 0) return;

                if (image.complete) break;
            }
        }

        // Definitive failure after retries (HTTP error, decode error, decode
        // timeout): poison + transparent fallback so later renders don't stall.
        photoFailureCache.add(source);
        image.removeAttribute("crossorigin");
        image.decoding = "async";
        image.setAttribute("src", fallbackSource);
        await waitForImage(image);
    }

    setText(node, value) {
        if (!node) return;

        const nextValue = value ?? "";

        if (node.nodeValue !== nextValue) {
            node.nodeValue = nextValue;
        }
    }

    getLogoDataUrl() {
        this.logoDataUrlPromise ??= Promise.resolve(getImageSource(CONFIG.TEMPLATE.logoUrl));

        return this.logoDataUrlPromise;
    }

    getBackLogoDataUrl() {
        this.backLogoDataUrlPromise ??= Promise.resolve(getImageSource(CONFIG.TEMPLATE.backLogoUrl));

        return this.backLogoDataUrlPromise;
    }

    async getPrefetchedPhotoSource(source) {
        if (!source || source.startsWith("data:")) {
            return source;
        }

        const entry = photoPrefetchCache.get(source);

        if (!entry) {
            return source;
        }

        if (entry.objectUrl) {
            return entry.objectUrl;
        }

        const prefetched = await withTimeout(entry.promise, 5000);

        return prefetched || source;
    }

    async applyData(session, { murid, qrSvg }) {
        if (session.isBack) {
            const backLogoDataUrl = await this.getBackLogoDataUrl();

            await this.setImageSource(session.refs.logoImage, backLogoDataUrl);

            return null;
        }

        const [photoSource, logoDataUrl] = await Promise.all([
            this.getPrefetchedPhotoSource(getImageSource(murid?.image_path ?? null)),
            this.getLogoDataUrl()
        ]);
        const { refs } = session;
        const qrSource = encodeSvgDataUrl(qrSvg);
        this.setText(refs.studentNameNode, truncateText(murid?.nama ?? "", CONFIG.TEXT.studentName).toUpperCase());
        this.setText(refs.classNameNode, truncateText(murid?.rombel?.nama_lengkap ?? "", CONFIG.TEXT.className));
        this.setText(refs.majorNode, truncateText(murid?.rombel?.jurusan?.nama ?? "", CONFIG.TEXT.major));
        this.setText(refs.birthDateNode, formatBirthDate(murid?.tanggal_lahir));
        this.setText(refs.nisnNode, truncateText(murid?.nisn ?? "", CONFIG.TEXT.nisn));

        await Promise.all([
            this.setImageSource(refs.logoImage, logoDataUrl),
            this.setImageSource(refs.photoImage, photoSource),
            this.setImageSource(refs.qrImage, qrSource)
        ]);

        return qrSource;
    }

    async renderCanvas({ orientation = "horizontal", murid, qrSvg, model = "standar" }) {
        const normalizedOrientation = normalizeOrientation(orientation);
        const cacheKey = createRenderCacheKey({
            orientation: normalizedOrientation,
            murid,
            qrSvg,
            model
        });

        if (canvasCache.has(cacheKey)) return canvasCache.get(cacheKey);

        const session = await this.getSession(normalizedOrientation, { model });
        let qrSource = null;

        try {
            qrSource = await this.applyData(session, {
                murid,
                qrSvg
            });

            // domToCanvas (modern-screenshot) serializes the card into an SVG
            // <foreignObject> and lets the browser's native renderer draw it, then
            // reads that back into a canvas. Unlike html2canvas — which re-parses
            // CSS and re-implements layout/paint by hand in JS — this leans on
            // rendering the browser already does, which is why it's noticeably
            // faster and more faithful to the real on-screen appearance.
            // Keep modern-screenshot's context alive for the whole session.
            // It caches stylesheet/default-style analysis; recreating it for
            // every student is avoidable overhead in large batches.
            session.renderContext ??= await createContext(session.card, {
                autoDestruct: false,
                font: false,
                backgroundColor: CONFIG.PDF.backgroundColor,
                scale: CONFIG.TEMPLATE.scale,
                width: session.card.offsetWidth,
                height: session.card.offsetHeight,
                fetch: {
                    requestInit: { mode: "cors", cache: "force-cache" },
                    placeholderImage: CONFIG.TEMPLATE.transparentPixel
                }
            });

            const canvas = await domToCanvas(session.renderContext);
            const roundedCanvas = roundCanvasCorners(canvas, session.cardRadiusPx ?? 0);

            cacheRenderedCanvas(cacheKey, roundedCanvas);

            return roundedCanvas;
        } finally {
            revokeObjectUrl(qrSource);
        }
    }

    async renderBackCanvas({ orientation = "horizontal", model = "standar" } = {}) {
        const backKey = backKeyOf(orientation);
        const cacheKey = JSON.stringify({ back: backKey, model });

        if (canvasCache.has(cacheKey)) return canvasCache.get(cacheKey);

        const session = await this.getSession(backKey, { model });

        await this.applyData(session, {});

        session.renderContext ??= await createContext(session.card, {
            autoDestruct: false,
            font: false,
            backgroundColor: CONFIG.PDF.backgroundColor,
            scale: CONFIG.TEMPLATE.scale,
            width: session.card.offsetWidth,
            height: session.card.offsetHeight,
            fetch: {
                requestInit: { mode: "cors", cache: "force-cache" },
                placeholderImage: CONFIG.TEMPLATE.transparentPixel
            }
        });

        const canvas = await domToCanvas(session.renderContext);
        const roundedCanvas = roundCanvasCorners(canvas, session.cardRadiusPx ?? 0);

        cacheRenderedCanvas(cacheKey, roundedCanvas);

        return roundedCanvas;
    }

    async preload(orientations = ["horizontal"], { model = "standar" } = {}) {
        const uniqueOrientations = [...new Set(orientations.flatMap((orientation) => {
            const normalizedOrientation = normalizeOrientation(orientation);

            return isBackKey(orientation) ? [orientation] : [normalizedOrientation, backKeyOf(normalizedOrientation)];
        }))];

        await Promise.allSettled([
            this.getLogoDataUrl(),
            this.getBackLogoDataUrl(),
            ...uniqueOrientations.map((orientation) => this.getSession(orientation, { model }))
        ]);
    }

    clear() {
        for (const session of this.sessions.values()) {
            if (session.renderContext) {
                destroyContext(session.renderContext);
                session.renderContext = null;
            }
            session.frame?.remove();
        }

        this.sessions.clear();
        this.sessionRequests.clear();
        this.logoDataUrlPromise = null;
        this.backLogoDataUrlPromise = null;
        this.motifDataUrlPromise = null;
    }
}

class PdfManager {
    constructor() {
        this.entries = new Map();
    }

    createPdf() {
        return createPdfDocument();
    }

    createEntry(orientation = "horizontal") {
        const pdf = this.createPdf();

        return {
            pdf,
            orientation: normalizeOrientation(orientation),
            grid: getPdfGrid(pdf, orientation),
            cursor: 0
        };
    }

    resolveEntry(key = "default", orientation = "horizontal") {
        if (!this.entries.has(key)) {
            this.entries.set(key, this.createEntry(orientation));
        }

        return this.entries.get(key);
    }

    nextPosition(entry) {
        if (entry.cursor > 0 && entry.cursor % entry.grid.positions.length === 0) {
            entry.pdf.addPage();
            entry.grid = getPdfGrid(entry.pdf, entry.orientation);
            entry.cursor = 0;
        }

        const position = entry.grid.positions[entry.cursor];
        entry.cursor += 1;

        return position;
    }

    async addCard({ key = "default", canvas, orientation = "horizontal" }) {
        const normalizedOrientation = normalizeOrientation(orientation);
        const entry = this.resolveEntry(key, normalizedOrientation);

        if (entry.orientation !== normalizedOrientation && entry.cursor > 0) {
            entry.pdf.addPage();
            entry.orientation = normalizedOrientation;
            entry.grid = getPdfGrid(entry.pdf, normalizedOrientation);
            entry.cursor = 0;
        }

        await addCardToPdf({
            pdf: entry.pdf,
            canvas,
            orientation: normalizedOrientation,
            position: this.nextPosition(entry)
        });
    }

    async addBackPage({ key = "default", canvas, orientation = "horizontal" }) {
        if (!canvas) return;

        const baseOrientation = baseOrientationOf(orientation);
        const entry = this.resolveEntry(key, baseOrientation);
        const size = getCardSize(baseOrientation);
        const pageWidth = entry.pdf.internal.pageSize.getWidth();
        const pageHeight = entry.pdf.internal.pageSize.getHeight();

        entry.pdf.addPage();
        entry.grid = getPdfGrid(entry.pdf, entry.orientation);
        entry.cursor = 0;

        const jpegBytes = await canvasToJpegBytes(canvas);

        if (!jpegBytes) throw new Error("Unable to create PDF image from back card canvas.");

        entry.pdf.addImage(
            jpegBytes,
            CONFIG.PDF.imageFormat,
            (pageWidth - size.width) / 2,
            (pageHeight - size.height) / 2,
            size.width,
            size.height,
            undefined,
            CONFIG.PDF.compression
        );

        releaseRenderedCanvas(canvas);
    }

    export() {
        return Array.from(this.entries.entries()).map(([className, entry]) => ({
            className,
            pdfBytes: new Uint8Array(entry.pdf.output("arraybuffer"))
        }));
    }

    async exportAsync() {
        const results = [];

        for (const [className, entry] of this.entries.entries()) {
            if (results.length > 0) {
                await yieldToBrowser();
            }

            results.push({
                className,
                pdfBytes: new Uint8Array(entry.pdf.output("arraybuffer"))
            });
        }

        return results;
    }

    clear() {
        this.entries.clear();
    }
}

const templateLoader = new TemplateLoader();
const pdfManager = new PdfManager();

/**
 * Render a student card template to canvas.
 *
 * @param {Object} options
 * @param {"horizontal"|"vertical"} options.orientation
 * @param {Object} options.murid
 * @param {string} options.qrSvg
 * @returns {Promise<HTMLCanvasElement>}
 */
const renderTemplateCanvas = async ({ orientation = "horizontal", murid, qrSvg, model = "standar" } = {}) => {
    if (!murid) throw new Error("Student data is required to render a card.");

    return templateLoader.renderCanvas({
        orientation: normalizeOrientation(orientation),
        murid,
        qrSvg,
        model
    });
};

const downloadPdfBytes = (fileName, bytes) => {
    if (!bytes) throw new Error("PDF bytes are required for download.");

    const blob = new Blob([bytes], { type: "application/pdf" });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement("a");

    anchor.href = url;
    anchor.download = fileName || "kartu-pelajar.pdf";
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(url);
};

const getQrSvg = async (murid) => {
    if (!murid?.uuid) return "";
    if (typeof window.generateQRSVG !== "function") return "";

    const { uuid } = murid;

    if (qrSvgCache.has(uuid)) {
        return qrSvgCache.get(uuid);
    }

    if (qrSvgRequestCache.has(uuid)) {
        return qrSvgRequestCache.get(uuid);
    }

    const request = Promise.resolve(window.generateQRSVG(uuid))
        .then((svg) => {
            const normalized = typeof svg === "string" ? svg : "";

            if (normalized) {
                qrSvgCache.set(uuid, normalized);
                cacheLimit(qrSvgCache, 128);
            }

            return normalized;
        })
        .finally(() => {
            qrSvgRequestCache.delete(uuid);
        });

    qrSvgRequestCache.set(uuid, request);

    return request;
};

const renderStudentCanvas = async ({ murid, qrSvg, orientation = "horizontal", model = "standar" }) => renderTemplateCanvas({
    orientation,
    murid,
    qrSvg,
    model
});

const appendBackPageBestEffort = async (manager, { key, orientation, model = "standar" }) => {
    try {
        const backCanvas = await templateLoader.renderBackCanvas({ orientation, model });

        await manager.addBackPage({
            key,
            canvas: backCanvas,
            orientation
        });
    } catch (error) {
        console.warn("Skipping card back page; front pages are unaffected.", error);
    }
};

const createSingleCardPdfBytes = async ({ canvas, orientation = "horizontal", model = "standar" }) => {
    const normalizedOrientation = normalizeOrientation(orientation);
    const manager = new PdfManager();

    await manager.addCard({
        key: "single",
        canvas,
        orientation: normalizedOrientation
    });

    await appendBackPageBestEffort(manager, { key: "single", orientation: normalizedOrientation, model });

    return manager.export()[0]?.pdfBytes ?? null;
};

const downloadSingleCardPdf = async ({ murid, orientation = "horizontal", model = "standar", filename }) => {
    if (!murid) throw new Error("Student data is required to generate a PDF.");

    const normalizedOrientation = normalizeOrientation(orientation);
    const qrSvg = await getQrSvg(murid);
    const canvas = await renderStudentCanvas({
        murid,
        qrSvg,
        orientation: normalizedOrientation,
        model
    });
    const pdfBytes = await createSingleCardPdfBytes({
        canvas,
        orientation: normalizedOrientation,
        model
    });

    if (!pdfBytes) throw new Error("Unable to generate student card PDF.");

    downloadPdfBytes(filename || buildStudentCardFileName(murid), pdfBytes);
};

const downloadGeneratedStudentCardPdf = async ({ murid, orientation = "horizontal", model = "standar", filename }) => {
    if (!murid) throw new Error("Student data is required to generate a PDF.");

    const normalizedOrientation = normalizeOrientation(orientation);
    const qrSvg = await getQrSvg(murid);
    const canvas = await renderStudentCanvas({
        murid,
        qrSvg,
        orientation: normalizedOrientation,
        model
    });
    const manager = new PdfManager();

    await manager.addCard({
        key: murid?.rombel?.nama_lengkap ?? "Kelas",
        canvas,
        orientation: normalizedOrientation
    });

    await appendBackPageBestEffort(manager, {
        key: murid?.rombel?.nama_lengkap ?? "Kelas",
        orientation: normalizedOrientation,
        model
    });

    const pdfBytes = manager.export()[0]?.pdfBytes;

    if (!pdfBytes) throw new Error("Unable to export student card PDF.");

    downloadPdfBytes(filename || buildStudentCardFileName(murid), pdfBytes);
};

const groupKeyFor = (murid) => murid?.rombel?.nama_lengkap ?? "Kelas";

/**
 * Render and lay out a batch of student cards into one PDF per class,
 * without freezing the UI.
 *
 * The render itself is still synchronous main-thread work (domToCanvas has
 * to draw into a real canvas), so this can't make a single card render
 * faster by itself — what it does instead is:
 *  1. Prefetch the *next* student's photo while the *current* card is being
 *     rendered/encoded, overlapping network latency with CPU work.
 *  2. Yield back to the browser after a small group of cards so the page can
 *     repaint and handle input without paying a scheduler/frame delay for
 *     every individual card.
 *
 * @param {Object} options
 * @param {Object[]} options.students
 * @param {"horizontal"|"vertical"} [options.orientation]
 * @param {(progress: {index: number, total: number, murid: Object}) => void} [options.onProgress]
 * @returns {Promise<{className: string, pdfBytes: Uint8Array}[]>}
 */
const generateStudentCardsBatch = async ({
    students = [],
    orientation = "horizontal",
    model = "standar",
    onProgress
} = {}) => {
    if (!Array.isArray(students) || students.length === 0) {
        throw new Error("A non-empty list of students is required.");
    }

    const normalizedOrientation = normalizeOrientation(orientation);
    const manager = new PdfManager();

    students.slice(0, 3).forEach(prefetchPhoto);
    await templateLoader.preload([normalizedOrientation], { model });

    let cardsSinceYield = 0;
    let lastYieldAt = performance.now();

    for (let index = 0; index < students.length; index += 1) {
        const murid = students[index];

        prefetchPhoto(students[index + 1]);
        prefetchPhoto(students[index + 2]);

        // eslint-disable-next-line no-await-in-loop -- batch must stay sequential; rendering is single-threaded main-thread work
        const qrSvg = await getQrSvg(murid);
        const canvas = await renderTemplateCanvas({
            orientation: normalizedOrientation,
            murid,
            qrSvg,
            model
        });

        // eslint-disable-next-line no-await-in-loop
        await manager.addCard({
            key: groupKeyFor(murid),
            canvas,
            orientation: normalizedOrientation
        });

        onProgress?.({ index, total: students.length, murid });

        cardsSinceYield += 1;

        if (shouldYieldAfterCard({ cardsSinceYield, lastYieldAt })) {
            // eslint-disable-next-line no-await-in-loop
            await yieldToBrowser();
            lastYieldAt = performance.now();
            cardsSinceYield = 0;
        }
    }

    for (const key of manager.entries.keys()) {
        const orientationForKey = manager.entries.get(key)?.orientation ?? normalizedOrientation;

        // eslint-disable-next-line no-await-in-loop
        await appendBackPageBestEffort(manager, { key, orientation: orientationForKey, model });
    }

    return manager.exportAsync();
};
/**
 * Render a batch of student cards and immediately download one PDF per
 * class (grouped by rombel).
 *
 * @param {Object} options
 * @param {Object[]} options.students
 * @param {"horizontal"|"vertical"} [options.orientation]
 * @param {(progress: {index: number, total: number, murid: Object}) => void} [options.onProgress]
 * @returns {Promise<{className: string, pdfBytes: Uint8Array}[]>}
 */
const downloadStudentCardsBatch = async ({ students, orientation = "horizontal", model = "standar", onProgress } = {}) => {
    const results = await generateStudentCardsBatch({ students, orientation, model, onProgress });

    results.forEach(({ className, pdfBytes }) => {
        const sanitized = sanitizeFileName(className);

        downloadPdfBytes(sanitized ? `${sanitized}.pdf` : "kartu-pelajar.pdf", pdfBytes);
    });

    return results;
};

/**
 * Render horizontal student card.
 *
 * @param {Object} payload
 * @returns {Promise<HTMLCanvasElement>}
 */
window.renderStudentCardCanvas = (payload = {}) => renderTemplateCanvas({
    ...payload,
    orientation: "horizontal"
});

/**
 * Render vertical student card.
 *
 * @param {Object} payload
 * @returns {Promise<HTMLCanvasElement>}
 */
window.renderStudentCardVerticalCanvas = (payload = {}) => renderTemplateCanvas({
    ...payload,
    orientation: "vertical"
});

/**
 * Reset accumulated student card PDF documents.
 *
 * @returns {void}
 */
window.resetStudentCardPdf = () => {
    pdfManager.clear();
};

/**
 * Add a rendered student card canvas to its class PDF.
 *
 * @param {Object} options
 * @param {string} options.className
 * @param {HTMLCanvasElement} options.canvas
 * @param {"horizontal"|"vertical"} [options.orientation]
 * @returns {Promise<void>}
 */
window.addStudentCardToPdf = async ({ className, canvas, orientation = "horizontal" } = {}) => {
    if (!className || !canvas) return;

    await pdfManager.addCard({
        key: className,
        canvas,
        orientation
    });
};

/**
 * Render one back card and append it as the final page of a class PDF.
 *
 * @param {Object} options
 * @param {string} options.className
 * @param {"horizontal"|"vertical"} [options.orientation]
 * @returns {Promise<void>}
 */
window.appendStudentCardBackPage = async ({ className, orientation = "horizontal", model = "standar" } = {}) => {
    if (!className) return;

    try {
        const backCanvas = await templateLoader.renderBackCanvas({ orientation, model });

        await pdfManager.addBackPage({
            key: className,
            canvas: backCanvas,
            orientation
        });
    } catch (error) {
        console.warn("Skipping card back page; front pages are unaffected.", error);
    }
};
/**
 * Export accumulated student card PDFs.
 *
 * @returns {{className: string, pdfBytes: Uint8Array}[]}
 */
window.exportStudentCardPdfs = () => pdfManager.export();

/**
 * Export accumulated student card PDFs without blocking the UI for one long task.
 *
 * @returns {Promise<{className: string, pdfBytes: Uint8Array}[]>}
 */
window.exportStudentCardPdfsAsync = () => pdfManager.exportAsync();

/**
 * Preload card templates, logo, and fonts.
 *
 * @param {("horizontal"|"vertical")[]} [orientations]
 * @returns {Promise<void>}
 */
window.preloadStudentCardAssets = async (orientations = ["horizontal", "vertical"], { model = "standar" } = {}) => {
    await templateLoader.preload(orientations, { model });
};

/**
 * Clear caches, rendered canvases, and PDF state.
 *
 * @returns {void}
 */
window.clearStudentCardMemory = () => {
    templateCache.clear();
    templateRequestCache.clear();
    canvasCache.clear();
    qrSvgCache.clear();
    qrSvgRequestCache.clear();
    for (const entry of photoPrefetchCache.values()) {
        revokeObjectUrl(entry?.objectUrl);
    }
    photoPrefetchCache.clear();
    photoFailureCache.clear();
    renderedCanvasRegistry.clear();
    whiteCanvas = null;
    whiteCanvasContext = null;
    templateLoader.clear();
    pdfManager.clear();
};

window.renderTemplateCanvas = renderTemplateCanvas;
window.getStudentCardSize = getCardSize;
window.getStudentCardPdfGrid = getPdfGrid;

/**
 * Render + download a single student card as a PDF (horizontal or vertical).
 *
 * @param {Object} options
 * @param {Object} options.murid
 * @param {"horizontal"|"vertical"} [options.orientation]
 * @param {string} [options.filename]
 * @returns {Promise<void>}
 */
window.downloadStudentCardPdf = downloadSingleCardPdf;

/**
 * Render a batch of student cards into one PDF per class without freezing the UI.
 * See generateStudentCardsBatch above for details.
 *
 * @returns {Promise<{className: string, pdfBytes: Uint8Array}[]>}
 */
window.generateStudentCardsBatch = generateStudentCardsBatch;

/**
 * Render a batch of student cards and download one PDF per class immediately.
 *
 * @returns {Promise<{className: string, pdfBytes: Uint8Array}[]>}
 */
window.downloadStudentCardsBatch = downloadStudentCardsBatch;

const scheduleStudentCardPreload = () => {
    const preload = () => {
        window.preloadStudentCardAssets?.(["horizontal", "vertical"]);
    };

    if (typeof window.requestIdleCallback === "function") {
        window.requestIdleCallback(preload, { timeout: 1500 });
        return;
    }

    window.setTimeout(preload, 0);
};

scheduleStudentCardPreload();

window.addEventListener("generateStudentCardSinglePdf", async (event) => {
    const detail = event?.detail ?? {};

    if (!detail.murid) return;

    try {
        await downloadSingleCardPdf({
            murid: detail.murid,
            orientation: detail.orientation,
            model: detail.model ?? "standar",
            filename: detail.filename
        });
    } finally {
        window.clearStudentCardMemory?.();
    }
});

window.addEventListener("generateStudentCardPdf", async (event) => {
    const detail = event?.detail ?? {};

    if (!detail.murid) return;

    try {
        await downloadGeneratedStudentCardPdf({
            murid: detail.murid,
            orientation: detail.orientation,
            model: detail.model ?? "standar",
            filename: detail.filename
        });
    } finally {
        window.clearStudentCardMemory?.();
    }
});

window.addEventListener("generateStudentCardsBatchPdf", async (event) => {
    const detail = event?.detail ?? {};

    if (!Array.isArray(detail.students) || detail.students.length === 0) return;

    try {
        await downloadStudentCardsBatch({
            students: detail.students,
            orientation: detail.orientation,
            model: detail.model ?? "standar",
            onProgress: detail.onProgress
        });
    } finally {
        window.clearStudentCardMemory?.();
    }
});
