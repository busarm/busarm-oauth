<?php

/**
 * @var string $client_name
 * @var string $org_name
 * @var string $user_name
 * @var string $user_email
 * @var array $scopes
 * @var string $action
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

    <title>Authorize Access</title>
    <style>
        body {
            margin: auto !important;
            user-select: none;
            background: <?= Configs::APP_THEME_PRIMARY_COLOR() ?> !important;
        }

        oauth-authorize body,
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

        oauth-authorize h1 {
            font-size: 26px;
            font-weight: bold;
        }

        oauth-authorize h2 {
            font-size: 24px;
            font-weight: bold;
        }

        oauth-authorize h3 {
            font-size: 20px;
            font-weight: bold;
        }

        oauth-authorize h4 {
            font-size: 16px;
            font-weight: bold;
        }

        oauth-authorize .auth-page {
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

        oauth-authorize .auth-page .logo.icon {
            margin: auto;
            height: 100%;
            padding: 0;
            image-resolution: from-image;
            image-rendering: auto;
            object-fit: contain;
            border: 0;
            background: transparent;
        }

        oauth-authorize .auth-page .logo.logo_icon {
            height: 80px;
        }

        oauth-authorize .auth-page .icon {
            height: 40px;
        }

        oauth-authorize .auth-page .logo.logo_txt {
            height: 25px;
        }

        oauth-authorize .auth-page .form {
            height: auto;
            font-family: "", sans-serif;
            background: #fff;
            min-width: 60%;
            max-width: 500px;
            padding: 20px;
            text-align: center;
            border-radius: 3px;
        }

        oauth-authorize .auth-page .form input {
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

        oauth-authorize .auth-page .form .auth-buttons .auth-button {
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

        oauth-authorize .auth-page .form .auth-buttons .auth-button.ok {
            background-color: seagreen;
        }

        oauth-authorize .auth-page .form .auth-buttons .auth-button.cancel {
            background-color: #DF632D;
        }

        oauth-authorize .auth-page .form button:hover,
        .form button:active,
        .form button:focus {
            opacity: .8;
        }

        oauth-authorize .auth-page .form .message {
            margin: 15px 0 0;
            color: #DF632D;
            font-size: 12px;
        }

        oauth-authorize .auth-page .form .message a {
            color: <?= Configs::APP_THEME_PRIMARY_COLOR() ?>;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <oauth-authorize>
        <div class="auth-page">
            <div class="form">
                <div>
                    <img class="logo logo_icon" src="<?= URL::assetUrl('public/images/logo/dark/logo_256px.png') ?>">
                </div>
                <div>
                    <img class="logo logo_txt" src="<?= URL::assetUrl('public/images/logo/dark/logo_txt_512px.png') ?>">
                </div>
                <br>
                <form id="login-form" class="form" method="post" action="<?= $action ?? null ?>">
                    <h3> <span style="color:#DF632D"><?= ucfirst($client_name ?? $org_name) ?></span> is requesting access to your account</h3>
                    <?php if (!empty($user_name) || !empty($user_email)) : ?>
                        <div>
                            <img class="icon" src="<?= URL::assetUrl('public/images/icons/Name_104px.png') ?>">
                            <div>
                                <?php if ($user_name) : ?>
                                    <strong><?= $user_name ?> </strong>
                                    <div><?= $user_email ?> </div>
                                <?php else : ?>
                                    <strong><?= $user_email ?> </strong>
                                <?php endif ?>
                            </div>
                        </div>
                        <?php if (!empty($scopes)) : ?>
                            <h4>Grant permission to do the following: </h3>
                                <ul>
                                    <?php foreach ($scopes as $scope) : ?>
                                        <li style="font-size: 14px; text-align: left;"><?= $scope ?></li>
                                    <?php endforeach ?>
                                </ul>
                            <?php endif ?>
                        <?php endif ?>
                        <br>
                        <br>
                        <div class="auth-buttons">
                            <div style="padding: 5px;">
                                <button id="approve" class="auth-button ok" name="approve" value="1" type="submit">Approve</button>
                            </div>
                            <div style="padding: 5px;">
                                <button id="decline" class="auth-button cancel" name="decline" value="1" type="submit">Decline</button>
                            </div>
                            <div style="padding: 8px; color:darkcyan; text-decoration: none;">
                                <a style="color:darkcyan; text-decoration: none;" href="/authorize/logout?redirect_url=<?= urlencode($action) ?? null ?>">Use another account</a>
                            </div>
                        </div>
                </form>
            </div>
        </div>
        <!-- Footer -->
        <?= app()->loadView('components/footer') ?>

    </oauth-authorize>
</body>

</html>