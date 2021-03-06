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
        $this->rebuildWebresources(true);

        $this->get('session')->setFlash('notice', 'Webresources were rebuild.');

        $url = $this->get('pivotx.routing')->buildUrl('_siteadmin/status');
        return $this->redirect($url);
    }

    public function clearCachesAction($target)
    {
        $path = $this->get('kernel')->getRootDir().'/cache';

        if (is_null($target) || ($target == 'all')) {
            // add nothing
        }
        else {
            $path .= '/' . $target;
        }

        if (!is_dir($path)) {
            $this->get('session')->setFlash('notice', 'No such cache was available.');
        }
        else {
            list($failed_files, $failed_directories, $failed_unknowns, $file_count, $directory_count) = $this->clearDirectoryRecursive($path);

            if ((count($failed_directories) > 0) || (count($failed_files) > 0) || (count($failed_unknowns) > 0)) {
                $this->get('session')->setFlash('error', 'Some cache files were not cleared.');

                $this->get('pivotx.activity')
                    ->administrativeMessage(
                        null,
                        'Failed cleaning cache "<strong>:target</strong>". <strong>:directories</strong> directories, <strong>:files</strong> files and <strong>:unknowns</strong> unknowns were not removed.',
                        array(
                            'target' => $target,
                            'directories' => count($failed_directories),
                            'files' => count($failed_files),
                            'unknowns' => count($failed_unknowns)
                        )
                    )
                    ->notImportant()
                    ->addContext(array(
                        'failed' => array(
                            'directories' => $failed_directories,
                            'files' => $failed_files,
                            'unknowns' => $failed_unknowns
                        )
                    ))
                    ->log()
                    ;
            }
            else {
                $this->get('session')->setFlash('notice', 'Cache was cleared succesfully.');
            }

            $this->get('session')->setFlash('previous_flush', $target);

            $this->get('pivotx.activity')
                ->administrativeMessage(
                    null,
                    'Cleared cache "<strong>:target</strong>". <strong>:directories</strong> directories and <strong>:files</strong> files were succesfully removed.',
                    array(
                        'target' => $target,
                        'directories' => $directory_count,
                        'files' => $file_count,
                    )
                )
                ->notImportant()
                ->log()
                ;
        }

        // IMPORTANT
        // sometimes flashes don't actually work correctly when clearing a cache!

        $siteoptions = $this->get('pivotx.siteoptions');
        $siteoptions->set('config.check.cache', 0, 'x-value/boolean', false, false, 'all');
        $siteoptions->set('config.check.translations', 0, 'x-value/boolean', false, false, 'all');
        $setup = new \PivotX\Component\Siteoptions\Setup($siteoptions);
        $setup->updateConfigCheck();

        $url = $this->get('pivotx.routing')->buildUrl('_siteadmin/status');
        return $this->redirect($url);
    }

    /**
     */
    public function trimActivitiesAction($policy = 'normal')
    {
    }

    /**
     * Special Loggable-feature trimmer
     */
    public function trimLoggableActivitiesAction()
    {
    }
}

