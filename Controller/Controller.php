<?php

namespace PivotX\BackendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use PivotX\Component\Webresourcer\DirectoryWebresource;
use PivotX\CoreBundle\Controller\Controller as CoreController;

class Controller extends CoreController
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

    public function getDefaultHtmlContext()
    {
        $context = array(
            'html' => array(
                'title' => 'PivotX',
                'meta' => array(
                    'charset' => 'utf-8'
                )
            )
        );

        $context['backend'] = array(
            'security' => $this->checkLogin($this->getRequest())
        );

        return $context;
    }

    private function runOnce()
    {
        $views = $this->get('pivotx.views');

        $views->registerView(new \PivotX\Component\Views\ArrayView(
            array(
                array(
                    'title' => 'Sitemap',
                    'template' => 'DashboardWidgets/sitemap.html.twig'
                ),
                array(
                    'title' => 'Example',
                    'template' => 'DashboardWidgets/example.html.twig'
                )
            ),
            'Dashboard/getWidgets', 'Backend'
        ));

        $webresourcer = $this->get('pivotx.webresourcer');
        $siteoptions  = $this->get('pivotx.siteoptions');

        $directories = $siteoptions->getValue('webresources.directory');
        foreach($directories as $directory) {
            $webresourcer->addWebresourcesFromDirectory($directory);
        }

        $webresource = $webresourcer->addWebresource(new DirectoryWebresource($siteoptions->getValue('themes.active'), true));
        if ($siteoptions->getValue('themes.debug', false)) {
            $webresource->allowDebugging();
        }
        $webresourcer->activateWebresource($webresource->getIdentifier());

        /*
        $outputter = $this->get('pivotx.outputter');
        $webresourcer->finalizeWebresources($outputter);
         */

        // @todo not hardcoded of course
        $twig_loader = $this->container->get('twig.loader');
        $twig_loader->addPath('/raiddata/2kdata/dev/____users/marcel/px4/src/PivotX/BackendBundle/Resources/themes/backend/twig');


        $topmenu = new \PivotX\Component\Lists\RouteItem('dashboard', '_page/dashboard');

        $contentmenu = $topmenu->addItem(new \PivotX\Component\Lists\Item('content'));
        $contentmenu->setAttribute('icon', 'icon-pencil');
        $contentmenu->resetBreadcrumb();

        $crudmenu = $contentmenu->addItem(new \PivotX\Backend\Lists\CrudTables('editor'));
        $crudmenu->setAsItemsholder();

        $siteadminmenu = $topmenu->addItem(new \PivotX\Backend\Lists\Siteadmin());
        $developermenu = $topmenu->addItem(new \PivotX\Backend\Lists\Developer());

        $this->get('pivotx.lists')->addItem('Backend/Topmenu', $topmenu, false);
    }

    public function render($view, array $parameters = array(), Response $response = null)
    {
        static $once = false;

        if ($once === false) {
            $once = true;

            $this->runOnce();
        }

        return parent::render($view, $parameters, $response);
    }

    public function xyzanyAction(Request $request)
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
