<?php

namespace PivotX\BackendBundle\Controller;

use PivotX\BackendBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;


class SiteadminController extends Controller
{
    private function getChecks()
    {
        $siteoptions_service = $this->container->get('pivotx.siteoptions');
        $translation_service = $this->container->get('pivotx.translations');

        $so_checks = $siteoptions_service->findSiteOptions('all', 'config.check');
        $checks    = array();
        foreach($so_checks as $so_check) {
            if ($so_check->getName() == 'any') {
                continue;
            }

            $name      = $so_check->getName();
            $attention = false;

            $description = $translation_service->translate('config.check.'.$name.'.false', null, 'twig');
            if ($so_check->getUnpackedValue() === true) {
                $attention   = $translation_service->translate('config.check.require-attention', null, 'twig');
                $description = $translation_service->translate('config.check.'.$name.'.true', null, 'twig');
            }

            $checks[] = array(
                'name' => ucfirst($name),
                'attention' => $attention,
                'description' => $description
            );
        }

        return $checks;
    }

    public function showStatusAction(Request $request)
    {
        $context = $this->getDefaultHtmlContext();

        $badges = array(
            'important' => 0,
            'warning' => 0
        );

        $context['checks'] = $this->getChecks();
        foreach($context['checks'] as $check) {
            if ($check['attention']) {
                $badges['important']++;
            }
        }

        $context['badges'] = $badges;

        return $this->render('Siteadmin/status.html.twig', $context);
    }

    public function showEntityAction(Request $request)
    {
        $context = $this->getDefaultHtmlContext();

        $entity_name = $this->getRequest()->attributes->get('entity');

        $view = $this->get('pivotx.views')->findView('Backend/findEntities');
        $view->setArguments(array('name'=>$entity_name));

        $entity = $view->getValue();

        $context['entity'] = $entity;

        return $this->render('Entities/entity.html.twig', $context);
    }

    public function rebuildWebresourcesAction()
    {
        /*
        $webresourcer = $this->get('pivotx.webresourcer');
        $outputter    = $this->get('pivotx.outputter');
        */
        $sites = explode("\n", $this->get('pivotx.siteoptions')->getValue('config.sites', '', 'all'));
        foreach($sites as $site) {
            $this->buildWebresources($site, false);
            $this->buildWebresources($site, true);

            /*
            $webresourcer->finalizeWebresources($outputter, false);
            $groups = $outputter->finalizeAllOutputs($site);
            $this->get('pivotx.siteoptions')->set('outputter.groups', json_encode($groups), 'application/json', true, false, $site);
             */
        }

        $url = $this->get('pivotx.routing')->buildUrl('_siteadmin/status');
        return $this->redirect($url);
    }

    public function clearCachesAction($name)
    {
        $path = $this->get('kernel')->getRootDir().'/cache';

        if (is_null($name) || ($name == 'all')) {
            // do nothing
        }
        else {
            $path .= '/' . $name;
        }

        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        foreach($objects as $name => $object){
            if ($object->getFilename() == '..') {
                // ignore
                continue;
            }
            if ($object->isDir()) {
                if (substr($name, -8) == '/cache/.') {
                    echo "$name ignore dir<br/>\n";
                    continue;
                }
                echo "$name<br/>\n";
                //rmdir(dirname($name));
            }
            else if ($object->isFile()) {
                if (substr($object->getFilename(), 0, 1) == '.') {
                    // ignore hidden files
                    echo "$name ignore file<br/>\n";
                    continue;
                }
                echo "$name<br/>\n";
                //@unlink($name);
            }
        }

        die('-');

        $url = $this->get('pivotx.routing')->buildUrl('_siteadmin/status');
        return $this->redirect($url);
    }
}

