# An example of a plug-in report.

    SELECT CONCAT('(', o.nickname, ') ', o.name) "System Name",
           CONCAT('(', s.confidentiality, ', ', s.integrity, ', ', s.availability, ')') "System Impact"
      FROM organization o 
INNER JOIN system s ON o.systemId = s.id
     WHERE o.id in (##ORGANIZATIONS##)
