#!/usr/bin/php
<?php
/**
 * Copyright (c) 2008 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenFISMA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenFISMA.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Jim Chen <xhorse@users.sourceforge.net>
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 * @version   $Id$
 */

require_once dirname(__FILE__) . '/../paths.php';
require_once APPS . '/basic.php';
import(LIBS, VENDORS, VENDORS . DS . 'Pear');

require_once 'Zend/Mail.php';
require_once 'Zend/Mail/Transport/Smtp.php';
require_once 'Zend/Mail/Transport/Sendmail.php';
require_once 'Zend/Db/Table.php';
require_once MODELS . DS . 'Abstract.php';
require_once MODELS . DS . 'notification.php';
require_once MODELS . DS . 'user.php';
require_once (CONFIGS . DS . 'setting.php');
require_once 'Zend/Date.php';
require_once 'Zend/View.php';

/**
 * This class is responsible for scanning for notifications which need to be
 * delivered, delivering the notifications, and then removing the sent
 * notifications from the queue.
 *
 * @package    Cron_Job
 * @subpackage Controller_Subpackage
 * @copyright (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license   http://www.openfisma.org/mw/index.php?title=License
 */
class FismaNotify
{
    const EMAIL_VIEW_PATH = '/scripts/mail';
    const EMAIL_VIEW = 'notification.tpl';

    /**
     * __construct() - Sets up the database connection
     */
    public function __construct() {
        Zend_Date::setOptions(array(
            'format_type' => 'php'
        ));
        $db = Zend_DB::factory(Zend_Registry::get('datasource'));
        Zend_Db_Table::setDefaultAdapter($db);
        Zend_Registry::set('db', $db);
        $this->bell();
    }

    /**
     * bell() - Iterate through the users and check who has notifications
     * pending.
     *
     *  The notification belling is individually. It enumerates all users. 
     *	For each one, do following:
     *  - To determine if his next notification is due, which is controled
     *	  by user's profile "Notify Frequency" setting.
     *  - To fetch all valid events he concerns if the notification is due.
     *	  The valid events are filtered by time. Those happen after his 
     *	  last notification belling time and before now. Please be aware 
     *	  that 'now' here is a snapshot of timestamp when the script starts
     *	  to run. It reduces the complexity introduced by the execution time
     *	  impaction of the script itself.
     *	- To send the mail to recipient.
     *	- To update the last notification belling timestamp if it succeeds.
     * 	- To delete those events that are obsoleted for all the users.
     *
     *  @todo log the email send results
     */
    public function bell() {
        $user = new User();
        $ulist = $user->getList(array('id', 'name_first', 'name_last',
                                      'email', 'email_validate',
                                      'notify_frequency', 'notify_email',
                                      'most_recent_notify_ts'));
        
        $currentTime = Zend_Date::now();
        foreach ($ulist as $id=>$userData) {
            $sendTime = new Zend_Date($userData['most_recent_notify_ts'], "Y-m-d H:i:s");
            $sendTime->add($userData['notify_frequency'], Zend_Date::MINUTE);
            if ($currentTime->isEarlier($sendTime)
                || false == $userData['email_validate']) {
                continue;
            }

            $mail = new Zend_Mail();
            $contentTpl = new Zend_View();
            $contentTpl->setScriptPath(VIEWS . DS . self::EMAIL_VIEW_PATH);

            $mail->setFrom(readSysConfig('sender'), "OpenFISMA");

            if ( !empty($userData['notify_email']) ) {
                $receiveEmail = $userData['notify_email'];
            } else {
                $receiveEmail = $userData['email'];
            }
                $mail->addTo($receiveEmail,
                "{$userData['name_first']} {$userData['name_last']}");
            $mail->setSubject(readSysConfig('subject'));

            $notification = new Notification();
            $contentTpl->notifyData = $notification->getEventData($id,
                $userData['most_recent_notify_ts']);

            if (count($contentTpl->notifyData)) {
                $content = $contentTpl->render(self::EMAIL_VIEW);
                $mail->setBodyText($content);
                $mail->send($this->getTransport());
		// Update user's last notify time
		$now = $currentTime->toString('Y-m-d H:i:s');
		$updateData = array('most_recent_notify_ts'=>$now);
		$user->update($updateData, 'id = '.$id);
            }

        }
        $this->purge();
    }

    /** 
     *  getTransport() - Make the instance of proper transport method according
     *  to the config.
     *
     * @return Zend_Mail_Transport_Smtp|Zend_Mail_Transport_Sendmail
     */
    public function getTransport() {
        $transport = null;
        if ( 'smtp' == readSysConfig('send_type')) {
            $config = array('auth' => 'login',
                'username' => readSysConfig('smtp_username'),
                'password' => readSysConfig('smtp_password'),
                'port' => readSysConfig('smtp_port'));
            $transport = new Zend_Mail_Transport_Smtp(readSysConfig('smtp_host'),
                $config);
        } else {
            $transport = new Zend_Mail_Transport_Sendmail();
        }
        return $transport;
    }

    /**
     *  purge() - Delete obseleted notification
     *
     *	The obselete timestamp is the earliest successfully belling time that 
     *	users have, which makes sure the event is concerned by nobody and can 
     *  be safely deleted.
     */
    public function purge() {
        $user = new User();
        $query = $user->select()->from('users',
            array('min_notify_ts'=>'MIN(most_recent_notify_ts)'));
        $result = $user->fetchRow($query)->toArray();
        $minNotifyTs = $result['min_notify_ts'];

        $where = $user->getAdapter()->quoteInto('timestamp < ?', $minNotifyTs);
        $user->getAdapter()->delete('notifications', $where);
    }
    
}

// start to bell
new FismaNotify();
