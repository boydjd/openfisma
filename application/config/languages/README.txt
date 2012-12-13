;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;
; Copyright (c) 2012 Endeavor Systems, Inc.
;
; This file is part of OpenFISMA.
;
; OpenFISMA is free software: you can redistribute it and/or modify
; it under the terms of the GNU General Public License as published by
; the Free Software Foundation, either version 3 of the License, or
; (at your option) any later version.
;
; OpenFISMA is distributed in the hope that it will be useful,
; but WITHOUT ANY WARRANTY; without even the implied warranty of
; MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
; GNU General Public License for more details.
;
; You should have received a copy of the GNU General Public License
; along with OpenFISMA.  If not, see {@link http://www.gnu.org/licenses/}.
;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;
; Instructions on using localization
;
; Author:    Duy K. Bui <duy.bui@endeavorsystems.com>
; Copyright: (c) Endeavor Systems, Inc. 2012 {@link http://www.endeavorsystems.com}
; License:   http://www.openfisma.org/content/license
;
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

Please DO NOT edit default.ini.
Instead, please clone it into client.ini and make the changes there. It will override default.ini.
Removing lines you do not intend to override can help avoids conflicts with later versions.

Example:
;;; default.ini ;;;

[finding]
Finding_Point_of_Contact                = "Point of Contact"
Finding_Point_of_Contact_Organization   = "POC Organization"

[incident]
Incident_Point_of_Contact               = "Point of Contact"

[vulnerability]

[system_inventory]
Organization_Point_of_Contact           = "General Point of Contact"

[configuration]

;;; client.ini ;;;

[finding]
Finding_Point_of_Contact                = "Assignee"
Finding_Point_of_Contact_Organization   = "Assignee Organization"

[system_inventory]
Organization_Point_of_Contact           = "Default Assignee"