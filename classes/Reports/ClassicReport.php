<?php

namespace Reports;

class ClassicReport
{
    private $styles = array();
    private $fonts = array();
    private $phpWord = null;

    protected $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;

        $single = $this->settings['charts_per_page'] === 1;

        $baseStyle = array(
            'paperSize' => 'Letter',
            'headerHeight' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(20),
            'footerHeight' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(20)
        );

        $this->styles['titlepage'] = array_merge($baseStyle, array(
            'marginTop' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(43),
            'marginBottom' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(43),
        ));

        $this->styles['page'] = array_merge($baseStyle, array(
            'marginTop' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip($single ? 78 : 48),
            'marginBottom' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(43),
        ));

        $this->fonts['hdrfoot'] = array(
            'size' => 12,
            'italic' => true
        );

        $this->styles['hdrfoot'] = array(
            'align'=>'center'
        );

        $this->fonts['reportTitle'] = array(
            'size' => $single ? 25 : 20
        );

        $this->styles['reportTitle'] = array(
            'align'=>'center',
            'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip($single ? 35 : 7)
        );

        $this->fonts['chartTitle'] = array(
            'size' => $single ? 16 : 14,
            'bold' => true
        );

        $this->styles['chartTitle'] = array(
            'align'=>'center',
            'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip($single ? 10 : 5)
        );

        // used for the second chart on a two-charts-per-page report
        $this->styles['chartTitle2'] = array(
            'align'=>'center',
            'spaceBefore' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(45),
            'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip($single ? 10 : 5)
        );

        $this->fonts['chartDrill'] = array(
            'size' => $single ? 10 : 8
        );

        $this->styles['chartDrill'] = array(
            'align'=>'center',
            'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip($single ? 21 : 0)
        );

        $this->fonts['chartComments'] = array(
            'size' => $single ? 12 : 8
        );

        $this->styles['chartComments'] = array(
            'align'=>'center',
            'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip($single ? 68 : 7)
        );

        $this->styles['chartImage'] = array(
             'width' => $single ? 432 : 340,
             'align' => 'center',
             'wrappingStyle' => 'inline'
        );

        $this->phpWord = new \PhpOffice\PhpWord\PhpWord();
        $this->phpWord->setDefaultFontName('Arial');
    }

    private function createSection($sectionStyle) {
        $section = $this->phpWord->addSection($this->styles[$sectionStyle]);

        if (strlen($this->settings['header']) > 0) {
            $header = $section->addHeader();
            $header->addText($this->settings['header'], $this->fonts['hdrfoot'], $this->styles['hdrfoot']);
        }

        if (strlen($this->settings['footer']) > 0) {
            $header = $section->addFooter();
            $header->addText($this->settings['footer'], $this->fonts['hdrfoot'], $this->styles['hdrfoot']);
        }

        return $section;
    }

    /**
     * Create the report
     */
    public function writeReport($path)
    {
        if (strlen($this->settings['title']) > 0) {
            $section = $this->createSection('titlepage');
            $section->addText($this->settings['title'], $this->fonts['reportTitle'], $this->styles['reportTitle']);
        } else {
            $section = $this->createSection('page');
        }

        $chartNo = 0;
        foreach ($this->settings['charts'] as $chart) {
            $pageNo = (int) ($chartNo / $this->settings['charts_per_page']);
            $chartSlot = $chartNo % $this->settings['charts_per_page'];

            if ($pageNo > 0 && $chartSlot === 0) {
                if(strlen($this->settings['title']) > 0 && $pageNo === 1) {
                    $section = $this->createSection('page');
                } else {
                    $section->addPageBreak();
                }
            }

            $chartStyle = 'chartTitle';
            if ($chartSlot === 1) {
                $chartStyle = 'chartTitle2';
            }

            $section->addText($chart['title'], $this->fonts['chartTitle'], $this->styles[$chartStyle]);
            $section->addText($chart['drill_details'], $this->fonts['chartDrill'], $this->styles['chartDrill']);
            $section->addText($chart['comments'], $this->fonts['chartComments'], $this->styles['chartComments']);
            $section->addImage($chart['imagedata'], $this->styles['chartImage']);

            $chartNo++;
        }

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($this->phpWord, 'Word2007');
        $objWriter->save($path);
    }
}
