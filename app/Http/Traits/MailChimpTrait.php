<?php

namespace App\Http\Traits;

/**
 * Created by PhpStorm.
 * User: Randall Bondoc
 * Date: 09/08/2020
 * Time: 10:18 PM
 * Class SystemSettingTrait
 * @package App\Http\Traits
 */
trait MailChimpTrait
{
    /**
     * @param $data
     *
     * @return mixed
     */
    public function request($data)
    {
        $ch = curl_init($data['url']);
        curl_setopt($ch, CURLOPT_USERPWD, 'user:' . env('MAILCHIMP_API_KEY'));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $data['request_type']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data['data']));

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function parseError($response)
    {
        $errors = [];
        foreach ($response->errors as $error) {
            $errors[] = $error->message;
        }
        return $errors;
    }

    public function generateURL($segment = '')
    {
        $endpoint = 'https://us1.api.mailchimp.com/3.0/';
        list(, $dc) = explode('-', env('MAILCHIMP_API_KEY'));
        $endpoint = str_replace('us1', $dc, $endpoint) . $segment;
        return $endpoint;
    }

}