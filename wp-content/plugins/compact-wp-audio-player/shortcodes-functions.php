<?php

add_shortcode('sc_embed_player', 'sc_embed_player_handler');
add_shortcode('sc_embed_player_template1', 'sc_embed_player_templater1_handler');

if (!is_admin()) {
    add_filter('widget_text', 'do_shortcode');
}
add_filter('the_excerpt', 'do_shortcode', 11);

function scap_sanitize_text( $text ) {
	$text = htmlspecialchars( $text );
	$text = strip_tags( $text );
	$text = sanitize_text_field( $text );
	$text = esc_attr( $text );
	return $text;
}

function sc_embed_player_handler($atts, $content = null) {
    extract(shortcode_atts(array(
        'fileurl' => '',
        'autoplay' => '',
        'volume' => '',
        'class' => '',
        'loops' => '',
    ), $atts));
    
    $volume = absint($volume)==0 ? 80 : absint($volume);    
    $loops = $loops === 'true' ? 'true' : 'false';
    
    //Check if URL is empty
    if (empty($fileurl)) {
        return '<div style="color:red;font-weight:bold;">Compact Audio Player Error! You must enter the mp3 file URL via the "fileurl" parameter. Please check the documentation and correct the mistake.</div>';
    }

    //Check if URL validation is disabled
    $disable_url_validation = get_option('sc_audio_disable_url_validation');
    //Trigger a filter here to allow customization of the URL validation.
    $disable_url_validation = apply_filters('sc_audio_disable_url_validation', $disable_url_validation);
    if ($disable_url_validation == '1') {
        //URL validation is disabled, then we will not validate the URL.
        
    } else {
        //Validate the file URL using our custom function.
        $fileurl = scap_validate_url($fileurl);
        if( is_wp_error($fileurl) ) {
            //The fileurl validation failed.
            $error_msg = is_object($fileurl) ? $fileurl->get_error_message() : "Error: the fileurl validation failed.";
            return '<div style="color:red;font-weight:bold;">'.$error_msg.'</div>';
        }
        //If the URL is valid, then we will sanitize it.
        $fileurl = esc_url($fileurl);
    }
    
    //Default volume
    if (empty($volume)) {
        $volume = '80';
    }
    
    //Set default container class
    if (empty($class)) {
        $class = "sc_player_container1";
    }
    
    //Default loop value
    if (empty($loops)) {
        $loops = "false";
    }
    $ids = uniqid('', true);//uniqid();

    $player_cont = '<div class="compact_audio_player_wrapper">';//Add a wrapper so it works with the block themes.
    $player_cont .= '<div class="' . esc_attr($class) . '">';
    $player_cont .= '<input type="button" id="btnplay_' . $ids . '" class="myButton_play" onClick="play_mp3(\'play\',\'' . $ids . '\',\'' . esc_url($fileurl) . '\',\'' . esc_attr($volume) . '\',\'' . esc_attr($loops) . '\');show_hide(\'play\',\'' . $ids . '\');" />';
    $player_cont .= '<input type="button"  id="btnstop_' . $ids . '" style="display:none" class="myButton_stop" onClick="play_mp3(\'stop\',\'' . $ids . '\',\'\',\'' . esc_attr($volume) . '\',\'' . esc_attr($loops) . '\');show_hide(\'stop\',\'' . $ids . '\');" />';
    $player_cont .= '<div id="sm2-container"><!-- flash movie ends up here --></div>';
    $player_cont .= '</div>';
    $player_cont .= '</div>';//end of .compact_audio_player_wrapper

    if (!empty($autoplay)) {
        $path_to_swf = SC_AUDIO_BASE_URL . 'swf/soundmanager2.swf';
        ob_start();
        ?>
        <script type="text/javascript" charset="utf-8">
        soundManager.setup({
            url: "<?php echo esc_url($path_to_swf); ?>",
            onready: function() {
                var mySound = soundManager.createSound({
                    id: "btnplay_<?php echo esc_attr($ids); ?>",
                    volume: "<?php echo esc_attr($volume); ?>",
                    url: "<?php echo esc_url($fileurl); ?>"
                });
                var auto_loop = "<?php echo $loops; ?>";
                mySound.play({
                    onfinish: function() {
                        if (auto_loop == "true") {
                            loopSound("btnplay_<?php echo esc_attr($ids); ?>");
                        } else {
                            document.getElementById("btnplay_<?php echo esc_attr($ids); ?>").style.display = "inline";
                            document.getElementById("btnstop_<?php echo esc_attr($ids); ?>").style.display = "none";
                        }
                    }
                });
                document.getElementById("btnplay_<?php echo esc_attr($ids); ?>").style.display = "none";
                document.getElementById("btnstop_<?php echo esc_attr($ids); ?>").style.display = "inline";
            },
            ontimeout: function() {
                // SM2 could not start. Missing SWF? Flash blocked? Show an error.
                alert("Error! Audio player failed to load.");
            }
        });
        </script>
        <?php
        $player_cont .= ob_get_clean();
    }//End autopay code

    return $player_cont;
}

function sc_embed_player_templater1_handler($atts){
    extract(shortcode_atts(array(
        'fileurl' => '',
        'autoplay' => '',
        'volume' => '',
        'class' => '',
        'loops' => '',
                    ), $atts));
    if (empty($fileurl)) {
        return '<div style="color:red;font-weight:bold;">Compact Audio Player Error! You must enter the mp3 file URL via the "fileurl" parameter in this shortcode. Please check the documentation and correct the mistake.</div>';
    }

    if (empty($class)) {
        $class = "sc_fancy_player_container";//Set default container class
    }

    if (empty($autoplay)) {//Set autoplay value
        $autoplay = "";
    }else{
        $autoplay = "on";
    }

    if (empty($loops)) {//Set the loops value
        $loops = "";
    }else{
        $loops = "on";
    }

    $args = array(
        'src'      => $fileurl,
        'loop'     => $loops,
        'autoplay' => $autoplay,
        'preload'  => 'none'
    );

    $player_container = "";
    $player_container .= '<div class="'.esc_attr($class).'">';
    $player_container .= wp_audio_shortcode($args);
    $player_container .= '</div>';
    return $player_container;
}
