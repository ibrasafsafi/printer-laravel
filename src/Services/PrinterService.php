<?php

namespace Ibrasafsafi\PrinterLaravel\Services;

use App;
use Dompdf\FontMetrics;
use Dompdf\Options;
use App\Models\Company;
use App\Models\Rapport;
use App\Models\Report;
use Barryvdh\DomPDF\PDF;
use DOMDocument;
use Dompdf\Dompdf;
use FontLib\AdobeFontMetrics;
use SimpleXMLElement;
use tidy;
use XSLTProcessor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\Compilers\BladeCompiler;
use Symfony\Component\VarDumper\Caster\DOMCaster;
use TheSeer\Tokenizer\XMLSerializer;

class PrinterService
{


    public function processs($header, $footer, $data, $archi, $name)
    {

        $xmlobj = new SimpleXMLElement("<root></root>");
        $xmlData = (new PrinterService)->convertArrayToXML($data, $xmlobj);

        $fragments[] = $header;
        $fragments[] = $footer;

        // compile xsl to html
        $proc = new XSLTProcessor();
        $xsl = new DOMDocument('1.0', 'utf-8');

        // Concat all fragments(array) to be one single string contains all fragments
        $fragmentsArchitecture = implode("\r\n", $fragments);
//        dd($data);
        $architecture = "<?xml version='1.0' encoding='UTF-8' ?>
                                <xsl:stylesheet xmlns:xsl='http://www.w3.org/1999/XSL/Transform' version='1.0'>
                                    <xsl:output method='html' indent='yes'/>
                                    $fragmentsArchitecture

                                    <xsl:template match='root'>
                                        $archi
                                    </xsl:template>
                                </xsl:stylesheet>
                                ";
        // load xsl
        $xsl->loadXML($architecture);

        // it return boolean
        $proc->importStyleSheet($xsl);

//        return $proc->transformToXML($xmlData);

        $html = $proc->transformToXML($xmlData);


        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait'); // landscape

        // Render the HTML as PDF
        $dompdf->render();

        $output = $dompdf->output();
        file_put_contents(public_path() . '/' . $name . '.pdf', $output);
//        $dompdf->stream($name . '.pdf');

//        return $name;

        $html = "<div class='btn-group mb-4'>

                <a href=" . ($name . ".pdf") . " class='btn btn-success' download >Save as PDF</a> </div>" . $html;

        return $html;

        // print it As PDF File
        $this->printAsPdf($html, $name);
//        return $proc->transformToXML($data);

    }

    public function process($reportConfig , $pageConfig)
    {
//        dd($config->reportHeader);



        $xmlobj = new SimpleXMLElement("<root></root>");
        $xmlData = (new PrinterService)->convertArrayToXML($reportConfig->data, $xmlobj);

        if($reportConfig->reportHeader == null ) $reportConfig->reportHeader = "<xsl:template name='header' > </xsl:template>";
        if($reportConfig->reportFooter == null ) $reportConfig->reportFooter = "<xsl:template name='footer' > </xsl:template>";

        $fragments[] = $reportConfig->reportHeader;
        $fragments[] = $reportConfig->reportFooter;
        $fragments[] = $pageConfig->style;
//        $fragments[] = $reportConfig->pageStyle;


        // compile xsl to html
        $proc = new XSLTProcessor();
        $xsl = new DOMDocument('1.0', 'utf-8');

        // Concat all fragments(array) to be one single string contains all fragments
        $fragmentsArchitecture = implode("\r\n", $fragments);
//        dd($data);
        $architecture = "<?xml version='1.0' encoding='UTF-8' ?>
                                <xsl:stylesheet xmlns:xsl='http://www.w3.org/1999/XSL/Transform' version='1.0'>
                                    <xsl:output method='html' indent='yes'/>
                                    $fragmentsArchitecture

                                    <xsl:template match='root'>
                                        <body>
                                            <div class='container-fluid'>
                                                <div class='row'>
                                                    <div class='col'>
                                                    <style>
                                                        <xsl:call-template name='style'/>
                                                    </style>

                                                     <xsl:call-template name='header'/>

                                                     $reportConfig->reportContent

                                                     <xsl:call-template name='footer'/>

                                                    </div>
                                                </div>
                                            </div>
                                        </body>

                                    </xsl:template>

                                </xsl:stylesheet>
                                ";
//        dd($architecture);

        // load xsl
        $xsl->loadXML($architecture);

        // it return boolean
        $proc->importStyleSheet($xsl);

//        return $proc->transformToXML($xmlData);

        $html = $proc->transformToXML($xmlData);


        $dompdf = new Dompdf();
        // Set Font
        $options = $dompdf->getOptions();
        $canvas = $dompdf->getCanvas();
//        dd($canvas);
        $font = $dompdf->getFontMetrics()->getFont("helvetica", "bold");

//        dd($font);
        $options->setDefaultFont( $font);
        $options->setIsPhpEnabled(true);
//        $options->setDpi(100);
        $options->setIsRemoteEnabled(true);
        $dompdf->setOptions($options);
//dd($options);
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper($pageConfig->paperSize, $pageConfig->oriontation); // landscape //letter // legal
        $dompdf->getCanvas()->page_text(16, 770, "Page: {PAGE_NUM} of {PAGE_COUNT}", $font, 8, array(0, 0, 0));

        // Render the HTML as PDF
        $dompdf->render();

//        $this->printAsPdf($html, $reportConfig->name);
//        $dompdf->stream($reportConfig->name . '.pdf');
        //Page numbers

        $output = $dompdf->output();
        file_put_contents(public_path() . '/' . $reportConfig->name . '.pdf', $output);

//        return $name;


        $html = "<div class='container container-smaller'>
                    <div class='row'>
                        <div class='col-lg-10 col-lg-offset-1' style='margin-top:20px; text-align: right'>
                            <div class='btn-group mb-4'>
                                <a href=" . ($reportConfig->name . ".pdf") . " class='btn btn-success' download >Save as PDF</a>
                            </div>
                        </div>
                    </div>
                 </div>" . $html;

        return $html;

        // print it As PDF File
        $this->printAsPdf($html, $name);
//        return $proc->transformToXML($data);

    }

