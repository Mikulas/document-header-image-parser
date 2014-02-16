#include "opencv2/highgui/highgui.hpp"
#include "opencv2/imgproc/imgproc.hpp"
#include <iostream>
#include <stdio.h>

#define BOX 25
#define GAP 74
#define SHOWDEBUG

using namespace std;
using namespace cv;

/// Global Variables
Mat img;
Mat result;
#ifdef DEBUG
    Mat img_display;
#endif
const char* image_window = "Source Image";
const char* result_window = "Result window";

int FromBinary(bool,bool,bool,bool,bool);
bool ReadBox(Mat, int, Point, Point);
Point MatchingMethod( int, Mat, void* );

/** @function main */
int main( int argc, char** argv )
{
    /// Load image and template
    #ifdef SHOWDEBUG
        img = imread("/Users/mikulas/Dropbox/Projects/OpenCVTest/scan_215745_rot.jpg");
    #else
        img = imread( argv[1], 1 );
    #endif
    // make sure marks are in the same dir as build
    Mat markLeft = imread("/Users/mikulas/Dropbox/Projects/OpenCVTest/mark_2014jaro_left.jpg");
    Mat markRight = imread("/Users/mikulas/Dropbox/Projects/OpenCVTest/mark_2014jaro_right.jpg");

    #ifdef SHOWDEBUG
        const int offsetLeft = 1250;
        img = img(Rect(offsetLeft, 0, 2550 - offsetLeft, 200));

        /// Create windows
        namedWindow( image_window, CV_WINDOW_AUTOSIZE );
        img.copyTo(img_display);
    #endif

    Point left = MatchingMethod( CV_TM_SQDIFF, markLeft, 0 );
    Point right = MatchingMethod( CV_TM_SQDIFF, markRight, 0 );

    // center to line
    left.y += 30;
    left.x += 80;
    right.y += 7;
    right.x += 20;

    #ifdef SHOWDEBUG
        printf("left: %d, %d\n", left.x, left.y);
        printf("right: %d, %d\n", right.x, right.y);
    #endif

    bool b1, b2, b3, b4, b5;

    int offset = 36;
    b1 = 0;
    b2 = ReadBox(img, offset, left, right);
    b3 = ReadBox(img, offset + BOX, left, right);
    b4 = ReadBox(img, offset + BOX * 2, left, right);
    b5 = ReadBox(img, offset + BOX * 3, left, right);
    const int dataMonth = FromBinary(b1, b2, b3, b4, b5);

    offset = 160;
    b1 = ReadBox(img, offset, left, right);
    b2 = ReadBox(img, offset + BOX, left, right);
    b3 = ReadBox(img, offset + BOX * 2, left, right);
    b4 = ReadBox(img, offset + BOX * 3, left, right);
    b5 = ReadBox(img, offset + BOX * 4, left, right);
    const int dataDay = FromBinary(b1, b2, b3, b4, b5);

    offset = 310;
    b1 = 0;
    b2 = ReadBox(img, offset, left, right);
    b3 = ReadBox(img, offset + BOX, left, right);
    b4 = ReadBox(img, offset + BOX * 2, left, right);
    b5 = ReadBox(img, offset + BOX * 3, left, right);
    const int dataPaper = FromBinary(b1, b2, b3, b4, b5);

    offset = 451;
    int s = -1;
    s = ReadBox(img, offset, left, right) ? 1 : s;
    s = ReadBox(img, offset + GAP, left, right) ? 2 : s;
    s = ReadBox(img, offset + GAP * 2, left, right) ? 3 : s;
    s = ReadBox(img, offset + GAP * 3, left, right) ? 4 : s;
    s = ReadBox(img, offset + GAP * 4, left, right) ? 5 : s;
    s = ReadBox(img, offset + GAP * 5, left, right) ? 6 : s;
    s = ReadBox(img, offset + GAP * 6, left, right) ? 7 : s;
    s = ReadBox(img, offset + GAP * 7, left, right) ? 8 : s;
    s = ReadBox(img, offset + GAP * 8, left, right) ? 9 : s;
    const int dataSubject = s;

    printf("month: %d\n", dataMonth);
    printf("day: %d\n", dataDay);
    printf("paper: %d\n", dataPaper);
    printf("subject: %d\n", dataSubject);

    #ifdef SHOWDEBUG
        line(img_display, left, right, Scalar(255,0,0));
        imshow(image_window, img_display);
        waitKey(0);
    #endif

    return 0;
}

int FromBinary(bool b1, bool b2, bool b3, bool b4, bool b5)
{
    return b5 + b4 * 2 + b3 * 4 + b2 * 8 + b1 * 16;
}

bool ReadBox(Mat img, int pos, Point left, Point right)
{
    const int distance = 1124;
    // linear rotation error fix
    const int top = left.y + (right.y - left.y) * pos / distance;

    Rect box = Rect(left.x + pos, top, BOX, BOX);
    Mat roi = img(box);
    Scalar res = mean(roi);
    const double avg = (res[0] + res[1] + res[2]) / 3;
    const bool result = avg < 100;

    #ifdef SHOWDEBUG
        printf("channels: %lf %lf %lf\n", res[0], res[1], res[2]);
        rectangle(img_display, box, Scalar(0,255,0));
        if (result)
        {
            box.y += BOX;
            rectangle(img_display, box, Scalar(0,255,0), -1);
        }
    #endif

    return result;
}

/**
 * @function MatchingMethod
 * @brief Trackbar callback
 */
Point MatchingMethod( int match_method, Mat mark, void* )
{
    /// Create the result matrix
    int result_cols = img.cols - mark.cols + 1;
    int result_rows = img.rows - mark.rows + 1;

    result.create( result_cols, result_rows, CV_32FC1 );

    /// Do the Matching and Normalize
    matchTemplate( img, mark, result, match_method );
    normalize( result, result, 0, 1, NORM_MINMAX, -1, Mat() );

    /// Localizing the best match with minMaxLoc
    double minVal; double maxVal; Point minLoc; Point maxLoc;
    Point matchLoc;

    minMaxLoc( result, &minVal, &maxVal, &minLoc, &maxLoc, Mat() );

    /// For SQDIFF and SQDIFF_NORMED, the best matches are lower values. For all the other methods, the higher the better
    if( match_method  == CV_TM_SQDIFF || match_method == CV_TM_SQDIFF_NORMED )
    { matchLoc = minLoc; }
    else
    { matchLoc = maxLoc; }

    return matchLoc;
}
