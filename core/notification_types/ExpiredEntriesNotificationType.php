<?php
namespace Craft\Plugins\Postmaster\NotificationTypes;

use Carbon\Carbon;
use Craft\Postmaster_TransportModel;
use Craft\Postmaster_TransportResponseModel;
use Craft\Postmaster_ExpiredEntriesNotificationTypeSettingsModel;
use Craft\Plugins\Postmaster\Components\BaseNotificationType;

class ExpiredEntriesNotificationType extends BaseNotificationType {
	
	public $name = 'After Entries Expire';

	public $id = 'expiredEntries';

	public function onBeforeSend(Postmaster_TransportModel $model)
	{
		$results = $this->_getEntries();

        // Set $this->totalResults and return false if no results
        if(($this->totalResults = count($results)) == 0)
        {
            // Return false to prevent the notification from sending
            return false;
        }

        $entry = $this->craft()->entries->getEntryById($results[0]['id']);

        $model->senderId = $entry->id;

        $this->notification->parse(array(
        	'entry' => $entry
        ));

        // Return true, as the notification should send
        return true;
	}
	
	public function onSendComplete(Postmaster_TransportResponseModel $model)
	{
	    // Since we want to send notifications to all users we need
	    // to test to see if $this->totalResults is greater than 1.
	    // If saw, trigger the parent notification's marshal() method.
	    // This will ensure the notification fires again until it either
	    // times out, or there are no more users.
	    if($this->totalResults > 1)
	    {
	        $this->notification->marshal();
	    }
	}

	public function getInputHtml(Array $data = array())
	{
		return $this->craft()->templates->render('postmaster/notification_types/expired_entries/fields', $data);	
	}

	public function getSettingsInputHtml(Array $data = array())
	{
		return $this->craft()->templates->render('postmaster/notification_types/expired_entries/settings', $data);	
	}

	public function getSettingsModelClassName()
	{
		return '\Craft\Postmaster_ExpiredEntriesNotificationTypeSettingsModel';
	}

	private function _getEntries()
	{
		$tz = $this->craft()->getTimezone();

        $sendDate = Carbon::now($tz);

        if(!empty($this->settings->elapsedTime))
        {
        	$sendDate->modify($this->settings->elapsedTime);
        }

		$query = $this->craft()->db->createCommand()
			->select('{{entries}}.*, count(notifications.id) as \'count\', notifications.dateCreated as \'lastSent\'')
			->from('entries')
            ->leftJoin('(SELECT * FROM {{postmasternotificationssent}} WHERE {{postmasternotificationssent}}.notificationId = '.$this->notification->id.' ORDER BY dateCreated DESC) notifications', '{{entries}}.id = notifications.senderId')
            ->andWhere('{{entries}}.expiryDate <= :date and {{entries}}.expiryDate is not null', array(
                'date' => $sendDate
            ))
            ->having(array('and', '(lastSent <= :date or lastSent is null)' . (!empty($this->settings->sendTotal) ? ' and count < '.(int) $this->settings->sendTotal : '')), array(
                'date' => $sendDate
            ))
            ->group('{{entries}}.id');

        return $query->queryAll();
	}
}