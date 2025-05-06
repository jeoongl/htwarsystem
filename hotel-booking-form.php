<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hotel Booking Form</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<style>
    body {
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 0;
  background-color: #f4f4f4;
}

.container {
  width: 80%;
  max-width: 600px;
  margin: 50px auto;
  background-color: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
}

h2 {
  text-align: center;
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  font-weight: bold;
}

input[type="text"],
input[type="tel"],
input[type="email"],
input[type="date"],
input[type="number"],
textarea,
select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
  box-sizing: border-box; /* Ensure padding doesn't affect width */
}
.required {
  color: red;
  margin-left: 5px;
}


textarea {
  height: 100px;
}

button {
  background-color: #4caf50;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  display: block;
  margin: auto;
}

button:hover {
  background-color: #45a049;
}

</style>

<div class="container">
  <form action="#" method="post" id="booking-form">
    <h2>Hotel Booking Form</h2>
    <div class="form-group">
      <label for="name">Name<span class="required">*</span></label>
      <input type="text" id="name" name="name" required>
    </div>
    <div class="form-group">
      <label for="contact_number">Contact Number<span class="required">*</span>:</label>
      <input type="tel" id="contact_number" name="contact_number" required>
    </div>
    <div class="form-group">
      <label for="email">Email<span class="required">*</span></label>
      <input type="email" id="email" name="email" required>
    </div>
    <div class="form-group">
      <label for="checkin">Check-in Date<span class="required"></span></label>
      <input type="date" id="checkin" name="checkin" required>
    </div>
    <div class="form-group">
      <label for="checkout">Check-out Date<span class="required"></span></label>
      <input type="date" id="checkout" name="checkout" required>
    </div>
    <div class="form-group">
      <label for="rooms">Number of Rooms<span class="required"></span></label>
      <input type="number" id="rooms" name="rooms" min="1" required>
    </div>
    <div class="form-group">
      <label for="adults">Number of Adults<span class="required"></span></label>
      <input type="number" id="adults" name="adults" min="1" required>
    </div>
    <div class="form-group">
      <label for="children">Number of Children</label>
      <input type="number" id="children" name="children" min="0">
    </div>
    <div class="form-group">
      <label for="payment">Payment Method<span class="required"></span></label>
      <select id="payment" name="payment" required>
        <option value="">Select Payment Method</option>
        <option value="cash">Cash on Hand</option>
        <option value="online">Online Payment</option>
      </select>
    </div>
    <div class="form-group">
      <label for="comments">Comments</label>
      <textarea id="comments" name="comments"></textarea>
    </div>
    <button type="submit">Submit</button>
  </form>
</div>
</body>
</html>
