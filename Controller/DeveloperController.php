<?php

namespace PivotX\BackendBundle\Controller;

use PivotX\BackendBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class DeveloperController extends Controller
{
    public static function cmpRoutePrefixes($a, $b)
    {
        $af = $a->getFilter();
        $bf = $b->getFilter();
        $ret = strcasecmp($af['site'], $bf['site']);
        if ($ret != 0) {
            return $ret;
        }
        $ret = strcasecmp($af['target'], $bf['target']);
        if ($ret != 0) {
            return $ret;
        }
        $ret = strcasecmp($af['language'], $bf['language']);
        if ($ret != 0) {
            return $ret;
        }
        return strcasecmp($a->getPrefix(), $b->getPrefix());
    }

    public static function cmpRoute($a, $b)
    {
        $af = $a->getFilter();
        $bf = $b->getFilter();
        if ((count($af['site']) == 0) && (count($bf['site']) > 0)) {
            return -1;
        }
        if ((count($af['site']) > 0) && (count($bf['site']) == 0)) {
            return +1;
        }
        if ((count($af['target']) == 0) && (count($bf['target']) > 0)) {
            return -1;
        }
        if ((count($af['target']) > 0) && (count($bf['target']) == 0)) {
            return +1;
        }
        if ((count($af['language']) == 0) && (count($bf['language']) > 0)) {
            return -1;
        }
        if ((count($af['language']) > 0) && (count($bf['language']) == 0)) {
            return +1;
        }
        if ((count($af['site']) > 0) && (count($bf['site']) > 0)) {
            $ret = strcasecmp($af['site'][0], $bf['site'][0]);
            if ($ret != 0) {
                return $ret;
            }
        }
        if ((count($af['target']) > 0) && (count($bf['target']) > 0)) {
            $ret = strcasecmp($af['target'][0], $bf['target'][0]);
            if ($ret != 0) {
                return $ret;
            }
        }
        if ((count($af['language']) > 0) && (count($bf['language']) > 0)) {
            $ret = strcasecmp($af['language'][0], $bf['language'][0]);
            if ($ret != 0) {
                return $ret;
            }
        }
        $ret = strcasecmp($a->getEntity(), $b->getEntity());
        if ($ret != 0) {
            return $ret;
        }
        return strcasecmp($a->getEntityFilter(), $b->getEntityFilter());
    }

    public function showRoutingAction(Request $request)
    {
        $html = array(
            'language' => 'en',
            'meta' => array(
                'charset' => 'utf-8',
            ),
            'title' => 'PivotX back-end'
        );

        $prefixeses = $this->get('pivotx.routing')->getRouteSetup()->getRoutePrefixeses();
        $prefixes   = array();
        foreach($prefixeses as $p) {
            $prefixes = array_merge($prefixes, $p->getAll());
        }
        // @todo we shouldn't do this when we have priorities
        usort($prefixes, array(get_class($this), 'cmpRoutePrefixes'));

        $collections = $this->get('pivotx.routing')->getRouteSetup()->getRouteCollections();
        $routes      = array();
        foreach($collections as $c) {
            $routes = array_merge($routes, $c->getAll());
        }
        // @todo we shouldn't do this when we have priorities
        usort($routes, array(get_class($this), 'cmpRoute'));

        $context = array(
            'html' => $html,
            'prefixes' => new \PivotX\Component\Views\ArrayView($prefixes, 'Developing/Routing/Prefixes', 'PivotX/Devend', 'Dynamic view to show the routeprefixes'),
            'routes' => new \PivotX\Component\Views\ArrayView($routes, 'Developing/Routing/Routes', 'PivotX/Devend', 'Dynamic view to show the routes')
        );

        return $this->render('Developer/routing.html.twig', $context);
    }

    public static function cmpViews($a, $b)
    {
        $ret = strcasecmp($a->getGroup(),$b->getGroup());
        if ($ret != 0) {
            return $ret;
        }

        return strcasecmp($a->getName(),$b->getName());
    }

    public static function cmpTags($a, $b)
    {
        $ret = strcasecmp($a['group'], $b['group']);
        if ($ret != 0) {
            return $ret;
        }
        return strcasecmp($a['name'], $b['name']);
    }

    protected function getTagsFromViews($views, $tag_args)
    {
        $tags = array();
        $tag_dups = array();
        foreach($views as $view) {
            foreach($view->getTags() as $_tag) {
                if (!isset($tag_dups[$_tag])) {
                    $tag = array();

                    $tag['group']   = (substr($_tag,0,6) == 'return') ? 'result' : 'entity';
                    $tag['name']    = $_tag;
                    $tag['checked'] = (isset($tag_args['tag'.$_tag])) ? true : false;

                    $tags[] = $tag;

                    $tag_dups[$_tag] = true;
                }
            }
        }
        usort($tags, array(get_class($this), 'cmpTags'));

        return $tags;
    }

    protected function filterViews($views, $tag_args)
    {
        $tags = array();
        foreach($tag_args as $tag_arg => $value) {
            if (substr($tag_arg,0,3) == 'tag') {
                $tags[] = substr($tag_arg, 3);
            }
        }

        if (count($tags) == 0) {
            return $views;
        }

        $filtered = array();
        $cnt_tags = count($tags);
        foreach($views as $view) {
            $view_tags = $view->getTags();

            $add = true;
            foreach($tags as $tag) {
                if (!in_array($tag, $view_tags)) {
                    $add = false;
                    break;
                }
            }
            if ($add) {
                $filtered[] = $view;
            }
        }

        return $filtered;
    }

    public function showViewsAction(Request $request)
    {
        $html = array(
            'language' => 'en',
            'meta' => array(
                'charset' => 'utf-8',
            ),
            'title' => 'PivotX back-end'
        );

        $all_views = $this->get('pivotx.views')->getRegisteredViews();
        usort($all_views, array(get_class($this), 'cmpViews'));

        $tags = $this->getTagsFromViews($all_views, $request->query->all());

        $views = $this->filterViews($all_views, $request->query->all());

        $context = array(
            'html' => $html,
            'views' => new \PivotX\Component\Views\ArrayView($views, 'Developing/Views', 'Dynamic view to show all views'),
            'tags' => new \PivotX\Component\Views\ArrayView($tags, 'Developing/Views/Tags', 'Dynamic view to show all tags of the views')
        );

        return $this->render('Developer/views.html.twig', $context);
    }

    public static function cmpFormats($a, $b)
    {
        $ret = strcasecmp($a->getGroup(),$b->getGroup());
        if ($ret != 0) {
            return $ret;
        }

        return strcasecmp($a->getName(),$b->getName());
    }

    public function showFormatsAction(Request $request)
    {
        $html = array(
            'language' => 'en',
            'meta' => array(
                'charset' => 'utf-8',
            ),
            'title' => 'PivotX back-end'
        );

        $formats = $this->get('pivotx.formats')->getRegisteredFormats();

        usort($formats, array(get_class($this), 'cmpFormats'));

        $context = array(
            'html' => $html,
            'items' => new \PivotX\Component\Views\ArrayView($formats, 'Developing/Formats', 'Dynamic view to show all formats')
        );

        return $this->render('Developer/formats.html.twig', $context);
    }

    public function showSiteAction(Request $request)
    {
        $context = $this->getDefaultHtmlContext();

        $formats = $this->get('pivotx.formats')->getRegisteredFormats();

        usort($formats, array(get_class($this), 'cmpFormats'));

        return $this->render('Developer/site.html.twig', $context);
    }
}

