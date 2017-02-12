<?php

namespace Application\Model\Mailchimp;

/**
 * Class MailchimpAPI
 * @package Application\Model\Mailchimp
 *
 * Class Retrieves data from Mailchimp API
 */
class MailchimpAPI
{
    /**
     * Instance for Mailchimp SDK
     * @var null|\sammaye\mailchimp\Chimp
     */
    public $sdkClient = null;

    public function __construct()
    {
        $this->setSdkClient(new \sammaye\mailchimp\Chimp());
    }

    /**
     * Mailchimp account has many Lists, get them all
     */
    public function retrieveAllLists()
    {
        return $this->getSDKClient()->get('/lists')->lists;
    }

    /**
     * Retrieves all subscribers from all Lists attached to Mailchimp account
     * to disable subscribers with same emails - pass true param to $unique
     *
     * @param bool $unique
     * @return array|null
     */
    public function retrieveAllSubscribersFromAllLists($unique = false)
    {
        $allLists = $this->retrieveAllLists();
        if (empty($allLists)) {

            return null;
        }

        $allSubscribers = [];
        $subscribersCounter = 0;
        foreach ($allLists as $singleList) {
            $subscribersFromSingleList = $this->getSDKClient()->get('/lists/' . $singleList->id . '/members')->members;
            if (empty($subscribersFromSingleList)) {

                continue;
            }

            foreach ($subscribersFromSingleList as $subscriber) {
                $allSubscribers[$subscribersCounter][$this->getSubscriberLabels()['email']] = $subscriber->email_address;
                $allSubscribers[$subscribersCounter][$this->getSubscriberLabels()['first_name']] = $subscriber->merge_fields->FNAME;
                $allSubscribers[$subscribersCounter][$this->getSubscriberLabels()['last_name']] = $subscriber->merge_fields->LNAME;
                $subscribersCounter++;
            }
        }

        if ($unique !== false) {

            $allSubscribers = $this->uniqueMultidimensionalArray($allSubscribers, $this->getSubscriberLabels()['email']);
        }

        return $allSubscribers;
    }

    /**
     * Set secret API key for an account
     *
     * @param $apiKey
     */
    public function setAPIKey($apiKey)
    {
        $this->sdkClient->apikey = $apiKey;
    }

    public function getSDKClient()
    {
        return $this->sdkClient;
    }

    /**
     * @param null|\sammaye\mailchimp\Chimp $sdkClient
     */
    public function setSDKClient($sdkClient)
    {
        $this->sdkClient = $sdkClient;
    }

    /**
     * Helper method to delete unique values in multidimensional array by key
     *
     * @param $array
     * @param $key
     * @return array
     */
    private function uniqueMultidimensionalArray($array,$key)
    {
        $temp_array = array();

        foreach ($array as &$v) {
            if (!isset($temp_array[$v[$key]]))

                $temp_array[$v[$key]] =& $v;
        }

        $array = array_values($temp_array);

        return $array;
    }

    /**
     * Labels for output array with subscribers, to prevent labels duplication
     *
     * @return array
     */
    public function getSubscriberLabels(){
        return [
            'email' => 'email',
            'first_name' => 'first_name',
            'last_name' => 'last_name',
        ];
    }
} 