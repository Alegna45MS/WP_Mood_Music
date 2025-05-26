<?php


function contains_non_latin_char($string) {
    // This regular expression checks for the presence of any character that is not a Latin letter, digit, or basic punctuation.
    return preg_match('/[^A-Za-z0-9 .,?!;:\'\"-]/', $string) > 0;
}

function scap_verify_file_url_accessible( $file_url ){
        //Uses wp_safe_remote_get() to check if the file exists and the response code is 200.
        $remote_get_args = array(
                'method'      => 'HEAD',
                'timeout'     => 30,
                'redirection' => 5,
                'sslverify'   => false,
        );

        $err_msg = '';
                
        $data = wp_safe_remote_get( $file_url, $remote_get_args );
        
        if ( is_wp_error( $data ) ) {
                $err = $data->get_error_message();
                $err_msg = 'Error occurred when trying to fetch the file using wp_safe_remote_get().' . ' ' . $err ;
                return $err_msg;
        }

        // Check if the file exists and the response code is 200.
        if ( $data['response']['code'] !== 200 ) {
                if ( $data['response']['code'] === 404 ) {
                        $err_msg = "Requested file could not be found (error code 404). Verify the file URL specified in the shortcode.";
                        return $err_msg;
                } else {
                        $err_msg = 'An HTTP error occurred during file retrieval. Error Code: ' . $data['response']['code'];
                        return $err_msg;
                }
        }
        
        return $err_msg;
}

function scap_validate_url($fileurl){
        $protocol = is_ssl() ? 'https:' : 'http:';

        //Checking if url is protocol relative
        if (substr($fileurl, 0, 2) === '//') {
                $fileurl = $protocol . $fileurl;
        }

        //Sanitizing the URL first to remove the JS or invalid / non-latin chars from url.
        $url = filter_var($fileurl, FILTER_SANITIZE_URL);

        //URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
                //Checking if the url is using relative path
                $absolute_path = site_url($fileurl);
                $is_accessible_response = scap_verify_file_url_accessible($absolute_path);

                if (empty($is_accessible_response)) {
                        //The URL is using relative path
                        //Converting it to absolute path
                        return $absolute_path;
                }
                //Return error message.
                return new WP_Error("1001", 'Compact Audio Player Error! The mp3 file URL that you entered in the "fileurl" parameter looks to be invalid. Please enter a valid URL of the audio file.');
        }

        //Lets use our function to verify the file is accessible.
        $is_accessible_response = scap_verify_file_url_accessible($fileurl);

        if ( !empty($is_accessible_response)) {
                //The file is accessible response came back negetive.
                return new WP_Error("1002", $is_accessible_response);
        }
        
        return $fileurl;
}