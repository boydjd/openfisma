<?php
/**
 * Copyright (c) 2010 Endeavor Systems, Inc.
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

/**
 * Add number column and remove 'Enhancement Supplemental Guidance'
 * Update the enhancement nubmer to number column in security control enhancement table
 * 
 * @author     Ben Zheng <ben.zheng@reyosoft.com>
 * @copyright  (c) Endeavor Systems, Inc. 2010 {@link http://www.endeavorsystems.com}
 * @license    http://www.openfisma.org/content/license GPLv3
 * @package    Migration
 */
class Version81 extends Doctrine_Migration_Base
{
    /**
     * The list of the records whose enhancement numbers are withdrawn need to
     * be assigned correct enhancement number.
     * 
     * @var array
     */
    private static $_specialNumbers = array(
        'AC-03' => array(1),
        'AU-02' => array(1, 2),
        'AU-06' => array(2),
        'CP-09' => array(4),
        'CP-10' => array(1),
        'MP-05' => array(1),
        'SA-12' => array(1),
        'SC-13' => array(1),
    );

    /**
     * A few of missing records of security control enhancement
     * 
     * @var array
     */
    private static $_missingEnhancements = array(
        'Rev0_AC_07_E1' => array(
            'number' => 1,
            'level' => 'NONE',
            'description' => "The information system automatically locks the account/node until released by an 
                              administrator when the maximum number of unsuccessful attempts is exceeded.",
            'control' => array('AC-07', 'NIST SP 800-53 Rev. 0')
        ),
        'Rev0_AU_02_E1' => array(
            'number' => 1,
            'level' => 'NONE',
            'description' => "The information system provides the capability to compile audit records from multiple 
                              components throughout the system into a systemwide (logical or physical), 
                              time-correlated audit trail.",
            'control' => array('AU-02', 'NIST SP 800-53 Rev. 0')
        ),
        'Rev0_AU_02_E2' => array(
            'number' => 2,
            'level' => 'NONE',
            'description' => "The information system provides the capability to manage the selection of events to be 
                              audited by individual components of the system.",
            'control' => array('AU-02', 'NIST SP 800-53 Rev. 0')
        ),
        'Rev1_AC_07_E1' => array(
            'number' => 1,
            'level' => 'NONE',
            'description' => "The information system automatically locks the account/node until released by an 
                              dministrator when the maximum number of unsuccessful attempts is exceeded.",
            'control' => array('AC-07', 'NIST SP 800-53 Rev. 1')
        ),
        'Rev1_SC_08_E1' => array(
            'number' => 1,
            'level' => 'HIGH',
            'description' => "The organization employs cryptographic mechanisms to recognize changes to information 
                              during transmission unless otherwise protected by alternative physical measures.",
            'control' => array('SC-08', 'NIST SP 800-53 Rev. 1')
        ),
        'Rev2_AC_07_E1' => array(
            'number' => 1,
            'level'  => 'NONE',
            'description' => "The information system automatically locks the account/node until released by an 
                              administrator when the maximum number of unsuccessful attempts is exceeded.",
            'control' => array('AC-07', 'NIST SP 800-53 Rev. 2')
        ),
        'Rev2_MP_06_E1' => array(
            'number' => 1,
            'level' => 'HIGH',
            'description' => "The organization tracks, documents, and verifies media sanitization and disposal 
                              actions.",
            'control' => array('MP-06', 'NIST SP 800-53 Rev. 2')
        ),
        'Rev2_MP_06_E2' => array(
            'number' => 2,
            'level' => 'HIGH',
            'description' => "The organization periodically tests sanitization equipment and procedures to verify 
                              correct performance.",
            'control' => array('MP-06', 'NIST SP 800-53 Rev. 2')
        ),
        'Rev3_SA_14_E1' => array(
            'number' => 1,
            'level' => 'NONE',
            'description' => "The organization: <ol> <li>Identifies information system components for which 
                              alternative sourcing is not viable; and</li> <li>Employs [<em>Assignment: 
                              organization-defined measures</em>] to ensure that critical security controls for the 
                              information system components are not compromised.</li> </ol>",
            'control' => array('SA-14', 'NIST SP 800-53 Rev. 3')
        ),
        'Rev3_SC_13_E1' => array(
            'number' => 1,
            'level' => 'NONE',
            'description' => "The organization employs, at a minimum, FIPS-validated cryptography to protect 
                              unclassified information.",
            'control' => array('SC-13', 'NIST SP 800-53 Rev. 3'),
        )
    );

