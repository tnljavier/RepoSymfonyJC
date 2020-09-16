<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints as Assert;
use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;

class DefaultController extends Controller
{
   
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')).DIRECTORY_SEPARATOR,
        ]);
    }


    public function loginAction(Request $request){
        $helpers = $this->get(Helpers::class);

        // recibir json por post

        $json = $request->get('json', null);

        // array a devolver por defecto

        $data = array(
            'status' => 'error',
            'data' => 'Send json via post'
        );


        if ($json != null) {

            // convertimos un json a un objeto de php

            $params = json_decode($json);

            $email = (isset($params->email)) ? $params->email : null;
            $password = (isset($params->password)) ? $params->password : null;
            $getHash = (isset($params->getHash)) ? $params->getHash : null;



            $emailConstraint = new Assert\Email();
            $emailConstraint->message = 'This email is not valid';
            $validate_email = $this->get("validator")->validate($email, $emailConstraint);


            //descifrar contrasena
                $pwd = hash('sha256',$password);

            if(count($validate_email) == 0 && $password != null){

                //Llamo a la clase Jwt 
                $jwt_auth = $this->get(JwtAuth::class);


                if($getHash == null || $getHash == false){
                    //Llamo el metodo signup
                    $signup = $jwt_auth->signup($email,$pwd);

                }else{
                    $signup = $jwt_auth->signup($email,$pwd,true);
                }


                return $this->json($signup);
                 

            }else{
                    $data = array(
                'status' => 'Error',
                'data' => 'Email or password incorrect'
                 ); 

            }

                 
        }
        return $helpers->json($data);
    }


    public function pruebasAction(Request $request)
    {
        $helpers = $this->get(Helpers::class);
        $jwt_auth = $this->get(JwtAuth::class);
        $token = $request->get('authorization',null);

        if($token && $jwt_auth->checkToken($token) == true){

           $em = $this->getDoctrine()->getManager();
           $userRepo = $em->getRepository('BackendBundle:Users');
           $users = $userRepo->findAll();

           
           return $helpers->json(array(
                'status' => 'sucess',   
                'users' => $users

           ));
        }else{
            return $helpers->json(array(
                'status' => 'error', 
                'code' => 400,  
                'users' => "Autorization no valid"

           ));

        }
    }
}
