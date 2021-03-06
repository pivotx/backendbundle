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

            $context = array(
                'headers' => $request->headers->all(),
                'server' => $request->server->all()
            );

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
                    ->addContext($context)
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
     * Clear a complete directory
     *
     * (no reason it should be here, but for it's ok)
     */
    protected function clearDirectoryRecursive($path)
    {
        $failed_files       = array();
        $failed_directories = array();
        $failed_unknowns    = array();

        $file_count = $directory_count = 0;

        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach($objects as $name => $object) {

            if ($object->getFilename() == '..') {
                // ignore
                continue;
            }
            if ($object->isDir()) {
                if ($name == $path.'/.') {
                    continue;
                }
                if (substr($name, -2) == '/.') {
                    continue;
                }
                if (@rmdir($name)) {
                    $directory_count++;
                }
                else {
                    $failed_directories[] = $name;
                }
            }
            else if ($object->isFile()) {
                if (substr($object->getFilename(), 0, 1) == '.') {
                    // ignore hidden files
                    continue;
                }
                if (@unlink($name)) {
                    $file_count++;
                }
                else {
                    $failed_files[] = $name;
                }
            }
            else {
                $failed_unknowns[] = $name;
            }
        }

        return array($failed_files, $failed_directories, $failed_unknowns, $file_count, $directory_count);
    }

    /**
     * Rebuild all webresource
     */
    protected function rebuildWebresources($clear_old_files = false)
    {
        if ($clear_old_files) {
            $directory = dirname($this->get('kernel')->getRootDir()).'/web/outputter';

            $ignore = $this->clearDirectoryRecursive($directory);
        }

        $sites = explode("\n", $this->get('pivotx.siteoptions')->getValue('config.sites', '', 'all'));
        foreach($sites as $site) {
            $targets = array();
            if ($site == 'pivotx-backend') {
                $targets[] = 'desktop'; // @todo ugly exception and should be removed
            }
            else {
                $_targets = $this->get('pivotx.siteoptions')->getValue('routing.targets', array(), $site);
                $targets  = array_map(function($_target){
                        return $_target['name'];
                    },
                    $_targets);
            }

            foreach($targets as $target) {
                $this->buildWebresources($site, $target, false);
                $this->buildWebresources($site, $target, true);
            }
        }
    }

    /**
     * Overruled version for the backend
     */
    public function getDefaultHtmlContext()
    {
        $context = parent::getDefaultHtmlContext();

        $context['html']['title'] = 'PivotX';
        $context['html']['meta']['charset'] = 'utf-8';

        $messages = array();

        $context['backend'] = array(
            'core_version' => \PivotX\CoreBundle\CoreBundle::VERSION,
            'backend_version' => \PivotX\BackendBundle\BackendBundle::VERSION,
            'security' => $this->checkLogin($this->getRequest()),
            'messages' => false
        );

        // @todo only add current_site when user is allowed to change sites
        $context['backend']['current_site'] = $this->getCurrentSite();

        $siteoptions = $this->container->get('pivotx.siteoptions');
        if ($siteoptions->getValue('config.check.any', false, 'all')) {
            $href = $this->container->get('pivotx.routing')->buildUrl('_siteadmin/status');
            $message = array(
                'importance' => 'very',
                'title' => 'Check configuration',
                'text' => new \Twig_Markup('The configuration is out of date. Check the <a href="'.$href.'">status</a> page.', 'utf-8')
            );

            $routing    = $this->container->get('pivotx.routing');
            $routematch = $routing->getLatestRouteMatch();
            if (!is_null($routematch)) {
                if ($routematch->buildReference()->buildLocalTextReference() == '_siteadmin/status') {
                    // if we ARE on the status page, then don't show the message
                    $message['text'] = 'The configuration is out of date. Check the page below.';
                }
            }

            $messages[] = $message;
        }

        if (count($messages) > 0) {
            $context['backend']['messages'] = $messages;
        }

        $context['siteoptions'] = array();
        if ($siteoptions->getValue('translations.debug', false) === true) {
            $context['siteoptions']['translations_debug'] = true;
        }
        //var_dump($context);

        return $context;
    }

    /**
     * Get the current site
     */
    protected function getCurrentSite()
    {
        $siteoptions = $this->get('pivotx.siteoptions');
        $sites = explode("\n", $siteoptions->getValue('config.sites', '', 'all'));

        $token = $this->get('security.context')->getToken();
        $user  = null;
        if (!is_null($token)) {
            $user = $token->getUser();
        }

        $_current_site = null;
        if (!is_null($user)) {
            $settings = $user->getSettings();
            if (isset($settings['backend.current_site'])) {
                $_current_site = $settings['backend.current_site'];
            }
        }

        $current_site = null;
        foreach($sites as $site) {
            if ($site == $_current_site) {
                $current_site = $site;
            }
        }

        if (is_null($current_site)) {
            $current_site = $sites[0];
        }

        return $current_site;
    }

    /**
     * Set the current site for the user
     */
    protected function setCurrentSite($current_site)
    {
        $siteoptions = $this->get('pivotx.siteoptions');
        $sites = explode("\n", $siteoptions->getValue('config.sites', array(), 'all'));

        $token = $this->get('security.context')->getToken();
        $user  = null;
        if (!is_null($token)) {
            $user = $token->getUser();
        }

        $_current_site = null;
        if (!is_null($user)) {
            $settings = $user->getSettings();
            $settings['backend.current_site'] = $current_site;
            $user->setSettings($settings);

            $this->get('doctrine')->getEntityManager()->persist($user);
            $this->get('doctrine')->getEntityManager()->flush();
        }
    }

    /**
     * Overruled runOnce
     */
    protected function runOnce()
    {
        $stopwatch = $this->container->get('debug.stopwatch', \Symfony\Component\DependencyInjection\ContainerInterface::NULL_ON_INVALID_REFERENCE);
        $sw        = null;
        if (!is_null($stopwatch)) {
            $sw = $stopwatch->start('backend runonce', 'controller');
        }

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


        // top menu
        $topmenu = new \PivotX\Component\Lists\RouteItem('dashboard', '_page/dashboard');
        $contentmenu = $topmenu->addItem(new \PivotX\Backend\Lists\Content($this->get('pivotx.siteoptions')));
        $siteadminmenu = $topmenu->addItem(new \PivotX\Backend\Lists\Siteadmin());
        $developermenu = $topmenu->addItem(new \PivotX\Backend\Lists\Developer());
        $documentationmenu = $topmenu->addItem(new \PivotX\Backend\Lists\Documentation());
        $this->get('pivotx.lists')->addItem('Backend/Topmenu', $topmenu, false);

        // @todo ideally we should be able to grab a part of the menu in {% loadList(..) %}
        //       then we don't have to define it twice
        $documentationmenu = new \PivotX\Backend\Lists\Documentation();
        $this->get('pivotx.lists')->addItem('Backend/Documentation', $documentationmenu, false);

        // profile menu
        $repository = $this->get('doctrine')->getRepository('PivotX\CoreBundle\Entity\User');
        $profilemenu = new \PivotX\Component\Lists\RouteItem('dashboard', '_page/dashboard');
        $item = $profilemenu->addItem(new \PivotX\Backend\Lists\Profile($this->get('security.context'), $repository, $this->get('pivotx.siteoptions')));
        $this->get('pivotx.lists')->addItem('Backend/Profilemenu', $profilemenu, false);

        if ($this->getRequest()->query->has('_switch_site')) {
            $this->setCurrentSite($this->getRequest()->query->get('_switch_site'));

            $routing    = $this->get('pivotx.routing');
            $routematch = $routing->getLatestRouteMatch();
            if (!is_null($routematch)) {
                $url = $routing->buildUrl($routematch->buildReference()->buildTextReference());
            }
            else {
                $url = $routing->buildUrl('_page/dashboard');
            }

            if (!is_null($sw)) {
                $sw->stop();
            }

            return $this->redirect($url);
        }

        if (!is_null($sw)) {
            $sw->stop();
        }

        return parent::runOnce();
    }

    /**
     * The default anyAction of the backend, inserts parameters
     */
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

        $parameters = $this->getDefaultHtmlContext();

        return $this->render(null, $parameters);
    }
}