    /**
     * Duplicated records in securityControlEnhancement.yml data fixture
     * Fox example: The record of Rev3_SA_12_E1 should be updated to Rev3_SA_14_E1
     *  Rev3_SA_12_E1:
     *      number: 1
     *      Control: Rev3_SA_14
     *      level: NONE
     *      description: >
     * 
     * @var array
     */
    private static $_duplicatedEnhancements = array(
        'Rev3_SA_12_E1' => array(
            'securityControlCode' => 'SA-14',
            'number' => 1,
            'level' => 'NONE',
            'description' => "The organization purchases all anticipated information system components and spares in 
                              the initial acquisition.",
            'control' => array('SA-12', 'NIST SP 800-53 Rev. 3')
        ),
        'Rev3_SC_12_E1' => array(
            'securityControlCode' => 'SC-12',
            'number' => 1,
            'level' => 'HIGH',
            'description' => "The organization maintains availability of information in the event of the loss of 
                              cryptographic keys by users.",
            'control' => array('SC-12', 'NIST SP 800-53 Rev. 3')
        )
     );

    /**
     * Add number column and remove 'Enhancement Supplemental Guidance'
     */
    public function up()
    {
        $this->addColumn(
            'security_control_enhancement',
            'number',
            'integer',
            '2',
            array(
                'default' => NULL,
                'comment' => 'Enhancement number'
            )
        );

        $conn = Doctrine_Manager::connection();

        // Remove the "Enhancement Supplemental Guidance" from the description field
        $updateSql = "UPDATE `security_control_enhancement` SET `description` = CONCAT(RTRIM(LEFT(`description`,"
                   . "LOCATE('<p><u>Enhancement Supplemental Guidance',`description`)-1)), '\n') where `description` "
                   . "like '%Enhancement Supplemental Guidance%'";
        $conn->exec($updateSql);
    }

    /**
     * Use postUp to update enhancement number and add the missing record
     */
    public function postUp()
    {
        // Regenerate models so that we can instantiate new SecurityControlEnhancement objects
        $task = new Doctrine_Task_GenerateModelsYaml();
        $task->setArguments(Zend_Registry::get('doctrine_config'));
        $task->execute();

        // Get securityControlId
        $securityControlIds = Doctrine_Query::CREATE()
                              ->select('securityControlId')
                              ->from('SecurityControlEnhancement')
                              ->groupBy('securityControlId')
                              ->setHydrationMode(Doctrine::HYDRATE_ARRAY)
                              ->execute();

        // Loop through the SeurityControlEnhancement records gotten by securityControlId to update number column.
        $this->_updateEnhancementNumber($securityControlIds);

        // Add the missing records of in security control enhancement table
        $this->_addMissingEnhancement(self::$_missingEnhancements);

        // Update the duplicated records in security control enhancement table
        $this->_updateEnhancement(self::$_duplicatedEnhancements);

        // Change all level value of Rev2_PE_13 from 'HIGH' to 'MODERATE' in security control enhancement table
        $securityControlId = $this->_getSecurityControl(array('PE-13', 'NIST SP 800-53 Rev. 2'))->id;
        $enhancements = Doctrine::getTable('SecurityControlEnhancement')->findBySecurityControlId($securityControlId);
        foreach ($enhancements as $enhancement) {
            $enhancement->level = 'MODERATE';
            $enhancement->save();
        }
    }

