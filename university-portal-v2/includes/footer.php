</main>

<footer class="site-footer">
    <p>
        <?= e(PORTAL_NAME) ?> &middot; Registrar's Office &middot;
        Session ID: <code><?= session_id() !== '' ? e(substr(session_id(), 0, 12)) . '&hellip;' : 'none' ?></code>
    </p>
    <p class="footer-note">
        Demonstrates <code>session_start()</code>, <code>$_SESSION</code>, <code>setcookie()</code>,
        <code>$_COOKIE</code>, <code>session_unset()</code> and <code>session_destroy()</code>.
    </p>
</footer>

</body>
</html>
