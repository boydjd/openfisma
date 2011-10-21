<?php

require_once(dirname(dirname(__FILE__)) . '/tcpdf/config/lang/eng.php');
require_once(dirname(dirname(__FILE__)) . '/tcpdf/tcpdf.php');

abstract class Fisma_PDF extends TCPDF
{
    public function __construct()
    {
        // @TODO Make this not a global
        global $l;
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $this->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $this->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->SetFooterMargin(PDF_MARGIN_FOOTER);
        $this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->setLanguageArray($l);
        $this->setFontSubsetting(true);
        //$this->SetFont('times', '', 14, '', true);
        $this->SetFont('dejavusans', '', 14, '', true);
    }
    abstract function render();
    protected function _render()
    {
        return $this->Output('', 'S');
    }
    public function header1($text)
    {
        $origSize = $this->getFontSizePt();
        $this->SetFontSize(14);
        $this->MultiCell('', 0, $text, 0, 'L');
        $this->SetFontSize($origSize);
    }

    public function header2($text)
    {
        $origPaddings = $this->getCellPaddings();
        $origSize = $this->getFontSizePt();
        $this->setCellPaddings(10, 2, 0, 2);
        $this->SetFontSize(12);
        $this->MultiCell('', 0, $text, 0, 'L');
        $this->setCellPaddings($origPaddings['L'], $origPaddings['T'], $origPaddings['R'], $origPaddings['B']);
        $this->SetFontSize($origSize);
    }

    public function header3($text)
    {
        $origPaddings = $this->getCellPaddings();
        $origSize = $this->getFontSizePt();
        $this->setCellPaddings(20, 2, 0, 2);
        $this->SetFontSize(12);
        $this->MultiCell('', 0, $text, 0, 'L');
        $this->setCellPaddings($origPaddings['L'], $origPaddings['T'], $origPaddings['R'], $origPaddings['B']);
        $this->SetFontSize($origSize);
    }
    
    public function paragraph($text)
    {
        $this->MultiCell('', 0, $text, 0, 'L');
    }
    
    public function tableHeader($text)
    {
        $margins = $this->getMargins();
        $w = $this->getPageWidth() - $margins['left'] - $margins['right'];
        $origSize = $this->getFontSizePt();
        $this->SetFontSize(10);
        $this->MultiCell($w, 0, $text, 0, 'C');
        $this->SetFontSize($origSize);
    }

    public function figureCaption($text)
    {
        $margins = $this->getMargins();
        $w = $this->getPageWidth() - $margins['left'] - $margins['right'];
        $origSize = $this->getFontSizePt();
        $this->SetFontSize(10);
        $this->MultiCell($w, 0, $text, 0, 'C');
        $this->SetFontSize($origSize);
    }
}
