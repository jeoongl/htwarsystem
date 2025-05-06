<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include('includes/dbconnection.php');

// Fetch contact information and social media links
$contact_query = "SELECT contact_no, email, facebook, instagram, x_twitter, linkedin FROM hinunangan_info_tbl LIMIT 1";
$contact_result = $conn->query($contact_query);
$contact_info = $contact_result->fetch_assoc();
?>

<footer>
    <div class="footer-container">
        <!-- Contact Information Section -->
        <div class="footer-column">
            <h3>Contact Us</h3>
            <p>Phone: <?php echo $contact_info['contact_no'] ?? 'Not available'; ?></p>
            <p>Email: <a href="mailto:<?php echo $contact_info['email'] ?? '#'; ?>"><?php echo $contact_info['email'] ?? 'Not available'; ?></a></p>
        </div>
        
        <!-- Social Media Links Section -->
        <div class="footer-column">
            <h3>Follow Us</h3>
            <div class="social-icons">
                <?php if (!empty($contact_info['facebook'])): ?>
                    <a href="<?php echo $contact_info['facebook']; ?>" target="_blank"><i class="fab fa-facebook"></i></a>
                <?php endif; ?>
                <?php if (!empty($contact_info['instagram'])): ?>
                    <a href="<?php echo $contact_info['instagram']; ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                <?php endif; ?>
                <?php if (!empty($contact_info['x_twitter'])): ?>
                    <a href="<?php echo $contact_info['x_twitter']; ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                <?php endif; ?>
                <?php if (!empty($contact_info['linkedin'])): ?>
                    <a href="<?php echo $contact_info['linkedin']; ?>" target="_blank"><i class="fab fa-linkedin"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <p>&copy; <?php echo date('Y'); ?> Hinunangan Tourism. All Rights Reserved.</p>
</footer>
