<?php

namespace App\Views;

use App\Dto\Page\LoginPageDto;
use App\Helpers\URL;
use Busarm\PhpMini\Dto\BaseDto;
use Busarm\PhpMini\View;

class LoginPage extends View
{
    public function __construct(protected LoginPageDto|BaseDto|array|null $data = null, protected $headers = array())
    {
    }

    public function render()
    {
    ?>

        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />

            <link rel="icon" type="image/png" href="<?= URL::assetUrl('public/images/favicon/light/icon_16px.png') ?>" sizes="16x16" />
            <link rel="icon" type="image/png" href="<?= URL::assetUrl('public/images/favicon/light/icon_32px.png') ?>" sizes="32x32" />
            <link rel="icon" type="image/png" href="<?= URL::assetUrl('public/images/favicon/light/icon_64px.png') ?>" sizes="64x64" />
            <link rel="icon" type="image/png" href="<?= URL::assetUrl('public/images/favicon/light/icon_96px.png') ?>" sizes="96x96" />

            <link rel="stylesheet" type="text/css" href="<?= URL::GOOGLE_FONT_URL ?>" />

            <title>Login</title>
            <style>
                body {
                    margin: auto !important;
                    user-select: none;
                    background: <?= APP_THEME_PRIMARY_COLOR ?> !important;
                }

                oauth-login body,
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

                oauth-login h1 {
                    font-size: 26px;
                    font-weight: bold;
                }

                oauth-login h2 {
                    font-size: 24px;
                    font-weight: bold;
                }

                oauth-login h3 {
                    font-size: 20px;
                    font-weight: bold;
                }

                oauth-login h4 {
                    font-size: 16px;
                    font-weight: bold;
                }

                oauth-login .login-page {
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

                oauth-login .login-page .icon {
                    height: 40px;
                }

                oauth-login .login-page .form {
                    height: auto;
                    font-family: "", sans-serif;
                    background: #fff;
                    max-width: 500px;
                    padding: 20px;
                    text-align: center;
                    border-radius: 3px;
                }

                oauth-login .login-page .form input {
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

                oauth-login .login-page .form button {
                    outline: 0;
                    background: <?= APP_THEME_PRIMARY_COLOR ?>;
                    width: 100%;
                    border: 0;
                    padding: 15px;
                    color: #FFFFFF;
                    font-size: 18px;
                    -webkit-transition: all 0.3s ease;
                    transition: all 0.3s ease;
                    cursor: pointer;
                }

                oauth-login .login-page .form button:hover,
                .form button:active,
                .form button:focus {
                    opacity: .8;
                }

                oauth-login .login-page .form .message {
                    margin: 15px 0 0;
                    color: #DF632D;
                    font-size: 12px;
                }

                oauth-login .login-page .form .message a {
                    color: <?= APP_THEME_PRIMARY_COLOR ?>;
                    text-decoration: none;
                }
            </style>
            <script src="<?= URL::GOOGLE_RECAPTCHA_SCRIPT_URL ?>"></script>
            <script>
                function onSubmit(token) {
                    let auth_type = document.getElementById('auth_type');
                    let username = document.getElementById('username');
                    let password = document.getElementById('password');
                    let csrf_token = document.getElementById('csrf_token');
                    let recaptcha_token = document.getElementById('recaptcha_token');
                    let form = document.getElementById("login-form");
                    // User auth request
                    if (auth_type.value == "user") {
                        if (username.value == null || username.value == '') {
                            return alert('Username or Email is required')
                        }
                    } 
                    // Login auth request
                    else if (auth_type.value == "login") {
                        if (username.value == null || username.value == '') {
                            return alert('Username or Email is required')
                        } else if (password.value == null || password.value == '') {
                            return alert('Password is required')
                        }
                    }
                    recaptcha_token.value = token;
                    form.submit();
                }
            </script>
        </head>

        <body>
            <oauth-login>
                <div class="login-page">
                    <div class="form">
                        <!-- Logo -->
                        <?php $this->include('components/logo') ?>
                        <br>
                        <form id="login-form" class="form" method="post" action="<?= $this->data->action ?? null ?>">
                            <h2> Login </h2>
                            <input id="username" type="text" required="required" name="username" placeholder="Username/Email" />
                            <input id="auth_type" type="hidden" required="required" name="auth_type" value="login" />
                            <input id="password" type="password" required="required" name="password" placeholder="Password" />
                            <input id="recaptcha_token" type="hidden" required="required" name="recaptcha_token" />
                            <input id="csrf_token" type="hidden" required="required" name="csrf_token" value="<?= $this->data->csrf_token ?>" />
                            <input id="redirect_url" type="hidden" required="required" name="redirect_url" value="<?= $this->data->redirect_url ?>" />
                            <button class="g-recaptcha" data-sitekey="<?= RECAPTCHA_CLIENT_KEY ?>" data-callback='onSubmit' data-action='submit'>Proceed</button>
                            <?php if (isset($this->data->msg)) : ?>
                                <div class="message"><?= $this->data->msg ?></div>
                            <?php endif ?>
                        </form>
                    </div>
                </div>
                <!-- Footer -->
                <?php $this->include('components/footer') ?>

            </oauth-login>
        </body>

        </html>

    <?php
    }
}
