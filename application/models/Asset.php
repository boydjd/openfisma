<?php

/**
 * Asset
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    ##PACKAGE##
 * @subpackage ##SUBPACKAGE##
 * @author     ##NAME## <##EMAIL##>
 * @version    SVN: $Id: Builder.php 5441 2009-01-30 22:58:43Z jwage $
 */
class Asset extends BaseAsset
{
    public function postInsert()
    {
        Notification::notify(Notification::ASSET_CREATED, $this, User::currentUser());
    }

    public function postUpdate()
    {
        Notification::notify(Notification::ASSET_MODIFIED, $this, User::currentUser());
    }

    public function postDelete()
    {
        Notification::notify(Notification::ASSET_DELETED, $this, User::currentUser());
    }
}
