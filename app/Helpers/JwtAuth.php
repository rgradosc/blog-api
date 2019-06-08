<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\DB;
use App\User;

class JwtAuth{

    public $key;

    public function __construct(){
        $this->key = 'este_es_un_token_super_secreto-987668779';
    }

    public function signup($email, $password, $getToken = null){
        // Buscar si existe el usuario con sus credenciales
        $user = User::where([
            'email' => $email,
            'password' => $password
        ])->first();

        // Comprobar si son correctas(objetos)
        $signup = false;

        if(is_object($user)){
            $signup = true;
        }

        // Generar el token con los datos del usuario identificado

        if($signup){

            $token = array(
                'sub' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'surname' => $user->surname,
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60)
            );

            $jwt = JWT::encode($token, $this->key, 'HS256');

            $decoded = JWT::decode($jwt, $this->key, ['HS256']);

            // Devolver los datos decodificados o el token,en funcion de un parametro

            if(is_null($getToken)){
                $data = $jwt;
            }else{
                $data = $decoded;
            }

        }else {
            $data = array(
                'status' => 'error',
                'message' => 'Login incorrecto.'
            );
        }

        return $data;

    }

    public function checkToken($jwt, $getIdentity = false){
        $auth = false;

        try {
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);   
        } catch (\UnexpectedException $ex) {
            $auth = false;
        } catch(\DomainException $ex){
            $auth = false;
        }

        if(!empty($decoded) && is_object($decoded) && isset($decoded->sub)){
            $auth = true;
        }else{
            $auth = false;
        }

        if($getIdentity){
            return $decoded;
        }

        return $auth;
    }
}