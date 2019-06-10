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
                $pwd = hash('sha256', $params->password);

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

        $jwtAuth = new \JwtAuth();
        
        // Recibir datos por POST
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        // Validar los datos
        $validate = \Validator::make($params_array, [
            'email'     => 'required|email',
            'password'  => 'required'
        ]); 

        if($validate->fails()){

            $signup = array(
                'status'    => 'error',
                'code'      => 404,
                'message'   => 'El usuario no se a podido identificar',
                'errors'    => $validate->errors()
            );
            
        } else {

            // Cifrar el password
            $pwd = hash('sha256', $params->password);

            // Devolver token o datos
            $signup = $jwtAuth->signup($params->email, $pwd);
            
            if(!empty($params->getToken)){
                $signup = $jwtAuth->signup($params->email, $pwd, true);
            }
        }

        return response()->json($signup, 200);
    }

    public function update(Request $request){
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        // Recoger los datos por POST
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        
        if($checkToken && !empty($params_array)){

            // Usuario identificado
            $user = $jwtAuth->checkToken($token, true);

            // Validar datos

            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users'.$user->sub
            ]); 

            // Quitar los campos que no seran actualizados
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['description']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);

            // Actualizar datos en bbdd
            $user_update = User::where('id', $user->sub)->update($params_array);

            // Devolver array con resultados
            $data = array(
                'code'   => 200,
                'status' => 'success',
                'user'   => $user,
                'changes' => $params_array
            );

        }else{
            
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no esta identificado.'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function upload(Request $request){
        
        $data = array(
            'code' => 400,
            'status' => 'error',
            'message' => 'Error al subir imagen.'
        );

        return response()->json($data, $data['code']);
    }
}
