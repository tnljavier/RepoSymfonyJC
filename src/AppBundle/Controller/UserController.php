<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use BackendBundle\Entity\Users;
use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;


class UserController extends Controller{

	public function newAction(Request $request){
		$helpers = $this->get(Helpers::Class);

		$json = $request->get("json", null);

		$params = json_decode($json);

		$data = array( 
			'status' => 'error',
			'code' => 400,
			'msg' => 'user not created'
		);

		if($json != null){
			$createdAt = new \Datetime("now");
			$role = "user";

			$email = (isset($params->email)) ? $params->email : null;
			$name = (isset($params->name)) ? $params->name : null;	
			$surname = (isset($params->surname)) ? $params->surname : null;
			$password = (isset($params->password)) ? $params->password : null;

			$emailConstraint = new Assert\Email();
            $emailConstraint->message = "This email is not valid";
            $validate_email = $this->get("validator")->validate($email, $emailConstraint);

			if($email != null && count($validate_email) == 0 && $password != null && $name != null && $surname != null){

				$user = new Users();
				$user->SetCreatedAt($createdAt);
				$user->SetRole($role);
				$user->SetEmail($email);	
				$user->SetName($name);
				$user->SetSurname($surname);

				//cifrar la contrasena
				$pwd = hash('sha256', $password);
				$user->SetPassword($pwd);


				//$user->SetPassword($password);
				$em = $this->getDoctrine()->getManager();
				$isset_user = $em->getRepository('BackendBundle:Users')->findBy(array(
					'email' => $email
				));

				if(count($isset_user) == 0){
					$em->persist($user);
					$em->flush();

					$data = array( 
						'status' => 'sucess',
						'code' => 200,
						'msg' => 'new user  created',
						'user' => $user
					);



				}else{
					$data = array( 
						'status' => 'error',
						'code' => 400,
						'msg' => 'user not created, duplicated'
					);
				}


			}
		}


		return $helpers->json($data);

	}




	public function editAction(Request $request){
		$helpers = $this->get(Helpers::class);

		$jwt_auth = $this->get(JwtAuth::class);
		$token = $request->get('authorization', null);
		$authCheck = $jwt_auth->checkToken($token);


		if ($authCheck) {
			// Entity manager
				$em = $this->getDoctrine()->getManager();
				// conseguir los datos del usuario indentificado coon el token
				$identity = $jwt_auth->checkToken($token,true);

				//conseguir el objeto a actualizar	

				$user = $em->getRepository('BackendBundle:Users')->findOneBy(array(
					'id' => $identity->sub
				));


				//recoger datos post
				$json = $request->get("json", null);
				$params = json_decode($json);

				//array por defecto
				$data = array( 
					'status' => 'error',
					'code' => 400,
					'msg' => 'user not update'
				);

				if($json != null){
					$createdAt = new \Datetime("now");
					$role = "user";

					$email = (isset($params->email)) ? $params->email : null;
					$name = (isset($params->name)) ? $params->name : null;	
					$surname = (isset($params->surname)) ? $params->surname : null;
					$password = (isset($params->password)) ? $params->password : null;

					$emailConstraint = new Assert\Email();
		            $emailConstraint->message = "This email is not valid";
		            $validate_email = $this->get("validator")->validate($email, $emailConstraint);

					if($email != null && count($validate_email) == 0 && $name != null && $surname != null){

						
						//$user->SetCreatedAt($createdAt);
						$user->SetRole($role);
						$user->SetEmail($email);	
						$user->SetName($name);
						$user->SetSurname($surname);
						//$user->SetPassword($password);

						if ($password != null) {
						//cifrar la contrasena
						$pwd = hash('sha256', $password);
						$user->SetPassword($pwd);
						}

						
						$isset_user = $em->getRepository('BackendBundle:Users')->findBy(array(
							'email' => $email
						));

						if(count($isset_user) == 0 || $identity->email == $email){
							$em->persist($user);
							$em->flush();

							$data = array( 
								'status' => 'sucess',
								'code' => 200,
								'msg' => 'new user  update',
								'user' => $user
							);



						}else{
							$data = array( 
								'status' => 'error',
								'code' => 400,
								'msg' => 'user not update, duplicated'
							);
						}


					}
				}
		 		
		 	}else{

		 					$data = array( 
								'status' => 'error',
								'code' => 400,
								'msg' => 'autorization not valid'
							);


		 	} 	

		return $helpers->json($data);

	}

}



  ?>