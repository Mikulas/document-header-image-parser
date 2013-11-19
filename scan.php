<?php

define('WHITE_THRESHOLD', 190);
define('WIDTH', 2480);
define('HEIGHT', 3508);
define('MARK_TOP', 13); // white above mark
define('MARK_LEFT', 1358); // white before mark

/** @see http://stackoverflow.com/a/596243/326257 */
function lum($image, $top, $left)
{
	$rgb = imagecolorat($image, $left, $top);
	$r = ($rgb >> 16) & 0xFF;
	$g = ($rgb >> 8) & 0xFF;
	$b = $rgb & 0xFF;
	return (0.2126 * $r) + (0.7152 * $g) + (0.0722 * $b);
}

function isBlack($image, $top, $left)
{
	return lum($image, $top, $left) < WHITE_THRESHOLD;
}

function isBlackAverage($image, $top, $left, $radius)
{
	$sum = 0;
	$samples = 0;
	for ($dispX = -$radius; $dispX <= $radius; ++$dispX)
	{
		for ($dispY = -$radius; $dispY <= $radius; ++$dispY)
		{
			if ($dispY * $dispY + $dispX * $dispX <= $radius * $radius)
			{
				$sum += lum($image, $top + $dispY, $left + $dispX);
				$samples++;
			}
		}
	}
	return ($sum / $samples) < WHITE_THRESHOLD; // average lum
}

function readBinary($image, $mark, $offsets)
{
	$res = 0;
	$power = pow(2, count($offsets) - 1);
	foreach ($offsets as $offset)
	{
		if (isBlackAverage($image, $mark->top + 13, $mark->left + $offset, 10))
		{
			$res += $power;
		}
		$power = $power / 2;
	}
	return $res;
}

function readMonth($image, $mark)
{
	return readBinary($image, $mark, [54, 78, 103, 129]);
}

function readDay($image, $mark)
{
	return readBinary($image, $mark, [177, 202, 227, 253, 276]);
}

function readPaper($image, $mark)
{
	return readBinary($image, $mark, [328, 351, 374, 401]);
}

function readCourse($image, $mark)
{
	$courses = [
		468 => 'DIM',
		543 => 'LAN1',
		620 => 'LAN1cv',
		691 => 'LAPcv',
		764 => 'MA1',
		836 => 'MA1cv',
		912 => 'MECH',
		986 => 'MECHcv',
		1061 => 'OCHEM',
	];
	foreach ($courses as $offset => $course)
	{
		if (isBlackAverage($image, $mark->top + 4, $mark->left + $offset, 15))
		{
			return $course;
		}
	}
	return NULL;
}

/** Finds mark which is used to relatively specify other elements on page */
function getMark($image, $startTop, $startEnd)
{
	$mark = (object) ['top' => $startTop, 'left' => $startEnd];
	while (!isBlackAverage($image, $mark->top, $mark->left, 2))
	{
		$mark->top++;
		$mark->left++;
	}
	while (isBlackAverage($image, $mark->top, $mark->left, 2))
	{
		$mark->top--;
	}
	$mark->top++;
	while (isBlackAverage($image, $mark->top, $mark->left, 2))
	{
		$mark->left--;
	}
	$mark->left++;
	return $mark;
}

function getFilename($image, $mark)
{
	$month = str_pad(readMonth($image, $mark), 2, '0', STR_PAD_LEFT);
	$day = str_pad(readDay($image, $mark), 2, '0', STR_PAD_LEFT);
	$paper = str_pad(readPaper($image, $mark), 2, '0', STR_PAD_LEFT);
	$course = readCourse($image, $mark);

	return "{$course}/2013{$month}{$day}_{$paper}.jpeg";
}

$image = imagecreatefromjpeg("template_test.jpeg");
$mark = getMark($image, MARK_TOP, MARK_LEFT);
$name = getFilename($image, $mark);
var_dump($name);
