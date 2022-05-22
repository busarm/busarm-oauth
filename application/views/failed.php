<?php

/**
 * @var string $msg
 * @var string $desc
 */

use System\Configs;
use System\URL;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <link rel="icon" type="image/png" href="<?= URL::assetUrl('public/images/favicon/dark/favicon-16x16.png') ?>" sizes="16x16" />
    <link rel="icon" type="image/png" href="<?= URL::assetUrl('public/images/favicon/dark/favicon-32x32.png') ?>" sizes="32x32" />
    <link rel="icon" type="image/png" href="<?= URL::assetUrl('public/images/favicon/dark/favicon-64x64.png') ?>" sizes="64x64" />
    <link rel="icon" type="image/png" href="<?= URL::assetUrl('public/images/favicon/dark/favicon-96x96.png') ?>" sizes="96x96" />

    <link rel="stylesheet" type="text/css" href="<?= URL::GOOGLE_FONT_URL ?>" />

    <title>Authentication - Failed</title>
    <style>
        body {
            margin: auto !important;
            user-select: none;
            background: <?= Configs::APP_THEME_PRIMARY_COLOR() ?> !important;
        }

        oauth-failed body,
        h1,
        h2,
        h3,
        h4,
        h5,
        p,
        span,
        div,
        input,
        button,
        textarea,
        li {
            font-family: "Arima Madurai", "Palatino Linotype", "Georgia", sans-serif, cursive !important;
        }

        oauth-failed h1 {
            font-size: 26px;
            font-weight: bold;
        }

        oauth-failed h2 {
            font-size: 24px;
            font-weight: bold;
        }

        oauth-failed h3 {
            font-size: 20px;
            font-weight: bold;
        }

        oauth-failed h4 {
            font-size: 16px;
            font-weight: bold;
        }

        oauth-failed .failed-page {
            display: flex;
            align-self: center;
            justify-content: center;
            width: auto;
            max-width: 500px;
            padding: 20px !important;
            margin: auto;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        oauth-failed .failed-page .logo.icon {
            margin: auto;
            height: 100%;
            padding: 0;
            image-resolution: from-image;
            image-rendering: auto;
            object-fit: contain;
            border: 0;
            background: transparent;
        }

        oauth-failed .failed-page .logo.logo_icon {
            height: 80px !important;
        }

        oauth-failed .failed-page .logo.icon {
            height: 100px !important;
        }

        oauth-failed .failed-page .logo.logo_txt {
            height: 25px !important;
        }

        oauth-failed .failed-page .form {
            height: auto;
            font-family: "", sans-serif;
            background: #fff;
            min-width: 60%;
            max-width: 360px;
            padding: 45px;
            text-align: center;
            border-radius: 3px;
            box-shadow: 0 2px 5px 0 rgba(77, 80, 79, 0.6)
        }

        oauth-failed .failed-page .form input {
            font-family: "", sans-serif;
            outline: 0;
            background: #f2f2f2;
            width: 100%;
            border: 0;
            margin: 0 0 15px;
            padding: 15px;
            box-sizing: border-box;
            font-size: 14px;
        }

        oauth-failed .failed-page .form button {
            outline: 0;
            background: <?= Configs::APP_THEME_PRIMARY_COLOR() ?>;
            width: 100%;
            border: 0;
            padding: 15px;
            color: #FFFFFF;
            font-size: 18px;
            -webkit-transition: all 0.3s ease;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        oauth-failed .failed-page .form button:hover,
        .form button:active,
        .form button:focus {
            opacity: .8;
        }

        oauth-failed .failed-page .form a:hover,
        .form a:active,
        .form a:focus {
            opacity: .8;
        }

        oauth-failed .failed-page .form .message {
            margin: 15px 0 0;
            color: #DF632D;
            font-size: 12px;
        }

        oauth-failed .failed-page .form .message a {
            color: <?= Configs::APP_THEME_PRIMARY_COLOR() ?>;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <oauth-failed>
        <div class="failed-page">
            <div class="form">
                <div>
                    <img class="logo logo_icon" src="<?= URL::assetUrl('public/images/logo/dark/logo_256px.png') ?>">
                </div>
                <div>
                    <img class="logo logo_txt" src="<?= URL::assetUrl('public/images/logo/dark/logo_txt_512px.png') ?>">
                </div>
                <br />
                <div>
                    <img class="logo icon" src="<?= URL::assetUrl('public/images/icons/Warning.png') ?>">
                </div>
                <br>
                <?php
                if (isset($msg)) {
                ?>
                    <h3><?= $msg ?></h3>
                <?php
                }
                if (isset($desc)) {
                ?>
                    <i>(<?= $desc ?>)</i>
                <?php
                }
                ?>

            </div>
        </div>
        <!-- Footer -->
        <?= app()->view('components/footer') ?>

    </oauth-failed>
</body>

</html>