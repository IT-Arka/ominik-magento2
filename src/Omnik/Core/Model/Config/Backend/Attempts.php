<?php
declare(strict_types=1);

namespace Omnik\Core\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class Attempts extends Value
{
    /**
     * Validate the attempts value before saving
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        
        if (!is_numeric($value) || $value < 1 || $value != (int)$value) {
            throw new LocalizedException(
                __('Attempts must be a positive integer value.')
            );
        }
        
        // Convert to integer to ensure it's stored as integer
        $this->setValue((int)$value);
        
        return parent::beforeSave();
    }
}
