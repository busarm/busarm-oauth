<?php

namespace App\Controllers\HTTP;

use Busarm\PhpMini\Crypto;
use App\Helpers\URL;
use Busarm\PhpMini\App;

/**
 * Created by VSCode.
 * User: Samuel
 * Date: 1/13/2022
 * Time: 3:34 PM
 */
class Misc
{
    public function __construct(private App $app)
    {
    }

    /**
     * Process secure link
     *
     * @param string $link
     * @return string
     */
    public function link($data = null)
    {
        $data = $this->app->request->query("data");
        if (!empty($data)) {
            $link = Crypto::decrypt(ENCRYPTION_KEY, $data);
            if ($link) {
                return response()->redirect($link);
            }
            return $this->app->showMessage(400, 'Failed to process link');
        }
        return $this->app->showMessage(400, 'Secure link not available');
    }
}
