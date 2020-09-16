<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use BackendBundle\Entity\Tasks;
use AppBundle\Services\Helpers;
use AppBundle\Services\JwtAuth;


class TaskController extends Controller{

	public function newAction(Request $request, $id = null){
		
		$helpers = $this->get(Helpers::class);
		$jwt_auth = $this->get(JwtAuth::class);

		$token = $request->get("authorization",null);
		$authCheck = $jwt_auth->checkToken($token);
		if ($authCheck) {

			$identity = $jwt_auth->checkToken($token,true);
			$json = $request->get("json",null);

			if ($json != null) {
				
				$params = json_decode($json);

				$createdAt = new \Datetime('now');
				$updatedAt = new \Datetime('now');

				$user_id = ($identity->sub != null) ? $identity->sub : null;
				$title = (isset($params->title)) ? $params->title : null;
				$description = (isset($params->description)) ? $params->description : null;
				$status = (isset($params->status)) ? $params->status : null;


				if ($user_id != null && $title != null) {
					//crear tarea

					$em = $this->getDoctrine()->getManager();
					$user = $em->getRepository('BackendBundle:Users')->findOneBy(array(
						"id" => $user_id
					));

					if($id == null){

						$task = new Tasks();
						$task->setUser($user);
						$task->setTitle($title);
						$task->setDescription($description);
						$task->setStatus($status);
						$task->setCreatedAt($createdAt);
						$task->setUpdatedAt($updatedAt);



						$em->persist($task);
						$em->flush();


						$data = array(
						"status"=>"sucess",
						"code"=> 200,
						"data"=> $task
						);


					}else{

							$task = $em->getRepository('BackendBundle:Tasks')->findOneBy(array(
								"id" => $id
							));

						if (isset($identity->sub) && $identity->sub == $task->getUser()->getId()) {

							


							$task->setTitle($title);
							$task->setDescription($description);
							$task->setStatus($status);
							$task->setUpdatedAt($updatedAt);



							$em->persist($task);
							$em->flush();

							$data = array(
								"status"=>"sucess",
								"code"=> 200,
								"data"=> $task
							);



							
						}else{

							$data = array(
							"status"=>"error",
							"code"=> 400,
							"msg"=> "task updated error, you are not owner"
							);


						}
					}


				}else{

					$data = array(
					'status'=>'error',
					'code'=> 400,
					'msg'=> "task not created, validation failed"
					);


				}


				
			}else{


				$data = array(
				'status'=>'error',
				'code'=> 400,
				'msg'=> "task not created, params failed"
			);

			}

		}else{
			$data = array(
				"status"=>"error",
				"code"=> 400,
				"msg"=> "authorization no valid"
			);

		}
		return $helpers->json($data);
	}



	public function tasksAction(request $request){

		$helpers = $this->get(Helpers::class);
		$jwt_auth = $this->get(JwtAuth::class);

		$token = $request->get('authorization',null);
		$authCheck = $jwt_auth->checkToken($token);
		if ($authCheck) {

			$identity = $jwt_auth->checkToken($token,true);

			$em = $this->getDoctrine()->getManager();

			//Para crear consultas

			$dql = "SELECT t FROM BackendBundle:Tasks t WHERE t.user = {$identity->sub} ORDER BY t.id DESC";
			$query = $em->createQuery($dql);

			//recogemos los parametros de la request, lo que trae.
			$page = $request->query->getInt('page', 1);
			$paginator = $this->get('knp_paginator');
			$items_per_page = 10;

			//Para llamar ala metodo paginator

			$pagination = $paginator->paginate($query, $page, $items_per_page);

			//cuenta todos los registro de la tabla
			$total_items_count = $pagination->getTotalItemCount(); 

			$data = array(
				'status'=>'sucess',
				'code'=> 200,
				'total_items_count' => $total_items_count,
				'page_actual' => $page,
				'items_per_page' => $items_per_page,
				'total_items_count' => ceil($total_items_count/$items_per_page),
				'data' => $pagination
			);
		}else{

			$data = array(
				'status'=>'error',
				'code'=> 400,
				'msg'=> "Authorization no valid"
			);

		}

		return $helpers->json($data);

	}


		// Metodo para devolver el detalle de una tarea

