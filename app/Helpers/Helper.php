<?php

namespace App\Helpers;

use App\Models\SubmissionTime;
use App\Models\ChecklistTask;
use App\Models\Designation;
use App\Models\TicketMember;
use App\Models\Currency;
use App\Jobs\TicketMail;
use \Carbon\Carbon;

class Helper {

    public static $roles = [
        'admin' => 1,
        'factory-manager' => 2,
        'store-manager' => 3,
        'store-employee' => 4,
        'dealer' => 5,
        'driver' => 6
    ];

    public static $rolesKeys = [
        1 => 'admin',
        2 => 'factory-manager',
        3 => 'store-manager',
        4 => 'store-employee',
        5 => 'dealer',
        6 => 'driver'
    ];

    public static $notificationTemplatePlaceholders = [
        '{$first_name}' => 'First Name',
        '{$middle_name}' => 'Middle Name',
        '{$last_name}' => 'Last Name',
        '{$username}' => 'Username',
        '{$phone_number}' => 'Phone Number',
        '{$employee_id}' => 'Employee ID',
        '{$email}' => 'Email'
    ];

    public static $error = 'Something went wrong! Please try again later.';


    public static function sendPushNotification($device_ids, $data) {

        $keyFilePath = storage_path('app/firebase.json');
        
        $client = new \Google\Client();
        $client->setAuthConfig($keyFilePath);
        $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);
    
        $tokenArray = $client->fetchAccessTokenWithAssertion();
        
        if (isset($tokenArray['error'])) {
            return false;
        }
    
        $accessToken = $tokenArray['access_token'];


        foreach ($device_ids as $did) {
            $notification = json_encode([
                "message" => [
                    "token" => $did, 
                    "notification" => [
                        "body" => $data['description'],
                        "title" => $data['title'],
                    ],
                    "android" => [
                        "priority" => "HIGH",
                    ],
                ]
            ]);
            
            $headers = array(
                'Authorization: Bearer '.$accessToken,
                'Content-Type: application/json'
            );
    
            $ch = curl_init();
            
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/v1/projects/farki-21ee2/messages:send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $notification);
    
            curl_exec($ch);
        }

        return true;
    }

    public static function slug($string, $separator = '-') {
        $string = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $string);
        $string = trim($string, $separator);
        if (function_exists('mb_strtolower')) {
            $string = mb_strtolower($string);
        } else {
            $string = strtolower($string);
        }
        $string = preg_replace("/[\/_|+ -]+/", $separator, $string);

        return $string;
    }

    public static function parseFlexibleDate($dateString) {
        $formats = ['d/m/Y', 'd-m-Y'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateString);
            $errors = \DateTime::getLastErrors();

            if ($errors === false) {
                return $date->format('Y-m-d');
            }

            if ($date && $errors['warning_count'] == 0 && $errors['error_count'] == 0) {
                return $date->format('Y-m-d');
            }
        }

        return '1970-01-01';
    }

    public static function getDefaultCurrency()
    {
        return Currency::where('is_default', true)->first();
    }

    public static function defaultCurrencySymbol()
    {
        $default = self::getDefaultCurrency();
        return $default ? $default->symbol : '$';
    }

    public static function formatLogKey($key)
    {
        $key = str_replace('_id', '', $key);
        return ucwords(str_replace('_', ' ', $key));
    }

    public static function formatLogValue($key, $value)
    {
        if (empty($value) && $value !== 0) {
            return 'N/A';
        }

        static $cache = [];

        $modelMap = [
            'sender_store_id' => \App\Models\Store::class,
            'receiver_store_id' => \App\Models\Store::class,
            'created_by' => \App\Models\User::class,
            'bill_to_id' => \App\Models\User::class,
            'delivery_user' => \App\Models\User::class,
            'dealer_id' => \App\Models\User::class,
            'user_id' => \App\Models\User::class,
            'product_id' => \App\Models\OrderProduct::class,
            'unit_id' => \App\Models\OrderUnit::class,
        ];

        if ($key === 'status') {
            $statuses = \App\Models\Order::getStatuses();
            return $statuses[$value] ?? $value;
        }

        if (isset($modelMap[$key])) {
            $modelClass = $modelMap[$key];
            $cacheKey = "{$modelClass}_{$value}";

            if (!isset($cache[$cacheKey])) {
                try {
                    $record = $modelClass::find($value);
                    $cache[$cacheKey] = $record ? ($record->name ?? $record->title ?? $record->id) : "Unknown ({$value})";
                } catch (\Exception $e) {
                    $cache[$cacheKey] = $value;
                }
            }

            return $cache[$cacheKey];
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    public static function amountInWords($number)
    {
        $no = floor($number);
        $decimal = round($number - $no, 2) * 100;

        $digits_length = strlen($no);
        $i = 0;
        $str = [];
        $words = [
            0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
            5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
            14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen',
            17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
            20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty',
            50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
            80 => 'Eighty', 90 => 'Ninety'
        ];

        $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];

        while ($i < $digits_length) {
            $divider = ($i == 2) ? 10 : 100;
            $number_part = $no % $divider;
            $no = floor($no / $divider);
            $i += ($divider == 10) ? 1 : 2;

            if ($number_part) {
                $plural = (($counter = count($str)) && $number_part > 9) ? '' : '';
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : '';

                if ($number_part < 21) {
                    $str[] = $words[$number_part] . " " . $digits[$counter] . $plural . " " . $hundred;
                } else {
                    $str[] = $words[floor($number_part / 10) * 10] 
                        . " " . $words[$number_part % 10] 
                        . " " . $digits[$counter] . $plural . " " . $hundred;
                }
            } else {
                $str[] = null;
            }
        }

        $rupees = implode('', array_reverse($str));
        $paise = ($decimal) ? "And " . $words[$decimal / 10] . " " . $words[$decimal % 10] . " Paise" : '';

        return trim($rupees) . " Rupees " . $paise . " Only";
    }    
}
