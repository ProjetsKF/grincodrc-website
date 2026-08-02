<script src="<?php echo grinco_url_html('/assets/vendor/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo grinco_url_html('/assets/js/admin.js'); ?>"></script>
<?php if (!empty($adminPageScripts) && is_array($adminPageScripts)): ?>
  <?php foreach ($adminPageScripts as $adminPageScript): ?>
    <script src="<?php echo grinco_url_html($adminPageScript); ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