	public function taskAction(request $request, $id = null){

		$helpers = $this->get(Helpers::class);
		$jwt_auth = $this->get(JwtAuth::class);

		$token = $request->get('authorization',null);
		$authCheck = $jwt_auth->checkToken($token);

		if ($authCheck) {


			$identity = $jwt_auth->checkToken($token,true);

			//Error comuno no llamar el entity manager 
			$em = $this->getDoctrine()->getManager();
			$task = $em->getRepository('BackendBundle:Tasks')->findOneBy(array(
				'id' => $id
			));

			if ($task && is_object($task) && $identity->sub == $task->getUser()->getId()){
				
				$data = array(
					'status'=>'sucess',
					'code'=> 200,
					'data'=> $task
				);

			}else{

				$data = array(
					'status'=>'error',
					'code'=> 404,
					'msg'=> 'Task not found'
				);

			}

		}else{

			$data = array(
				'status'=>'error',
				'code'=> 400,
				'msg'=> 'authorization no valid'
			);

		}

		return $helpers->json($data);

	}

		//busca y filtrar las tareas

	public function searchAction(request $request, $search = null){

		$helpers = $this->get(Helpers::class);
		$jwt_auth = $this->get(JwtAuth::class);

		$token = $request->get('authorization',null);
		$authCheck = $jwt_auth->checkToken($token);
		

		if ($authCheck){

			//Sacar la identidad del usuario
			$identity = $jwt_auth->checkToken($token,true);

			$em = $this->getDoctrine()->getManager();

			//filtro
			$filter = $request->get('filter',null);
			
			//empty 0 o 1 

			if (empty($filter)) {

				$filter = null;
			}elseif($filter == 1){

				$filter = 'new';
			}elseif($filter == 2){

				$filter = 'todo';
			}else{

				$filter = 'finished';
			}

			//ordenar, Recoger la variable
			$order = $request->get('order',null);

			if(empty($order) || $order == 2){
				$order = 'DESC';
			}else{
				$order = 'ASC';
			}

			//busqueda,

			if ($search != null) {
				$dql = "SELECT t FROM BackendBundle:Tasks t "
					  ."WHERE t.user = $identity->sub AND "
					  ."(t.title LIKE :search OR t.description LIKE :search) ";

			}else{

				$dql = "SELECT t FROM BackendBundle:Tasks t "
						." WHERE t.user = $identity->sub";
			}

			//set filter

			  if ($filter != null) {
			  	$dql .= " AND t.status = :filter ";
			  }

			//set Order
			$dql .= " ORDER BY t.id $order";

			//create query
			$query = $em->createQuery($dql);

			//set parameter filter
			if ($filter != null) {
				$query->setParameter('filter',"$filter");
			}

			// set parameter search
			if (!empty($search)) {
				//"%search%"  decimos que busque una palabra aunque tenga texto por delante y por detras
				$query->setParameter('search',"%$search%");
			}

			
			$tasks = $query->getResult();
			

			$data = array(
				'status'=>"sucess",
				'code'=> 200,
				'data'=> $tasks
			);

		}else{

			$data = array(
				'status'=>"error",
				'code'=> 400,
				'msg'=> "authorization no validation"
			);
		}

		return $helpers->json($data);

	}


		// 
	public function removeAction(request $request, $id = null){

		$helpers = $this->get(Helpers::class);
		$jwt_auth = $this->get(JwtAuth::class);

		$token = $request->get('authorization',null);
		$authCheck = $jwt_auth->checkToken($token);

		if ($authCheck) {


			$identity = $jwt_auth->checkToken($token,true);

			$em = $this->getDoctrine()->getManager();
			$task = $em->getRepository('BackendBundle:Tasks')->findOneBy(array(
				'id' => $id
			));

			if ($task && is_object($task) && $identity->sub == $task->getUser()->getId()){
				
				//borrar objeto y borrar registro
				$em->remove($task);
				$em->flush();	

				$data = array(
					'status'=>"sucess",
					'code'=> 200,
					'data'=> $task
				);
			}
			else{

				$data = array(
					'status'=>"error",
					'code'=> 404,
					'msg'=> "task not found"
				);

			}

		}else{

			$data = array(
				'status'=>"error",
				'code'=> 400,
				'msg'=> "authorization no valid"
			);

		}

		return $helpers->json($data);


	}


}

































?>