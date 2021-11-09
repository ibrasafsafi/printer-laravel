<?php

namespace Ibrasafsafi\PrinterLaravel\Services;


class ReportConfig {

public $reportHeader;
public $reportFooter;
public $reportContent;
public $data;
public $name;

    /**
     * ReportConfig constructor.
     * @param $reportHeader
     * @param $reportFooter
     * @param $reportContent
     * @param $data
     * @param $name
     */
    public function __construct($reportHeader, $reportFooter, $reportContent, $data, $name)
    {
        $this->reportHeader = $reportHeader;
        $this->reportFooter = $reportFooter;
        $this->reportContent = $reportContent;
        $this->data = $data;
        $this->name = $name;


    }












//    public function  report_config($header, $footer, $content, $data, $name){}

}
