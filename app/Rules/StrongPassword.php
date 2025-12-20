<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class StrongPassword implements Rule
{
    protected $minLength;
    protected $requireUppercase;
    protected $requireLowercase;
    protected $requireNumbers;
    protected $requireSpecialChars;

    /**
     * Create a new rule instance.
     *
     * @param int $minLength
     * @param bool $requireUppercase
     * @param bool $requireLowercase
     * @param bool $requireNumbers
     * @param bool $requireSpecialChars
     */
    public function __construct(
        int $minLength = 8,
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSpecialChars = false
    ) {
        $this->minLength = $minLength;
        $this->requireUppercase = $requireUppercase;
        $this->requireLowercase = $requireLowercase;
        $this->requireNumbers = $requireNumbers;
        $this->requireSpecialChars = $requireSpecialChars;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Check minimum length
        if (strlen($value) < $this->minLength) {
            return false;
        }

        // Check for uppercase letter
        if ($this->requireUppercase && !preg_match('/[A-Z]/', $value)) {
            return false;
        }

        // Check for lowercase letter
        if ($this->requireLowercase && !preg_match('/[a-z]/', $value)) {
            return false;
        }

        // Check for number
        if ($this->requireNumbers && !preg_match('/\d/', $value)) {
            return false;
        }

        // Check for special character
        if ($this->requireSpecialChars && !preg_match('/[^A-Za-z0-9]/', $value)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        $requirements = [];
        
        $requirements[] = "minimal {$this->minLength} karakter";
        
        if ($this->requireUppercase) {
            $requirements[] = "huruf besar";
        }
        
        if ($this->requireLowercase) {
            $requirements[] = "huruf kecil";
        }
        
        if ($this->requireNumbers) {
            $requirements[] = "angka";
        }
        
        if ($this->requireSpecialChars) {
            $requirements[] = "karakter khusus";
        }

        return 'Password harus mengandung ' . implode(', ', $requirements) . '.';
    }
}
