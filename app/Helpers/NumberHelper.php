<?php

if (!function_exists('convertToWords')) {
    function convertToWords($number)
    {
        $decimalPart = "";
        if (strpos($number, '.') !== false) {
            // Pisahkan angka sebelum dan setelah titik desimal
            list($integerPart, $decimalPart) = explode('.', $number);
        } else {
            $integerPart = $number;
        }

        // Fungsi untuk mengubah angka menjadi kata dalam bahasa Inggris
        $integerWords = numberToWords((int)$integerPart);
        $decimalWords = $decimalPart ? numberToWords((int)$decimalPart) : null;

        // Gabungkan kata-kata untuk total amount
        $result = $integerWords . " dollars";
        if ($decimalWords) {
            $result .= " and " . $decimalWords . " ";
        }

        return ucfirst($result) . " only";
    }

    function numberToWords($number)
    {
        $words = array(
            0 => '',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            30 => 'thirty',
            40 => 'forty',
            50 => 'fifty',
            60 => 'sixty',
            70 => 'seventy',
            80 => 'eighty',
            90 => 'ninety'
        );

        $scales = array('', 'thousand', 'million', 'billion', 'trillion');

        if ($number == 0) {
            return 'zero';
        }

        $wordsResult = '';

        // Split into groups of 3 digits
        $number = str_pad($number, ceil(strlen($number) / 3) * 3, '0', STR_PAD_LEFT);
        $chunks = str_split($number, 3);
        $chunkCount = count($chunks);

        foreach ($chunks as $index => $chunk) {
            $chunk = (int)$chunk;
            if ($chunk == 0) {
                $chunkCount--;
                continue;
            }

            $hundreds = (int)($chunk / 100);
            $remainder = $chunk % 100;
            $tens = (int)($remainder / 10) * 10;
            $units = $remainder % 10;

            $scale = $scales[$chunkCount - 1];

            if ($hundreds) {
                $wordsResult .= $words[$hundreds] . " hundred ";
            }

            if ($remainder) {
                if ($remainder < 20) {
                    $wordsResult .= $words[$remainder] . " ";
                } else {
                    $wordsResult .= $words[$tens] . " " . $words[$units] . " ";
                }
            }

            $wordsResult .= $scale ? $scale . " " : "";
            $chunkCount--;
        }

        return trim($wordsResult);
    }
}
