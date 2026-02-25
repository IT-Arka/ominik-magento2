<?php

declare(strict_types=1);

namespace Omnik\Core\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Telephone extends AbstractHelper
{

    /**
     * @param string $telephone
     * @return array
     */
    public function getTelephoneFormatted(string $telephone): array
    {
        $result = [];

        if (empty($telephone)) {
            return $result;
        }

        if (str_contains($telephone, "*")) {
            $result['ddd'] = "**";
            $result["number"] = "********";
        } else {
            $data = explode(" ", $telephone);
            $ddd = str_replace("(", "", $data[0]);
            $ddd = str_replace(")", "", $ddd);

            $phone = str_replace("-", "", $data[1]);

            $result['ddd'] = $ddd;
            $result['number'] = trim($phone);
        }

        return $result;
    }

    /**
     * @param string $telephone
     * @return array
     */
    public function getTelephoneFormattedIntegration(string $telephone): array
    {
        $result = [];

        if (empty($telephone)) {
            return $result;
        }

        if (str_contains($telephone, "*")) {
            $result['ddd'] = "**";
            $result["number"] = "********";
        } else {
            $ddd = substr($telephone, 0, 2);
            $phone = substr($telephone, 2);

            $result['ddd'] = $ddd;
            $result['number'] = trim($phone);
        }

        return $result;
    }

    /**
     * @param string $ddd
     * @param string $telephone
     * @return string
     */
    public function getTelephoneFormattedDatabase(string $ddd, string $telephone): string
    {
        if (strlen($telephone) == 9) {
            $firstPart = substr($telephone,0, 5);
            $secondPart = substr($telephone, 5, 4);
        } elseif (strlen($telephone) == 8) {
            $firstPart = substr($telephone,0, 4);
            $secondPart = substr($telephone, 4, 4);
        }
        $phone = "(" . $ddd . ") " . $firstPart . "-" . $secondPart;
        return $phone;
    }

    /**
     * @param array $data
     * @return string
     */
    public function getPhone(array $data, int $index): string
    {
        if (isset($data['users'][0]['phones'][$index])) {
            $ddd = $data['users'][0]['phones'][$index]['ddd'];
            $telephone = $data['users'][0]['phones'][$index]['number'];

            return $this->getTelephoneFormattedDatabase($ddd, $telephone);
        }

        return "";
    }

}
