<?php

namespace App\Models;

use Laravel\Jetstream\Membership as JetstreamMembership;
use App\Traits\HasSecureId;

class Membership extends JetstreamMembership
{
    use HasSecureId;
    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;
}
