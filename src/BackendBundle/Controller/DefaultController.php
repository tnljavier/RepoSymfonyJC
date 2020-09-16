<?php

namespace BackendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
    	echo "hola mundo";
    	die();

        //return $this->render('@BackendBundle/Default/index.html.twig');
    }
}
