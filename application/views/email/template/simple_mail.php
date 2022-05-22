<?php

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 3/18/2018
 * Time: 1:40 AM
 *
 * @var string $content
 */

use System\Configs;
use System\URL;

if (empty($content)) {
    exit;
}
?>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Cache-control" content="public, max-age=3600, must-revalidate">
    <meta http-equiv="Expires" content="public, max-age=3600, must-revalidate">
    <meta http-equiv="Last-Modified" content="public, max-age=3600, must-revalidate">
    <meta http-equiv="Pragma" content="cache">
    <meta http-equiv="Content-Type" content="text/html; charset = utf - 8" />
</head>

<!--Style-->
<style>
    html,
    body,
    div,
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    p,
    strong,
    input,
    button,
    select,
    textarea,
    td {
        font-family: "Palatino Linotype", "Georgia", sans-serif !important;
        overflow-wrap: break-word;
        word-wrap: break-word;
        hyphens: auto;
        text-align: center !important;
        font-size: 16px !important;
    }

    h1 {
        text-align: center;
        padding: 5px;
        font-size: 30px !important;
        font-weight: bolder;
        color: <?= Configs::APP_THEME_PRIMARY_COLOR() ?>
    }

    h2 {
        text-align: center;
        padding: 5px;
        font-size: 28px !important;
        font-weight: bolder;
        color: <?= Configs::APP_THEME_PRIMARY_COLOR() ?> !important;
    }

    h3 {
        text-align: center;
        padding: 5px;
        font-size: 26px !important;
        color: #5f5f5f !important;
    }

    h4 {
        text-align: center;
        padding: 5px;
        font-size: 24px !important;
        color: #5f5f5f !important;
    }

    h5 {
        text-align: center;
        font-size: 20px !important;
    }

    h6 {
        text-align: center;
        font-size: 18px !important;
    }

    input,
    button {
        background: transparent;
        outline: 0;
        border: 0;
    }

    input:focus,
    button:focus {
        outline: 0;
    }

    button {
        cursor: pointer !important;
    }

    a {
        text-decoration: none;
        cursor: pointer;
    }

    a:hover,
    a:visited,
    a:link,
    a:active {
        text-decoration: none !important;
    }


    /* List */

    ol {
        list-style: decimal;
        margin: 5px;
        padding: 5px;
    }

    ul {
        list-style: disc;
        margin: 5px;
    }

    ul li {
        list-style: none;
    }

    ul.alt {
        list-style: none;
        padding-left: 0;
    }

    ul.alt li {
        border-top: solid 1px #e5e5e5;
        padding: 0.5em 0;
    }

    ul.alt li:first-child {
        border-top: 0;
        padding-top: 0;
    }

    ul.icons {
        cursor: default;
        list-style: none;
        padding-left: 0;
    }

    ul.icons li {
        display: inline-block;
        padding: 5px;
    }

    ul.icons li:last-child {
        padding-right: 0;
    }

    ul.icons li .icon {
        color: inherit;
    }

    ul.icons li .icon:before {
        font-size: 1.75em;
    }

    ul.actions {
        cursor: default;
        list-style: none;
        padding-left: 0;
    }

    ul.actions li {
        display: inline-block;
        padding: 0 1em 0 0;
        vertical-align: middle;
    }

    ul.actions li:last-child {
        padding-right: 0;
    }

    ul.actions.small li {
        padding: 0 0.5em 0 0;
    }

    ul.actions.vertical li {
        display: block;
        padding: 1em 0 0 0;
    }

    ul.actions.vertical li:first-child {
        padding-top: 0;
    }

    ul.actions.vertical li>* {
        margin-bottom: 0;
    }

    ul.actions.vertical.small li {
        padding: 0.5em 0 0 0;
    }

    ul.actions.vertical.small li:first-child {
        padding-top: 0;
    }

    ul.actions.fit {
        display: table;
        margin-left: -1em;
        padding: 0;
        table-layout: fixed;
        width: calc(100% + 1em);
    }

    ul.actions.fit li {
        display: table-cell;
        padding: 0 0 0 1em;
    }

    ul.actions.fit li>* {
        margin-bottom: 0;
    }

    ul.actions.fit.small {
        margin-left: -0.5em;
        width: calc(100% + 0.5em);
    }

    ul.actions.fit.small li {
        padding: 0 0 0 0.5em;
    }

    dl {
        margin: 0 0 2em 0;
    }

    .x-scroll {
        -webkit-overflow-scrolling: touch;
        overflow-x: auto;
    }

    table {
        margin: auto;
    }

    table tbody tr {
        border-left: 0;
        border-right: 0
    }

    table td {
        padding: 5px;
        margin: auto !important;
        text-align: center !important;
        ;
    }

    p {
        margin: auto;
        text-align: center !important;
    }

    table th {
        width: auto;
        color: #646464;
        font-weight: 300;
        padding: 5px;
    }

    table thead {
        border-bottom: solid 2px #e5e5e5
    }

    table tfoot {
        border-top: solid 2px #e5e5e5
    }

    table.alt {
        border-collapse: separate
    }

    table.alt tbody tr td {
        border: solid 1px #e5e5e5;
        border-left-width: 0;
        border-top-width: 0
    }

    table.alt tbody tr td:first-child {
        border-left-width: 1px
    }

    table.alt tbody tr:first-child td {
        border-top-width: 1px
    }

    table.alt thead {
        border-bottom: 0;
    }

    table.alt tfoot {
        border-top: 0;
    }

    html {
        width: 100%;
    }

    #outlook a {
        padding: 0;
    }


    img {
        outline: none;
        text-decoration: none;
        border: none;
        -ms-interpolation-mode: bicubic;
    }

    a img {
        border: none;
    }


    table {
        border-collapse: collapse;
    }

    sup {
        vertical-align: top;
        line-height: 100%;
    }

    .appleLinksGrey a {
        color: #36495A !important;
        text-decoration: none;
        font-size: 20px !important;
    }

    .appleLinksBlue a {
        color: #0061AB !important;
        text-decoration: none;
    }

    @media only screen {
        html {
            background: #f3f3f3;
        }
    }
