<?php

// COMMENT ONE
// Debug: skip scanner
//copy(__DIR__ . '/test/scan.jpg', __DIR__ . '/scan.jpg');
//$files = [realpath(__DIR__ . '/scan.jpg')];
//
// Production: read from scanner
$scanline = escapeshellarg(__DIR__ . '/scanline/scanline');
$scans = escapeshellarg(__DIR__ . '/scans');
$output = [];
$retval;
exec("$scanline -jpeg -dir $scans", $output, $retval);
$files = glob(__DIR__ . '/scans/*.jpg');


foreach ($files as $file)
{
	$reader = __DIR__ . '/opencv/reader';

	echo "\n$file\n";

	$command = escapeshellarg($reader) . ' ' . escapeshellarg($file);
	// echo "$command\n";
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
		echo "Given date is not valid, skipped\n";
		continue;
	}

	$stamp = mktime(0, 0, 0, $data->month, $data->day, 2014);
	if ($stamp < strToTime('7 days ago'))
	{
		echo "Given date is older than a week, skipped\n";
		continue;
	}
	if ($stamp > strToTime('+1 day'))
	{
		echo "Given date is in future, skipped\n";
		continue;
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

	echo "2014/{$data->month}/{$data->day} " . $subjects[$data->subject] . " $data->paper\n";

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
}
