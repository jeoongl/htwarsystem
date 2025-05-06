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

// Prepare and execute the query to fetch the stores owned by the logged-in user
$store_query = "SELECT business_profile_photo, name, description, address, id, business_status FROM businesses_tbl WHERE created_by = ?";
$store_stmt = $conn->prepare($store_query);
$store_stmt->bind_param("i", $user_id);
$store_stmt->execute();
$store_result = $store_stmt->get_result();

// Fetch all stores into an array
$stores = $store_result->fetch_all(MYSQLI_ASSOC);

// Close the statement and database connection
$store_stmt->close();
$conn->close();

// Separate stores into pending and non-pending arrays, then sort them alphabetically by name
$pending_stores = [];
$non_pending_stores = [];

foreach ($stores as $store) {
    if ($store['business_status'] == 1) {
        $pending_stores[] = $store;
    } else {
        $non_pending_stores[] = $store;
    }
}

// Sort both arrays alphabetically by store name
usort($pending_stores, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

usort($non_pending_stores, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: black;
            margin: 0;
            padding: 0;
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
            width: 150px;
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

        .container {
            background-color: #333;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 900px;
            max-width: 90%;
            margin: 50px auto;
            position: relative;
        }

        .edit-button-top {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            border: none;
            background-color: green;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }

        .edit-button-top:hover {
            background-color: white;
            color: black;
        }

        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #d8d8d8;
            margin-right: 20px;
        }

        .profile-info {
            display: flex;
            flex-direction: column;
        }

        .profile-info h2 {
            margin: 0;
        }

        .info-section {
            margin-top: 10px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0;
            /* Remove background color, padding, and border radius */
        }

        .info-item p {
            margin: 0;
        }

        .edit-button-password {
            padding: 10px 20px;
            border: none;
            background-color: green;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }

        .edit-button-password:hover {
            background-color: white;
            color: black;
        }

        .separator, .separator-thick {
            border: 0;
            height: 1px; /* Default thickness for general separators */
            background: #444; /* Default color for general separators */
            margin: 30px 0; /* Default margin for general separators */
        }

        .separator-thick {
            height: 1px; /* Thickness of the separator between user info and stores */
        }

        .store-section {
            margin-top: 30px;
        }

        .store-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .store-header h3 {
            margin: 0;
            margin-right: 10px;
        }
        /* Pending store styles */
        .store-card.pending {
            position: relative;
            background-color: rgba(0, 0, 0, 0.7); /* Dark transparent background */
        }

        /* Overlay for pending text */
        .pending-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black overlay */
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 18px;
            font-weight: bold;
            border-radius: 8px; /* Match the card's border radius */
            z-index: 2; /* Ensures it appears above the card content */
        }
        .add-store-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            background-color: green;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .add-store-button:hover {
            background-color: white;
            color: black;
        }

    /* Horizontal scrolling for the store cards */
    .stores-grid {
        display: flex;
        flex-wrap: nowrap; /* Prevent wrapping to the next line */
        overflow-x: auto; /* Enable horizontal scrolling */
        gap: 20px;
        padding-bottom: 10px; /* Add some space for the scrollbar */
    }

    /* Scrollbar styles (optional, customize for appearance) */
    .stores-grid::-webkit-scrollbar {
        height: 10px;
    }

    .stores-grid::-webkit-scrollbar-thumb {
        background-color: #444;
        border-radius: 4px;
    }

    .stores-grid::-webkit-scrollbar-thumb:hover {
        background-color: #555;
    }

    /* Adjust the width of store cards for horizontal scrolling */
    .store-card {
        flex: 0 0 auto; /* Prevent flexbox from shrinking or growing cards */
        width: 275px; /* Set a fixed width for the cards */
        margin: 0 0 10px 0;
        background-color: #444;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        position: relative;
        cursor: pointer;
        text-decoration: none;
        color: white;
    }
    .store-card img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 8px 8px 0 0;
    }
    .store-card h4 {
        font-size: 20px;
        padding: 10px 15px 0 15px;
        margin: 0 10px 10px 0;
        white-space: nowrap; /* Prevents text from wrapping to the next line */
        overflow: hidden; /* Hides overflowing content */
        text-overflow: ellipsis; /* Displays ellipsis for overflowing text */
    }

    .store-card p.address {
        margin: 0 0 10px 0;
        font-style: italic;
        padding: 0 15px 0 15px;
        white-space: nowrap; /* Prevents text from wrapping to the next line */
        overflow: hidden; /* Hides overflowing content */
        text-overflow: ellipsis; /* Displays ellipsis for overflowing text */
    }
    .store-card p.description {
        margin: 0 0 10px 0;
        padding: 0 15px 0 15px;
        min-height: 3.5em; /* Ensures that the description box spans about 3 lines */
        overflow: hidden; /* Prevents content overflow */
        display: -webkit-box; /* Flexbox for truncating text */
        -webkit-line-clamp: 3; /* Limits the description to 3 lines */
        -webkit-box-orient: vertical;
        text-overflow: ellipsis;
    }
    .button-wrapper {
        padding: 0 15px 5px 15px;
    }
        /* Menu button for 3 vertical dots */
        .menu-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }


        /* Menu content (dropdown) */
        .menu-content {
            display: none;
            position: absolute;
            top: 30px;
            right: 10px;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        .menu-content a {
            display: block;
            padding: 5px 10px;
            color: #000;
            text-decoration: none;
        }

        .menu-content a:hover {
            background-color: #f0f0f0;
        }
        .delete-section h3 {
            margin: 0;
            margin-bottom: 20px;
        }

        .delete-section p {
            margin: 0;
            margin-bottom: 10px;
        }

        .delete-button {
            padding: 10px 20px;
            border: none;
            background-color: red;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 0;
        }

        .delete-button:hover {
            background-color: darkred;
        }
        /* Position the delete icon in the top right corner */
        .delete-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            color: white;
            background-color: rgba(255, 0, 0, 0.7); /* Red background for visibility */
            padding: 5px;
            border-radius: 50%;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            z-index: 3; /* Ensure it's above the card content */
        }

        .delete-icon:hover {
            background-color: red; /* Darker on hover */
            color: white;
        }

        .manage-business-button {
            display: block;
            margin: 10px auto;
            width: 100%;
            padding: 10px 20px;
            background-color: green;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
        }

        .manage-business-button:hover {
            background-color: white;
            color: black;
        }
        .info-item #userRole {
            font-weight: bold;
            font-size: 15px;
            margin-bottom: 25px;
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
        <a href="user-login-index.php"><i class="fas fa-home"></i> Home</a>
        <a href="user-profile-page.php"><i class="fas fa-user"></i> My Account</a>
        <a href="index.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
