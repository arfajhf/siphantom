<?php 
require 'koneksi.php';

$message = '';
$error = '';

if (isset($_POST["register"])) {
    // Ambil data dari form
    $data = [
        'username' => $_POST['username'],
        'password' => $_POST['password'],
        'password2' => $_POST['password2'],
        'nama_lengkap' => $_POST['nama_lengkap'],
        'role' => 'user' // Default role untuk registrasi
    ];
    
    if (daftar_user($data) > 0) {
        $message = "Registrasi berhasil! Anda dapat login sekarang.";
    }
    // Error sudah ditangani di function daftar_user()
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - SIMACMUR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        
        .registration-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
        }
        
        .registration-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
            margin: 0;
            font-size: 2.5rem;
        }
        
        .logo p {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 1.1rem;
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-control {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            padding: 15px 20px;
            height: auto;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .form-floating > label {
            padding: 15px 20px;
            font-weight: 500;
            color: #666;
        }
        
        .btn-register {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-weight: 600;
            width: 100%;
            color: white;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-register:disabled {
            opacity: 0.6;
            transform: none;
            box-shadow: none;
        }
        
        .alert {
            border-radius: 12px;
            margin-bottom: 20px;
            border: none;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .password-requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
            font-size: 14px;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .requirement:last-child {
            margin-bottom: 0;
        }
        
        .requirement-icon {
            width: 20px;
            margin-right: 8px;
        }
        
        .requirement.valid {
            color: #28a745;
        }
        
        .requirement.invalid {
            color: #dc3545;
        }
        
        .strength-meter {
            height: 4px;
            border-radius: 2px;
            background: #e9ecef;
            margin-top: 10px;
            overflow: hidden;
        }
        
        .strength-fill {
            height: 100%;
            transition: all 0.3s;
            border-radius: 2px;
        }
        
        .strength-weak { width: 25%; background: #dc3545; }
        .strength-fair { width: 50%; background: #ffc107; }
        .strength-good { width: 75%; background: #28a745; }
        .strength-strong { width: 100%; background: #20c997; }
        
        @media (max-width: 576px) {
            .registration-card {
                margin: 10px;
                padding: 30px 25px;
            }
            
            .logo h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="registration-card">
        <div class="logo">
            <h1>🍄 SIMACMUR</h1>
            <p>Daftar Akun Baru</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <strong>✅ Berhasil!</strong> <?php echo $message; ?>
                <div class="mt-2">
                    <a href="login.php" class="btn btn-light btn-sm">
                        🔐 Login Sekarang
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="registrationForm">
            <div class="form-floating">
                <input type="text" name="nama_lengkap" class="form-control" id="namaLengkap" 
                       placeholder="Nama Lengkap" required>
                <label for="namaLengkap">Nama Lengkap</label>
            </div>
            
            <div class="form-floating">
                <input type="text" name="username" class="form-control" id="username" 
                       placeholder="Username" required minlength="3" maxlength="50">
                <label for="username">Username</label>
                <div class="form-text">Username minimal 3 karakter, hanya huruf, angka, dan underscore</div>
            </div>
            
            <div class="form-floating">
                <input type="password" name="password" class="form-control" id="password" 
                       placeholder="Password" required minlength="6" onkeyup="checkPasswordStrength()">
                <label for="password">Password</label>
            </div>
            
            <div class="password-requirements" id="passwordRequirements" style="display: none;">
                <div class="requirement" id="req-length">
                    <span class="requirement-icon">❌</span>
                    <span>Minimal 6 karakter</span>
                </div>
                <div class="requirement" id="req-uppercase">
                    <span class="requirement-icon">❌</span>
                    <span>Minimal 1 huruf besar</span>
                </div>
                <div class="requirement" id="req-lowercase">
                    <span class="requirement-icon">❌</span>
                    <span>Minimal 1 huruf kecil</span>
                </div>
                <div class="requirement" id="req-number">
                    <span class="requirement-icon">❌</span>
                    <span>Minimal 1 angka</span>
                </div>
                <div class="strength-meter">
                    <div class="strength-fill" id="strengthFill"></div>
                </div>
                <small class="text-muted">Kekuatan password: <span id="strengthText">Lemah</span></small>
            </div>
            
            <div class="form-floating">
                <input type="password" name="password2" class="form-control" id="password2" 
                       placeholder="Konfirmasi Password" required onkeyup="checkPasswordMatch()">
                <label for="password2">Konfirmasi Password</label>
                <div class="form-text" id="passwordMatch"></div>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                    <label class="form-check-label" for="agreeTerms">
                        Saya setuju dengan <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Syarat & Ketentuan</a>
                    </label>
                </div>
            </div>
            
            <button type="submit" name="register" class="btn-register" id="submitBtn" disabled>
                🔐 Daftar Sekarang
            </button>
        </form>
        
        <div class="login-link">
            <span class="text-muted">Sudah punya akun?</span>
            <a href="login.php">Masuk di sini</a>
        </div>
    </div>

    <!-- Modal Terms & Conditions -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">📋 Syarat & Ketentuan SIMACMUR</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Penggunaan Sistem</h6>
                    <p>Dengan mendaftar di SIMACMUR, Anda setuju untuk menggunakan sistem ini sesuai dengan ketentuan yang berlaku.</p>
                    
                    <h6>2. Akun dan Keamanan</h6>
                    <ul>
                        <li>Anda bertanggung jawab untuk menjaga kerahasiaan akun dan password</li>
                        <li>Setiap aktivitas yang dilakukan dengan akun Anda adalah tanggung jawab Anda</li>
                        <li>Segera laporkan jika terjadi penggunaan akun yang tidak sah</li>
                    </ul>
                    
                    <h6>3. Device IoT</h6>
                    <ul>
                        <li>Setiap device yang didaftarkan harus mendapat persetujuan admin</li>
                        <li>Anda bertanggung jawab atas device yang terdaftar atas nama Anda</li>
                        <li>Admin berhak menghapus device yang melanggar ketentuan</li>
                    </ul>
                    
                    <h6>4. Data dan Privasi</h6>
                    <p>Data yang dikumpulkan dari device Anda akan digunakan untuk keperluan monitoring dan tidak akan dibagikan kepada pihak ketiga tanpa persetujuan Anda.</p>
                    
                    <h6>5. Pembatasan</h6>
                    <p>Dilarang menggunakan sistem ini untuk tujuan yang dapat merugikan atau melanggar hukum.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const requirements = document.getElementById('passwordRequirements');
            
            if (password.length > 0) {
                requirements.style.display = 'block';
            } else {
                requirements.style.display = 'none';
                return;
            }
            
            // Check requirements
            const reqLength = document.getElementById('req-length');
            const reqUppercase = document.getElementById('req-uppercase');
            const reqLowercase = document.getElementById('req-lowercase');
            const reqNumber = document.getElementById('req-number');
            
            // Length check
            if (password.length >= 6) {
                reqLength.classList.add('valid');
                reqLength.classList.remove('invalid');
                reqLength.querySelector('.requirement-icon').textContent = '✅';
            } else {
                reqLength.classList.add('invalid');
                reqLength.classList.remove('valid');
                reqLength.querySelector('.requirement-icon').textContent = '❌';
            }
            
            // Uppercase check
            if (/[A-Z]/.test(password)) {
                reqUppercase.classList.add('valid');
                reqUppercase.classList.remove('invalid');
                reqUppercase.querySelector('.requirement-icon').textContent = '✅';
            } else {
                reqUppercase.classList.add('invalid');
                reqUppercase.classList.remove('valid');
                reqUppercase.querySelector('.requirement-icon').textContent = '❌';
            }
            
            // Lowercase check
            if (/[a-z]/.test(password)) {
                reqLowercase.classList.add('valid');
                reqLowercase.classList.remove('invalid');
                reqLowercase.querySelector('.requirement-icon').textContent = '✅';
            } else {
                reqLowercase.classList.add('invalid');
                reqLowercase.classList.remove('valid');
                reqLowercase.querySelector('.requirement-icon').textContent = '❌';
            }
            
            // Number check
            if (/[0-9]/.test(password)) {
                reqNumber.classList.add('valid');
                reqNumber.classList.remove('invalid');
                reqNumber.querySelector('.requirement-icon').textContent = '✅';
            } else {
                reqNumber.classList.add('invalid');
                reqNumber.classList.remove('valid');
                reqNumber.querySelector('.requirement-icon').textContent = '❌';
            }
            
            // Calculate strength
            let strength = 0;
            if (password.length >= 6) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            switch (strength) {
                case 0:
                case 1:
                    strengthFill.className = 'strength-fill strength-weak';
                    strengthText.textContent = 'Lemah';
                    break;
                case 2:
                    strengthFill.className = 'strength-fill strength-fair';
                    strengthText.textContent = 'Cukup';
                    break;
                case 3:
                    strengthFill.className = 'strength-fill strength-good';
                    strengthText.textContent = 'Baik';
                    break;
                case 4:
                case 5:
                    strengthFill.className = 'strength-fill strength-strong';
                    strengthText.textContent = 'Kuat';
                    break;
            }
            
            checkFormValidity();
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const password2 = document.getElementById('password2').value;
            const matchText = document.getElementById('passwordMatch');
            
            if (password2.length === 0) {
                matchText.textContent = '';
                return;
            }
            
            if (password === password2) {
                matchText.textContent = '✅ Password cocok';
                matchText.style.color = '#28a745';
            } else {
                matchText.textContent = '❌ Password tidak cocok';
                matchText.style.color = '#dc3545';
            }
            
            checkFormValidity();
        }
        
        function checkFormValidity() {
            const password = document.getElementById('password').value;
            const password2 = document.getElementById('password2').value;
            const username = document.getElementById('username').value;
            const namaLengkap = document.getElementById('namaLengkap').value;
            const agreeTerms = document.getElementById('agreeTerms').checked;
            const submitBtn = document.getElementById('submitBtn');
            
            // Check if password meets requirements
            const passwordValid = password.length >= 6 && 
                                /[A-Z]/.test(password) && 
                                /[a-z]/.test(password) && 
                                /[0-9]/.test(password);
            
            const formValid = passwordValid && 
                            password === password2 && 
                            username.length >= 3 && 
                            namaLengkap.length > 0 && 
                            agreeTerms;
            
            submitBtn.disabled = !formValid;
        }
        
        // Event listeners
        document.getElementById('username').addEventListener('input', checkFormValidity);
        document.getElementById('namaLengkap').addEventListener('input', checkFormValidity);
        document.getElementById('agreeTerms').addEventListener('change', checkFormValidity);
        
        // Username validation
        document.getElementById('username').addEventListener('input', function() {
            const username = this.value;
            const validPattern = /^[a-zA-Z0-9_]+$/;
            
            if (username.length > 0 && !validPattern.test(username)) {
                this.setCustomValidity('Username hanya boleh berisi huruf, angka, dan underscore');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>