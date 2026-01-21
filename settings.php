        <!-- Panduan Konfigurasi -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">📖 Panduan Konfigurasi ESP8266</h5>
            </div>
            <div class="card-body">
                <h6>🔧 Konfigurasi di kode Arduino:</h6>
                <div class="code-display">
// Ganti dengan kode device Anda
const char* kodeDevice = "JAMUR123";

// Ganti dengan username akun Anda  
const char* username = "<?php echo $username; ?>";

// URL server (ganti dengan domain Anda)
const char* serverURL = "http://domain-anda.com/api.php";
                </div>
                
                <h6>📤 Format data yang dikirim:</h6>
                <div class="code-display">
String postData = "kode=" + String(kodeDevice) + 
                 "&suhu=" + String(temperature) + 
                 "&kelembaban=" + String(humidity) + 
                 "&username=" + String(username);
                </div>
                
                <div class="alert alert-info">
                    <strong>💡 Tips:</strong> Setelah mendapatkan kode device, masukkan kode tersebut ke dalam program ESP8266 Anda, lalu upload ke perangkat.
                </div>
            </div>
        </div>
    </div>