</style>

<!--Mail Body-->

<body style="margin:0; padding:10px 0 0 0;display: flex;align-items: center;justify-content: center;
            text-align: center !important;
            font-size: 16px !important;">

    <table class="responsive-table" style="
            background: #fff;
            margin: 10px;
            border-radius: 3px;
            border:none;
            -webkit-box-shadow: 0px 2px 2px 0px rgba(85, 85, 85, 0.2);
            -moz-box-shadow: 0px 2px 2px 0px rgba(85, 85, 85, 0.2);
            box-shadow: 0px 2px 2px 0px rgba(85, 85, 85, 0.2)">
        <!--Header-->
        <tr align="center" style="height: auto !important;background: <?= Configs::APP_THEME_PRIMARY_COLOR() ?>;">
            <td height="40" align="center" style="padding:10px;display: flex; align-content: center; justify-content: center;">
                <img style="height:40px !important;margin:auto;object-fit: contain;user-select: none;image-rendering:auto;" src="<?= URL::assetUrl('public/images/logo/white/logo_txt_512px.png') ?>" alt="Busarm Logo" />
            </td>
        </tr>
        <!--Content-->
        <tr>
            <td align="center" style="display: flex;align-items: center;justify-content: center;padding: 40px 30px 40px 30px;">
                <?= $content ?>
            </td>
        </tr>
        <!--Footer-->
        <tr style="height: auto !important;background: <?= Configs::APP_THEME_PRIMARY_COLOR() ?>;">
            <td align="center" style="display: flex; align-content: center; justify-content: center;">
                <table style="margin:auto;display: flex; align-content: center; justify-content: center;">
                    <tr>
                        <td align="center" style="padding:10px;display: flex; align-content: center; justify-content: center;">
                            <p style="color: white;margin:auto;" align="center">&copy; <?= Configs::COMPANY_NAME() ?> All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>