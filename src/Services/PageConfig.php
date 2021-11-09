<?php

namespace Ibrasafsafi\PrinterLaravel\Services;

class PageConfig {

public $paperSize;
public $oriontation;
public $style;


    /**
     * ReportConfig constructor.
     * @param $paperSize
     * @param $oriontation
     * @param $style
     */
    public function __construct($paperSize, $oriontation ,$style)
    {
        $this->paperSize = $paperSize;
        $this->oriontation = $oriontation;
        $this->style = $style;
    }

}
