<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class UserController extends Controller
{
    public function pruebas(Request $request){
        return 'Accion de prueba de USER-CONTROLLER';
    }

    public function register(Request $request){
        
        // Recoger los datos del usuario por post

        $json = $request->input('json', null);
        $params = json_decode($json);   // objeto
        $params_array = json_decode($json, true);   // array

        if(!empty($params) && !empty($params_array)){
            // Limpiar espacios
            $params_array = array_map('trim', $params_array);

            // Validar datos
            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users',
                'password'  => 'required'
            ]); 

            if($validate->fails()){
                
                $data = array(
                    'status'    => 'error',
                    'code'      => 404,
                    'message'   => 'El usuario no se a creado',
                    'errors'    => $validate->errors()
                );

            }else{
                
                // Cifrar la contraseña
                $pwd = password_hash($params->password, PASSWORD_BCRYPT, ['cost' => 4]);

                // Crear el usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->role = 'ROLE_USER';
                $user->password = $pwd;

                // Guardar el usuario
                $user->save();

                $data = array(
                    'status'    => 'success',
                    'code'      => 200,
                    'message'   => 'El usuario se a creado correctamente',
                    'user'      => $user
                );
            }
        } else{

            $data = array(
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'Los datos enviados no son correctos'
            );
        }

        return response()->json($data, $data['code']);
        
    }

    public function login(Request $request){
        return 'Accion de login de usuarios';
    }
}
