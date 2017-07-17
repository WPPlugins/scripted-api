<?php
add_action('admin_menu', 'scripted_settings_menu');
add_action( 'admin_notices', 'scripted_install_warning' );

/**
 * Display an admin-facing warning if the current user hasn't authenticated with scripted yet
 *
 * @since 1.0
 */
function scripted_install_warning() {
	$ID               = get_option( '_scripted_ID' );
        $accessToken      = get_option( '_scripted_auccess_tokent' );

	$page = (isset($_GET['page']) ? $_GET['page'] : null);

	if ((empty($ID)  || empty($accessToken)) && $page != 'scripted_settings_menu' && current_user_can( 'manage_options' ) ) {
		admin_dialog( sprintf( 'You must %sconfigure the plugin%s to enable Scripted for WordPress.', '<a href="admin.php?page=scripted_settings_menu">', '</a>' ), true);
	}
}
function admin_dialog($message, $error = false) {
	if ($error) {
		$class = 'error';
	}
	else {
		$class = 'updated';
	}
	
	echo '<div ' . ( $error ? 'id="scripted_warning" ' : '') . 'class="' . $class . ' fade' . '"><p>'. $message . '</p></div>';
}
function scripted_admin_styles() {
    wp_register_style( 'scripteAdminStyle', plugins_url('admin/scripts/scripted.css', SCRIPTED_FILE_URL) );
    wp_enqueue_style( 'scripteAdminStyle' );
}

function scripted_settings_menu() {
   add_menu_page('Scripted Settings', 'Scripted.com', 'add_users','scripted_settings_menu', 'scripted_settings_menu_function', SCRIPTED_ICON, 83);
   
    $ID               = get_option( '_scripted_ID' );
    $accessToken      = get_option( '_scripted_auccess_tokent' );
    
    if($ID != '' and $accessToken !='') {
	//$createAJobPage = add_submenu_page( 'scripted_settings_menu', 'Create a Job', 'Create a Job', 'manage_options', 'scripted_create_a_job', 'scripted_create_a_job_callback' ); 
        //add_action( 'admin_footer-'. $createAJobPage, 'getFormFields' );
        $currentJobPage = add_submenu_page( 'scripted_settings_menu', 'Jobs', 'Jobs', 'manage_options', 'scripted_jobs', 'scripted_create_current_jobs_callback' );
        
        // javascript functions
        add_action( 'admin_footer-'. $currentJobPage, 'createProjectAjax' );
        
        //adding style sheet to admin pages
        //add_action( 'admin_print_styles-' . $createAJobPage, 'scripted_admin_styles' );
        add_action( 'admin_print_styles-' . $currentJobPage, 'scripted_admin_styles' );
    }
}
function scripted_settings_menu_function() {
    
  if(isset($_POST) && wp_verify_nonce($_POST['_wpnonce'],'scriptedFormAuthSettings')) {        
      
        $validate = validateApiKey($_POST['ID_text'],$_POST['success_tokent_text']);
        if($validate) {
            update_option( '_scripted_ID', sanitize_text_field($_POST['ID_text']) );        
            update_option( '_scripted_auccess_tokent', sanitize_text_field($_POST['success_tokent_text'] ));        
            update_option( '_powered_scripted', sanitize_text_field($_POST['powered_scripted'] ));        
        } else {
            echo '<div class="updated" id="message"><p>Sorry, we found an error. Please confirm your Organization Key and Access Token are correct and try again.</p></div>';
        }
    }
   $out = '<div class="wrap">
            <div class="icon32" style="width:100px;padding-top:5px;" id="icon-scripted"><img src="'.SCRIPTED_LOGO.'"></div><h2>Settings</h2>';
   
   $out .='<p>Authentication is required to use your Scripted WordPress plugin.</p>';
   
   $out .='<p>To get your Organization Key and Access Token, please register or log in at Scripted.com and then <a href="https://dashboard.scripted.com/business/account/api" target="_blank">click here</a>. Your private authentication credentials will be available there. Copy and paste them into the settings below!</p>';
            
   $out .='<form action="" method="post" name="scripted_settings">'.wp_nonce_field( 'scriptedFormAuthSettings', '_wpnonce' );
   
   $ID               = get_option( '_scripted_ID' );
   $accessToken      = get_option( '_scripted_auccess_tokent' );
   $powered          = get_option( '_powered_scripted' );
   
   $out .='<table class="form-table">
      <tbody>
        <tr valign="top">
          <th scope="row"><label for="ID_text">Organization Key</label></th>
          <td><input type="text" class="regular-text" value="'.$ID.'" name="ID_text"></td>
        </tr>
        <tr valign="top">
          <th scope="row"><label for="acceess_tokent_text">Access Token</label></th>
          <td><input type="text" class="regular-text" value="'.$accessToken.'" name="success_tokent_text"></td>
        </tr>
        <tr valign="top">
          <th scope="row"><label for="powered_scripted">Include "Powered by Scripted.com" on your posts?</label></th>
          <td><input type="checkbox"  value="1" name="powered_scripted" '.checked(1,$powered).'></td>
        </tr>
     </tbody>
    </table>
    <p class="submit">
      <input type="submit" value="Save Changes" class="button-primary" id="submit" name="submit">
    </p>';
   
   
   $out .='</form>';
   
   $out .='</div>';// end of wrap div
   echo $out;
}
function validateApiKey($ID,$accessToken)
{
    
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken));    
    curl_setopt($ch, CURLOPT_HEADER, 1);    
    curl_setopt($ch, CURLOPT_URL, SCRIPTED_END_POINT.'/'.$ID.'/v1/industries/');     
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $result = curl_exec($ch);     
    curl_close($ch);
    
    if ($result === false) {        
        return false;
    }    
    list( $header, $contents ) = preg_split( '/([\r\n][\r\n])\\1/', $result, 2 );    
    $industries = json_decode($contents);  
   if($contents != '') {
        if(isset($industries->data) and count($industries->data) > 0) {
            return true;
        }
   }
   return false;
}

function curlRequest($type,$post = false,$fields = '') {
    
    $ID               = get_option( '_scripted_ID' );
    $accessToken      = get_option( '_scripted_auccess_tokent' );
    
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken));    
    curl_setopt($ch, CURLOPT_HEADER, 1);    
    curl_setopt($ch, CURLOPT_URL, SCRIPTED_END_POINT.'/'.$ID.'/v1/'.$type);     
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if($post) {
         curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,$fields);
    } else {
        curl_setopt($ch, CURLOPT_POST, 0);
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $result = curl_exec($ch);   
    curl_close($ch);
        
    if ($result === false) {        
        return false;
    }
    
    list( $header, $contents ) = preg_split( '/([\r\n][\r\n])\\1/', $result, 2 ); // extracting
    if($contents != '') {
        $contents = json_decode($contents);    
        if(isset($contents->data) and count($contents->data) > 0) {
            if(isset($contents->total_count))
                return $contents;
            return $contents->data;
        }
    }
    
    return false;
}