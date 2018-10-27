<?php
defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');

/**
 * @var string $email
 * @var string $msg
 */

defined('OAUTH_BASE_PATH') OR exit('No direct script access allowed');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <link rel="icon" type="image/png" href="https://ebusgh.com/cdn/public/img/favicon/favicon-16x16.png" sizes="16x16" />
    <link rel="icon" type="image/png" href="https://ebusgh.com/cdn/public/img/favicon/favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="https://ebusgh.com/cdn/public/img/favicon/favicon-64x64.png" sizes="64x64" />
    <title>Login</title>
    <style>
        body{
            margin: auto !important;
            user-select: none;
            background: #335038 !important;
        }
        oauth-success h1,h2,h3,h4,h5,p,div,input,button,textarea {
            font-family: "Palatino Linotype", sans-serif !important;
        }

        oauth-success h1{
            font-size: 26px;
            font-weight: bold;
        }
        oauth-success h2{
            font-size: 24px;
            font-weight: bold;
        }
        oauth-success h3{
            font-size: 20px;
            font-weight: bold;
        }
        oauth-success h4{
            font-size: 16px;
            font-weight: bold;
        }

        oauth-success footer {
            height: auto;
            background: transparent;
            padding: 10px;
            text-align: center;
        }
        oauth-success footer .copyright {
            color: #fff;
            font-size: 0.9em;
            padding: 0;
            text-align: center;
        }

        oauth-success footer .copyright a {
            color: inherit;
        }

        oauth-success footer .copyright li {
            border-left: solid 1px #dddddd;
            display: inline-block;
            list-style: none;
            margin: 5px;
            padding:5px;
        }

        oauth-success footer .copyright li:first-child {
            border-left: 0;
            margin-left: 0;
            padding-left: 0;
        }

        oauth-success .success-page {
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

        oauth-success .success-page .logo.icon{
            margin: auto;
            height: 100%;
            padding: 0;
            image-resolution: from-image;
            image-rendering: auto;
            object-fit: contain;
            border: 0;
            background: transparent;
        }
        oauth-success .success-page .logo.logo_icon{
            height:80px !important;
        }
        oauth-success .success-page .logo.icon{
            height:100px !important;
        }
        oauth-success .success-page .logo.logo_txt{
            height:25px !important;
        }

        oauth-success .success-page .form {
            height: auto;
            font-family: "", sans-serif;
            background: #fff;
            max-width: 360px;
            padding: 45px;
            text-align: center;
            border-radius: 3px;
            box-shadow:  0 2px 5px 0 rgba(77, 80, 79, 0.6)
        }
        oauth-success .success-page .form input {
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
        oauth-success .success-page .form button,a {
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
        oauth-success .success-page .form button:hover,.form button:active,.form button:focus {
            opacity: .8;
        }
        oauth-success .success-page .form a:hover,.form a:active,.form a:focus {
            opacity: .8;
        }

        oauth-success .success-page .form .message {
            margin: 15px 0 0;
            color: #DF632D;
            font-size: 12px;
        }
        oauth-success .success-page .form .message a {
            color: #3F5F44;
            text-decoration: none;
        }
    </style>
</head>
<body>
<oauth-success>
    <div class="success-page">
        <div class="form">
            <div>
                <img class="logo logo_icon" src="https://ebusgh.com/cdn/public/img/logo/dark/logo_128px.png">
            </div>
            <div>
                <img class="logo logo_txt" src="https://ebusgh.com/cdn/public/img/logo/dark/logo_txt_128px.png">
            </div>
            <br/>
            <div>
                <img class="logo icon" src="https://ebusgh.com/cdn/public/img/Verified.png">
            </div>
            <br>
            <h3> Authorization link Sent to <strong style="font-size: 16px"><?=$email?></strong></h3>
            <i>(Please check your email)</i>

        </div>
    </div>
    <!-- Footer -->
    <footer>
        <ul class="copyright">
            <li>&copy; eBusGh All rights reserved.</li>
        </ul>
    </footer>
</oauth-success>
</body>
</html>