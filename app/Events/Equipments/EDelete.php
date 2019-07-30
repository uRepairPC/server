<?php

namespace App\Events\Equipments;

use App\Events\Common\EDeleteBroadcast;

class EDelete extends EDeleteBroadcast
{
    use EModel;

    /**
     * @return array|string|null
     */
    public function rooms()
    {
        return self::$roomName . ".{$this->data['id']}";
    }
}
