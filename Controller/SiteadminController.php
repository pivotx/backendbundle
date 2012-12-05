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

            $description = $translation_service->translate('config.check.'.$name.'.false');
            if ($so_check->getUnpackedValue() === true) {
                $attention   = $translation_service->translate('config.check.require-attention');
                $description = $translation_service->translate('config.check.'.$name.'.true');
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
}

