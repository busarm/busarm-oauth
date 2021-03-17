<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

/**
 * @var string $email
 * @var string $msg
 * @var string $csrf_token
 * @var string $action
 */ 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <link rel="icon" type="image/png" href="<?=App::get_cdn_path('public/images/favicon/dark/favicon-16x16.png')?>" sizes="16x16" />
    <link rel="icon" type="image/png" href="<?=App::get_cdn_path('public/images/favicon/dark/favicon-32x32.png')?>" sizes="32x32" />
    <link rel="icon" type="image/png" href="<?=App::get_cdn_path('public/images/favicon/dark/favicon-64x64.png')?>" sizes="64x64" />
    <link rel="icon" type="image/png" href="<?=App::get_cdn_path('public/images/favicon/dark/favicon-96x96.png')?>" sizes="96x96" />
    <title>Login</title>
    <style>
        body{
            margin: auto !important;
            user-select: none;
            background: #335038 !important;
        }
        oauth-login h1,h2,h3,h4,h5,p,div,input,button,textarea {
            font-family: "Palatino Linotype", sans-serif !important;
        }

        oauth-login h1{
            font-size: 26px;
            font-weight: bold;
        }
        oauth-login h2{
            font-size: 24px;
            font-weight: bold;
        }
        oauth-login h3{
            font-size: 20px;
            font-weight: bold;
        }
        oauth-login h4{
            font-size: 16px;
            font-weight: bold;
        }

        oauth-login footer {
            height: auto;
            background: transparent;
            padding: 10px;
            text-align: center;
        }
        oauth-login footer .copyright {
            color: #fff;
            font-size: 0.9em;
            padding: 0;
            text-align: center;
        }

        oauth-login footer .copyright a {
            color: inherit;
        }

        oauth-login footer .copyright li {
            display: inline-block;
            list-style: none;
            margin: 5px;
            padding:5px;
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

        oauth-login .login-page .logo.icon{
            margin: auto;
            height: 100%;
            padding: 0;
            image-resolution: from-image;
            image-rendering: auto;
            object-fit: contain;
            border: 0;
            background: transparent;
        }
        oauth-login .login-page .logo.logo_icon{
            height:80px;
        }
        oauth-login .login-page .icon{
            height:40px;
        }
        oauth-login .login-page .logo.logo_txt{
            height:25px;
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
            background: #3F5F44;
            width: 100%;
            border: 0;
            padding: 15px;
            color: #FFFFFF;
            font-size: 18px;
            -webkit-transition: all 0.3s ease;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        oauth-login .login-page .form button:hover,.form button:active,.form button:focus {
            opacity: .8;
        }

        oauth-login .login-page .form .message {
            margin: 15px 0 0;
            color: #DF632D;
            font-size: 12px;
        }
        oauth-login .login-page .form .message a {
            color: #3F5F44;
            text-decoration: none;
        }
    </style>
    <script src="https://www.google.com/recaptcha/api.js"></script>
    <script>        
        function onSubmit(token) {
            let auth_type = document.getElementById('auth_type');
            let username = document.getElementById('username');
            let password = document.getElementById('password');
            let csrf_token = document.getElementById('csrf_token');
            let recaptcha_token = document.getElementById('recaptcha_token');
            let form = document.getElementById("login-form");
            if(auth_type.value == "user"){
                if(username.value == null || username.value == ''){
                    return alert('Username or Email is required')
                }
            }
            else if(auth_type.value == "login"){
                if(username.value == null || username.value == ''){
                    return alert('Username or Email is required')
                }
                else if(password.value == null || password.value == ''){
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
            <div>
                <img class="logo logo_icon" src="<?=App::get_cdn_path('public/images/logo/dark/logo_128px.png')?>">
            </div>
            <div>
                <img class="logo logo_txt" src="<?=App::get_cdn_path('public/images/logo/dark/logo_txt_256px.png')?>">
            </div>
            <br>
            <form id="login-form" class="form" method="post" action="<?=$action?>">
                <?php
                if (!empty($email)){
                    ?>
                    <h3> Authorize access to this account</h3>
                    <br>
                    <div>
                        <img class="icon" src="<?=App::get_cdn_path('public/images/icons/Name_104px.png')?>">
                        <div>
                            <?= $email ?>
                        </div>
                    </div>
                    <br>
                    <input id="username" type="hidden" required="required" name="username" value="<?= $email ?>"/>
                    <input id="auth_type" type="hidden" required="required" name="auth_type" value="user"/>
                    <?php
                }
                else {
                    ?>
                    <h2> Login </h2>
                    <input id="username" type="text" required="required" name="username" placeholder="Username/Email"/>
                    <input id="auth_type" type="hidden" required="required" name="auth_type" value="login"/>
                    <?php
                }
                ?>
                <input id="password" type="password" required="required" name="password" placeholder="Password"/>
                <input id="csrf_token" type="hidden" required="required" name="csrf_token" value="<?=$csrf_token?>"/>
                <input id="recaptcha_token" type="hidden" required="required" name="recaptcha_token"/>
                <button class="g-recaptcha" 
                    data-sitekey="<?= Configs::RECAPTCHA_CLIENT_KEY() ?>" 
                    data-callback='onSubmit' 
                    data-action='submit'>Proceed</button>
                <?php if (isset($msg)): ?>
                    <div class="message"><?=$msg?></div>
                <?php endif ?>
            </form>
        </div>
    </div>
    <!-- Footer -->
    <footer>
        <ul class="copyright">
            <li style="min-width: 100px;"><a href="<?=App::get_app_path('privacy')?>" target="_blank">Privacy Policy</a></li>
            <li style="min-width: 100px;"><a href="<?=App::get_app_path('terms')?>" target="_blank">Terms & Conditions</a></li>
        </ul>
        <ul class="copyright">
            <li>&copy; Wecari All rights reserved.</li>
        </ul>
    </footer>
</oauth-login>
</body>
</html>