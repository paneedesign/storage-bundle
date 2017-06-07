<?php

namespace PaneeDesign\StorageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('PedStorageBundle:Default:index.html.twig');
    }
}
