<?php

namespace PdfFormFieldsCoords;

use Smalot\PdfParser\Element\ElementXRef;
use Smalot\PdfParser\Parser;
use Smalot\PdfParser\PDFObject;

class PdfFormFieldsCoords
{
    public function getFieldsData($filePath)
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $pages = $pdf->getPages();
        $mediaHeight = $pages[0]->getDetails()['MediaBox'][3];

        $fieldsData = [];
        foreach ($pages as $page) {
            $annots = $page->getHeader()->getElements()['Annots'] ?? null;
            if ($annots) {
                $elements = is_array($annots->getContent()) ? $annots->getContent() : $annots->getHeader()->getElements();
                foreach ($elements as $element) {
                    $inputDetails = null;
                    if (get_class($element) == PDFObject::class) {
                        $inputDetails = $element->getHeader()->getDetails();
                    } else if (get_class($element) == ElementXRef::class) {
                        $elementNr = $element->getContent();
                        $inputDetails = $page->getDocument()->getObjects()[$elementNr]->getHeader()->getDetails();
                    }
                    if ($inputDetails) {
                        if (!isset($inputDetails['T']) && !isset($inputDetails['Parent'])) {
                            continue;
                        }

                        $name = !isset($inputDetails['T']) ? $inputDetails['Parent']['T'] : $inputDetails['T'];
                        $coords = $inputDetails['Rect'];

                        $fieldsData[$page->getPageNumber()][$name] = [
                            'x' => $coords[0],
                            'y' => $mediaHeight - $coords[3],
                            'w' => $coords[2] - $coords[0],
                            'h' => $coords[3] - $coords[1],
                        ];
                    }
                }
            }
        }

        return $fieldsData;
    }
}