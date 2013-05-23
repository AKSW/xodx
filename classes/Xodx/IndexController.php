<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class Xodx_IndexController extends Saft_Controller
{
    public function indexAction ($template) {
        $userController = $this->_app->getController('Xodx_UserController');
        $user = $userController->getUser();

        $template->disableLayout();
        $template->setRawContent('');

        $location = new Saft_Url($this->_app->getBaseUri());

        if ($user->getName() == 'guest') {
            $location->setParameter('c', 'application');
            $location->setParameter('a', 'login');
        } else {
            $location->setParameter('c', 'user');
            $location->setParameter('a', 'home');
        }

        $template->redirect($location);

        return $template;
    }
}
