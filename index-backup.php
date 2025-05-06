<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hinunangan Tourism Website and Reservation System</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    body {
      font-family: Arial, Helvetica, sans-serif;
      margin: 0;
      padding: 0;
      background-color: black;
      color: white;
    }
    header {
      background-color: green; 
      padding: 10px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo {
      width: 350px;
    }
    .login-btn, .contact-btn {
      background-color: transparent;
      border: none;
      font-size: 16px;
      cursor: pointer;
      color: white;
    }
    .login-btn {
      order: 1;
      margin-left: auto;
    }
    .contact-btn {
      order: 2;
    }
    #title {
      font-family: Helvetica;
      font-size: 50px;
      text-align: left;
      padding-left: 8%;
      margin: 50px 0;
    }
    #sub-title {
      font-family: Helvetica;
      font-size: 30px;
      font-weight: lighter;
    }
    .container {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      padding: 10% 5%;
    }
    .slideshow-container {
      position: relative;
      width: calc(50% - 20px);
      height: 340px;
      margin: 10px 10px;
      overflow: hidden;
      border-radius: 10px;
      box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.3);
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .slideshow-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      margin: 0;
      padding: 0;
      transition: transform 0.3s ease;
    }
    .slideshow-container:hover img {
      transform: scale(1.1);
    }
    @media only screen and (max-width: 767px) {
      .slideshow-container:active img {
        transform: scale(1.1);
      }
    }
    .label-container {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      padding: 10px;
      box-sizing: border-box;
      background-image: linear-gradient(to top, rgba(0, 0, 0, 0.5) 0%, rgba(0, 0, 0, 0) 100%);
      color: white;
    }
    .label {
      font-size: 40px;
      font-family: Helvetica;
      font-weight: bold;
      position: relative;
      text-align: left;
      cursor: pointer;
      margin: 3px 15px;
    }
    @media only screen and (max-width: 767px) {
      .container {
        flex-direction: column;
        align-items: center;
        padding: 0 0;
      }
      .slideshow-container {
        width: 92%;
        max-width: none;
        margin: 10px 0;
        height: 200px;
        cursor: pointer;
      }
      .slideshow-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        margin: 0;
        padding: 0;
        transition: transform 0.5s ease;
      }
      .label {
        width: 80%;
        font-size: 25px;
        margin: 0 5px;
      }
      .logo {
        width: 250px;
      }
      .login-btn, .contact-btn {
        background-color: transparent;
        border: none;
        font-size: 15px;
        cursor: pointer;
        color: white;
      }
      #title {
        font-size: 29px;
        padding-left: 7%;
      }
      #sub-title {
        font-size: 16px;
      }
    }
    .slideshow-container a {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1;
      text-decoration: none;
    }
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.5);
      justify-content: center;
      align-items: center;
    }
    .modal-content {
      background-color: #333;
      padding: 20px;
      border-radius: 8px;
      width: 300px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
      text-align: center;
    }
    .modal-content h2 {
      margin-top: 0;
      color: white;
    }
    .modal-content .input-container {
      position: relative;
      width: 100%;
      margin: 10px 0;
    }
    .modal-content input {
      width: 92%;
      padding: 10px;
      border: 1px solid #555;
      border-radius: 4px;
      background-color: #222;
      color: white;
    }
    .modal-content button {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      background-color: green;
      color: white;
      font-size: 16px;
    }
    .signup-text {
      color: white;
      margin-top: 10px;
      text-align: center;
    }
    .signup-link {
      color: green;
      cursor: pointer;
      text-decoration: underline;
    }
    .password-toggle {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: white;
    }
  </style>
