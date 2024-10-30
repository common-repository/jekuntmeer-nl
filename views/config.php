<div class="wrap">
	<h2><?php echo __('Jekuntmeer.nl instellingen:', 'jekuntmeer'); ?></h2>
	<?php settings_errors();?>
	<form name="jekuntmeerconfig" id="jekuntmeerconfig" action="options.php" method="POST" autocomplete="off">
		<?php settings_fields('jekuntmeerconfig');?>
		<?php do_settings_sections('jekuntmeerconfig');?>
		<p class="submit"><input name="submit" id="submit" class="button button-primary" value="<?php echo __('Opslaan en controleren', 'jekuntmeer'); ?>" type="submit"></p>
	</form>
</div>
