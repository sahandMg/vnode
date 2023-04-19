<?php


namespace App\Services;


class Http
{
    public static function sendHttp($url , $data = [])
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Cookie: session=MTY4MTg4OTEyN3xEdi1CQkFFQ180SUFBUkFCRUFBQVpmLUNBQUVHYzNSeWFXNW5EQXdBQ2t4UFIwbE9YMVZUUlZJWWVDMTFhUzlrWVhSaFltRnpaUzl0YjJSbGJDNVZjMlZ5XzRNREFRRUVWWE5sY2dIX2hBQUJBd0VDU1dRQkJBQUJDRlZ6WlhKdVlXMWxBUXdBQVFoUVlYTnpkMjl5WkFFTUFBQUFHUC1FRlFFQ0FRUnliMjkwQVFwVGN6UTBOalEwT0RNeEFBPT18clXeuoTQSVlUtuNdVMz6tSeZWGALtdn29DDau6IxayU=',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
        ));
        $response = curl_exec($curl);
        info('x-ui response: ');
        info(json_encode($response));
        curl_close($curl);
    }
}