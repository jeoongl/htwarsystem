<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Places of Interest</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <style>
    body {
      font-family: Helvetica;
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
    .profile-icon {
      position: relative;
      cursor: pointer;
      color: white;
      font-size: 30px;
    }
    .dropdown-menu {
      display: none;
      position: absolute;
      top: 40px;
      right: 0;
      background-color: #333;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
      border-radius: 8px;
      overflow: hidden;
      width: 150px;
    }
    .dropdown-menu a {
      display: flex;
      align-items: center;
      padding: 10px;
      color: white;
      text-decoration: none;
      background-color: #333;
      border-bottom: 1px solid #444;
      font-size: 14px;
    }
    .dropdown-menu a:hover {
      background-color: #444;
    }
    .dropdown-menu i {
      margin-right: 10px;
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
    .all-tourist-sites {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(20%, 1fr));
      grid-gap: 20px;
      justify-content: center;
      padding: 10% 2%;
    }
    .container {
      text-align: left;
      position: relative;
    }
    .container img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-top-left-radius: 10px;
      border-top-right-radius: 10px;
    }
    .details {
      background-color: white;
      padding: 0 0;
      padding-top: 13px;
      border-bottom-left-radius: 10px;
      border-bottom-right-radius: 10px;
      margin-top: -4px;
    }
    .details h4,
    .price-range p {
      color: #333;
      margin: 0;
      padding: 0 5%;
    }
    .address {
      font-size: 15px;
      font-style: italic;
      display: flex;
      align-items: center;
      color: #333;
      margin: 0;
      padding: 0 5%;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      width: calc(90% - 18px);
    }
    .address i {
      margin-right: 5px;
    }
    .details h4 {
      font-size: 20px;
    }
    .price-range {
      border-top: 1px solid #ccc;
      padding-top: 20px;
      padding-bottom: 0;
      position: absolute;
      left: 0;
      right: 0;
      z-index: 1;
      background-color: white;
    }
    .price-value {
      font-size: 22px;
      font-weight: bold;
      color: green;
    }
    .buttons-wrapper {
      overflow: hidden;
      position: relative;
      height: auto;
      padding: 0 5%;
    }
    .buttons {
      margin-top: 10px;
      padding-top: 50px;
      display: flex;
      justify-content: space-between;
      margin-bottom: 15px;
    }
    .visit-btn, .view-btn {
      background-color: green;
      color: white;
      border: none;
      padding: 5px 10px;
      cursor: pointer;
      height: 40px;
      width: calc(50% - 5px);
      margin-right: 15px;
      border-radius: 20px;
      box-shadow: 0px 6px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .view-btn {
      background-color: white;
      color: #333;
      border: 2px solid green;
    }
    .visit-btn:hover,
    .view-btn:hover {
      transform: scale(1.05);
      box-shadow: 0px 6px 8px rgba(0, 0, 0, 0.1);
    }
    .view-btn {
      background-color: transparent;
      color: #333;
      border: 2px solid green;
    }
    .visit-btn:last-child,
    .view-btn:last-child {
      margin-right: 0;
    }
    @media only screen and (max-width: 767px) {
      .all-tourist-sites {
        grid-template-columns: repeat(1, 1fr);
        padding: 10% 4%;
      }
      .container img {
        height: 150px;
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
    }
  </style>
</head>
<body>

  <header>
    <img class="logo" src="img/logo.png" alt="Logo">
    <div class="profile-icon" onclick="toggleDropdown()">
      <i class="fas fa-user-circle"></i>
      <div class="dropdown-menu" id="dropdown-menu">
        <a href="login-index.php"><i class="fas fa-home"></i> Home</a>
        <a href="seller-profile-page.php"><i class="fas fa-user"></i> My Account</a>
        <a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
  </header>

  <h3 id="title">Welcome to Hinunangan<br><span id="sub-title">Discover places you want to visit during your travel.</span></h3>

  <div class="all-tourist-sites">
    <div class="container">
      <img src="img/twinislands.jpg" alt="Tourist Site 1">
      <div class="details">
        <h4>San Pedro Island</h4>
        <div class="address">
          <i class="fas fa-map-marker-alt"></i>
          <p>Canipaan, Hinunangan, Southern Leyte</p>
        </div>
        <div class="buttons-wrapper">
          <div class="price-range">
            <p class="currency-sign">PHP <span class="price-value">600</span><span class="dash"> - </span><span class="currency-sign">PHP </span><span class="price-value">1000</span></p>
          </div>
          <div class="buttons">
            <button class="visit-btn">Book
              <a href="boat_booking_form.html"></a>
            </button>
            <button class="view-btn">Info</button>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <img src="img/dny.jpg" alt="Tourist Site 2">
      <div class="details">
        <h4>San Pablo Island</h4>
        <div class="address">
          <i class="fas fa-map-marker-alt"></i>
          <p>Canipaan, Hinunangan, Southern Leyte</p>
        </div>
        <div class="buttons-wrapper">
          <div class="price-range">
            <p class="currency-sign">PHP <span class="price-value">600</span><span class="dash"> - </span><span class="currency-sign">PHP </span><span class="price-value">1000</span></p>
          </div>
          <div class="buttons">
            <button class="visit-btn">Book</button>
            <button class="view-btn">Info</button>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <img src="img/sanpablo.jpg" alt="Tourist Site 3">
      <div class="details">
        <h4>Panas River</h4>
        <div class="address">
          <i class="fas fa-map-marker-alt"></i>
          <p>Ingan, Hinunangan, Southern Leyte</p>
        </div>
        <div class="buttons-wrapper">
          <div class="price-range">
            <p class="currency-sign">PHP <span class="price-value">600</span><span class="dash"> - </span><span class="currency-sign">PHP </span><span class="price-value">1000</span></p>
          </div>
          <div class="buttons">
            <button class="visit-btn">Book</button>
            <button class="view-btn">Info</button>
          </div>
        </div>
      </div>
    </div>
    
    <div class="container">
      <img src="img/xentro.jpg" alt="Tourist Site 4">
      <div class="details">
        <h4>Kalinao Beach</h4>
        <div class="address">
          <i class="fas fa-map-marker-alt"></i>
          <p>Bangcas A, Hinunangan, Southern Leyte</p>
        </div>
        <div class="buttons-wrapper">
          <div class="price-range">
            <p class="currency-sign">PHP <span class="price-value">600</span><span class="dash"> - </span><span class="currency-sign">PHP </span><span class="price-value">1000</span></p>
          </div>
          <div class="buttons">
            <button class="visit-btn">Book</button>
            <button class="view-btn">Info</button>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <img src="img/twinislands.jpg" alt="Tourist Site 5">
      <div class="details">
        <h4>Heartshape</h4>
        <div class="address">
          <i class="fas fa-map-marker-alt"></i>
          <p>Calag-itan, Hinunangan, Southern Leyte</p>
        </div>
        <div class="buttons-wrapper">
          <div class="price-range">
            <p class="currency-sign">PHP <span class="price-value">600</span><span class="dash"> - </span><span class="currency-sign">PHP </span><span class="price-value">1000</span></p>
          </div>
          <div class="buttons">
            <button class="visit-btn">Book</button>
            <button class="view-btn">Info</button>
          </div>
        </div>
      </div>
    </div>

    <div class="container">
      <img src="img/sanpablo.jpg" alt="Tourist Site 6">
      <div class="details">
        <h4>Pungkay Mountain Park</h4>
        <div class="address">
          <i class="fas fa-map-marker-alt"></i>
          <p>Manalog, Hinunangan, Southern Leyte</p>
        </div>
        <div class="buttons-wrapper">
          <div class="price-range">
            <p class="currency-sign">PHP <span class="price-value">600</span><span class="dash"> - </span><span class="currency-sign">PHP </span><span class="price-value">1000</span></p>
          </div>
          <div class="buttons">
            <button class="visit-btn">Book</button>
            <button class="view-btn">Info</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    function toggleDropdown() {
      const dropdown = document.getElementById("dropdown-menu");
      dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }

    window.onclick = function(event) {
      if (!event.target.matches('.profile-icon, .profile-icon *')) {
        const dropdown = document.getElementById("dropdown-menu");
        if (dropdown.style.display === "block") {
          dropdown.style.display = "none";
        }
      }
    }
  </script>
</body>
</html>
