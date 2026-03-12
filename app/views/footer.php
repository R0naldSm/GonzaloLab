<?php /* LAYOUT FOOTER — cerrar main, incluir scripts */ ?>
</div><!-- /page-content -->
</main><!-- /main -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Overlay móvil sidebar -->
<script>
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && sidebar.classList.contains('show') && !sidebar.contains(e.target)) {
        sidebar.classList.remove('show');
    }
});
// Auto-cerrar alertas flash del topbar
const flashEl = document.querySelector('#topbar .gl-alert');
if (flashEl) setTimeout(() => flashEl.style.opacity = '0', 4000);
</script>
<?php if (isset($extraJS)) echo $extraJS; ?>
</body>
</html>