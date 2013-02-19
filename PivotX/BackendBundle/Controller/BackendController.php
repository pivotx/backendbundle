<?php

namespace PivotX\BackendBundle\Controller;

use PivotX\BackendBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;


class BackendController extends Controller
{
    public function showDocumentationAction(Request $request, $name)
    {
        $path = \PivotX\Backend\Lists\Documentation::getPath();

        $documentation = 'This documentation is missing. Our apologies.';

        $filename = $path . '/' . $name . '.md';
        if (file_exists($filename)) {
            $documentation = file_get_contents($filename);
        }

        $format = $this->get('pivotx.formats')->findFormat('md');

        $context = $this->getDefaultHtmlContext();

        $context['documentation'] = $format->format($documentation);

        return $this->render('Documentation/page.html.twig', $context);
    }

    public function showLoginAction()
    {
        $request = $this->getRequest();
        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        }
        else {
            $error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
            $session->remove(SecurityContext::AUTHENTICATION_ERROR);
        }

        $context = $this->getDefaultHtmlContext();

        $context['last_username'] = $session->get(SecurityContext::LAST_USERNAME);
        $context['error']         = $error;

        return $this->render('Core/login.html.twig', $context);
    }

    public function performLoginCheckAction()
    {
        $url = $this->get('pivotx.routing')->buildUrl('_page/dashboard');
        return $this->redirect($url);
    }

    public function performLogoutAction()
    {
        $this->get('security.context')->setToken(null);
        $this->get('request')->getSession()->invalidate();

        $url = $this->get('pivotx.routing')->buildUrl('_page/login');
        return $this->redirect($url);
    }
}
