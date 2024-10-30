<div class="wrap">
    <h2><?php echo __('Jekuntmeer.nl informatie:', 'jekuntmeer'); ?></h2>

    <?php if (isset($askforauth)) {
    $go = true;
    try {
        if (!class_exists('SoapClient')) {
            throw new Exception(__('Soap staat niet aan!', 'jekuntmeer'));
        } else {
            $client = new SoapClient(Jekuntmeer::getSoapUrl(), array('cache_wsdl' => WSDL_CACHE_NONE));
            $client->Codeboek();
        }
    } catch (SoapFault $e) {
        echo __('SOAPTEST: ERROR!!', 'jekuntmeer') . ' ' . $e->getMessage();
        $go = false;
    } catch (Exception $e) {
        echo __('SOAPTEST: ERROR!!', 'jekuntmeer') . ' ' . $e->getMessage();
        $go = false;
    }

    if (!isset($email) || empty($email)) {
        echo __('SOAPTEST: ERROR!! No Email', 'jekuntmeer');
        $go = false;
    }

    if ($go) {
        $res = $client->verzoek_toegang($email, $message);
        $text = Jekuntmeer::getSOAPError($res);

        echo '<h2>' . __('Result:', 'jekuntmeer') . ' ' . $text . '</h2>';
    }
} else {
    ?>
    <?php if (isset($testres)) {?>
        <div id="setting-error-settings_updated" class="updated settings-error notice is-dismissible"><p><strong><?php echo $testres ?></strong></p><button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php echo __('Verberg.', 'jekuntmeer'); ?></span></button></div>
    <?php }?>
    <p><?php echo __('De Jekuntmeer.nl plugin zorgt voor een koppeling tussen het aanbod van het Jekuntmeer.nl platform en uw website. U heeft een loginnaam en wachtwoord nodig hiervoor. Deze is hieronder op te vragen.
<br/>Na het maken van een connectie kunt u zelf filteren welk aanbod u op uw site wilt tonen. En waarop bezoekers van uw website kunnen zoeken.
<br/>
<br/>Wilt u hulp bij het instellen van de plugin? Bel met de helpdesk van Jekuntmeer.nl op 085-2733637 of mail naar helpdesk@jekuntmeer.nl
<br/>
<br/>Jekuntmeer.nl is een product van stichting De Omslag.
<br/>');?>
</p>
    <p><?php
$lastsync = get_option('jkm_sync');
    if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON == true) {

        echo '<br/>' . PHP_EOL;
        echo '<br/>' . PHP_EOL;

        echo '<strong>' . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/?runjob=jkm&offset=0&maxtime=300' . '</strong>';
        echo '<br/>' . PHP_EOL;

        echo __('Deze link ververst de data van Jekuntmeer.nl. <br/>Middels een automatische cronjob moet deze link periodiek aangeroepen worden, in verband met de belasting van de server tussen 4:00 en 6:00 in de nacht:', 'jekuntmeer');
        echo '<br/>' . PHP_EOL;
        echo __('Gebruik <strong>offset</strong> om de opdracht te hervatten op het punt waar deze vorige keer gestopt is. En gebruik <strong>maxtime</strong> om de maximale tijd in seconden op te geven die de opdracht mag blijven draaien.<br/>0 betekend geen tijd limiet, maar geeft waarschijnlijk een timeout op uw server.', 'jekuntmeer');
        echo '<br/>' . PHP_EOL;
        echo __('Indien u geen cronjobs op uw server kunt activeren neem dan contact ', 'jekuntmeer') . '<a href="https://amsterdam.jekuntmeer.nl/over-jekuntmeer/contact" rel="nofollow" target="_blank">' . __('hier', 'jekuntmeer') . '</a> contact op met Jekuntmeer.nl';
    } else {
        echo '<br/>' . PHP_EOL;
        echo '<br/>' . PHP_EOL;
        echo __('Volgende synchronisatie: ', 'jekuntmeer');
        $nextjob = wp_next_scheduled('jekuntmeer_job');
        echo (empty($nextjob) ? __('niet', 'jekuntmeer') : strftime('%d-%m-%Y ' . __('op', 'jekuntmeer') . ' %H:%M:%S', $nextjob));
    }
    ?></p>

    <form name="jekuntmeerconfigmain" id="jekuntmeerconfigmain" action="options.php" method="POST" autocomplete="off">
        <?php settings_fields('jekuntmeerconfigmain');?>
        <?php do_settings_sections('jekuntmeerconfigmain');?>
        <p class="submit"><input name="submit" id="submit" class="button button-primary" value="<?php echo __('Opslaan en controleren', 'jekuntmeer'); ?>" type="submit"></p>
    </form>

    <?php if (!Jekuntmeer::isConnected()) {?>
    <form method="post" action="">
    <h2><?php echo __('heeft u nog geen account op jekuntmeer.nl?', 'jekuntmeer'); ?></h2>
    <label for="email"><?php echo __('uw e-mail:', 'jekuntmeer'); ?></label>
    <input name="email" id="email" value="" type="email" required="">
    <br/>
    <label for="message"><?php echo __('Bericht:', 'jekuntmeer'); ?></label>
    <input name="message" id="message" value="" type="text" required="">
	<br/>
    <input name="askforauth" id="askforauth" class="button button-primary" value="<?php echo __('Vraag account aan', 'jekuntmeer'); ?>" type="submit">
    </form>

    <?php }?>

    <?php }?>
</div>
