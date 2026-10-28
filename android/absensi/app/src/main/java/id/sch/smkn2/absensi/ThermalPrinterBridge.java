package id.sch.smkn2.absensi;

import android.Manifest;
import android.app.Activity;
import android.bluetooth.BluetoothAdapter;
import android.bluetooth.BluetoothDevice;
import android.bluetooth.BluetoothManager;
import android.bluetooth.BluetoothSocket;
import android.content.pm.PackageManager;
import android.os.Build;
import android.util.Base64;
import android.webkit.JavascriptInterface;
import android.webkit.WebView;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.OutputStream;
import java.util.Set;
import java.util.UUID;

/**
 * Jembatan cetak langsung untuk WebView. WebView (system) tidak mendukung
 * WebUSB maupun WebBluetooth, jadi byte ESC/POS yang sudah dirakit JavaScript
 * (resources/js/thermal) dikirim lewat Bluetooth Classic (SPP) secara native.
 *
 * Alamat printer dipilih otomatis: perangkat bonded yang namanya berawalan
 * "IWARE", atau perangkat bonded pertama bila tidak ada yang cocok.
 * Printer harus sudah di-pairing di Pengaturan Bluetooth HP; pencarian
 * (discovery) sengaja tidak dilakukan agar tidak perlu izin lokasi/SCAN.
 */
final class ThermalPrinterBridge {
    /** SPP (Serial Port Profile) — dipakai printer thermal Bluetooth Classic. */
    private static final UUID SPP_UUID =
            UUID.fromString("00001101-0000-1000-8000-00805F9B34FB");
    private static final String NAME_PREFIX = "IWARE";
    private static final int CHUNK = 512;

    static final int PERMISSION_REQUEST = 11;

    private final Activity activity;
    private final WebView webView;

    ThermalPrinterBridge(Activity activity, WebView webView) {
        this.activity = activity;
        this.webView = webView;
    }

    private BluetoothAdapter adapter() {
        BluetoothManager manager = activity.getSystemService(BluetoothManager.class);
        return manager == null ? null : manager.getAdapter();
    }

    private boolean hasBtPermission() {
        if (Build.VERSION.SDK_INT < 31) return true;
        return activity.checkSelfPermission(Manifest.permission.BLUETOOTH_CONNECT)
                == PackageManager.PERMISSION_GRANTED;
    }

    @JavascriptInterface
    public boolean isSupported() {
        return adapter() != null;
    }

    @JavascriptInterface
    public boolean hasPermission() {
        return hasBtPermission();
    }

    /** Minta izin BLUETOOTH_CONNECT (API 31+). Hasil kembali via event JS. */
    @JavascriptInterface
    public void requestPermission() {
        if (Build.VERSION.SDK_INT < 31 || hasBtPermission()) {
            notifyPermissionGranted();
            return;
        }
        activity.runOnUiThread(() -> activity.requestPermissions(
                new String[]{Manifest.permission.BLUETOOTH_CONNECT}, PERMISSION_REQUEST));
    }

    /** Dipanggil MainActivity dari onRequestPermissionsResult. */
    void onPermissionResult(boolean granted) {
        if (granted) notifyPermissionGranted();
    }

    private void notifyPermissionGranted() {
        activity.runOnUiThread(() -> webView.evaluateJavascript(
                "window.dispatchEvent(new Event('thermal-permission-granted'))", null));
    }

    /** Daftar perangkat bonded sebagai JSON: [{"name","address"}]. */
    @JavascriptInterface
    public String pairedPrinters() {
        JSONArray list = new JSONArray();
        BluetoothAdapter adapter = adapter();
        if (adapter == null || !hasPermission()) return list.toString();
        try {
            Set<BluetoothDevice> bonded = adapter.getBondedDevices();
            if (bonded == null) return list.toString();
            for (BluetoothDevice device : bonded) {
                try {
                    JSONObject item = new JSONObject();
                    item.put("name", String.valueOf(device.getName()));
                    item.put("address", device.getAddress());
                    list.put(item);
                } catch (Exception ignored) {
                    // Satu entri rusak tidak boleh menggagalkan daftar.
                }
            }
        } catch (SecurityException ignored) {
            // Izin dicabut di tengah jalan: kembalikan daftar kosong.
        }
        return list.toString();
    }

    /**
     * Kirim byte ESC/POS (base64) ke printer. Berjalan di thread bridge
     * WebView (bukan UI thread) sehingga connect yang memblokir aman.
     * Kembali JSON: {"ok":true} atau {"ok":false,"error":"...","needPermission":bool}.
     */
    @JavascriptInterface
    public String print(String base64, String address) {
        if (adapter() == null) return fail("HP tidak mendukung Bluetooth.", false);
        BluetoothAdapter bt = adapter();
        if (!bt.isEnabled()) return fail("Nyalakan Bluetooth HP dulu.", false);
        if (!hasPermission()) return fail("Izin Bluetooth belum diberikan.", true);
        byte[] data;
        try {
            data = Base64.decode(String.valueOf(base64), Base64.DEFAULT);
        } catch (IllegalArgumentException error) {
            return fail("Data cetak rusak.", false);
        }
        if (data.length == 0) return fail("Data cetak kosong.", false);
        BluetoothDevice device = pickDevice(bt, String.valueOf(address));
        if (device == null) {
            return fail("Belum ada printer ter-pairing. Pairing printer IWARE di Pengaturan Bluetooth HP dulu.", false);
        }
        BluetoothSocket socket = null;
        try {
            socket = device.createRfcommSocketToServiceRecord(SPP_UUID);
            socket.connect();
            OutputStream out = socket.getOutputStream();
            for (int i = 0; i < data.length; i += CHUNK) {
                int end = Math.min(data.length, i + CHUNK);
                out.write(data, i, end - i);
            }
            out.flush();
            return ok();
        } catch (SecurityException error) {
            return fail("Izin Bluetooth belum diberikan.", true);
        } catch (Exception error) {
            return fail("Tidak tersambung ke printer. Pastikan printer IWARE menyala dan masih ter-pairing.", false);
        } finally {
            if (socket != null) {
                try {
                    socket.close();
                } catch (Exception ignored) {
                    // Socket sudah dipakai: tutup best-effort.
                }
            }
        }
    }

    private BluetoothDevice pickDevice(BluetoothAdapter bt, String address) {
        try {
            Set<BluetoothDevice> bonded = bt.getBondedDevices();
            if (bonded == null || bonded.isEmpty()) return null;
            if (address != null && !address.isEmpty() && !"null".equals(address)) {
                for (BluetoothDevice device : bonded) {
                    if (address.equals(device.getAddress())) return device;
                }
            }
            for (BluetoothDevice device : bonded) {
                String name = device.getName();
                if (name != null && name.toUpperCase().startsWith(NAME_PREFIX)) return device;
            }
            return bonded.iterator().next();
        } catch (SecurityException error) {
            return null;
        }
    }

    private static String ok() {
        try {
            JSONObject result = new JSONObject();
            result.put("ok", true);
            return result.toString();
        } catch (Exception error) {
            return "{\"ok\":true}";
        }
    }

    private static String fail(String message, boolean needPermission) {
        try {
            JSONObject result = new JSONObject();
            result.put("ok", false);
            result.put("error", message);
            result.put("needPermission", needPermission);
            return result.toString();
        } catch (Exception error) {
            return "{\"ok\":false,\"error\":\"Gagal mencetak.\"}";
        }
    }
}
