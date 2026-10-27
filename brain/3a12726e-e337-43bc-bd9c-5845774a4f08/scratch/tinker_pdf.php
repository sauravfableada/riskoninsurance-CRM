$parser = new \Smalot\PdfParser\Parser();
$pdf = $parser->parseFile('d:/xampp/htdocs/insurance-crm-latest/mr_PRATIKKUMAR_PATEL 2.pdf');
echo $pdf->getText();
