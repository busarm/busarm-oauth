<?php

use System\URL;

?>
<style>
    app-logo .logo {
        margin: auto;
        height: 100%;
        padding: 0;
        image-resolution: from-image;
        image-rendering: auto;
        object-fit: contain;
        border: 0;
        background: transparent;
    }

    app-logo .logo.logo_txt {
        height: 80px;
    }
</style>
<app-logo>
    <div>
        <img class="logo logo_txt" src="<?= URL::assetUrl('public/images/logo/dark/logo_txt_512px.png') ?>">
    </div>
</app-logo>