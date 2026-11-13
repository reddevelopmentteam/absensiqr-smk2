// import "./bootstrap";
import "./toastFlash";
import { watchCameraSchedule } from "./camera-schedule.js";
window.watchCameraSchedule = watchCameraSchedule;
window.startFaceRecognition = (...args) =>
    import("./face-recognition.js").then(({ startFaceRecognition }) => startFaceRecognition(...args));
window.faceRecognitionScanner = (students, scheduleUrl) => ({
    students,
    scheduleUrl,
    scheduleWatcher: null,
    scheduleOpen: null,
    scanner: null,
    startGeneration: 0,
    restartWhenReady: false,
    cameraReady: false,
    scanning: false,
    busy: false,
    status: '',
    cameraError: '',
    watchSchedule() {
        this.scheduleWatcher = window.watchCameraSchedule(this.scheduleUrl, (open) => {
            this.scheduleOpen = open;
            if (open) {
                this.cameraError = '';
                if (this.busy) this.restartWhenReady = true;
                else this.startCamera();
            } else {
                this.restartWhenReady = false;
                this.cameraError = '';
                this.stopCamera();
                this.status = 'Jadwal absensi belum dibuka atau sudah ditutup. Kamera dimatikan.';
            }
        });
    },
    async startCamera() {
        if (!this.scheduleOpen || this.busy || this.scanning) return;
        const generation = ++this.startGeneration;
        this.busy = true;
        this.cameraError = '';
        try {
            const scanner = await window.startFaceRecognition(
                this.$refs.cameraVideo,
                this.$refs.overlay,
                this.students,
                {
                    shouldContinue: () => this.scheduleOpen && generation === this.startGeneration,
                    onStatus: (status) => { this.status = status; },
                    onRecognition: (uuid, similarity) => this.$wire.call('recognizeStudent', uuid, similarity),
                    onLost: (uuid) => this.$wire.call('clearRecognizedStudent', uuid),
                    onError: (error) => console.error('Face recognition:', error),
                },
            );
            if (generation !== this.startGeneration || !this.scheduleOpen) {
                scanner.stop();
                return;
            }
            this.scanner = scanner;
            this.cameraReady = true;
            this.scanning = true;
        } catch (error) {
            if (generation !== this.startGeneration || !this.scheduleOpen) return;
            this.cameraError = error?.message || 'Pengenalan wajah gagal dimulai.';
            this.status = '';
            this.scanner?.stop();
            this.scanner = null;
        } finally {
            this.busy = false;
            if (this.restartWhenReady && this.scheduleOpen) {
                this.restartWhenReady = false;
                this.startCamera();
            }
        }
    },
    stopCamera() {
        this.startGeneration++;
        this.scanner?.stop();
        this.scanner = null;
        this.cameraReady = false;
        this.scanning = false;
        this.status = '';
    },
    stopScheduleWatch() {
        this.scheduleWatcher?.();
        this.scheduleWatcher = null;
        this.scheduleOpen = false;
        this.restartWhenReady = false;
    },
    enableAudio() { this.scanner?.enableAudio(); },
    playAttendanceAudio(status) { this.scanner?.playScanAudio(status); },
    destroy() { this.stopScheduleWatch(); this.stopCamera(); },
});
// import "./chart";
// import "./scanner";
// // import "./progress";
// import "./generateQR";
// import "./generateCard";
