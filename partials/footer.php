        </main>
        <footer class="bg-white border-top text-center py-3">
            <small>&copy; <?php echo date('Y'); ?> <?php echo h(APP_NAME); ?></small>
        </footer>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/app.js"></script>
<?php
if (!empty($pageScripts) && is_array($pageScripts)) {
    foreach ($pageScripts as $snippet) {
        echo $snippet;
    }
}
?>
</body>
</html>
