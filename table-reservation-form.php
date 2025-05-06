<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Table Reservation Form</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <style>
    body {
      font-family: Arial, Helvetica, sans-serif;
      margin: 0;
      padding: 0;
      background-color: black;
      color: black;
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

    .login-btn {
      background-color: transparent;
      border: none;
      font-size: 16px;
      cursor: pointer;
      color: white;
      margin-left: auto;
    }

    /* Main container for the calendar, time selector, and number of people */
    .calendar-container {
      display: flex;
      flex-direction: column;
      justify-content: start;
      align-items: center;
      background-color: #444;
      color: black;
      border-radius: 10px;
      padding: 20px;
      width: 1000px;
      margin: 40px auto;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    /* Store Info */
    .store-info {
      width: 100%;
      text-align: left;
      margin-bottom: 20px;
    }

    .store-info h1 {
      font-size: 26px;
      color: white;
      margin: 0;
    }

    .store-info p {
      font-size: 18px;
      color: white;
      margin-top: 5px;
    }

    /* Steps Container */
    .steps-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      margin-bottom: 40px;
      position: relative;
    }

    /* Horizontal line behind the steps */
    .steps-container::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      height: 1px;
      background-color: #555;
      z-index: 0; /* Line should be behind the steps */
    }

    /* Each step element */
    .step {
      display: inline-flex; /* Adjust width based on content */
      align-items: center;
      background-color: #444; /* Green background for each step group */
      border-radius: 10px; /* Rounded corners */
      padding: 5px 10px; /* Padding around content */
      position: relative;
      z-index: 1; /* Ensure steps are above the line */
    }

    .step:first-child {
      justify-content: flex-start;
    }

    .step:nth-child(2) {
      justify-content: center;
    }

    .step:last-child {
      justify-content: flex-end;
    }

    .step-number {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background-color: gray; /* Circle color */
      color: white;
      display: flex;
      justify-content: center;
      align-items: center;
      margin-right: 10px;
      z-index: 1; /* Ensure step number is above the line */
    }

    .step-text {
      color: white;
    }

    .step.active .step-number {
      background-color: black;
    }

    /* Calendar and Time Selectors */
    .calendar-row {
      display: flex;
      justify-content: start;
      align-items: stretch;
      width: 100%;
    }

    .calendar-group {
      display: flex;
      width: 50%;
    }

    .calendar-date {
      background-color: black;
      color: white;
      padding: 20px;
      text-align: center;
      width: 150px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      border-top-left-radius: 10px;
      border-bottom-left-radius: 10px;
    }

    .calendar-date h2 {
      margin: 0;
      font-size: 24px;
    }

    .calendar-date p {
      margin: 5px 0;
    }

    .flatpickr-calendar {
      width: calc(100% - 180px);
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
      border-top-right-radius: 10px;
      border-bottom-right-radius: 10px;
      background-color: white;
      color: black;
      display: flex;
    }

    .flatpickr-day.selected {
      background-color: black;
      color: white;
      border-radius: 50%;
    }

    .time-and-buttons {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      margin-left: 20px;
      width: 50%;
    }

    .time-selector-container {
      background-color: #333;
      color: white;
      padding: 20px;
      border-radius: 10px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: flex-start;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      margin-bottom: 10px;
    }

    .time-selector-container label {
      margin-bottom: 10px;
      font-size: 16px;
      font-weight: normal;
    }

    .time-selector {
      padding: 10px;
      font-size: 16px;
      border-radius: 5px;
      background-color: #222;
      border: 1px solid #555;
      color: white;
      width: 100%;
    }

    .number-input-container {
      background-color: #333;
      color: white;
      padding: 20px;
      border-radius: 10px;
      display: flex;
      flex-direction: column;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      margin-bottom: 10px;
    }

    .number-label {
      margin-bottom: 10px;
      font-size: 16px;
      font-weight: normal;
    }

    .number-input-wrapper {
      display: flex;
      align-items: center;
    }

    .number-input {
      width: 60px;
      padding: 10px;
      font-size: 16px;
      border: 1px solid #555;
      border-radius: 5px;
      background-color: #222;
      color: white;
      text-align: center;
      margin-right: 10px;
    }

    .number-buttons {
      display: flex;
    }

    .number-buttons button {
      width: 40px;
      height: 40px;
      background-color: #555;
      color: white;
      border: none;
      font-size: 18px;
      cursor: pointer;
      border-radius: 5px;
    }

    .number-buttons .minus-btn {
      background-color: red;
    }

    .number-buttons .plus-btn {
      background-color: green;
      margin-left: 10px;
    }

    .button-group {
      display: flex;
      justify-content: space-between;
    }

    .button-group button {
      flex: 1;
      padding: 10px;
      font-size: 16px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .button-group .back-btn {
      background-color: #ccc;
      color: black;
      margin-right: 10px;
    }

    .button-group .next-btn {
      background-color: black;
      color: white;
    }
  </style>
</head>
<body>
  <header>
    <img class="logo" src="img/logo.png" alt="Logo">
    <button class="login-btn" onclick="openLogin()">Login</button>
  </header>

  <div class="calendar-container">
    <!-- Store Info -->
    <div class="store-info">
      <h1>Book table at Store Name</h1>
      <p>Please fill out the required information to complete your reservation.</p>
    </div>

    <!-- Steps Display -->
    <div class="steps-container">
      <div class="step active">
        <div class="step-number">1</div>
        <div class="step-text">Reservation</div>
      </div>
      <div class="step">
        <div class="step-number">2</div>
        <div class="step-text">Details</div>
      </div>
      <div class="step">
        <div class="step-number">3</div>
        <div class="step-text">Summary</div>
      </div>
    </div>

    <div class="calendar-row">
      <div class="calendar-group">
        <div class="calendar-date">
          <h2>2024</h2>
          <p>Thu, Aug 15</p>
        </div>
        <div id="calendar"></div>
      </div>
      <div class="time-and-buttons">
        <div class="time-selector-container">
          <label for="time-selector">Select Time:</label>
          <select id="time-selector" class="time-selector">
            <option>09:00 AM</option>
            <option>10:00 AM</option>
            <option>11:00 AM</option>
          </select>
        </div>
        <div class="number-input-container">
          <label class="number-label" for="number-of-people">Number of People:</label>
          <div class="number-input-wrapper">
            <input type="text" id="number-of-people" class="number-input" value="1" readonly>
            <div class="number-buttons">
              <button class="minus-btn" onclick="decrement()">-</button>
              <button class="plus-btn" onclick="increment()">+</button>
            </div>
          </div>
        </div>
        <div class="button-group">
          <button class="back-btn">BACK</button>
          <button class="next-btn">NEXT</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script>
    flatpickr("#calendar", {
      inline: true,
    });

    function increment() {
      const numberInput = document.getElementById('number-of-people');
      numberInput.value = parseInt(numberInput.value) + 1;
    }

    function decrement() {
      const numberInput = document.getElementById('number-of-people');
      if (numberInput.value > 1) {
        numberInput.value = parseInt(numberInput.value) - 1;
      }
    }

    
function openLogin() {
        window.location.href = 'log-in.php';
    }
  </script>
</body>
</html>
