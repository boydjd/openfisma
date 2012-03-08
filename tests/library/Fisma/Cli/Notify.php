<?php
/**
 * Copyright (c) 2011 Endeavor Systems, Inc.
 *
 * This file is part of OpenFISMA.
 *
 * OpenFISMA is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * OpenFISMA is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with OpenFISMA.  If not, see
 * {@link http://www.gnu.org/licenses/}.
 */

require_once(realpath(dirname(__FILE__) . '/../../../Case/Unit.php'));

/**
 * Test suite for /library/Fisma/Cli/Notify.php. Due to the use of Zend_Console_Getopt, this test must be run
 * without any options for phpunit.
 *
 * @author     Duy K. Bui <duy.bui@endeavorsystems.com>
 * @copyright  (c) Endeavor Systems, Inc. 2011 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Test
 * @subpackage Test_Library
 */
class Test_Library_Fisma_Cli_Notify extends Test_Case_Unit
{
    /**
     * setUp
     *
     * @access public
     * @return void
     */
    public function setUp()
    {
        Fisma::setConfiguration(new Fisma_Configuration_Array(), true);

        Fisma::configuration()->setConfig('system_name', 'OpenFISMA');
    }

    /**
     * Test the main function
     * @return void
     */
    public function testProcessNotificationQueue()
    {
        $user = $this->getMock('Mock_Blank', array('updateNotificationTs'));
        $user->expects($this->any())->method('updateNotificationTs');

        $job1 = $this->getMock('Mock_Blank');
        $job1->userId = 1;
        $job1->User = $user;

        $job2 = $this->getMock('Mock_Blank');
        $job2->userId = 1;
        $job2->User = $user;

        $job3 = $this->getMock('Mock_Blank');
        $job3->userId = 2;
        $job3->User = $user;

        $notifications = array($job1, $job2, $job3);

        $query = $this->getMock('Mock_Blank', array('execute'));
        $query->expects($this->any())->method('execute')->will($this->returnValue($notifications));

        $notify = $this->getMock('Fisma_Cli_Notify',
            array(
                'sendNotificationEmail',
                'purgeNotifications',
                'getNotificationQuery'
            )
        );
        $notify->setLog($this->getMock("Zend_Log"));

        //Expect 2 e-mails for 3 jobs as the $job1 and $job2 are for the same user
        $notify->expects($this->exactly(2))->method('sendNotificationEmail');
        $notify->expects($this->exactly(2))->method('purgeNotifications');
        $notify->expects($this->once())->method('getNotificationQuery')->will($this->returnValue($query));
        Fisma::initialize(Fisma::RUN_MODE_TEST);
        $notify->run();
    }

    /**
     * Test the query to getNotification
     *
     * @return void
     * @todo pending on the re-implementation of source method
     */
    public function testQuery()
    {
        $notify = new Fisma_Cli_Notify();
        $notify->setLog($this->getMock("Zend_Log"));
        $query = $notify->getNotificationQuery()->getSql();
        $conditions = 'FROM poc u INNER JOIN notification n on u.id = n.userid '
                     .'WHERE u.type = "User" AND (u.mostrecentnotifyts IS NULL '
                     .'OR u.mostrecentnotifyts <= DATE_SUB(NOW(), INTERVAL u.notifyFrequency HOUR)) '
                     .'AND (u.locked = FALSE OR (u.locked = TRUE AND u.locktype = "manual"))';
        $this->assertContains($conditions, $query);
    }

    /**
     * Test the sending of notifications (in a very simple way)
     *
     * @return void
     */
    public function testSendMail()
    {
        $mail = $this->getMock('Mock_Blank', array('send', 'setMail'));
        $mail->expects($this->once())->method('setMail')->will($this->returnSelf());
        $mail->expects($this->once())->method('send');

        $notify = new Fisma_Cli_Notify();
        $notify->setLog($this->getMock("Zend_Log"));
        $notify->sendNotificationEmail(array(), $mail);
    }

    /**
     * Test the execution (useless, put in to get 100% cover)
     *
     * @return void
     */
    public function testPurgeNotifications()
    {
        $notification = $this->getMock('Mock_Blank', array('delete'));
        $notification->expects($this->once())->method('delete');
        $notify = new Fisma_Cli_Notify();
        $notify->setLog($this->getMock("Zend_Log"));
        $notify->purgeNotifications(array($notification));
    }
}

