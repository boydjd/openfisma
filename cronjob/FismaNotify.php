<?php
/**
 * FismaNotify.php
 *
 * @package cronjob
 * @author     Xhorse xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version $Id$
 */

require_once dirname(__FILE__) . '/../paths.php';
require_once APPS . '/basic.php';
import(LIBS, VENDORS, VENDORS . DS . 'Pear');

require_once 'Zend/Mail.php';
require_once 'Zend/Mail/Transport/Smtp.php';
require_once 'Zend/Db/Table.php';
require_once MODELS . DS . 'Abstract.php';
require_once MODELS . DS . 'notification.php';
require_once MODELS . DS .'user.php';
require_once (CONFIGS . DS . 'setting.php');
require_once 'Zend/Date.php';
require_once 'Zend/View.php';

/**
 * The CLI object that responsible for extracting notifications, sending them 
 * and purge them.
 *
 * @package cronjob
 * @author     Xhorse xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 */
class FismaNotify
{
    const EMAIL_VIEW_PATH = '/scripts/mail';
    const EMAIL_VIEW = 'notification.tpl';

    public function __construct()
    {
        Zend_Date::setOptions(array(
            'format_type' => 'php'
        ));
        $db = Zend_DB::factory(Zend_Registry::get('datasource'));
        Zend_Db_Table::setDefaultAdapter($db);
        Zend_Registry::set('db', $db);
        $this->bell();
    }

    /**
     *  Iterat the users and decide to send any message to them
     *  @todo log the email send results
     */
    public function bell()
    {
        $user = new User();
        $ulist = $user->getList(array('id', 'name_first', 'name_last',
                                      'email', 'notify_frequency',
                                      'most_recent_notify_ts'));
        
        foreach ($ulist as $id=>$userData) {
            $sendTime = new Zend_Date($userData['most_recent_notify_ts']);
            $sendTime->add($userData['notify_frequency'], Zend_Date::MINUTE);
                
            if (Zend_Date::now()->isEarlier($sendTime)) {
                continue;
            }

            $mail = new Zend_Mail();
            $contentTpl = new Zend_View();
            $contentTpl->setScriptPath(VIEWS . DS . self::EMAIL_VIEW_PATH);

            //$contentTpl->emailInfo=$emailInfo;
            $mail->setFrom(readSysConfig('sender'), "OpenFISMA");
            $mail->addTo($userData['email'],
                "{$userData['name_first']} {$userData['name_last']}");
            $mail->setSubject(readSysConfig('subject'));

            $notification = new Notification();
            $contentTpl->notifyData = $notification->getEventData($id);
            if (count($contentTpl->notifyData)) {
                $content = $contentTpl->render(self::EMAIL_VIEW);
                $mail->setBodyText($content);
                $mail->send($this->getTransport());
            }

            // Update user's last notify time
            $now = Zend_Date::NOW()->toString('Y-m-d H:i:s');
            $updateData = array('most_recent_notify_ts'=>$now);
            $user->update($updateData, 'id = '.$id);
        }
        $this->purge();
    }

    /** 
     *  Make the instance of proper transport method according to the config
     */
    public function getTransport()
    {
        $transport = null;
        if (readSysConfig('smtp_username') 
            && readSysConfig('smtp_password') 
            && readSysConfig('smtp_host')) {
            $config = array('auth' => 'login',
                'username' => readSysConfig('smtp_username'),
                'password' => readSysConfig('smtp_password'));
            $transport = new Zend_Mail_Transport_Smtp(readSysConfig('smtp_host'),
                $config);
        }
        return $transport;
    }

    /**
     *  Delete obseleted notification
     */
    public function purge()
    {
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
