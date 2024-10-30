<div class="wrap">
    <?php if (isset($testres)) {?>
    	<?php if (!empty($testres['updated'])) {?>
			<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong><?php echo __('Resultaat', 'jekuntmeer') . ': ' . $testres['updated'] . __(' Activiteiten opgeslagen. Dit duurde ', 'jekuntmeer') . $testres['took'] . __(' seconden.', 'jekuntmeer'); ?></strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo __('Verberg.', 'jekuntmeer'); ?></span></button></div>
		<?php } else {?>
			<div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong><?php echo __('Resultaat', 'jekuntmeer') . ': ' . __('Helaas, niets gevonden. Weet u zeker dat de instellingen correct zijn?', 'jekuntmeer'); ?></strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo __('Verberg.', 'jekuntmeer'); ?></span></button></div>
		<?php }?>
	<?php }?>
<form method="post" action="options.php" name="jekuntmeerconfigfilter" id="jekuntmeerconfigfilter">
<?php settings_fields('jekuntmeerconfigfilter');?>
<?php do_settings_sections('jekuntmeerconfigfilter');?>

<?php submit_button();?>
<p><strong><?php echo __('Wees geduldig, het opslaan en verwerken kan enkele minuten duren.', 'jekuntmeer'); ?></strong></p>

        </form>
            </div>
