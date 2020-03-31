<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Helpers\JwtAuth;

class PostController extends Controller
{
    public function __construct(){
        $this->middleware('api.auth', ['except' => ['index','show', 'getImage','getPostsByCategory','getPostsByUser']]);
    }

    public function index(){
        $posts = Post::all()->load('category');

        return response()->json([
            'code' => 200,
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }

    public function show($id){
        $post = Post::find($id)->load('category');

        if(is_object($post)){
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
            ];   
        } else {
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'La entrada no existe'
            ]; 
        }

        return response()->json($data, $data['code']); 
    }

    public function store(Request $request){
        // Recoger los datos por post
        $json = $request->input('json', null);
        $param_array = json_decode($json, true);

        if(!empty($param_array)){

            // Conseguir usuario identificado
            $user = $this->getIdentityUser($request);

            // Validar los datos
            $validate = \Validator::make($param_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required',
                'image' => 'required'
            ]);

            // Guardar el articulo
            if($validate->fails()){
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'No se ha guardado el post o faltan datos.'
                ]; 
            }else{
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $param_array['category_id'];
                $post->title = $param_array['title'];
                $post->content = $param_array['content'];
                $post->image = $param_array['image'];
                $post->save();
                
                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post
                ]; 
            }
        } else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'No se ha enviado ningún dato correctamente'
            ]; 
        }
        
        return response()->json($data, $data['code']);
    } 

    public function update($id, Request $request){
        // Recoger datos por post
        $json = $request->input('json', null);
        $param_array = json_decode($json, true);

        if(!empty($param_array)){

            // Validar los datos
            $validate = \Validator::make($param_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required'
            ]);

            if($validate->fails()){
                return response()->json($validate->errors(), true);    
            }

            // Quitar lo que no quiero actualizar
            unset($param_array['id']);
            unset($param_array['created_at']);
            unset($param_array['user_id']);
            unset($param_array['user']);

            // Conseguir usuario identificado
            $user = $this->getIdentityUser($request);

            // Buscar el post a actualizar
            $post = Post::where('id', $id)->where('user_id', $user->sub)->first();

            if(!empty($post) && is_object($post)){

                // Actualizar el post en concreto
                $post->update($param_array);

                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post,
                    'changes' => $param_array
                ]; 

            }
        } else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'No se ha enviado ninguna entrada'
            ]; 
        }

        return response()->json($data, $data['code']);
    }

    public function destroy($id, Request $request){
        
        $user = $this->getIdentityUser($request);
        
        $post = Post::where('id', $id)->where('user_id', $user->sub)->get();

        if(!empty($post)){
            
            $post->delete();

            $data = [
                'code' => 200,
                'status' => 'success',
                'post' =>  $post
            ];
        }else {
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' =>  'El post no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    private function getIdentityUser($request){
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        return $user;
    }

    public function upload(Request $request){
        $image = $request->file('file0');
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,gif,png'
        ]);

        if(!$image || $validate->fails()){
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' =>  'Error al subir la imagen'
            ];
        }else{
            $image_name = time().$image->getClientOriginalName();
            \Storage::disk('images')->put($image_name, File::get($image));
            $data = [
                'code' => 200,
                'status' => 'success',
                'image' =>  $image_name
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function getImage($filename){

        // Comprobar si existe el fichero
        $isset = \Storage::disk('images')->exists($filename);

        if($isset){
            // Conseguir la imagen
            $file = \Storage::disk('images')->get($filename);

            return new Response($file, 200);
        }else{
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' =>  'La imagen no existe'
            ];
        }

        return response()->json($data, $data['code']);
    }

    public function getPostsByCategory($id){
        $posts = Post::where('category_id', $id)->get();

        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }

    public function getPostsByUser($id){
        $posts = Post::where('user_id', $id)->get();

        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }
}
