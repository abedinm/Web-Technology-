<?php
// footer.php - closes whichever shell header.php opened.
$role = isset($_SESSION["role"]) ? $_SESSION["role"] : "";
?>
<?php if (empty($authSplit)): ?>
        <div class="footer">
            DoctorConnect &middot; CSC 3215 Web Technologies &middot; Group 6, Section F &middot; Summer 2025-26
        </div>
<?php endif; ?>

<?php if (is_logged_in()): ?>
        </div><!-- .container -->
    </main>
</div><!-- .shell -->
<?php elseif (empty($authSplit)): ?>
</div><!-- .plain-wrap -->
<?php endif; ?>

</body>
</html>
