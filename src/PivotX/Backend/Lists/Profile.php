<?php

namespace PivotX\Backend\Lists;

use PivotX\Component\Lists\Item;
use PivotX\Component\Lists\RouteItem;
use PivotX\Component\Lists\UrlItem;
use PivotX\Component\Lists\SeparatorItem;

class Profile extends Item
{
    public function __construct($security_context, $user_repository, $siteoptions)
    {
        parent::__construct(get_class($security_context));

        $token = $security_context->getToken();
        $user  = null;
        if (!is_null($token)) {
            $user = $token->getUser();

            $this->setLabel($token->getUsername());
        }

        $this->setAttribute('icon', 'icon-user');
        $this->setAttribute('switched_user', false);
        $this->resetBreadcrumb();

        $this->addItem(new RouteItem('profile', '_profile/home'));

        $current_site = $this->getCurrentSite($security_context, $siteoptions);

        $sites = explode("\n", $siteoptions->getValue('config.sites', array(), 'all'));
        if (count($sites) > 1) {
            $this->addItem(new SeparatorItem());

            foreach($sites as $site) {
                $this->addItem($item = new UrlItem('site: '.$site, '?_switch_site='.rawurlencode($site)));
                if ($site == $current_site) {
                    $item->setAttribute('selected', true);
                }
                else {
                    $item->setAttribute('icon', 'icon');
                }
            }

            $this->addItem(new SeparatorItem());
        }

        /*
        if (!is_null($token)) {
            if ($security_context->isGranted('ROLE_PREVIOUS_ADMIN')) {
                // warn this is not the default user
                $this->setAttribute('switched_user', true);

                $this->addItem(new SeparatorItem());

                $switchmenu = $this->addItem(new UrlItem('switch_profile_back', '?_switch_user=_exit'));
            }
            else if ($security_context->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
                $switchmenu = $this->addItem(new Item('switch_profile'));

                $switchmenu->addItem(new SeparatorItem());

                // @todo this doesn't scale
                $users = $user_repository->findAll();
                foreach($users as $_user) {
                    if ($_user->getUsername() != $token->getUsername()) {
                        //$switchmenu->addItem(new RouteItem($_user->getUsername(), '_profile/home?_switch_user=anke@twokings.nl'));
                        $useritem = $switchmenu->addItem(new UrlItem($_user->getUsername(), '?_switch_user=' . rawurlencode($_user->getUsername())));
                        $useritem->setLabel('As "' . $_user->getUsername() . '"');
                    }
                }

                $switchmenu->setAsItemsHolder();
            }
        }
         */

        $this->addItem(new RouteItem('logout', '_page/logout'));
    }

    /**
     * Get the current site
     */
    private function getCurrentSite($security_context, $siteoptions)
    {
        $sites = explode("\n", $siteoptions->getValue('config.sites', array(), 'all'));

        $token = $security_context->getToken();
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
}
