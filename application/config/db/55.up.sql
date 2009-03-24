--
-- Author:    Mark E. Haase <mhaase@endeavorsystems.com>
-- Copyright: (c) 2008 Endeavor Systems, Inc.
-- License:   http://www.openfisma.org/mw/index.php?title=License
-- Version:   $Id$
--

-- Update the textual configuration items to include HTML markup

update configurations set description ='<p>SENSITIVE BUT UNCLASSIFIED INFORMATION PROPERTY OF THE UNITED STATES GOVERNMENT</p><p>DISCLOSURE, COPYING, DISSEMINATION, OR DISTRIBUTION OF SENSITIVE BUT UNCLASSIFIED INFORMATION TO UNAUTHORIZED USERS IS PROHIBITED.</p><p>Please dispose of sensitive but unclassified information when no longer needed.</p><p>I. Usage Agreement</p><p>This is a Federal computer system and is the property of the United States Government. It is for authorized use only. Users (authorized or unauthorized) have no explicit or implicit expectation of privacy in anything viewed, created, downloaded, or stored on this system, including e-mail, Internet, and Intranet use. Any or all uses of this system (including all peripheral devices and output media) and all files on this system may be intercepted, monitored, read, captured, recorded, disclosed, copied, audited, and/or inspected by authorized Agency personnel, the Office of Inspector General (OIG),and/or other law enforcement personnel, as well as authorized officials of other agencies. Access or use of this computer by any person, whether authorized or unauthorized, constitutes consent to such interception, monitoring, reading, capturing, recording, disclosure, copying, auditing, and/or inspection at the discretion of authorized Agency personnel, law enforcement personnel (including the OIG),and/or authorized officials other agencies. Unauthorized use of this system is prohibited and may constitute a violation of 18 U.S.C. 1030 or other Federal laws and regulations and may result in criminal, civil, and/or administrative action. By continuing to use this system, you indicate your awareness of, and consent to, these terms and conditions and acknowledge that there is no reasonable expectation of privacy in the access or use of this computer system.</p>' where `key` like 'behavior_rule';

update configurations set description = CONCAT('<p>', description, '</p>') where `key` like 'use_notification';

update configurations set description = '<ul><li>This is a U.S. Federal government computer system that is FOR OFFICIAL USE ONLY.</li><li>This system is subject to monitoring. No expectation of privacy is to be assumed.</li><li>Individuals found performing unauthorized activities are subject to disciplinary action including criminal prosecution.</li></ul>' where `key` like 'privacy_policy';
    