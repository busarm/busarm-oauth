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
            border-left: solid 1px #dddddd;
            display: inline-block;
            list-style: none;
            margin: 5px;
            padding:5px;
        }

        oauth-login footer .copyright li:first-child {
            border-left: 0;
            margin-left: 0;
            padding-left: 0;
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
            max-width: 360px;
            padding: 45px;
            text-align: center;
            border-radius: 3px;
            box-shadow:  0 2px 5px 0 rgba(77, 80, 79, 0.6)
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
            <form class="login-form" method="post" action="<?=$action?>">
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
                    <input type="hidden" required="required" name="username" value="<?= $email ?>"/>
                    <?php
                }
                else{
                    ?>
                    <h2> Login </h2>
                    <input type="text" required="required" name="username" placeholder="Username/Email"/>
                    <?php
                }
                ?>
                <input type="password" required="required" name="password" placeholder="Password"/>
                <input type="hidden" required="required" name="csrf_token" value="<?=$csrf_token?>"/>
                <button>Proceed</button>
                <?php
                if (isset($msg)){
                    ?>
                    <div class="message"><?=$msg?></div>
                    <?php
                }
                ?>
            </form>
        </div>
    </div>
    <!-- Footer -->
    <footer>
        <ul class="copyright">
            <li>&copy; Wecari All rights reserved.</li>
        </ul>
    </footer>
</oauth-login>
</body>
</html>