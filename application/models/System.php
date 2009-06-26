<?php
/**
 * System
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5441 2009-01-30 22:58:43Z jwage $
 */
class System extends BaseSystem
{
    /**
     * Map the values to Organization table
     */
    public function construct()
    {
        $this->mapValue('organizationid');
        $this->mapValue('name');
        $this->mapValue('nickname');
        $this->mapValue('description');
    }

    /**
     * set the mapping value 'organizationid'
     *
     * @param int $id
     */
    public function setOrganizationid($id)
    {
        $this->set('organizationid', $id);
    }

    /**
     * set the mapping value 'name'
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->set('name', $name);
        // if the object hasn't identity,
        // then we think it is under the insert status.
        // otherwise it is update status
        if (empty($this->Organization[0]->id)) {
            $this->state(Doctrine_Record::STATE_TDIRTY);
        } else {
            $this->state(Doctrine_Record::STATE_DIRTY);
        }
    }
    
    /**
     * set the mapping value 'nickname'
     *
     * @param string $nickname
     */
    public function setNickname($nickname)
    {
        $this->set('nickname', $nickname);
        // if the object hasn't identity,
        // then we think it is under the insert status.
        // otherwise it is update status
        if (empty($this->Organization[0]->id)) {
            $this->state(Doctrine_Record::STATE_TDIRTY);
        } else {
            $this->state(Doctrine_Record::STATE_DIRTY);
        }
    }
    
    /**
     * set the map value 'description'
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->set('description', $description);
        // if the object hasn't identity,
        // then we think it is under the insert status.
        // otherwise it is update status
        if (empty($this->Organization[0]->id)) {
            $this->state(Doctrine_Record::STATE_TDIRTY);
        } else {
            $this->state(Doctrine_Record::STATE_DIRTY);
        }
    }

    /**
     * Confidentiality, Integrity, Availability
     */
    const CIA_HIGH = 'high';
    
    /**
     * Confidentiality, Integrity, Availability
     */
    const CIA_MODERATE = 'moderate';
    
    /**
     * Confidentiality, Integrity, Availability
     */
    const CIA_LOW = 'low';
    
    /**
     * Only confidentiality can have 'NA'
     */
    const CIA_NA = 'na';

    /**
     * A mapping from the physical system types to proper English terms
     */
    private $_typeMap = array(
        'gss' => 'General Support System',
        'major' => 'Major Application',
        'minor' => 'Minor Application'
    );
    
    /**
     * Return the English version of the orgType field
     */
    public function getTypeLabel() {
        return $this->_typeMap[$this->type];
    }
    
    /**
     * Calculate FIPS-199 Security categorization.
     *
     * The calculation over enumeration fields {LOW, MODERATE, HIGH} is tricky here. The algorithm 
     * is up to their mapping value, which is decided by the appear consequence in TABLE definition.
     * For example, in case `confidentiality` ENUM('NA','LOW','MODERATE','HIGH') it turns out the 
     * mapping value: LOW=0, MODERATE=1, HIGH=2. The value calculated is the maximum of C, I, A. And 
     * is transferred back to enumeration name again.
     * 
     * @return string
     */
    public function fipsSecurityCategory()
    {
        $confidentiality = $this->confidentiality;
        $integrity = $this->integrity;
        $availability = $this->availability;
        
        $array = $this->getTable()->getEnumValues('confidentiality');
        $confidentiality = array_search($confidentiality, $array) - 1;
        
        $array = $this->getTable()->getEnumValues('integrity');
        $integrity = array_search($integrity, $array);
        
        $array = $this->getTable()->getEnumValues('availability');
        $availability = array_search($availability, $array);

        $index = max((int)$confidentiality, (int)$integrity, (int)$availability);
        return $array[$index];
    }
    
}
