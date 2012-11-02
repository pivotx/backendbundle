<?php

namespace PivotX\BackendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PivotX\Component\Webresourcer\DirectoryWebresource;

class Controller extends \Symfony\Bundle\FrameworkBundle\Controller\Controller
{
    /**
     * Naieve implementation of logged in/out
     * 
     * @todo should be replaced by a proper one (at a different location probably)
     */
    protected function checkLogin(Request $request)
    {
        $session = $request->getSession();
        $context = $this->get('security.context');
        $token   = $context->getToken();

        $currently_logged = $session->has('security.logged') && ($session->get('security.logged') === true);

        $parameters = array();
        $parameters['logged'] = !is_null($token);
        if (!is_null($token)) {
            $parameters['username'] = $token->getUsername();
        }

        if ($parameters['logged'] != $currently_logged) {
            $activity = $this->get('pivotx.activity');

            if (!$currently_logged) {
                $activity
                    ->administrativeMessage(
                        null,
                        'User with name <strong>:name</strong> signed in from <strong>:client_ip</strong>.',
                        array(
                            'name' => $parameters['username'],
                            'client_ip' => $request->getClientIp()
                        )
                    )
                    ->log();

                $user_id = null;

                $repository = $this->get('doctrine')->getRepository('PivotX\CoreBundle\Entity\User');
                $user = $repository->findOneBy(array('email' => $parameters['username']));
                if (!is_null($user)) {
                    $user_id = $user->getId();

                    $user->setDateLastLogin(new \DateTime());
                    $this->get('doctrine')->getEntityManager()->persist($user);
                    $this->get('doctrine')->getEntityManager()->flush();
                }


                $session->set('security.logged', $parameters['logged']);
                $session->set('security.username', $parameters['username']);
                $session->set('security.user_id', $user_id);
            }
            /*
            else {
                $activity->administrativeMessage(null, 'User with name ":name" signed out.', array('name'=>$session->get('security.username')))->log();

                $session->remove('security.logged');
                $session->remove('security.username');
            }
            */

        }

        return $parameters;
    }

    /**
     * @todo set the current user into the activity service
     */
    /*
    protected function setActivityUser()
    {
        echo 'setting activitt user';
        $session = $this->getRequest()->getSession();

        $user_id = null;
        if (!is_null($session) && ($session->has('security.logged') && ($session->get('security.logged') === true))) {
            $user_id = $session->get('security.user_id');
        }

        $activityservice = $this->get('pivotx.activity');
        if (!is_null($activityservice)) {
            $activityservice->setUserId($user_id);
            echo 'setting';
        }

        var_dump($user_id);
    }
*/

    public function render($view, array $parameters = array(), Response $response = null)
    {
        //$this->setActivityUser();

        if (is_null($view)) {
            $request = $this->getRequest();
            $view    = $request->get('_view');
        }
        if (is_null($view)) {
            $view = 'BackendBundle:Core:unconfigured.html.twig';
        }

        if (!isset($parameters['backend'])) {
            $parameters['backend'] = array();
        }
        if (!isset($parameters['backend']['title'])) {
            $parameters['backend']['title'] = 'PivotX';
        }
        if (!isset($parameters['backend']['meta'])) {
            $parameters['backend']['meta'] = array();
        }
        if (!isset($parameters['backend']['meta']['charset'])) {
            $parameters['backend']['meta']['charset'] = 'utf-8';
        }

        $parameters['backend']['security'] = $this->checkLogin($this->getRequest());

        static $once = false;

        if ($once === false) {
            $once = true;

            $webresourcer = $this->get('pivotx.webresourcer');

            $webresourcer->addWebresource(new DirectoryWebresource('@BackendBundle/Resources/lib/jquery'));
            $webresourcer->addWebresource(new DirectoryWebresource('@BackendBundle/Resources/lib/jquery-ui'));
            $webresourcer->addWebresource(new DirectoryWebresource('@BackendBundle/Resources/lib/jquery-fileupload'));
            $webresourcer->addWebresource(new DirectoryWebresource('@BackendBundle/Resources/lib/google-code-prettify'));
            $webresourcer->addWebresource(new DirectoryWebresource('@BackendBundle/Resources/lib/redactor'));
            $webresourcer->addWebresource(new DirectoryWebresource('@BackendBundle/Resources/lib/bootstrap/webresource_js.json'));

            $webresource = new DirectoryWebresource('@BackendBundle/Resources/public');
            $webresource->allowDebugging();
            $webresourcer->addWebresource($webresource);

            $outputter = $this->get('pivotx.outputter');
            $webresourcer->finalizeWebresources($outputter);
        }

        if (is_array($view)) {
            foreach($view as $_view) {
                try {
                    return parent::render($_view, $parameters, $response);
                }
                catch (\InvalidArgumentException $e) {
                }
            }
            throw new \InvalidArgumentException('Cannot find any of the given templates.');
        }

        return parent::render($view, $parameters, $response);
    }

    public function anyAction(Request $request)
    {
/*
            echo 'hier';

            $factory = $this->get('security.encoder_factory');
            $user = new \PivotX\CoreBundle\Entity\User();

            $encoder = $factory->getEncoder($user);
            $password = $encoder->encodePassword('jsif943', 'bc4c1b405d29d1bf0763c123ad0a498f');

            echo 'pwd[ ' .$password. ' ]';
//*/

        return $this->render(null);
    }
}
