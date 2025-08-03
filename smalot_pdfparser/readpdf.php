<?php
use Smalot\PdfParser\Parser;

require_once __DIR__ . '/alt_autoload.php';


// Path to the PDF file
$pdfFile = __DIR__ . '/PostgreSQL-for-developers.pdf';

// Create a new Parser instance
$parser = new Parser();

// Parse the PDF file
$pdf = $parser->parseFile($pdfFile);

// Retrieve the text content from the PDF
$text = $pdf->getText();

// Output the content
echo $text;