</head>
<body>
  <header>
    <img class="logo" src="img/logo.png" alt="Logo">
    <button class="login-btn" onclick="openLoginModal()">Login</button>
  </header>
  <h3 id="title">Welcome to Hinunangan<br><span id="sub-title">Discover places you want to visit during your travel.</span></h3> 
  <div class="container">
    <div class="slideshow-container" id="slideshow-container-1">
      <a href="ecotourism.html"></a>
      <img src="img/twinislands.jpg" alt="Ecotourism">
      <div class="label-container">
        <div class="label">Twin Islands</div>
      </div>
    </div>
    <div class="slideshow-container" id="slideshow-container-2">
      <a href="ecotourism.html"></a>
      <img src="img/sanpablo.jpg" alt="Ecotourism">
      <div class="label-container">
        <div class="label">Ecotourism</div>
      </div>
    </div>
    <div class="slideshow-container" id="slideshow-container-3">
      <a href="accommodation.html"></a>
      <img src="img/dny.jpg" alt="accommodation">
      <div class="label-container">
        <div class="label">Accommodation</div>
      </div>
    </div>
    <div class="slideshow-container" id="slideshow-container-4">
      <a href="dining.html"></a>
      <img src="img/xentro.jpg" alt="Dining">
      <div class="label-container">
        <div class="label">Dining</div>
      </div>
    </div>
  </div>

  <!-- Login Modal -->
  <div id="loginModal" class="modal">
    <div class="modal-content">
      <h2>Login</h2>
      <div class="input-container">
        <input type="text" id="username" placeholder="Username">
      </div>
      <div class="input-container">
        <input type="password" id="password" placeholder="Password">
        <span class="password-toggle" onclick="togglePassword()"><i class="fas fa-eye"></i></span>
      </div>
      <button onclick="login()">Login</button>
      <div class="signup-text">
        No account? <span class="signup-link" onclick="openSignupModal()">Sign Up</span>
      </div>
    </div>
  </div>

  <!-- Sign Up Modal -->
  <div id="signupModal" class="modal">
    <div class="modal-content">
      <h2>Sign Up</h2>
      <div class="input-container">
        <input type="text" id="firstName" placeholder="First Name">
      </div>
      <div class="input-container">
        <input type="text" id="middleName" placeholder="Middle Name">
      </div>
      <div class="input-container">
        <input type="text" id="lastName" placeholder="Last Name">
      </div>
      <div class="input-container">
        <input type="text" id="signupUsername" placeholder="Username">
      </div>
      <div class="input-container">
        <input type="email" id="email" placeholder="Email">
      </div>
      <div class="input-container">
        <input type="tel" id="phoneNumber" placeholder="Phone Number">
      </div>
      <button onclick="signup()">Sign Up</button>
      <div class="signup-text">
        Already have an account? <span class="signup-link" onclick="openLoginModal()">Login</span>
      </div>
    </div>
  </div>

  <script>
    function openLoginModal() {
      document.getElementById('loginModal').style.display = 'flex';
      document.getElementById('signupModal').style.display = 'none';
    }
    
    function openSignupModal() {
      document.getElementById('signupModal').style.display = 'flex';
      document.getElementById('loginModal').style.display = 'none';
    }

    window.onclick = function(event) {
      const loginModal = document.getElementById('loginModal');
      const signupModal = document.getElementById('signupModal');
      if (event.target === loginModal || event.target === signupModal) {
        loginModal.style.display = 'none';
        signupModal.style.display = 'none';
      }
    }

    function login() {
      const username = document.getElementById('username').value;
      const password = document.getElementById('password').value;
      alert(`Username: ${username}\nPassword: ${password}`);
      closeModals();
    }

    function signup() {
      const firstName = document.getElementById('firstName').value;
      const middleName = document.getElementById('middleName').value;
      const lastName = document.getElementById('lastName').value;
      const username = document.getElementById('signupUsername').value;
      const email = document.getElementById('email').value;
      const phoneNumber = document.getElementById('phoneNumber').value;
      alert(`First Name: ${firstName}\nMiddle Name: ${middleName}\nLast Name: ${lastName}\nUsername: ${username}\nEmail: ${email}\nPhone Number: ${phoneNumber}`);
      closeModals();
    }

    function closeModals() {
      document.getElementById('loginModal').style.display = 'none';
      document.getElementById('signupModal').style.display = 'none';
    }

    function togglePassword() {
      const passwordField = document.getElementById('password');
      const icon = document.querySelector('.password-toggle i');
      const type = passwordField.type === 'password' ? 'text' : 'password';
      passwordField.type = type;
      icon.classList.toggle('fa-eye');
      icon.classList.toggle('fa-eye-slash');
    }
  </script>
</body>
</html>
