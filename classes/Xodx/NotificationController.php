<?php
/**
 * This file is part of the {@link http://aksw.org/Projects/Xodx Xodx} project.
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

/**
 * The NotificationController provides methods to interact with Notification Objects
 */
class Xodx_NotificationController extends Xodx_ResourceController
{
    /**
     *
     * Method returns all tuples of a resource to html template
     * @param $template
     */
    public function showAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');

        $notificationId = $request->getValue('id', 'get');
        $controller = $request->getValue('c', 'get');
        $baseUri = $this->_app->getBaseUri();
        $notificationUri = $baseUri . '?c=' . $controller . '&id=' . $notificationId;

        $nsAair = 'http://xmlns.notu.be/aair#';
        $nsDssn = 'http://purl.org/net/dssn/';
        $nsDssn = 'http://www.w3.org/2005/Atom/';

        $query = 'PREFIX aair: <' . $nsAair . '>' . PHP_EOL;
        $query.= 'PREFIX dssn: <' . $nsDssn . '>' . PHP_EOL;
        $query.= 'PREFIX atom: <' . $nsAtom . '>' . PHP_EOL;
        $query.= 'SELECT ?actor, ?verb ?object ?date ?feed ' . PHP_EOL;
        $query.= 'SELECT ?actor, ?verb ?object ?date ?feed ' . PHP_EOL;
        $query.= 'WHERE { ' . PHP_EOL;
        $query.= '   <' . $objectUri . '> aair:activityActor ?actor ; ' . PHP_EOL;
        $query.= '       aair:activityVerb ?verb ; ' . PHP_EOL;
        $query.= '       aair:activityObject ?object ; ' . PHP_EOL;
        $query.= '       atom:published ?date ; ' . PHP_EOL;
        $query.= '       dssn:activityFeed ?feed . ' . PHP_EOL;
        $query.= '} ';

        $properties = $model->sparqlQuery($query);

        $activityController = $this->_app->getController('Xodx_ActivityController');
        $activities = $activityController->getActivities($objectUri);

        $template->addContent('templates/resourceshow.phtml');
        $template->properties = $properties;
        $template->actor = $properties[0]['actor'];
        $template->verb = $properties[0]['verb'];
        $template->object = $properties[0]['object'];
        $template->date = $properties[0]['date'];
        $template->feed = $properties[0]['feed'];

        $template->activities = $activities;

        return $template;
    }

    /**
     * This action gets the notifications for the specified user
     * @param user (get) the uri of the user who wants to get its notifications
     * @return json representation of the Xodx_Notification objects
     */
    public function getNewAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');

        $userUri = $request->getValue('user', 'get');

        if ($userUri === null) {
            $userController = $this->_app->getController('Xodx_UserController');
            $userUri = $userController->getUser()->getUri();
        }

        $factory = new Xodx_NotificationFactory($this->_app);
        $notifications = $factory->getForUser($userUri);

        $template->disableLayout();
        foreach ($notifications as $uri => $notification) {
            $notifications[$uri] = $notification->toArray();
        }
        $notificationsJson = json_encode($notifications);
        $template->setRawContent($notificationsJson);

        return $template;
    }

    /**
     * This action gets the notifications for the specified user
     * @param user (get) the uri of the user who wants to get its notifications
     * @return json representation of the Xodx_Notification objects
     */
    public function getAllAction ($template)
    {
        $bootstrap = $this->_app->getBootstrap();
        $model = $bootstrap->getResource('model');
        $request = $bootstrap->getResource('request');

        $userUri = $request->getValue('user', 'get');

        if ($userUri === null) {
            $userController = $this->_app->getController('Xodx_UserController');
            $userUri = $userController->getUser()->getUri();
        }

        $factory = new Xodx_NotificationFactory($this->_app);
        $notifications = $factory->getForUser($userUri, false);

        $template->disableLayout();
        foreach ($notifications as $uri => $notification) {
            $notifications[$uri] = $notification->toArray();
        }
        $notificationsJson = json_encode($notifications);
        $template->setRawContent($notificationsJson);

        return $template;
    }
}
