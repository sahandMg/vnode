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
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Cookie: session=MTY4MTg4NTA3NnxEdi1CQkFFQ180SUFBUkFCRUFBQVpmLUNBQUVHYzNSeWFXNW5EQXdBQ2t4UFIwbE9YMVZUUlZJWWVDMTFhUzlrWVhSaFltRnpaUzl0YjJSbGJDNVZjMlZ5XzRNREFRRUVWWE5sY2dIX2hBQUJBd0VDU1dRQkJBQUJDRlZ6WlhKdVlXMWxBUXdBQVFoUVlYTnpkMjl5WkFFTUFBQUFHUC1FRlFFQ0FRUnliMjkwQVFwVGN6UTBOalEwT0RNeEFBPT18NfrDNr3fDKGSucgUHuB6XnrO8TD0MzCW_bfUi0DMvF8='
            ),
        ));
        $response = curl_exec($curl);
        info('x-ui response: ');
        info(json_encode($response));
        curl_close($curl);

    }
}