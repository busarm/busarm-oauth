<?php

namespace Application\Controllers\HTTP;

use System\Encrypter;
use Application\Helpers\URL;

/**
 * Created by VSCode.
 * User: Samuel
 * Date: 1/13/2022
 * Time: 3:34 PM
 */
class Misc
{
    /**
     * Process secure link
     *
     * @param string $link
     * @return string
     */
    public function link($data = null)
    {
        $data = app()->request->query("data");
        if (!empty($data)) {
            $link = Encrypter::decrypt(ENCRYPTION_KEY, $data);
            if ($link) {
                return URL::redirect($link);
            }
            return app()->showMessage(400, false, 'Invalid Request', 'Failed to process link');
        }
        return app()->showMessage(400, false, 'Invalid Request', 'Secure link not available');
    }
}
