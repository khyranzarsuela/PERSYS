<?php

declare(strict_types=1);

$template = __DIR__ . '/Documents/Leave_Request_Form_FILLABLE.pdf';
$outputDir = __DIR__ . '/generated';
$outputPdf = $outputDir . '/Form6_Test.pdf';

$pdftk = 'C:\\Program Files (x86)\\PDFtk Server\\bin\\pdftk.exe';

// Temporary file containing the PDF form field values.
$fdfFile = tempnam(sys_get_temp_dir(), 'form6_');

if ($fdfFile === false) {
    exit('Error: Could not create temporary form data file.');
}

try {
    if (!file_exists($template)) {
        throw new RuntimeException(
            'PDF template not found: ' . $template
        );
    }

    if (!file_exists($pdftk)) {
        throw new RuntimeException(
            'PDFtk executable not found: ' . $pdftk
        );
    }

    if (!is_dir($outputDir) &&
        !mkdir($outputDir, 0775, true) &&
        !is_dir($outputDir)) {
        throw new RuntimeException(
            'Could not create output directory.'
        );
    }

    // Escape special characters used in FDF strings.
    $escapeFdf = static function (string $value): string {
        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\\(', '\\)', '', '\\n'],
            $value
        );
    };

    // Test values only. These are not real employee records.
    $fields = [
        'office_department' => 'BCSHS-SAC',
        'employee_last_name' => 'DELA CRUZ',
        'employee_first_name' => 'JUAN',
        'employee_middle_name' => 'SANTOS',
        'date_of_filing' => 'October 15, 2026',
        'employee_position' => 'Teacher I',
        'employee_salary' => '30000',
        'leave_vacation' => 'Yes',
        'reason_details' => '',
        'total_working_days' => '3',
        'inclusive_dates' => 'October 20-22, 2026',
        'commutation_not_requested' => 'Yes',
    ];

    $fdf = "%FDF-1.2\n";
    $fdf .= "1 0 obj\n";
    $fdf .= "<< /FDF << /Fields [\n";

    foreach ($fields as $name => $value) {
        $fieldName = $escapeFdf($name);
        $fieldValue = $escapeFdf($value);

        $fdf .= "<< /T (" . $fieldName . ") ";

        if ($value === 'Yes') {
            $fdf .= "/V /Yes >>\n";
        } else {
            $fdf .= "/V (" . $fieldValue . ") >>\n";
        }
    }

    $fdf .= "] >> >>\n";
    $fdf .= "endobj\n";
    $fdf .= "trailer\n";
    $fdf .= "<< /Root 1 0 R >>\n";
    $fdf .= "%%EOF\n";

    if (file_put_contents($fdfFile, $fdf) === false) {
        throw new RuntimeException(
            'Could not write temporary form data.'
        );
    }

    // Build the PDFtk command using escaped arguments.
    $command =
        escapeshellarg($pdftk) . ' ' .
        escapeshellarg($template) .
        ' fill_form ' . escapeshellarg($fdfFile) .
        ' output ' . escapeshellarg($outputPdf) .
        ' flatten 2>&1';

    exec($command, $commandOutput, $exitCode);

    if ($exitCode !== 0 || !file_exists($outputPdf)) {
        throw new RuntimeException(
            "PDF generation failed:\n" .
            implode("\n", $commandOutput)
        );
    }

    echo 'Success! Test PDF created at:' . PHP_EOL;
    echo $outputPdf . PHP_EOL;

} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();

} finally {
    if (file_exists($fdfFile)) {
        unlink($fdfFile);
    }
}