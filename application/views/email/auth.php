<?php

/**
 * Created by PhpStorm.
 * User: Samuel
 * Date: 1/13/2022
 * Time: 1:40 AM
 *
 * @var string $link
 */
if (empty($link)) {
    exit;
}
?>
<table style='max-width:500px;' border='0'>
    <tr width='350'>
        <td style='text-align: center;'>
            <h3>Access your account</h3>
        </td>
    </tr>
    <tr width='350'>
        <td style='text-align: center;'><strong style='font-size:12px !important; color: #9d223c;'>(Please ignore this
                message if it wasn't triggered or requested by you)</strong></td>
    </tr>
    <tr width='350'>
        <td style='text-align: center;'><span style='font-size:14px !important; color: #0b2e13;'>This link can only be
                used <strong>ONCE</strong> and will expire in <strong>AN HOUR</strong></span></td>
    </tr>
    <tr width='350'>
        <br />
    </tr>
    <tr width='350'>
        <td style='padding: 10px; display: flex; align-content: center; justify-content: center; text-align: center;'>
            <div style='margin:auto; text-align: center;'>
                <a href="<?= $link ?>" style="margin:auto; background-color:#267272; border-radius:4px; color:#ffffff; display:inline-block; font-family:sans-serif; font-size:16px; font-weight:bold; line-height:40px; text-align:center; text-decoration:none; height:40px; width:200px; -webkit-text-size-adjust:none; mso-hide:all;" rel="noreferrer">
                    Click to Login
                </a>
            </div>
        </td>
    </tr>
</table>