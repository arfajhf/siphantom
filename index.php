<?php
session_start();

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION["login"]) && $_SESSION["login"] === true) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMACMUR - Sistem Monitoring Jamur</title>
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
        }
        
        .landing-container {
            text-align: center;
            color: white;
            max-width: 600px;
            padding: 20px;
        }
        
        .logo {
            font-size: 5rem;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        .title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .subtitle {
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        
        .btn-login {
            background: white;
            color: #667eea;
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            color: #667eea;
        }
        

        
        .version {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(255,255,255,0.1);
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            backdrop-filter: blur(10px);
        }
        
        @media (max-width: 768px) {
            .logo {
                font-size: 3rem;
            }
            .title {
                font-size: 2rem;
            }

        }
    </style>
</head>
<body>
    <div class="landing-container">
        <div class="logo">🍄</div>
        <h1 class="title">SIMACMUR</h1>
        <p class="subtitle">Sistem Monitoring Jamur IoT</p>
        
        <a href="login.php" class="btn-login">
            🔐 Masuk ke Dashboard
        </a>
        

    </div>
    
    <div class="version">
        v1.0 - Simple & Secure
    </div>
</body>
</html>