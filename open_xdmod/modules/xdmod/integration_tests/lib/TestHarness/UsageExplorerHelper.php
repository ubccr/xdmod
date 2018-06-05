<?php

namespace TestHarness;

class UsageExplorerHelper
{
    private static function findbrace($instr, $startoffset)
    {
        $len = strlen($instr);
        if ($startoffset >= $len) {
            return false;
        }

        $start = strpos($instr, '{', $startoffset);
        if ($start === false) {
            return false;
        }

        $updown = 0;
        for ($i = $start; $i < $len; $i++)
        {
            if ($instr[$i] === '{') {
                $updown++;
            }
            if ($instr[$i] === '}') {
                $updown--;
            }
            if ($updown === 0) {
                break;
            }
        }
        return $i;
    }

    public static function demanglePlotData($data)
    {
        $outstr = str_replace("'{'", '', $data);

        while (true) {
            $fnoff = strpos($outstr, 'function');
            if ($fnoff === false) {
                break;
            }
            $endbrace = self::findbrace($outstr, $fnoff);

            $startQuote = substr($outstr, $fnoff - 1, 1);
            $endQuote = substr($outstr, $endbrace + 1, 1);
            $isQuoted = $startQuote === '"' && $endQuote === '"';

            $replacementMessage = $isQuoted ? 'FUNCTION DELETED' : '"FUNCTION DELETED"';

            $outstr = substr($outstr, 0, $fnoff) . $replacementMessage .  substr($outstr, $endbrace + 1);
        }

        return $outstr;
    }
}
