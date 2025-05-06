<?php
// Start session and include database connection
session_start();
include 'includes/dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: log-in.php');
    exit();
}

// Retrieve the logged-in user's ID from the session
$user_id = $_SESSION['user_id'];

// Prepare and execute the query to fetch user information
$query = "SELECT fullname, username, email, profile_photo, role_id FROM users_tbl WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Set default profile photo if none exists
$profile_photo = !empty($user['profile_photo']) ? $user['profile_photo'] : 'img/default-profile.jpg';

// Close the user query statement
$stmt->close();

// Query for pending businesses (status = 1)
$pending_query = "
    SELECT b.id, b.name, b.business_owner, b.address, c.category_name, b.business_permit_no, b.business_permit_photo
    FROM businesses_tbl b
    JOIN categories_tbl c ON b.category = c.id
    WHERE b.business_status = 1
";
$pending_result = $conn->query($pending_query);

// Query for registered businesses (status = 2)
$registered_query = "
    SELECT b.id, b.name, b.business_owner, b.address, c.category_name, b.business_permit_no, b.business_permit_photo
    FROM businesses_tbl b
    JOIN categories_tbl c ON b.category = c.id
    WHERE b.business_status = 2
";
$registered_result = $conn->query($registered_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Business Owners</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    body {
      font-family: Helvetica, Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: black;
      color: white;
    }

    header {
      background-color: black;
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
            font-size: 20px;
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
            width: 180px;
            z-index: 1000;
        }
            /* Adjusted CSS for the name below the profile photo */
        .dropdown-menu div {
            text-align: center;
            padding: 10px 0; /* Reduced padding */
            background-color: #444;
        }
        .dropdown-menu div p {
            font-size: 20px;
            margin: 5px 0; /* Reduced margin for name */
        }
        
        /* Add margin at the top of the profile photo */
        .dropdown-menu div img {
            margin-top: 5px; /* Increased top space for the photo */
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

    .businesses-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 90%;
      margin: 20px auto;
      background-color: #333;
      padding: 20px;
      border-radius: 8px;
      position: relative;
    }

    .business-title {
      position: absolute;
      top: 20px;
      left: 20px;
      font-size: 30px;
      font-weight: bold;
    }

    .pending-table-container {
      width: 100%; /* Adjusted to make container wider */
      max-width: 1300px; /* Increased max-width for more space */
      margin: 60px auto;
      background-color: #222;
      padding: 20px;
      border-radius: 8px;
      box-sizing: border-box;
    }
    .table-container {
      width: 100%; /* Adjusted to make container wider */
      max-width: 1300px; /* Increased max-width for more space */
      margin: -20px auto;
      background-color: #444;
      padding: 20px;
      border-radius: 8px;
      box-sizing: border-box;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      margin-bottom: 20px; /* Added margin to separate tables */
    }

    th, td {
      padding: 10px;
      text-align: left;
      border: 1px solid #555;
      word-wrap: break-word;
    }

    th {
      background-color: #444;
    }
    

    .action-buttons i {
      cursor: pointer;
      margin-right: 10px;
    }

    .action-buttons i:hover {
      color: #ff6347; /* Tomato color on hover */
    }

    .action-buttons .fa-check {
      color: #4CAF50; /* Green for verify */
    }

    .action-buttons .fa-trash {
      color: #f44336; /* Red for delete */
    }

    .action-buttons .fa-edit {
      color: white; /* Blue for edit */
    }

    .permit-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .permit-list li {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
    }

    .permit-list img {
      width: 20px; /* Small icon size */
      height: 20px;
      object-fit: cover;
      margin-right: 10px;
      cursor: pointer;
    }

    .permit-list span {
      color: white;
    }
    /* Modal Styles */
    .modal {
      display: none; 
      position: fixed; 
      z-index: 999; 
      left: 0;
      top: 0;
      width: 100%; 
      height: 100%; 
      background-color: rgba(0, 0, 0, 0.8); 
    }

    .modal-content {
      position: relative;
      margin: 0 auto;
      padding: 20px;
      width: 80%; 
      max-width: 600px; 
      background-color: #fff;
      color: black;
    }

    .close-btn {
      position: absolute;
      top: 10px;
      right: 20px;
      color: black;
      font-size: 24px;
      cursor: pointer;
    }
    .toggle-btn {
      background-color: transparent; /* Make background transparent */
      border: none;
      cursor: pointer;
      display: block;
      margin: 0 auto; /* Center the button */
    }

    .toggle-btn i {
        color: white; /* Make the icon white */
        font-size: 24px; /* Adjust the size as needed */
        display: flex;
        justify-content: center;
        align-items: center;
    }
    /* Modal Styles for Verification */
    #verifyModal {
        display: none;
        position: fixed;
        z-index: 999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
    }

    #confirmDeleteModal .modal-content,  
    #confirmRegisterModal .modal-content,
    #removeRegisteredBusinessModal .modal-content{
        margin: 10% auto;
        padding: 20px;
        width: 80%;
        max-width: 400px;
        background-color: #fff;
        color: black;
        text-align: center;
        border-radius: 10px;
    }

    #businessInfoModal .modal-content{
        margin: 5% auto;
        padding: 10px 20px 20px 20px;
        width: 80%;
        max-width: 400px;
        background-color: #111;
        color: white;
        border-radius: 10px;
        max-width: 500px;
        position: relative;
    }

    #businessInfoModal .close-btn{
        color: white;
    }

    #businessInfoModal p{
        color: #ccc;
    }

        /* Style for the info icon */
    #businessInfoModal .modal-content h3 {
        display: flex;
        align-items: center;
        font-size: 1.5em;
        color: white;
    }

    #businessInfoModal .modal-content .info-icon {
        color: white; /* Green color to make the icon stand out */
        margin-right: 8px;
        font-size: 1.2em;
    }

    .close-btn {
        position: absolute;
        top: 0;
        right: 10px;
        color: black;
        font-size: 24px;
        cursor: pointer;
    }

    .action-button {
        padding: 10px 20px;
        margin: 5px;
        background-color: #4CAF50;
        color: white;
        border: none;
        cursor: pointer;
        border-radius: 5px;
    }

    .action-button:hover {
        background-color: #45a049;
    }
    /* Specific style for delete confirmation button */
    .delete-confirm-button {
        background-color: #f44336; /* Red for delete */
    }

    .delete-confirm-button:hover {
        background-color: #d32f2f; /* Darker red on hover */
    }
    .permit-list li {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        white-space: nowrap;
        
    }

    .permit-list img {
        width: 20px;
        height: 20px;
        object-fit: cover;
        margin-right: 10px;
        cursor: pointer;
    }

    .permit-list a {
    color: white;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px; /* Adjust width as necessary */
    white-space: normal; /* Allows text to wrap within its container */
    overflow-wrap: break-word; /* Breaks long words if needed */
}
.table-wrapper {
    display: flex;
    flex-direction: column;
    gap: 20px; /* Space between tables */
    width: 100%;
    max-width: 1250px;
    margin: auto;
    padding: 0;
    background-color: #222;
    border-radius: 10px;
}
.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0;
    padding: 0 20px;
}

