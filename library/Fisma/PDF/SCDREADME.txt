TODO:
* Finish coding the PDF generation.
  - Subclass TCPDF to override the Header() and Footer() methods.
  - Finish coding the remaining document sections into the test.php script.
* Incorperate this code into the ScdController.
* Incorporate data from Systems into the generated document.
* Create a form in ScdController for any information not already available.
* Incorporate form responses into the generated document.
* Delete the scripts/SCDPDF directory when no longer needed.

Not required, but ideal:
* Refactor PDF code into a testable set of library classes.
* Use TCPDF's TOC features to add a table of contents to the document.

