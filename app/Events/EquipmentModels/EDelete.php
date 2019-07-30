<?php

namespace App\Events\EquipmentModels;

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
