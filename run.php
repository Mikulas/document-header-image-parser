<?php

// COMMENT ONE
// Debug: skip scanner
//copy(__DIR__ . '/test/scan.jpg', __DIR__ . '/scan.jpg');
//
// Production: read from scanner
$scanline = escapeshellarg(__DIR__ . '/scanline/scanline');
$output = [];
$retval;
exec("$scanline -jpeg -dir . -name scan.jpg", $output, $retval);

$file = realpath(__DIR__ . '/scan.jpg');
$reader = __DIR__ . '/opencv/reader';

$command = escapeshellarg($reader) . ' ' . escapeshellarg($file);
echo "$command\n";
$output = [];
exec($command, $output);

$data = (object) [
	'month' => explode(': ', $output[0])[1],
	'day' => explode(': ', $output[1])[1],
	'paper' => explode(': ', $output[2])[1],
	'subject' => explode(': ', $output[3])[1],
];

if (!checkdate($data->month, $data->day, 2014))
{
	echo "Given date is not valid\n";
	die;
}

$subjects = [
	1 => 'AN2',
	'ANL1',
	'DIM2',
	'ELMA',
	'LAB2',
	'MAB2',
	'OCHN1',
	'SOJ',
	'UPSY',
];

$root = '/Users/mikulas/Dropbox/Evernote/2014_jaro';

$dir = "$root/" . $subjects[$data->subject];
if (!file_exists($dir))
{
	if (!mkdir($dir)) {
		echo "Failed creating dir '$dir'\n";
		die;
	}
}

$month = $data->month < 10 ? "0{$data->month}" : $data->month;
$day = $data->day < 10 ? "0{$data->day}" : $data->day;
$paper = $data->paper < 10 ? "0{$data->paper}" : $data->paper;

$path = "$dir/2014{$month}{$day}_{$paper}.jpg";

rename($file, $path);
echo "moved to $path\n";
