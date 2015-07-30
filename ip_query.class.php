<?php
/*
print_r(IPQuery::query("8.8.8.8"));
print_r(IPQuery::query("114.114.114.114"));
print_r(IPQuery::query("202.38.64.3"));
print_r(IPQuery::query("1.25.167.165"));
print_r(IPQuery::query("183.25.68.3"));
print_r(IPQuery::query("14.109.86.140"));
 */
class IPQuery{
    #const IPFILE = "./CoralWry.dat";
    #const IPFILE = "./qqwry.dat";
    const IPFILE =  "/../tools/qqwry.dat";

    public static function query($ip_address){
        $intIp = self::encodeIp($ip_address);
        $res = self::ip2addr($intIp);
        if(!is_array($res)){
            return null;
        }
        $region = iconv ("gbk", "utf8", $res['region']);
        $region = preg_replace("/市$/", "", $region);
        $result = array("province" => $region, "city" => $region, );
        if (mb_strpos($region, "省")!==false){
            $res  = explode("省", $region);
            $result = array("province" => $res[0], "city" => $res[1], );
        }
        return $result;
        
    }

    private static function encodeIp($strDotquadIp) {
        $arrIpSep = explode('.', $strDotquadIp);
        if (count($arrIpSep) != 4) return 0;
        $intIp = 0;    
        foreach ($arrIpSep as $k => $v) $intIp += (int)$v * pow(256, 3 - $k);
        return $intIp;
    }

    private static function bin2dec($strBin) {
        $intLen = strlen($strBin);
        for (
            $i = 0, $intBase = 1, $intResult = 0;
        $i < $intLen; $i++, $intBase *= 256
        ) $intResult += ord($strBin{$i}) * $intBase;
        return $intResult;
    }

    // error code: 1-open file error; 2-data error;
    private static function ip2addr($intIp) {
        $arrUnknown = array(
            "region" => "(unknown)",
            "address" => "(unknown)"
        );
        $fileIp = fopen(__DIR__ . self::IPFILE, "rb");
        if (!$fileIp) return 1;
        $strBuf = fread($fileIp, 4);
        $intFirstRecord = self::bin2dec($strBuf);
        $strBuf = fread($fileIp, 4);
        $intLastRecord = self::bin2dec($strBuf);
        $intCount = floor(($intLastRecord - $intFirstRecord) / 7);
        if ($intCount < 1) return 2;
        $intStart = 0;
        $intEnd = $intCount;
        while ($intStart < $intEnd - 1) {
            $intMid = floor(($intStart + $intEnd) / 2);
            $intOffset = $intFirstRecord + $intMid * 7;
            fseek($fileIp, $intOffset);
            $strBuf = fread($fileIp, 4);
            $intMidStartIp = self::bin2dec($strBuf);
            if ($intIp == $intMidStartIp) {
                $intStart = $intMid;
                break;
            }
            if ($intIp > $intMidStartIp) $intStart = $intMid;
            else $intEnd = $intMid;
        }
        $intOffset = $intFirstRecord + $intStart * 7;
        fseek($fileIp, $intOffset);
        $strBuf = fread($fileIp, 4);
        $intStartIp = self::bin2dec($strBuf);
        $strBuf = fread($fileIp, 3);
        $intOffset = self::bin2dec($strBuf);
        fseek($fileIp, $intOffset);
        $strBuf = fread($fileIp, 4);
        $intEndIp = self::bin2dec($strBuf);
        if ($intIp < $intStartIp || $intIp > $intEndIp) return $arrUnknown;
        $intOffset += 4;
        while (($intFlag = ord(fgetc($fileIp))) == 1) {
            $strBuf = fread($fileIp, 3);
            $intOffset = self::bin2dec($strBuf);
            if ($intOffset < 12) return $arrUnknown;
            fseek($fileIp, $intOffset);
        }
        switch ($intFlag) {
        case 0:
            return $arrUnknown;
            break;
        case 2:
            $intOffsetAddr = $intOffset + 4;
            $strBuf = fread($fileIp, 3);
            $intOffset = self::bin2dec($strBuf);
            if ($intOffset < 12) return $arrUnknown;
            fseek($fileIp, $intOffset);
            while (($intFlag = ord(fgetc($fileIp))) == 2 || $intFlag == 1) {
                $strBuf = fread($fileIp, 3);
                $intOffset = self::bin2dec($strBuf);
                if ($intOffset < 12) return $arrUnknown;
                fseek($fileIp, $intOffset);
            }
            if (!$intFlag) return $arrUnknown;
            $arrAddr = array(
                "region" => chr($intFlag)
            );
            while (ord($c = fgetc($fileIp))) $arrAddr["region"] .= $c;
            fseek($fileIp, $intOffsetAddr);
            while (($intFlag = ord(fgetc($fileIp))) == 2 || $intFlag == 1) {
                $strBuf = fread($fileIp, 3);
                $intOffset = self::bin2dec($strBuf);
                if ($intOffset < 12) {
                    $arrAddr["address"] = "(unknown)";
                    return $arrAddr;
                }
                fseek($fileIp, $intOffset);
            }
            if (!$intFlag) {
                $arrAddr["address"] = "(unknown)";
                return $arrAddr;
            }
            $arrAddr["address"] = chr($intFlag);
            while (ord($c = fgetc($fileIp))) $arrAddr["address"] .= $c;
            return $arrAddr;
            break;
        default:
            $arrAddr = array("region" => chr($intFlag));
            while (ord($c = fgetc($fileIp))) $arrAddr["region"] .= $c;
            while (($intFlag = ord(fgetc($fileIp))) == 2 || $intFlag == 1) {
                $strBuf = fread($fileIp, 3);
                $intOffset = self::bin2dec($strBuf);
                if ($intOffset < 12) {
                    $arrAddr["address"] = "(unknown)";
                    return $arrAddr;
                }
                fseek($fileIp, $intOffset);
            }
            if (!$intFlag) {
                $arrAddr["address"] = "(unknown)";
                return $arrAddr;
            }
            $arrAddr["address"] = chr($intFlag);
            while (ord($c = fgetc($fileIp))) $arrAddr["address"] .= $c;
            return $arrAddr;
        }
    }
}
