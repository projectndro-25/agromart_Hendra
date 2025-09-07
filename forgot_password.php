<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lupa Password - AgroMart</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #4CAF50, #2E7D32);
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .container {
      background: white;
      padding: 40px 30px;
      border-radius: 15px;
      width: 400px;
      max-width: 90%;
      box-shadow: 0px 8px 20px rgba(0,0,0,0.2);
      text-align: center;
      animation: fadeIn 0.6s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    h2 {
      margin-bottom: 15px;
      color: #2E7D32;
    }
    p {
      color: #555;
      margin-bottom: 25px;
      line-height: 1.5;
    }
    .btn {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      margin: 10px 0;
      padding: 12px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      text-decoration: none;
      color: white;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0px 5px 15px rgba(0,0,0,0.15);
    }
    .btn-gmail {
      background: #d93025;
    }
    .btn-wa {
      background: #25d366;
    }
    .btn span {
      margin-left: 8px;
    }
    .back-link {
      display: block;
      margin-top: 20px;
      font-size: 14px;
      color: #0066cc;
      text-decoration: none;
    }
    .back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>Lupa Password?</h2>
    <p>Saat ini reset password hanya bisa dilakukan melalui admin.<br>
       Silakan hubungi admin melalui salah satu cara berikut:</p>

    <!-- Via Gmail -->
    <a class="btn btn-gmail" 
       href="https://mail.google.com/mail/?view=cm&fs=1&to=kongsaimun12@gmail.com&su=Reset%20Password%20AgroMart&body=Halo%20Admin,%20saya%20lupa%20password%20akun%20AgroMart%20saya.%20Mohon%20bantu%20reset."
       target="_blank" rel="noopener noreferrer">
       📧 <span>Hubungi via Gmail</span>
    </a>

    <!-- Via WhatsApp -->
    <a class="btn btn-wa" 
       href="https://wa.me/6287832555857?text=Halo%20Admin,%20saya%20lupa%20password%20akun%20AgroMart.%20Mohon%20bantu%20reset." 
       target="_blank" rel="noopener noreferrer">
       💬 <span>Hubungi via WhatsApp</span>
    </a>

    <!-- Back to login -->
    <a href="login.php" class="back-link">⬅️ Kembali ke Login</a>
  </div>
</body>
</html>