    /**
     * Update the enhancement nubmer to number column
     * 
     * @param array $ids
     */
    private function _updateEnhancementNumber($ids)
    {
        if (count($ids) > 0) {
            foreach ($ids as $id) {
                $number = 1;
                $enhancements = Doctrine::getTable('SecurityControlEnhancement')
                                ->findBySecurityControlId($id['securityControlId']);
                foreach ($enhancements as $enhancement) {
                    $this->_setNumber($enhancement->Control->code, $enhancement->Control->Catalog->name, &$number);
                    $enhancement->number = $number;
                    $enhancement->save();
                    $number++;
                }
            }
        }
    }

    /**
     * To assign correct enhancement number
     * 
     * @param string $code The code of security control
     * @param string $catalogName The name of security control catalog
     * @param integer $number The number of security control enhancement
     * 
     * @return integer
     */
    private function _setNumber($code, $catalogName, &$number)
    {
        foreach (self::$_specialNumbers as $key => $value) {
            if ($code == $key && $catalogName == 'NIST SP 800-53 Rev. 3' && in_array($number, $value)) {
                //skip the numbers that have been withdrawn
                $number = max($value) + 1;
            }
        }
    }

    /**
     * Add the missing records of securitycontrol enhancement
     * 
     * @param array $missingEnhancements
     * @return void
     */
    private function _addMissingEnhancement($missingEnhancements)
    {
        foreach ($missingEnhancements as $missingEnhancement) {
            $enhancement = new SecurityControlEnhancement();
            $enhancement->number = $missingEnhancement['number'];
            $enhancement->level = $missingEnhancement['level'];
            $enhancement->description = $missingEnhancement['description'];
            $enhancement->Control = $this->_getSecurityControl($missingEnhancement['control']);
            $enhancement->save();
        }
    }

    /**
     * Update the duplicated records of securitycontrol enhancement
     * 
     * @param array $duplicatedEnhancements
     * @return void
     */
    private function _updateEnhancement($duplicatedEnhancements)
    {
        foreach ($duplicatedEnhancements as $duplicatedEnhancement) {
            $id = $this->_getEnhancementId($duplicatedEnhancement['securityControlCode']);
            $enhancement = new SecurityControlEnhancement();
            $enhancement->assignIdentifier($id);
            $enhancement->number = $duplicatedEnhancement['number'];
            $enhancement->level = $duplicatedEnhancement['level'];
            $enhancement->description = $duplicatedEnhancement['description'];
            $enhancement->Control = $this->_getSecurityControl($duplicatedEnhancement['control']);
            $enhancement->save();
        }
    }

    /**
     * Get foreign key to a security control object in security control table
     * 
     * @param array $misingNumber The missing enhancement number of security control enhancement
     * @return Doctrine_Collection
     */
    private function _getSecurityControl($missingNumber)
    {
        $securityControl = Doctrine_Query::create()
                           ->from('SecurityControl s')
                           ->leftJoin('s.Catalog c')
                           ->where('s.code = ? AND c.name = ?', $missingNumber)
                           ->fetchOne();

       return $securityControl;
    }

    /**
     * Get the first record from required update enhancement
     * 
     * @param string $code The security code is used to find the securityControlEnhancement record id whose
     *                     contents need to be updated.
     * @return string
     */
    private function _getEnhancementId($code)
    {
        $enhancement = Doctrine_Query::create()
                       ->from('SecurityControlEnhancement s')
                       ->innerJoin('s.Control sc')
                       ->innerJoin('sc.Catalog ca')
                       ->where('sc.code = ? AND ca.name = ?', array($code, 'NIST SP 800-53 Rev. 3'))
                       ->orderBy('s.id')
                       ->setHydrationMode(Doctrine::HYDRATE_SCALAR)
                       ->fetchOne();

        return $enhancement['s_id'];
    }

    /**
     * No reverse migration
     */
    public function down()
    {
        throw new Doctrine_Migration_IrreversibleMigrationException();
    }
}
