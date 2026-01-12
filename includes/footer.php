<!-- Footer Section -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-left">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo INSTITUTION_NAME; ?>. All rights reserved.</p>
                    <p><?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?></p>
                </div>
                <div class="footer-right">
                    <p>Developed by K-Gang Team</p>
                    <p>
                        <a href="mailto:<?php echo INSTITUTION_EMAIL; ?>">
                            <i class="fas fa-envelope"></i> <?php echo INSTITUTION_EMAIL; ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Files -->
    <script src="<?php echo ASSETS_PATH; ?>js/main.js"></script>
    <script src="<?php echo ASSETS_PATH; ?>js/validation.js"></script>
    <script src="<?php echo ASSETS_PATH; ?>js/ajax-handler.js"></script>
    
    <?php if (isset($additionalJS) && is_array($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo ASSETS_PATH . 'js/' . $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline Scripts (if any) -->
    <?php if (isset($inlineScript)): ?>
        <script>
            <?php echo $inlineScript; ?>
        </script>
    <?php endif; ?>

</body>
</html>

<style>
.footer {
    background-color: #2c3e50;
    color: #ecf0f1;
    padding: 2rem 0;
    margin-top: 3rem;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.footer-left,
.footer-right {
    flex: 1;
}

.footer-right {
    text-align: right;
}

.footer a {
    color: #3498db;
    text-decoration: none;
}

.footer a:hover {
    color: #5dade2;
}

.footer p {
    margin: 0.25rem 0;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
    }
    
    .footer-right {
        text-align: center;
    }
}
</style>