<?php
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
        .login-page {
            width: 360px;
            padding: 8% 0 0;
            margin: auto;
        }
        .logo{
            image-rendering: auto;
            image-resolution: normal;
            width: 80px;
            height: 80px;
        }
        .form {
            font-family: "", sans-serif;
            position: relative;
            z-index: 1;
            background: #fff;
            max-width: 360px;
            margin: 0 auto 100px;
            padding: 45px;
            text-align: center;
            border-radius: 3px;
            box-shadow:  0 2px 5px 0 rgba(77, 80, 79, 0.6)
        }
        .form input {
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
        .form button {
            font-family: "", sans-serif;
            text-transform: uppercase;
            outline: 0;
            background: #3F5F44;
            width: 100%;
            border: 0;
            padding: 15px;
            color: #FFFFFF;
            font-size: 14px;
            -webkit-transition: all 0.3s ease;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .form button:hover,.form button:active,.form button:focus {
            background: #335038;
        }
        .form .message {
            margin: 15px 0 0;
            color: #b3b3b3;
            font-size: 12px;
        }
        .form .message a {
            color: #3F5F44;
            text-decoration: none;
        }
        .container {
            position: relative;
            z-index: 1;
            width: auto;
            margin: 0 auto;
        }
        .container:before, .container:after {
            content: "";
            display: block;
            clear: both;
        }
        .container .info {
            margin: 50px auto;
            text-align: center;
        }
        .container .info h1 {
            margin: 0 0 15px;
            padding: 0;
            font-size: 36px;
            font-weight: 300;
            color: #1a1a1a;
        }
        .container .info span {
            color: #4d4d4d;
            font-size: 12px;
        }
        .container .info span a {
            color: #000000;
            text-decoration: none;
        }
        .container .info span .fa {
            color: #EF3B3A;
        }
        body {
            user-select: none;
            background: rgba(63, 95, 68, 1);
            font-family: "", sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
    </style>
    <script
            src="https://code.jquery.com/jquery-3.3.1.min.js"
            integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
            crossorigin="anonymous">
    </script>
</head>
<body>
<div class="container login-page">

    <div class="form">
        <div>
            <img class="logo" src="https://ebusgh.com/cdn/public/img/favicon/favicon-128x128.png">
        </div>
        <br>
        <form class="login-form" method="post">
            <input type="text" name="username" placeholder="Username/Email"/>
            <input type="password" name="password" placeholder="Password"/>
            <button>login</button>
        </form>
    </div>
</div>
</body>
</html>