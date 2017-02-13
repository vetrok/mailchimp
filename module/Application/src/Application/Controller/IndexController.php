<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Application\Model\Doctrine\Users;
use Application\Model\Mailchimp\MailchimpAPI;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;


class IndexController extends AbstractActionController
{
    /**
     * @var DoctrineORMEntityManager
     */
    protected $em;

    /**
     * @return DoctrineORMEntityManager|array|object
     */
    public function getEntityManager()
    {
        if (null === $this->em) {
            $this->em = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        }
        return $this->em;
    }

    /**
     * Showing subscribers from database
     *
     * @return array|void
     */
    public function indexAction()
    {
        $em = $this->getEntityManager();
        $allUsers = $em->getRepository('\Application\Model\Doctrine\Users')->findAll();

        return new ViewModel(array('users' => $allUsers));
    }

    /**
     * Sync data from API with DB and show it to user
     *
     * @return null
     */
    public function syncAction()
    {
        try {
            $request = $this->getRequest();
            if (!$request->isPost()) {

                throw new \Exception();
            }

            //Get all subscribers from API
            $mailchimpAPIKey = $this->getServiceLocator()->get('Config')['mailChimpAPIKey'];
            $mailchimp = new MailchimpAPI();
            $mailchimp->setAPIKey($mailchimpAPIKey);
            $subscribers = $mailchimp->retrieveAllSubscribersFromAllLists(true);

            //Because the task contains only one customer, delete all records from DB and insert new from API
            $em = $this->getEntityManager();
            $connection = $em->getConnection();
            $platform = $connection->getDatabasePlatform();
            $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0;');
            $truncateSql = $platform->getTruncateTableSQL('users');
            $connection->executeUpdate($truncateSql);
            $connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1;');

            //Insert new users
            if (!empty($subscribers)) {
                foreach ($subscribers as $subscriber) {
                    $user = new Users();
                    $user->setEmail($subscriber['email'] ? : '');
                    $user->setFirstName($subscriber['first_name'] ? : '');
                    $user->setLastName($subscriber['last_name'] ? : '');
                    $em->persist($user);
                    $em->flush();
                }
            }

            //If everything success add message
            $this->flashMessenger()->addSuccessMessage('Synchronized!');
        } catch (\Exception $e) {
            $this->flashMessenger()->addErrorMessage('Can\'t synchronize data. Please try later or contact administrator');
        } finally {
            //Redirect back to users list
            return $this->redirect()->toRoute('home');
        }
    }
}
