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

        if ($user->getName() == 'guest') {
            $location = $this->_app->getBaseUri() . '?c=application&a=login';
        } else {
            $location = $this->_app->getBaseUri() . '?c=user&a=home';
        }
        header('HTTP/1.1 302 Found');
        header('Location: ' . $location);

        return $template;
    }
}
