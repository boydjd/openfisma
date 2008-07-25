<?php
/**
 * blscr.php
 *
 * BLSCR means baseline security control requirements. NIST 800-53 specifies
 * which security controls apply to each level of system: LOW, MODERATE, and HIGH.
 * The baseline requirments specify a greater level of security for systems
 * with a higher impact rating.
 *
 * @package Model
 * @author     Xhorse xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=License
 * @version    $Id$
*/

require_once 'Abstract.php';

/**
 * @package Model
 * @author     Xhorse xhorse at users.sourceforge.net
 * @copyright  (c) Endeavor Systems, Inc. 2008 (http://www.endeavorsystems.com)
 * @license    http://www.openfisma.org/mw/index.php?title=Licensea
 */
class Blscr extends Fisma_Model
{
    protected $_name = 'blscrs';
    protected $_primary = 'code';
    
    /**
     * getList
     *
     * Overrides the parent function in order to enforce sorting on the `code`
     * column, which in most cases will be the most intuitive sort for the end
     * user.
     *
     * If the caller specifies a non-null sort order, then the sort order is
     * not changed.
     *
     * @param string|string[] $fields Which field[s] to get from the BLSCR list
     * @param string $primary_key Which column becomes the key for the returned array (defaults to the primary key of the table)
     * @param string|string[] $order The column[s] to sort on (defaults to `code`)
     *
     * @return array An array of arrays where the primary_key is the outer key and the column names are the inner keys
     */
    public function getList($fields = '*', $primary_key = null, $order=null) {
        if ($order == null) {
            return parent::getList($fields, $primary_key, 'code');
        } else {
            return parent::getList($fields, $primary_key, $order);
        }
    }
}

?>
