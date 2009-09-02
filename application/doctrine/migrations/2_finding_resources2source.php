<?php
/**
 * Change the Privilege resource 'finding_sources' to 'source'
 */
Class ModifyResource extends Doctrine_Migration_Base
{
    public function up()
    {
        $privileges = Doctrine::getTable('Privilege')->findByDql('resource = "finding_sources"');
        foreach ($privileges as $privilege) {
            $privilege->resource = 'source';
            $privilege->save();
        }
    }

    public function down()
    {
        $privileges = Doctrine::getTable('Privilege')->findByDql('resource = "source"');
        foreach ($privileges as $privilege) {
            $privilege->resource = 'finding_sources';
            $privilege->save();
        }
    }
}
