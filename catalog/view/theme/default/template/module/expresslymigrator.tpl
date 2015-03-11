<?php
    echo $header;
    echo $column_left;
    echo $column_right; ?>
    
<script>
	var baseUrl = "<?php echo $base ?>";
	var isRedirectToCheckoutEnabled = <?php echo $isRedirectToCheckoutEnabled ?>;
	var isRedirectToLoginEnabled = <?php echo $isRedirectToLoginEnabled ?>;
</script>

    <div id="content">
        <?php
            echo $content_top;
            echo $content_bottom;
        ?>
        <form method="POST" id="expresslyLoginHelperForm" action="<?php $base ?>index.php?route=account/login">
        	<input id="expresslyLoginHelperEmail" type="hidden" name="email" value="">
        	<input type="hidden" name="password" value="">
        </form>
    </div>
<?php echo $footer; ?>