.businesses-title {
    font-size: 30px;
    font-weight: bold;
    color: white;
}
.generate-report-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        padding: 10px 15px;
        background-color: #d9534f;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .generate-report-btn:hover {
        background-color: #c9302c;
    }


  </style>
</head>
<body>
<header>
    <img class="logo" src="img/logo.png" alt="Logo">
    
    <div class="profile-icon" onclick="toggleDropdown()">
      <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Photo" style="width: 40px; height: 40px; border-radius: 50%;">
      <div class="dropdown-menu" id="dropdown-menu">
        <div>
          <img src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Photo" style="width: 60px; height: 60px; border-radius: 50%;">
          <p><?php echo htmlspecialchars($user['fullname']); ?></p>
        </div>
        <a href="admin-dashboard.php"><i class="fa fa-bar-chart"></i>Dashboard</a>
        <a href="businesses.php"><i class="fas fa-users"></i>Businesses</a>
        <a href="admin-settings.php"><i class="fas fa-cogs"></i>Settings</a>
        <a href="index.php"><i class="fas fa-sign-out-alt"></i>Log out</a>
      </div>
    </div>
</header>

<div class="businesses-container">
    <div class="business-title">Businesses</div>
    <a href="generate-businesses-report.php" target="_blank" class="generate-report-btn">
      <i class="fas fa-file-pdf"></i> Generate Report
    </a>
    <div class="pending-table-container">
        <h3>Pending Business Registrations</h3>
        <table id="pendingTable">
            <thead>
                <tr>
                    <th>Business Name</th>
                    <th>Business Owner</th>
                    <th>Business Address</th>
                    <th>Category</th>
                    <th>Business Permit No.</th>
                    <th>Business Permit Photo</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($pending_result->num_rows > 0) {
                $row_count = 0;
                while ($row = $pending_result->fetch_assoc()) {
                    $row_count++;
                    $hidden_class = ($row_count > 3) ? 'hidden-row' : '';
                    echo '<tr class="' . $hidden_class . '">';
                    echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['business_owner']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['address']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['category_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['business_permit_no']) . '</td>';
                    echo '<td>
                            <ul class="permit-list">
                                <li>
                                    <a href="javascript:void(0);" onclick="openPermit(\'' . htmlspecialchars($row['business_permit_photo']) . '\')">
                                        <img src="' . htmlspecialchars($row['business_permit_photo']) . '" alt="Permit Photo" />
                                        <span>' . basename($row['business_permit_photo']) . '</span>
                                    </a>
                                </li>
                            </ul>
                          </td>';
                      echo '<td class="action-buttons">
                          <i class="fas fa-check" onclick="openConfirmRegisterModal(' . $row['id'] . ', \'' . htmlspecialchars($row['name']) . '\')"></i>
                          <i class="fas fa-trash" onclick="openDeleteModal(' . $row['id'] . ', \'' . htmlspecialchars($row['name']) . '\')"></i>
                          <i class="fas fa-info-circle" onclick="showMoreInfo(' . $row['id'] . ')"></i>
                    </td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="7">No pending businesses found.</td></tr>';
            }
            ?>
            </tbody>
        </table>
        <button class="toggle-btn" onclick="toggleRows('pendingTable')"><i class="fas fa-angle-down"></i></button>
    </div>

    <!-- Registered Business Table -->
    <div class="table-container">
        <h3>Registered Businesses</h3>
        <table id="registeredTable">
            <thead>
                <tr>
                    <th>Business Name</th>
                    <th>Business Owner</th>
                    <th>Business Address</th>
                    <th>Category</th>
                    <th>Business Permit No.</th>
                    <th>Business Permit Photo</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($registered_result->num_rows > 0) {
                $row_count = 0;
                while ($row = $registered_result->fetch_assoc()) {
                    $row_count++;
                    $hidden_class = ($row_count > 3) ? 'hidden-row' : '';
                    echo '<tr class="' . $hidden_class . '">';
                    echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['business_owner']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['address']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['category_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['business_permit_no']) . '</td>';
                    echo '<td>
                            <ul class="permit-list">
                                <li>
                                    <a href="javascript:void(0);" onclick="openPermit(\'' . htmlspecialchars($row['business_permit_photo']) . '\')">
                                        <img src="' . htmlspecialchars($row['business_permit_photo']) . '" alt="Permit Photo" />
                                        <span>' . basename($row['business_permit_photo']) . '</span>
                                    </a>
                                </li>
                            </ul>
                          </td>';
                    echo '<td class="action-buttons">
                            <i class="fas fa-close" onclick="openRemoveRegisteredBusinessModal(' . $row['id'] . ', \'' . htmlspecialchars($row['name']) . '\')"></i>
                            <i class="fas fa-trash" onclick="openDeleteModal(' . $row['id'] . ', \'' . htmlspecialchars($row['name']) . '\')"></i>
                            <i class="fas fa-info-circle" onclick="showMoreInfo(' . $row['id'] . ')"></i>
                        </td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="7">No registered businesses found.</td></tr>';
            }
            ?>
            </tbody>
        </table>
        <button class="toggle-btn" onclick="toggleRows('registeredTable')"><i class="fas fa-angle-down"></i></button>
    </div>
</div>
  </div>

<!-- Modal Structure -->
<div id="permitModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <img id="permitImage" src="" alt="Permit Image" style="width: 100%;">
    </div>
</div>

<!-- Modal Structure for Confirmation -->
<div id="confirmRegisterModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeVerifyModal()">&times;</span>
        <h3>Are you sure you want to register <span id="confirmRegisterBusinessName"></span>?</h3>
        <p>Please make sure you have carefully checked the business details.</p>
        <button class="action-button" id="confirmRegisterBtn">Confirm</button>
        <button class="action-button" onclick="closeVerifyModal()">Cancel</button>
    </div>
</div>

<!-- Delete Confirmation Modal Structure -->
<div id="confirmDeleteModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeDeleteModal()">&times;</span>
        <h3>Are you sure you want to delete <span id="deleteBusinessName"></span>?</h3>
        <p>This action cannot be undone.</p>
        <button class="action-button delete-confirm-button" id="confirmDeleteBtn">Delete</button>
        <button class="action-button" onclick="closeDeleteModal()">Cancel</button>
    </div>
</div>

<!-- Modal Structure for Remove Confirmation -->
<div id="removeRegisteredBusinessModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeRemoveModal()">&times;</span>
        <h3>Are you sure you want to remove <span id="removeBusinessName"></span> from registered businesses?</h3>
        <p>This will be added back to pending businesses.</p>
        <button class="action-button" id="confirmRemoveBtn">Remove</button>
        <button class="action-button" onclick="closeRemoveModal()">Cancel</button>
    </div>
</div>

<div id="businessInfoModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeBusinessInfoModal()">&times;</span>
        <h3><i class="fa fa-info-circle info-icon"></i> Business Details</h3>
        <p><strong>Name:</strong> <span id="businessName"></span></p>
        <p><strong>Owner:</strong> <span id="businessOwner"></span></p>
        <p><strong>Address:</strong> <span id="businessAddress"></span></p>
        <p><strong>Category:</strong> <span id="businessCategory"></span></p>
        <p><strong>Permit Number:</strong> <span id="businessPermitNo"></span></p>
        <p><strong>Date Created:</strong> <span id="businessDateCreated"></span></p>
        <p><strong>Status:</strong> <span id="businessStatus"></span></p>
        <!-- Conditionally displayed Date Registered -->
        <div id="dateRegisteredContainer" style="display: none;">
            <p><strong>Date Registered:</strong> <span id="businessDateRegistered"></span></p>
        </div>
    </div>
</div>


<script>
    let businessIdToRegister = null;

    // Function to open the modal for registering a business
    function openConfirmRegisterModal(businessId, businessName) {
        businessIdToRegister = businessId;
        document.getElementById('confirmRegisterBusinessName').innerText = businessName;
        document.getElementById('confirmRegisterModal').style.display = 'block';
    }

    // Function to close the register confirmation modal
    function closeVerifyModal() {
        document.getElementById('confirmRegisterModal').style.display = 'none';
        businessIdToRegister = null; // Reset the business ID
    }

    // Handle confirm button click inside modal
    document.getElementById('confirmRegisterBtn').addEventListener('click', function () {
        if (businessIdToRegister) {
            // AJAX request to register the business (update business_status)
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'register-business.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                if (xhr.status === 200) {
                    // On success, close the modal and refresh the page or update UI
                    closeVerifyModal();
                    location.reload(); // Reload to reflect changes (or use JS to update the table dynamically)
                } else {
                    alert('Failed to register the business. Please try again.');
                }
            };
            xhr.send('id=' + businessIdToRegister); // Send business ID to the server
        }
    });
    
    function toggleDropdown() {
        const dropdown = document.getElementById("dropdown-menu");
        // Toggle dropdown display
        if (dropdown.style.display === "block") {
            dropdown.style.display = "none";
        } else {
            dropdown.style.display = "block";
        }
        }

        // Close dropdown when clicking outside
        window.onclick = function(event) {
        const dropdown = document.getElementById("dropdown-menu");
        // If the click is outside the profile-icon and dropdown menu
        if (!event.target.closest('.profile-icon') && !event.target.closest('.dropdown-menu')) {
            if (dropdown.style.display === "block") {
            dropdown.style.display = "none";
            }
        }
        }

    function openPermit(src) {
      // Open the image in a new window or tab
      window.open(src, '_blank');
    }

    // Function to open the permit image in the modal
    function openPermit(src) {
        const modal = document.getElementById("permitModal");
        const modalImg = document.getElementById("permitImage");
        modalImg.src = src;
        modal.style.display = "block";
    }

    // Function to close the modal
    function closeModal() {
        const modal = document.getElementById("permitModal");
        modal.style.display = "none";
    }
    function toggleRows(tableId) {
        const table = document.getElementById(tableId);
        const hiddenRows = table.querySelectorAll('.hidden-row');
        const btnIcon = table.nextElementSibling.querySelector('i');
        
        // Toggle hidden rows
        hiddenRows.forEach(row => {
            if (row.style.display === 'table-row') {
                row.style.display = 'none';
                btnIcon.classList.remove('fa-angle-up');
                btnIcon.classList.add('fa-angle-down');
            } else {
                row.style.display = 'table-row';
                btnIcon.classList.remove('fa-angle-down');
                btnIcon.classList.add('fa-angle-up');
            }
        });
    }

    // Function to show or hide the toggle button depending on the number of rows
    function checkRowsVisibility(tableId) {
        const table = document.getElementById(tableId);
        const rows = table.querySelectorAll('tbody tr');
        const hiddenRows = table.querySelectorAll('.hidden-row');
        const toggleButton = table.nextElementSibling;

        if (rows.length <= 3) {
            // Hide the button if 3 or fewer rows are present
            toggleButton.style.display = 'none';
        } else {
            // Show the button if more than 3 rows are present
            toggleButton.style.display = 'block';
            hiddenRows.forEach(row => row.style.display = 'none');
        }
    }

    let deleteBusinessId = null;

    // Function to open the delete confirmation modal
    function openDeleteModal(businessId, businessName) {
        deleteBusinessId = businessId; // Store the business ID
        document.getElementById('deleteBusinessName').innerText = businessName;
        document.getElementById('confirmDeleteModal').style.display = 'block';
    }

    // Function to close the delete modal
    function closeDeleteModal() {
        document.getElementById('confirmDeleteModal').style.display = 'none';
    }

    // Add click event listener for delete confirmation button
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (deleteBusinessId) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "delete-business.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                console.log("Response from server:", xhr.responseText); // Log response for debugging
                if (xhr.status === 200 && xhr.responseText.trim() === 'success') {
                    closeDeleteModal();
                    location.reload();
                } else {
                    alert("Failed to delete business. Response: " + xhr.responseText); // Show actual response for debugging
                }
            }
        };

        console.log("Sending delete request for business ID:", deleteBusinessId);
        xhr.send("id=" + deleteBusinessId);
    }
});