    // GGGGGGG
    public function convertArrayToXML($array, $xml)
    {
        foreach ($array as $key => $line) {
            if (!is_array($line)) {
                $xml->addChild($key, $line);
            } else {
                $obj = $xml->addChild($key);

                if (!empty($line['attribute'])) {

                    $attr = explode(":", $line['attribute']);
                    $obj->addAttribute($attr[0], $attr[1]);
                    unset($line['attribute']);
                }
                $this->convertArrayToXML($line, $obj);
            }
        }
        return $xml;
    }

    public function printAsPdf($html, $docName)
    {
        /*
           $pdf = (new \Barryvdh\DomPDF\PDF)->loadView($ya);
           return $pdf->download('invoice.pdf');
        */

        // instantiate and use the dompdf class
//        return $html;
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'portrait'); // landscape

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser
        // return Pdf : No Need To Write Return

        $dompdf->stream($docName . '.pdf');
    }

    public function insert(Request $request)
    {
        $ya = $request->architecture;
        /*if ($this->check($ya)){
            return "ok";
        }else return "Not OK";
       return $this->check($ya);*/
        // All options : http://tidy.sourceforge.net/docs/quickref.html
        $options = array('output-xhtml' => true, 'clean' => true, 'wrap-php' => true, 'show-body-only' => true, 'indent' => true);

        $tidy = new tidy(); // create new instance of Tidy
        $tidy->parseString($ya, $options); // open file
//        copy($filename, $filename . '.bak'); // backup current file
        $tidy->cleanRepair(); // process with specified options
//        return  file_put_contents($ya, $tidy); // overwrite current file with XHTML version
//        dd($tidy->value);

        $newRapport = Report::query()->create([
            "name" => $request->name,
            "architecture" => $tidy->value,
            "is_printable" => $request->is_printable ? 1 : 0,
        ]);
        $newRapport->save();

        if ($request->header || $request->footer) {
            $fragments[0] = $request->header;
            $fragments[1] = $request->footer;
            foreach ($fragments as $fragment) {
                $report_fragment = App\Models\ReportFragment::query()->create([
                    "report_id" => $newRapport->id,
                    "fragment_id" => $fragment,
//                    "fragment_key" => "THE_KEY",
                ]);
                $report_fragment->save();
            }
        }
    }


            /*$ya = "<?xml version='1.0' encoding='UTF-8' ?>
                                <xsl:stylesheet xmlns:xsl='http://www.w3.org/1999/XSL/Transform' version='1.0'>
                                    <xsl:output method='html' indent='yes'/>
                                    $fragmentsArchitecture

                                    <xsl:template match='root'>

                                        $reportConfig->reportContent
                                    </xsl:template>

                                </xsl:stylesheet>
            ";*/


}
