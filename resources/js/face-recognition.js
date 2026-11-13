import { createScanAudio } from '../../Modules/ScanQR/resources/assets/js/scan-audio.js';

let faceApiPromise;
let descriptorDatabasePromise;
const DESCRIPTOR_DATABASE = 'face-recognition-descriptors-v1';
const DESCRIPTOR_STORE = 'students';
const DESCRIPTOR_MODEL_VERSION = 'face-api-0.22.2-recognition-v1';

function openDescriptorDatabase() {
    if (!('indexedDB' in window)) return Promise.resolve(null);
    if (descriptorDatabasePromise) return descriptorDatabasePromise;

    descriptorDatabasePromise = new Promise((resolve) => {
        try {
            const request = window.indexedDB.open(DESCRIPTOR_DATABASE, 1);
            request.onupgradeneeded = () => {
                if (!request.result.objectStoreNames.contains(DESCRIPTOR_STORE)) {
                    request.result.createObjectStore(DESCRIPTOR_STORE, { keyPath: 'uuid' });
                }
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => resolve(null);
            request.onblocked = () => resolve(null);
        } catch {
            resolve(null);
        }
    });

    return descriptorDatabasePromise;
}

function databaseRequest(request) {
    return new Promise((resolve, reject) => {
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function writeCachedDescriptor(database, entry) {
    if (!database) return;
    try {
        const transaction = database.transaction(DESCRIPTOR_STORE, 'readwrite');
        transaction.objectStore(DESCRIPTOR_STORE).put(entry);
        await new Promise((resolve) => {
            transaction.oncomplete = resolve;
            transaction.onerror = resolve;
            transaction.onabort = resolve;
        });
    } catch {
        // Recognition continues even when browser storage is unavailable.
    }
}

async function readAllCachedDescriptors(database) {
    if (!database) return new Map();
    try {
        const transaction = database.transaction(DESCRIPTOR_STORE, 'readonly');
        const entries = await databaseRequest(transaction.objectStore(DESCRIPTOR_STORE).getAll());
        return new Map(entries.map((entry) => [entry.uuid, entry]));
    } catch {
        return new Map();
    }
}

async function removeStaleDescriptors(database, students) {
    if (!database) return;
    try {
        const activeIds = new Set(students.map((student) => student.uuid));
        const transaction = database.transaction(DESCRIPTOR_STORE, 'readwrite');
        const store = transaction.objectStore(DESCRIPTOR_STORE);
        const request = store.getAllKeys();
        request.onsuccess = () => request.result.forEach((uuid) => {
            if (!activeIds.has(uuid)) store.delete(uuid);
        });
    } catch {
        // Cache cleanup is best effort and does not block the camera.
    }
}

function loadFaceApi() {
    if (window.faceapi) return Promise.resolve(window.faceapi);
    if (faceApiPromise) return faceApiPromise;

    faceApiPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = '/vendor/face-api.min.js';
        script.async = true;
        script.onload = () => window.faceapi ? resolve(window.faceapi) : reject(new Error('Pustaka face-api tidak termuat.'));
        script.onerror = () => reject(new Error('Pustaka face-api gagal dimuat.'));
        document.head.appendChild(script);
    });

    return faceApiPromise;
}

export async function startFaceRecognition(video, canvas, students, callbacks = {}) {
    const scanAudio = createScanAudio();
    void scanAudio.preloadScanAudios();
    let faceapi;
    try {
        faceapi = await loadFaceApi();
    } catch (error) {
        scanAudio.dispose();
        throw error;
    }
    callbacks.onStatus?.('Memuat model pengenalan wajah…');

    try {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('/models'),
            faceapi.nets.faceLandmark68TinyNet.loadFromUri('/models'),
            faceapi.nets.faceRecognitionNet.loadFromUri('/models'),
        ]);
    } catch (error) {
        scanAudio.dispose();
        throw error;
    }

    const labeledDescriptors = [];
    const namesByUuid = new Map();
    const detectorOptions = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 });
    const descriptorDatabase = await openDescriptorDatabase();
    const cachedDescriptors = await readAllCachedDescriptors(descriptorDatabase);
    const activeImageVersions = new Map(students.map((student) => [
        student.uuid,
        `${DESCRIPTOR_MODEL_VERSION}:${student.imageVersion}`,
    ]));
    const photosToPrepare = students.filter((student) =>
        cachedDescriptors.get(student.uuid)?.imageVersion !== activeImageVersions.get(student.uuid),
    );
    let preparedNewPhotos = 0;
    callbacks.onStatus?.(photosToPrepare.length === 0
        ? `Menggunakan cache foto ${students.length} murid…`
        : `Memproses ${photosToPrepare.length} foto baru; foto lainnya menggunakan cache…`);

    for (let index = 0; index < students.length; index++) {
        const student = students[index];
        const imageVersion = activeImageVersions.get(student.uuid);
        const cached = cachedDescriptors.get(student.uuid);
        if (cached?.imageVersion === imageVersion) {
            if (Array.isArray(cached.descriptor) && cached.descriptor.length > 0) {
                labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(
                    student.uuid,
                    [new Float32Array(cached.descriptor)],
                ));
                namesByUuid.set(student.uuid, student.nama);
            }
            continue;
        }

        preparedNewPhotos++;
        callbacks.onStatus?.(`Menyiapkan foto baru ${preparedNewPhotos} dari ${photosToPrepare.length}…`);

        try {
            const image = await faceapi.fetchImage(student.imageUrl);
            const result = await faceapi
                .detectSingleFace(image, detectorOptions)
                .withFaceLandmarks(true)
                .withFaceDescriptor();

            if (!result) {
                await writeCachedDescriptor(descriptorDatabase, {
                    uuid: student.uuid,
                    imageVersion,
                    descriptor: null,
                });
                continue;
            }

            labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(student.uuid, [result.descriptor]));
            namesByUuid.set(student.uuid, student.nama);
            await writeCachedDescriptor(descriptorDatabase, {
                uuid: student.uuid,
                imageVersion,
                descriptor: Array.from(result.descriptor),
            });
        } catch (error) {
            console.warn(`Foto referensi murid ${student.uuid} tidak dapat dianalisis.`, error);
        }
    }

    await removeStaleDescriptors(descriptorDatabase, students);

    if (labeledDescriptors.length === 0) {
        scanAudio.dispose();
        throw new Error('Tidak ada foto murid yang menampilkan wajah dengan jelas. Periksa image_path pada data murid.');
    }

    const matcher = new faceapi.FaceMatcher(labeledDescriptors, 0.55);
    callbacks.onStatus?.(`Kamera siap. ${labeledDescriptors.length} foto murid dimuat.`);

    if (callbacks.shouldContinue && !callbacks.shouldContinue()) {
        scanAudio.dispose();
        throw new Error('Jadwal absensi telah ditutup. Kamera tidak diaktifkan.');
    }

    let stream = null;
    let running = true;
    let frameBusy = false;
    let frameTimer = null;
    let currentMatch = null;
    let stableMatch = null;
    let stableFrames = 0;
    let missingSince = null;

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
            audio: false,
        });
        video.srcObject = stream;
        await video.play();
        if (callbacks.shouldContinue && !callbacks.shouldContinue()) {
            stream.getTracks().forEach((track) => track.stop());
            stream = null;
            scanAudio.dispose();
            throw new Error('Jadwal absensi telah ditutup. Kamera tidak diaktifkan.');
        }
    } catch (error) {
        stream?.getTracks().forEach((track) => track.stop());
        scanAudio.dispose();
        throw new Error(error?.name === 'NotAllowedError'
            ? 'Izin kamera ditolak. Izinkan akses kamera pada browser lalu coba lagi.'
            : 'Kamera gagal dibuka. Pastikan kamera tidak sedang digunakan aplikasi lain.');
    }

    const drawMatches = async () => {
        if (!running || frameBusy) return;
        if (!video.videoWidth || !video.videoHeight) {
            frameTimer = window.setTimeout(drawMatches, 150);
            return;
        }

        frameBusy = true;
        const displaySize = { width: video.videoWidth, height: video.videoHeight };
        canvas.width = displaySize.width;
        canvas.height = displaySize.height;

        try {
            const detections = await faceapi
                .detectAllFaces(video, detectorOptions)
                .withFaceLandmarks(true)
                .withFaceDescriptors();
            const resized = faceapi.resizeResults(detections, displaySize);
            const context = canvas.getContext('2d');
            context.clearRect(0, 0, canvas.width, canvas.height);

            const results = resized.map((detection) => matcher.findBestMatch(detection.descriptor));
            results.forEach((result, index) => {
                const studentName = namesByUuid.get(result.label);
                const label = studentName
                    ? `${studentName} · ${Math.max(0, (1 - result.distance) * 100).toFixed(0)}%`
                    : 'Tidak dikenali';
                new faceapi.draw.DrawBox(resized[index].detection.box, { label }).draw(canvas);
            });

            const recognized = results
                .map((result) => ({ result, name: namesByUuid.get(result.label) }))
                .filter(({ result, name }) => name && result.label !== 'unknown');

            if (detections.length === 1 && recognized.length === 1) {
                const { result } = recognized[0];
                missingSince = null;
                if (stableMatch === result.label) {
                    stableFrames++;
                } else {
                    stableMatch = result.label;
                    stableFrames = 1;
                }

                if (stableFrames >= 3 && currentMatch !== result.label) {
                    currentMatch = result.label;
                    const similarity = Math.max(0, (1 - result.distance) * 100);
                    if (similarity > 45) callbacks.onRecognition?.(result.label, similarity);
                }
            } else {
                stableMatch = null;
                stableFrames = 0;
                if (currentMatch && missingSince === null) missingSince = Date.now();
                if (currentMatch && Date.now() - missingSince > 1500) {
                    const lostMatch = currentMatch;
                    currentMatch = null;
                    missingSince = null;
                    callbacks.onLost?.(lostMatch);
                }
            }
        } catch (error) {
            callbacks.onError?.(error);
        } finally {
            frameBusy = false;
            if (running) frameTimer = window.setTimeout(drawMatches, 180);
        }
    };

    drawMatches();

    return {
        enableAudio() {
            void scanAudio.preloadScanAudios();
        },
        playScanAudio(status) {
            void scanAudio.playScanAudio(status);
        },
        stop() {
            running = false;
            window.clearTimeout(frameTimer);
            stream?.getTracks().forEach((track) => track.stop());
            video.pause();
            video.srcObject = null;
            canvas.getContext('2d')?.clearRect(0, 0, canvas.width, canvas.height);
            labeledDescriptors.length = 0;
            namesByUuid.clear();
            scanAudio.dispose();
        },
    };
}
