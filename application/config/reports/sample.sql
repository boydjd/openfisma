# An example of a plug-in report.

    SELECT CONCAT('(',nickname,') ', name) "System Name",
           CONCAT('(', confidentiality, ', ', integrity, ', ', availability, ')') "System Impact"
      FROM systems