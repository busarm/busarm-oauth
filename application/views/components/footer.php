<?php

use System\Configs;
use System\URL;

?>
<style>
    footer {
        height: auto;
        background: transparent;
        padding: 10px;
        text-align: center;
    }

    footer .copyright {
        color: #fff;
        font-size: 0.9em;
        padding: 0;
        text-align: center;
    }

    footer .copyright a {
        color: inherit;
    }

    footer .copyright li {
        display: inline-block;
        list-style: none;
        margin: 5px;
        padding: 5px;
    }
</style>
<footer>
    <ul class="copyright">
        <li style="min-width: 60px;"><a href="<?= URL::appUrl(URL::APP_PRIVACY_PATH) ?>" target="_blank">Privacy</a></li>
        <li style="min-width: 60px;"><a href="<?= URL::appUrl(URL::APP_TERMS_PATH) ?>" target="_blank">Terms</a></li>
        <li style="min-width: 60px;"><a href="<?= URL::appUrl(URL::APP_SUPPORT_PATH) ?>" target="_blank">Support</a></li>
    </ul>
    <ul class="copyright">
        <li>&copy; <?= Configs::COMPANY_NAME() ?> All rights reserved.</li>
    </ul>
</footer>