let removeBusinessId = null;

function openRemoveRegisteredBusinessModal(businessId, businessName) {
    removeBusinessId = businessId; // Store the ID of the business to remove
    document.getElementById('removeBusinessName').innerText = businessName;
    document.getElementById('removeRegisteredBusinessModal').style.display = 'block';
}

function closeRemoveModal() {
    document.getElementById('removeRegisteredBusinessModal').style.display = 'none';
}

document.getElementById('confirmRemoveBtn').onclick = function() {
    // Make AJAX request to remove the business
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "remove-registered-business.php", true); // Update this to your PHP handler
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                closeRemoveModal(); // Close the modal
                location.reload(); // Reloads the page to see the changes
            } else {
                alert("Error removing business.");
            }
        }
    };
    xhr.send("id=" + removeBusinessId); // Send the business ID to the server
};

    // Run the row check for both tables on page load
    window.onload = function() {
        checkRowsVisibility('pendingTable');
        checkRowsVisibility('registeredTable');
    }

    function showMoreInfo(businessId) {
    // Make an AJAX request to fetch business details
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'fetch-business-info.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            // Parse the JSON response
            const business = JSON.parse(xhr.responseText);

            // Populate the modal content
            document.getElementById('businessName').innerText = business.name;
            document.getElementById('businessOwner').innerText = business.business_owner;
            document.getElementById('businessAddress').innerText = business.address;
            document.getElementById('businessCategory').innerText = business.category_name;
            document.getElementById('businessPermitNo').innerText = business.business_permit_no;
            document.getElementById('businessDateCreated').innerText = business.created_at;

            // Set the status text
            const statusText = business.business_status == 1 ? 'Pending' : 'Registered';
            document.getElementById('businessStatus').innerText = statusText;

            // Show or hide Date Registered based on status
            const dateRegisteredContainer = document.getElementById('dateRegisteredContainer');
            if (business.business_status == 1) { // Pending
                dateRegisteredContainer.style.display = 'none';
            } else { // Registered
                dateRegisteredContainer.style.display = 'block';
                document.getElementById('businessDateRegistered').innerText = business.updated_at;
            }

            // Display the modal
            document.getElementById('businessInfoModal').style.display = 'block';
        } else {
            alert('Failed to fetch business details.');
        }
    };
    xhr.send('id=' + businessId);
}

// Close the modal
function closeBusinessInfoModal() {
    document.getElementById('businessInfoModal').style.display = 'none';
}


  </script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>