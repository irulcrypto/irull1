    </div><!-- end .content -->
    <footer style="padding:16px 28px;border-top:1px solid var(--cream-dark);font-size:.75rem;color:var(--brown-pale);display:flex;justify-content:space-between;align-items:center;background:#fff;">
        <span>© <?= date('Y') ?> <?= APP_NAME ?> — Sistem Manajemen Perpustakaan</span>
        <span>v1.0</span>
    </footer>
</div><!-- end .main -->

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('overlay').classList.remove('show');
}
</script>
</body>
</html>
