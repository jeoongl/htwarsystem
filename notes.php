<link rel="stylesheet" href="css/footer.css">

    // Fetch contact information and social media links
$contact_query = "SELECT contact_no, email, facebook, instagram, x_twitter, linkedin FROM hinunangan_info_tbl LIMIT 1";
$contact_result = $conn->query($contact_query);
$contact_info = $contact_result->fetch_assoc();


<footer>
    <img src="img/tourism-design.png" id="decorative-line" class="decorative-line">
    <div class="footer-container">
        <div class="footer-column">
            <img class="logo1" src="img/hinunangan.png" alt="Logo">
            <img class="logo2" src="img/love-hn.png" alt="Love HN">
        </div>

        <div class="footer-column">
            <h3>Contact Us</h3>
            <p>Phone: <a href="tel:<?php echo $contact_info['contact_no']; ?>"><?php echo $contact_info['contact_no']; ?></a></p>
            <p>Email: <a href="mailto:<?php echo $contact_info['email']; ?>"><?php echo $contact_info['email']; ?></a></p>
        </div>
        <div class="footer-column">
            <h3>Follow Us</h3>
            <div class="social-icons">
                <?php if (!empty($contact_info['facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($contact_info['facebook']); ?>" target="_blank"><i class="fab fa-facebook"></i></a>
                <?php endif; ?>
                <?php if (!empty($contact_info['instagram'])): ?>
                    <a href="<?php echo htmlspecialchars($contact_info['instagram']); ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                <?php endif; ?>
                <?php if (!empty($contact_info['x_twitter'])): ?>
                    <a href="<?php echo htmlspecialchars($contact_info['x_twitter']); ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                <?php endif; ?>
                <?php if (!empty($contact_info['linkedin'])): ?>
                    <a href="<?php echo htmlspecialchars($contact_info['linkedin']); ?>" target="_blank"><i class="fab fa-linkedin"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</footer>
