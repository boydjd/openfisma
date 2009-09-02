<?php
Class ModifyLdapConfig extends Doctrine_Migration_Base
{
    public function up()
    {
        $this->addColumn('ldap_config', 'usestarttls', 'boolean');
        $this->renameColumn('ldap_config', 'domainname', 'accountdomainname');
        $this->renameColumn('ldap_config', 'domainshort', 'accountdomainnameshort');
        $this->renameColumn('ldap_config', 'accountfilter', 'accountfilterformat');
        $this->renameColumn('ldap_config', 'accountcanonical', 'accountcanonicalform');
        Doctrine::generateModelsFromYaml(Fisma::getPath('schema'), Fisma::getPath('model'));
    }

    public function down()
    {
        $this->removeColumn('ldap_config', 'usestarttls');
        $this->renameColumn('ldap_config', 'accountdomainname', 'domainname');
        $this->renameColumn('ldap_config', 'accountdomainnameshort', 'domainshort');
        $this->renameColumn('ldap_config', 'accountfilterformat', 'accountfilter');
        $this->renameColumn('ldap_config', 'accountcanonicalform', 'accountcanonical');
        Doctrine::generateModelsFromYaml(Fisma::getPath('schema'), Fisma::getPath('model'));
    }
}
