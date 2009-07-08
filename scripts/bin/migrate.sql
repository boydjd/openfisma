SET FOREIGN_KEY_CHECKS = 0;

select 'account_log';
truncate table account_log;
insert into account_log
select
    id,
    `timestamp`,
    user_id, 
    ip,
    #todo
    lower(event),
    message
from ofdbname.account_logs;

select 'asset';
truncate table asset;
insert into asset
select
    id,
    create_ts,
    create_ts,
    name,
    lower(source),
    address_ip,
    address_port,
    prod_id, 
    system_id, #this becomes org ID, so I will ensure that sysId=orgId later, otherwise the logic is complex
    network_id
from ofdbname.assets;

select 'audit_log';
truncate table audit_log;
insert into audit_log
select
    id,
    `timestamp`,
    description,
    user_id,
    poam_id
from ofdbname.audit_logs;

select 'configuration step 1';
truncate table configuration;
insert into configuration
select
    id, 
    `key`,
    `value`,
    description
from ofdbname.configurations
where id NOT between 18 and 20;

select 'configuration step 2';
insert into configuration
select
    id, 
    `key`,
    description,
    `value`
from ofdbname.configurations
where id between 18 and 20;

select 'email_validation';
truncate table email_validation;
insert into email_validation
select
    id, 
    email,
    validate_code,
    user_id
from ofdbname.validate_emails;

select 'evaluation';
# this one is tricky... just build it from scratch
truncate table evaluation;
insert into evaluation values
(1, 'Evidence Awaiting ISSO Approval', 'EV ISSO', 2, 0, 'evidence', 17, 25),
(2, 'Evidence Awaiting IV&amp;V Approval', 'EV IV&amp;V', null, 1, 'evidence', 19, 26),
(4, 'Mitigation Strategy Awaiting ISSO Approval', 'MS ISSO', 5, 0, 'action', 15, 24),
(5, 'Mitigation Strategy Awaiting IV&amp;V Approval', 'MS IV&amp;V', null, 2, 'action', 53, 92);
    
select 'event';
truncate table event;
delete from ofdbname.events where id=54 limit 1;
insert into event
select
    id, 
    name,
    function_id
from ofdbname.events;

select 'evidence';
truncate table evidence;
insert into evidence
select
    id, 
    submit_ts,
    submission,
    poam_id,
    submitted_by
from ofdbname.evidences;

select 'finding';
truncate table finding;
insert into finding
select
    id, 
    create_ts,
    modify_ts,
    discover_ts,
    close_ts,
    now(), #todo next due date
    legacy_finding_id,
    type,
    status,
    null, #todo current evaluation id
    finding_data,
    action_suggested,
    action_planned,
    action_resources,
    action_est_date, #todo ECD
    1, #todo ECD locked
    threat_source,
    threat_level,
    cmeasure,
    cmeasure_effectiveness,
    duplicate_poam_id,
    system_id, # I will make sure that sysId = orgID
    asset_id,
    source_id,
    1, #todo lookup 
    created_by,
    null,
    upload_id
from ofdbname.poams;

select 'finding_evaluation';
truncate table finding_evaluation;
insert into finding_evaluation
select
    id, 
    date,
    group_id, #todo finding id
    group_id, #todo evidence id
    eval_id,
    decision,
    user_id,
    'sample comment' #todo comment
from ofdbname.poam_evaluations;

select 'ldap_config';
truncate table ldap_config;
insert into ldap_config
select
    id, 
    host,
    port,
    domain_name,
    domain_short,
    username,
    password,
    basedn,
    account_filter, 
    account_canonical,
    bind_requires_dn,
    use_ssl
from ofdbname.ldap_config;

select 'network';
truncate table network;
insert into network
select
    id,
    name,
    nickname,
    `desc`
from ofdbname.networks;

select 'notification';
truncate table notification;
insert into notification
select
    id, 
    `timestamp`,
    event_text,
    event_id,
    user_id
from ofdbname.notifications;

# org and system must be handled by a script

select 'plugin';
truncate table plugin;
insert into plugin
select
    id, 
    name,
    class,
    `desc`
from ofdbname.plugins;

select 'privilege';
truncate table privilege;
insert into privilege
select
    id,
    screen,
    action,
    `desc`,
    0
from ofdbname.functions;

# manually set the orgspecific flag
#todo double check these
update privilege set orgspecific = 1 
where id between 2 and 30
or id in (90,91);

select 'product';
truncate table product;
insert into product
select
    id,
    vendor,
    name,
    version,
    cpe_name
from ofdbname.products;

select 'role';
truncate table role;
insert into role
select
    id,
    now(),
    now(),
    name,
    nickname,
    `desc`
from ofdbname.roles;

select 'role_privilege';
truncate table role_privilege;
insert into role_privilege
select
    role_id,
    function_id
from ofdbname.role_functions;

select 'security_control';
truncate table security_control;
insert into security_control
select
    null, # add a PK
    code,
    lower(class),
    subclass,
    family,
    control,
    guidance,
    lower(control_level),
    enhancements,
    supplement
from ofdbname.blscrs;

select 'source';
truncate table source;
insert into source
select
    id,
    name,
    nickname,
    `desc`
from ofdbname.sources;

select 'upload';
truncate table upload;
insert into upload
select
    id,
    upload_ts,
    filename,
    user_id,
    null
from ofdbname.uploads;

select 'user';
truncate table user;
insert into user
select
    id,
    created_ts,
    created_ts,
    account,
    password,
    '',
    password_ts,
    history_password,
    hash,
    last_rob,
    1 - is_active, #invert is_active to get locked
    if (is_active = 1, null, now()),
    if (is_active = 1, null, 'manual'),
    0,
    0,
    last_login_ip,
    last_login_ip,
    last_login_ts,
    title,
    name_first,
    name_last,
    email,
    email_validate,
    phone_office,
    phone_mobile,
    search_columns_pref,
    notify_frequency,
    most_recent_notify_ts,
    notify_email,
    null
from ofdbname.users;

select 'user_event';
truncate table user_event;
insert into user_event
select
    user_id,
    event_id
from ofdbname.user_events;

select 'user_organization';
truncate table user_organization;
insert into user_organization
select
    user_id,
    system_id # sysID and orgID will be manipulated to be equal
from ofdbname.user_systems;

select 'user_role';
truncate table user_role;
insert into user_role
select
    user_id,
    role_id
from ofdbname.user_roles;

SET FOREIGN_KEY_CHECKS = 1;
