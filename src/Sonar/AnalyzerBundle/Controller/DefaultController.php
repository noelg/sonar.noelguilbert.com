<?php

namespace Sonar\AnalyzerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class DefaultController extends Controller
{
    
    public function indexAction($name)
    {
        return $this->render('SonarAnalyzerBundle:Default:index.html.twig', array('name' => $name));
    }
}
