<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function pruebas(Request $request){
        return 'Accion de prueba de USER-CONTROLLER';
    }
}
