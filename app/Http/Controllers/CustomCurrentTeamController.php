<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Jetstream\Http\Controllers\CurrentTeamController as JetstreamCurrentTeamController;

class CustomCurrentTeamController extends JetstreamCurrentTeamController
{
    
    public function update(Request $request)
    {
        parent::update($request);

        return redirect()->back();
    }
}
