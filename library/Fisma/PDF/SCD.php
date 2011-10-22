<?php

class Fisma_PDF_SCD extends Fisma_PDF
{
    protected $_system = null;

    public function __construct(System $system)
    {
        parent::__construct();
        $this->_system = $system;
    }

    protected function _renderTitlePage()
    {
        $this->AddPage();

        $margins = $this->getMargins();
        $w = $this->getPageWidth() - $margins['left'] - $margins['right'];

        $this->Ln(20);

        $text = 'U.S. Department of Transportation' . PHP_EOL
              . 'Federal Aviation Administration' . PHP_EOL
              . 'Office of Information Technology (AMI-1)' . PHP_EOL
              . $this->_system->Organization->name . ' (' . $this->_system->Organization->nickname . ')';
        $this->SetFontSize(24);
        $this->MultiCell($w, 0, $text, 0, 'C');

        $this->Ln(5);

        $text = 'System Characterization Document (SCD)';
        $this->SetFontSize(32);
        $this->MultiCell($w, 0, $text, 0, 'C');

        $now = Zend_Date::now();
        $text = $now->toString(Zend_Date::MONTH_NAME) . ' ' . $now->toString(Zend_Date::YEAR);
        $this->SetFontSize(20);
        $this->MultiCell($w, 0, $text, 0, 'C');

        $this->Ln(2);

        $logo = dirname(__FILE__) . '/SCDLogo.jpg';
        $this->Image($logo, 75, '', 66, 66, '', '', 'C');
        
        $this->Ln(70);

        $text = 'Federal Aviation Administration' . PHP_EOL
              . '6500 S. MacArthur Blvd., Oklahoma City, OK 73169';
        $this->SetFontSize(10);
        $this->MultiCell($w, 0, $text, 0, 'C');
    }