</header>

<div class="container">
    <button class="edit-button-top" onclick="location.href='edit-user-profile.php'">Edit Profile</button>
    <div class="profile-header">
        <div class="profile-picture">
            <img id="profilePictureMain" 
                 src="<?php echo htmlspecialchars($profile_photo); ?>" alt="Profile Photo"
                 style="width: 100px; height: 100px; border-radius: 50%;">
        </div>
        <div class="profile-info">
            <h2 id="userName"><?php echo htmlspecialchars($user['fullname']); ?></h2>
            <p id="userUsername">@<?php echo htmlspecialchars($user['username']); ?></p>
        </div>
    </div>
    
    <div class="info-section">
        <div class="info-item">
        <p id="userRole"><?php echo ($user['role_id'] == 2) ? "Business Owner" : "Tourist"; ?></p>
        </div>
        <div class="info-item">
            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
        </div>
        <div class="info-item">
            <p>Password: ********</p>
            <button class="edit-button-password" onclick="location.href='user-change-password.php'">Change Password</button>
        </div>
    </div>

    <?php if ($user['role_id'] == 2): ?>
    <hr class="separator-thick">
    
    <div class="store-section">
        <div class="store-header">
            <h3>Businesses Owned</h3>
            <button class="add-store-button" onclick="location.href='create-store.php'"><i class="fas fa-plus"></i></button>
        </div>

        <div class="stores-grid" id="storesGrid">
            
        <!-- Display pending stores first -->
        <?php foreach ($pending_stores as $store): ?>
        <div class="store-card pending">
            <img src="<?php echo !empty($store['business_profile_photo']) ? htmlspecialchars($store['business_profile_photo']) : 'img/default-store-profile.jpg'; ?>" 
                alt="Store Photo" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px 8px 0 0;">
            
                <h4><?php echo htmlspecialchars($store['name']); ?></h4>
                <p class = "address">
                    <i class="fas fa-map-marker-alt"></i> <!-- This is the location marker icon -->
                    <?php
                    // Check if the description is empty or null
                    $address = htmlspecialchars($store['address']);
                    if (empty($address)) {
                        // Display a default message if no description is provided
                        echo "No address available.";
                    } else {
                        // Check if the description length exceeds 75 characters and truncate if needed
                        if (strlen($address) > 35) {
                            echo substr($address, 0, 35) . '...';
                        } else {
                            echo $address;
                        }
                    }
                    ?>
                </p>
            <p class = "description">
            <?php 
            // Check if the description is empty or null
            $description = htmlspecialchars($store['description']);
            if (empty($description)) {
                // Display a default message if no description is provided
                echo "No description available.";
            } else {
                // Check if the description length exceeds 75 characters and truncate if needed
                if (strlen($description) > 75) {
                    echo substr($description, 0, 75) . '...';
                } else {
                    echo $description;
                }
            }
            ?>
            </p>

            <!-- Delete Button Icon -->
            <a href="delete-pending-store.php?store_id=<?php echo urlencode($store['id']); ?>" 
            class="delete-icon">
            <i class="fas fa-trash"></i>
            </a>

            
            <button class="menu-button" onclick="toggleMenu(this)">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="menu-content">
                <a href="edit-business-profile.php?store_id=<?php echo urlencode($store['id']); ?>">Edit</a>
                <a href="delete-store.php?store_id=<?php echo urlencode($store['id']); ?>" onclick="return confirm('Are you sure you want to delete this store?');">Delete</a>
            </div>
            <div class="pending-overlay">
                <p>Pending</p>
            </div>
            <div class="button-wrapper">
            <button class="manage-business-button" 
                onclick="window.location.href='user-store-profile.php?business_id=<?php echo $store['id']; ?>'">
                Manage Business
            </button>
            </div>
        </div>
    <?php endforeach; ?>

        
        <!-- Display non-pending stores -->
        <?php if (count($non_pending_stores) > 0): ?>
            <?php foreach ($non_pending_stores as $store): ?>
                <div class="store-card">
                    <img src="<?php echo !empty($store['business_profile_photo']) ? htmlspecialchars($store['business_profile_photo']) : 'img/default-store-profile.jpg'; ?>" 
                        alt="Store Photo" style="width: 100%; height: 150px; object-fit: cover; border-radius: 8px 8px 0 0;">

                    <h4><?php echo htmlspecialchars($store['name']); ?></h4>
                    <p class = "address">
                    <i class="fas fa-map-marker-alt"></i> <!-- This is the location marker icon -->
                    <?php
                    // Check if the description is empty or null
                    $address = htmlspecialchars($store['address']);
                    if (empty($address)) {
                        // Display a default message if no description is provided
                        echo "No address available.";
                    } else {
                        // Check if the description length exceeds 75 characters and truncate if needed
                        if (strlen($address) > 30) {
                            echo substr($address, 0, 30) . '...';
                        } else {
                            echo $address;
                        }
                    }
                    ?>
                </p>
            <p class = "description">
            <?php 
            // Check if the description is empty or null
            $description = htmlspecialchars($store['description']);
            if (empty($description)) {
                // Display a default message if no description is provided
                echo "No description available.";
            } else {
                // Check if the description length exceeds 75 characters and truncate if needed
                if (strlen($description) > 75) {
                    echo substr($description, 0, 75) . '...';
                } else {
                    echo $description;
                }
            }
            ?>
            </p>

                    <button class="menu-button" onclick="toggleMenu(this)">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="menu-content">
                        <a href="edit-business-profile.php?store_id=<?php echo urlencode($store['id']); ?>">Edit</a>
                        <a href="delete-store.php?store_id=<?php echo urlencode($store['id']); ?>" onclick="return confirm('Are you sure you want to delete this store?');">Delete</a>
                    </div>
                    <div class="button-wrapper">
                    <button class="manage-business-button" 
                        onclick="window.location.href='user-store-profile.php?business_id=<?php echo $store['id']; ?>'">
                        Manage Business
                    </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

    <hr class="separator">
    
    <div class="delete-section">
        <h3>Account Deletion</h3>
        <p>Deleting your account will permanently remove all stores owned by you.</p>
        <button class="delete-button">Delete Account</button>
    </div>
</div>

    <script>
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

        // Prevent dropdown from closing when clicking inside the dropdown menu
        document.getElementById("dropdown-menu").addEventListener("click", function(event) {
        event.stopPropagation();
        });

        function confirmDelete() {
            if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
                // Add account deletion logic here
                alert("Account deleted successfully.");
            }
        }


        function toggleMenu(button) {
            // Close other open menus
            closeAllMenus();

            // Get the menu related to the clicked button
            const menu = button.nextElementSibling;

            // Toggle the visibility of the menu
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }

        // Function to close all menus
        function closeAllMenus() {
            const menus = document.querySelectorAll('.menu-content');
            menus.forEach(menu => {
                menu.style.display = 'none';
            });
        }

        // Close menus when clicking outside
        document.addEventListener('click', (event) => {
            if (!event.target.closest('.menu-button')) {
                closeAllMenus();
            }
        });


    // Initialize store cards on page load
    document.addEventListener('DOMContentLoaded', () => {
        createStoreCards(stores);
    });
</script>
</body>
</html>