    public function render()
    {
        $margins = $this->getMargins();
        $w = $this->getPageWidth() - $margins['left'] - $margins['right'];

        $this->_renderTitlePage();

        $this->SetFontSize(12);
        $this->AddPage();

        $this->header1('1. INTRODUCTION');

        $this->paragraph(
            'This ' . $this->_system->Organization->name . ' (' . $this->_system->Organization->nickname . ') System '
            . 'Characterization documents the system description, including the system overview and mission; system '
            . 'architecture; hardware and software; internal and external connectivity; and system data/information '
            . 'types, sensitivity, and criticality. The System Characterization Document (SCD) is included as part of '
            . 'each assessment submittal.'
        );

        $this->header2('1.1. General System Information');
        $this->paragraph('Table 1 provides general system information for the system.');
        $this->tableHeader('Table 1: General System Information');

        $html = '<table border="1" cellpadding="4"><tbody>'
              . '<tr>'
              . '<td>System name</td>'
              . '<td>' . $this->_system->Organization->name . '</td>'
              . '</tr><tr>'
              . '<td>System acronym</td>'
              . '<td>' . $this->_system->Organization->nickname . '</td>'
              . '</tr><tr>'
              . '<td>System owner and telephone number</td>'
              . '<td>????<br>????</td>'
              . '</tr><tr>'
              . '<td>Organization responsible for this system</td>'
              . '<td>???? (???)</td>'
              . '</tr><tr>'
              . '<td>System FIPS 199 Security Categorization</td>'
              . '<td>'
              . 'Confidentiality = ' . $this->_system->confidentiality . '<br>'
              . 'Integrity = ' . $this->_system->integrity . '<br>'
              . 'Availability = ' . $this->_system->availability
              . '</td>'
              . '</tr><tr>'
              . '<td>Operational status of the system </td>'
              . '<td>??? SDLC Phase: ' . ucwords($this->_system->sdlcPhase) . '</td>'
              . '</tr><tr>'
              . '<td>System Type</td>'
              . '<td>' . $this->_system->getTypeLabel() . '</td>'
              . '</tr><tr>'
              . '<td>Contractor System</td>'
              . '<td>' . ($this->_system->controlledBy === 'CONTRACTOR' ? 'Yes' : 'No') . '</td>'
              . '</tr><tr>'
              . '<td>If the system is deployed, how many sites?</td>'
              . '<td>?????</td>'
              . '</tr>'
              . '</tbody></table>';
        $this->SetFontSize(12);
        $this->writeHTMLCell($w, '', '', '', $html, 0, 1);

        $this->header2('1.2. Purpose');

        $this->paragraph(
            'The purpose of this ' . $this->_system->Organization->name . ' System Characterization is to provide a '
            . 'single document that contains relevant system description information for inclusion in the system '
            . 'assessment process, including system architecture, interfaces, data/information types, and the general '
            . 'system operations and maintenance environment.'
        );

        $this->header2('1.3. Summary of Changes (revisions only)');

        $this->paragraph(
            'This System Characterization dated February 2011 replaces the System Characterization dated August 2010.'
        );

        $this->header3('1.3.1. System Change Identification');

        $this->paragraph(
            'Table 2 defines the system changes made since the last system security assessment and includes the '
            . 'system assets modified, a description of the modification, and reference to the document that was used '
            . 'to implement the change'
        );

        $this->tableHeader('Table 2:  OF DEMO Change Identification');

        $html = <<<EOD
<table border="1" cellpadding="4"><tbody>
    <tr bgcolor="#CCC">
        <th>System Assets Modified</th>
        <th>Description of Modification</th>
        <th>System Change Document Reference</th>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
</tbody></table>
EOD;
        $this->writeHTMLCell($w, '', '', '', $html, 0, 1);

        $this->AddPage();

        $this->header1('2. System Description');

        $this->paragraph(
            'This section describes the functional and technical characteristics of the Enterprise Architecture and '
            . 'Solutions Environment, including: system overview and mission description; system architecture; '
            . 'system interfaces; data type; and the criticality and sensitivity of information that is received, '
            . 'processed, and transmitted by the system.'
        );

        $this->header2('2.1. System Overview and Mission');

        $this->paragraph($this->_system->Organization->description);

        $this->header2('2.2. Security Categorization');

        $this->paragraph(
            'System data is mapped to information types outlined in NIST SP 800-60 Revision 1 Volume II, Appendices '
            . 'to Guide for Mapping Types of Information and Information Systems to Security Categories (as amended). '
            . 'The Security Category (SC) of this system is determined based on the impact to Confidentiality, '
            . 'Integrity, and Availability (C, I, and A) of all system data, per Federal Information Processing '
            . 'Standards Publication (FIPS PUB) 199 Standards for Security Categorization of Federal Information and '
            . 'Information Systems.  Table 3 identifies the system information and corresponding SC for each '
            . 'information type.'
        );

        $this->tableHeader('Table 3: System Information Security Categories   (z/OS GSS)');

        $html = ''
            . '<table border="1" cellpadding="4" style="text-align: center;"><tbody>'
            . '<tr valign="center">'
            . '<td width="40%" rowspan="2">Information Type</td>'
            . '<td width="30%" colspan="3"> NIST SP 800-60<br> Provisional<br> Impact Value </td>'
            . '<td width="30%" colspan="3"> FIPS 199<br> Impact Value </td>'
            . '</tr><tr>'
            . '<td>C</td> <td>I</td> <td>A</td>'
            . '<td>C</td> <td>I</td> <td>A</td>'
            . '</tr>';
        foreach ($this->_system->InformationTypes as $informationType) {
        $html .= ''
            . '<tr>'
            . '<td style="text-align: left;">' . $informationType->name . '</td>'
            . '<td>' . $informationType->confidentiality{0} . '</td>'
            . '<td>' . $informationType->integrity{0} . '</td>'
            . '<td>' . $informationType->availability{0} . '</td>'
            . '<td>' . $informationType->confidentiality{0} . '</td>'
            . '<td>' . $informationType->integrity{0} . '</td>'
            . '<td>' . $informationType->availability{0} . '</td>'
            . '</tr>';
        }
        $html .= ''
            . '<tr>'
            . '<td style="text-align: left;"><b>System Security Categorization</b></td>'
            . '<td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>'
            . '<td>' . $this->_system->confidentiality{0} . '</td>'
            . '<td>' . $this->_system->integrity{0} . '</td>'
            . '<td>' . $this->_system->availability{0} . '</td>'
            . '</tr>';
        $html .= '</tbody></table>';
        $this->writeHTMLCell($w, '', '', '', $html, 0, 1);

        $this->paragraph(
            $this->_system->Organization->name . ' is a ' . $this->_system->fipsCategory
            . ' Impact System and its minimum security requirements will be based on the '
            . $this->_system->fipsCategory . ' baseline defined in NIST SP 800-53 Revision 3 Recommended Security '
            . 'Controls for Federal Information Systems and Organizations.'
        );

        $this->header3('2.2.1. Personally Identifiable Information (PII)');

        $this->paragraph('The system has completed a Privacy Threshold Analysis (PTA).');

        if ($this->_system->hasPii) {
            $this->paragraph(
                'This ' . $this->_system->Organization->name . ' system does contain PII, which is documented in the '
                . 'PTA/PIA that is attached as part of this submittal. The resultant PIA has been reviewed and '
                . 'updated accordingly.'
            );
        } else {
            $this->paragraph('The system does not contain PII and the PTA is attached as part of this submittal.');
        }

        $this->header2('2.3. E-Authentication');

        if ($this->_system->eAuthLevel === 'unassigned') {
            $this->paragraph(
                'This ' . $this->_system->Organization->name . ' does not require an E-Authentication analysis.'
            );
        } else {
            $this->paragraph(
                'This ' . $this->_system->Organization->name . ' does require an E-Authentication analysis.'
            );
            $this->paragraph(
                'An E-Authentication analysis is required for ' . $this->_system->Organization->name . ', and is '
                . 'included in Attachment I: E-Authentication Analysis. As a part of this analysis, E-Authentication '
                . 'assurance levels and impacts were determined using guidance provided by the Office of Management '
                . 'and Budget (OMB) in Memorandum 04-04.  As a part of this process, in accordance with OMB M-04-04, '
                . 'a risk assessment was conducted to determine the assurance level for '
                . $this->_system->Organization->name . '. It was determined that the authentication of users requires '
                . ucwords($this->_system->eAuthLevel) . ' assurance.'
            );
        }

        $this->header2('2.4. System Architecture');

        // find the system's Architecture diagram
        $document = null;
        foreach ($this->_system->Documents as $doc) {
            if ($doc->DocumentType->name == 'Architecture Diagram') {
                $document = $doc;
                break;
            }
        }
        if (!empty($document)) {
            $this->Image( $document->getPath(), '', '', $w, 100, '', '', 'C', false, 300, '', false, false, 0, 'CM');
            $this->Ln(100);
        }

        $this->figureCaption('Figure 1: ' . $this->_system->Organization->name . ' Architecture and Interfaces');

        $this->header2('2.5. System Interfaces, Interconnections, and Data Flow');

        $html = $this->_system->interconnections;
        $html = str_replace('<table>', '<table border="1" cellpadding="4">', $html);
        $this->SetFontSize(12);
        $this->writeHTMLCell($w, '', '', '', $html, 0, 1);

        $this->header1('3. SYSTEM ENVIRONMENT');

        $html = $this->_system->environment;
        $html = str_replace('<table>', '<table border="1" cellpadding="4">', $html);
        $this->SetFontSize(12);
        $this->writeHTMLCell($w, '', '', '', $html, 0, 1);

        return $this->_render();

    }
}

