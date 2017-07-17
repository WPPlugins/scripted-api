<?php

function scripted_create_current_jobs_callback()
{
    wp_enqueue_style('thickbox');
    wp_enqueue_script('thickbox');    
    
    $ID               = get_option( '_scripted_ID' );
    $accessToken      = get_option( '_scripted_auccess_tokent' );
    $paged            = (isset($_GET['paged']) and $_GET['paged'] !='') ? sanitize_text_field($_GET['paged']) : '';
    $per_page         = 15;    
    $validate = validateApiKey($ID,$accessToken);    
    $out = '<div class="wrap">
            <div class="icon32" style="width:100px;padding-top:5px;" id="icon-scripted"><img src="'.SCRIPTED_LOGO.'"></div><h2>Jobs</h2>';
    
    $filter = (!isset($_GET['filter'])) ? 'all' : sanitize_text_field($_GET['filter']);
    $jobUrl = ($filter !='all') ? 'jobs/'.$filter : 'jobs/';
    
    if($validate) {
        $url = ($paged != '') ? $jobUrl.'?next_cursor='.$paged : $jobUrl;
        $result = curlRequest($url);
        $allJobs = $result->data; 
        
        $next = (isset($result->paging->has_next) and $result->paging->has_next == 1) ? $result->paging->next_cursor : '';
        $totalProjects  = $result->total_count;
        $totalPages     = ceil($totalProjects/$per_page);
        
        // paggination
        
        $paggination = '';
        
         $pageOne = '';         
         if($paged == '' and $result->paging->has_next != 1)
             $pageOne = ' one-page';     
         
         $paggination .='<div class="tablenav">
             <div class="alignleft actions bulkactions">
                <select class="filter-jobs" name="action">
                    <option '.selected('all',$filter,false).' value="">All</option>
                    <option '.selected('accepted',$filter,false).' value="accepted">Accepted</option>
                    <option '.selected('finished',$filter,false).' value="finished">Finished</option>
					<option '.selected('screening',$filter,false).' value="screening">Screening</option>
					<option '.selected('writing',$filter,false).' value="writing">Writing</option>
					<option '.selected('draft_ready',$filter,false).' value="draft_ready">Draft Ready</option>
					<option '.selected('revising',$filter,false).' value="revising">Revising</option>
					<option '.selected('final_ready',$filter,false).' value="final_ready">Final Ready</option>
					<option '.selected('in_progress',$filter,false).' value="in_progress">In Progress</option>
					<option '.selected('needs_review',$filter,false).' value="needs_review">Needs Review</option>
                </select>
            </div>
            <div class="tablenav-pages'.$pageOne.'">';
         
                $paggination .='';
                $nextPage = '';
                if($result->paging->has_next != 1) 
                    $nextPage = 'disabled';
                
                $paggination .='<span class="pagination-links"> 
                            <span class="displaying-num">'.$totalProjects.' items</span>
                            <a href="admin.php?page=scripted_current_jobs&paged='.$next.'&filter='.$filter.'" title="Go to the next page" class="next-page '.$nextPage.'">&rsaquo;</a>';
         
                   $paggination .='</span>
             </div>
            <br class="clear">
            </div>';
        // paggination end
                   
        $out .= $paggination;
        
        $out .='<table cellspacing="0" class="wp-list-table widefat sTable">
                    <thead>
                        <tr>
                        <th scope="col" width="50%"><span>Topic</span></th>
                        <th scope="col" width="10%"><span>State</span></th>
                        <th scope="col" width="15%"><span>Deadline</span></th>
                        <th scope="col" width="23%"></th>
                        </tr>
                    </thead>
                      <tbody>';
        
        if($allJobs)  {           
            $i = 1;
            foreach($allJobs as $job) {
                $out .='<tr valign="top" class="scripted type-page status-publish hentry alternate">
                    <input type="hidden" id="project_'.$i.'" value="'.$job->id.'">
                    <td>'.$job->topic.'</td>
                    <td>'.ucfirst($job->state).'</td>
                    <td>'.date('F j', strtotime($job->deadline_at)).'</td>';
                
                    $out .='<td>';
                    if ($job->state == 'accepted') {
                        $out .= '<a id="create_'.$job->id.'" href="javascript:void(0)"  onclick="finishedProjectActions(\''.$job->id.'\',\'Create\')">Create Draft</a> | ';
                        $out .= '<a id="post_'.$job->id.'" href="javascript:void(0)"  onclick="finishedProjectActions(\''.$job->id.'\',\'Post\')">Create Post</a> | ';
                        $out .= '<a href="'.admin_url('admin-ajax.php').'?action=scripted_poject_finished&do=view_project&project_id='.$job->id.'&secure='.wp_create_nonce('view_project').'&amp;type=page&amp;TB_iframe=1&amp;width=850&amp;height=500" class="thickbox" title="'.strip_tags(substr($job->topic,0,50)).'">View</a>';
                    }
                    $out .='</td>';
                    $out .='</tr>';
                    $i++;
            }
            
        } else {
            $out .='<tr valign="top">
                    <th colspan="5"  style="text-align:center;" class="check-column"><strong>Your Scripted account has no jobs... yet!</strong></td>
                    </tr>';
        }
        
         $out .= '</tbody>
                </table>'; // end table
        
       $out .= $paggination;
         
         
    }
    
    $out .='</div>';// end of wrap div
    
    echo $out;
}
function createScriptedProject($proId,$ID,$accessToken,$type = 'draft')
{
    global $current_user;
    $userID = $current_user->ID;
    
    $_projectJob = curlRequest('jobs/'.$proId);
    $_projectContent = curlRequest('jobs/'.$proId.'/html_contents');
    if($_projectContent->id == $proId and !empty($_projectJob)) {
        $content = $_projectContent->html_contents;
        if(is_array($content)) {
            $content = $content[0];
        }
        $success_message = 'Your draft was created!';
        $post['post_title']     = wp_strip_all_tags($_projectJob->topic);
        if($type == 'draft')
            $post['post_status']    = 'draft';
        elseif($type == 'publish') {
            $post['post_status']    = 'publish';
            $success_message = 'Your post was published!';
        }
        $post['post_author']    = $userID;
        $post['post_type']      = 'post';
        $post['post_content']   = $content;
        $post['post_content']  .= '<p style="font-style:italic; font-size: 10px;">Powered by <a href="https://www.scripted.com" alt="Scripted.com content marketing automation">Scripted.com</a></p>';
        $post_id                = wp_insert_post($post ,true); // draft created
        echo $post_id;
    } else {
        echo 'Failed';
    }
        
   
}
function createProjectAjax()
{
    ?>
    <script>
       function finishedProjectActions(proId,actions) {
           if(actions == 'Accept')
                jQuery("#accept_"+proId).html('Accepting...'); 
           else if(actions == 'Reject')
                jQuery("#reject_"+proId).html('Rejecting...'); 
           else if(actions == 'Create')
                jQuery("#create_"+proId).html('Creating...'); 
           else if(actions == 'Post')
                jQuery("#post_"+proId).html('Creating Post...'); 
                
            jQuery.ajax({
                    type: 'POST',
                    url: '<?php echo wp_nonce_url( admin_url('admin-ajax.php'), 'create_reject_accept' );?>&do='+actions+'&project_id='+proId+'&action=scripted_poject_finished',
                    data: '',
                    success: function(data) {                            
                        if(actions == 'Accept')
                            jQuery("#accept_"+proId).html(data); 
                       else if(actions == 'Reject')
                            jQuery("#reject_"+proId).html(data); 
                       else if(actions == 'Create') {
                            jQuery("#create_"+proId).html('Create Draft'); 
                            window.open('<?php echo admin_url('post.php?action=edit') ?>&post='+data);                            
                       } else if(actions == 'Post') {
                           jQuery("#post_"+proId).html('Create Post'); 
                            window.open('<?php echo admin_url('post.php?action=edit') ?>&post='+data);                            
                        }
                    }
                });
       }
       function doSorting() {
           var sortDo =  jQuery("#actions").val(); 
           if(sortDo == '')
                document.location.href='<?php echo admin_url();?>/admin.php?page=scripted_create_jobs<?php echo (isset($_GET['paged']) and $_GET['paged'] !='') ? '&paged'.$_GET['paged'] : ''?>';
           else
               document.location.href='<?php echo admin_url();?>/admin.php?page=scripted_create_jobs<?php echo (isset($_GET['paged']) and $_GET['paged'] !='') ? '&paged='.$_GET['paged'] : ''?>&sort='+sortDo;
       }
       function completeActionRefresh() {
           window.location.reload();
       }
       jQuery( document ).ready(function() {
        jQuery('.filter-jobs').change(function() {
            var filter = jQuery(this).val();
            document.location.href = '<?php echo admin_url('admin.php?page=scripted_jobs');?>&filter='+filter
        });
       });
    </script>
        <?php
}
add_action('wp_ajax_scripted_poject_finished', 'scriptedPojectFinished');
function scriptedPojectFinished() {
    $do             = (isset($_GET['do']) and $_GET['do'] !='') ? sanitize_text_field($_GET['do']) : '';
    $project_id     = (isset($_GET['project_id']) and $_GET['project_id'] !='') ? sanitize_text_field($_GET['project_id']) : '';
    
    $ID               = get_option( '_scripted_ID' );
    $accessToken      = get_option( '_scripted_auccess_tokent' );
    $validate               = validateApiKey($ID,$accessToken);
    
    $scriptedBaseUrl        = 'https://app.scripted.com/';
    
    if(!$validate or $project_id == '' or $do == '') 
        die('Failed');
    
    if(wp_verify_nonce($_GET['secure'],'view_project') and $do == 'view_project') {
        $_projectContent = curlRequest('jobs/'.$project_id.'/html_contents');
        
        if($_projectContent->id == $project_id) {
            $content = $_projectContent->html_contents;
            if(is_array($content)) {
                $content = $content[0];
            }            
            echo $content;
        }
    }elseif(wp_verify_nonce($_GET['_wpnonce'],'create_reject_accept') and $do == 'Accept') {
        $_projectAction = curlRequest('jobs/'.$project_id.'/accept',true);
        if($_projectAction)
            echo 'Accepted';
        else
            echo 'Failed';
    }elseif(wp_verify_nonce($_GET['_wpnonce'],'create_reject_accept') and $do == 'Reject') {
        $_projectAction = curlRequest('jobs/'.$project_id.'/reject',true);     
        if($_projectAction)
            echo 'Accepted';
        else
            echo 'Failed';
    }elseif(wp_verify_nonce($_GET['_wpnonce'],'create_reject_accept') and $do == 'Create') {
        createScriptedProject($project_id,$ID,$accessToken);
    }elseif(wp_verify_nonce($_GET['_wpnonce'],'create_reject_accept') and $do == 'Post') {
        createScriptedProject($project_id,$ID,$accessToken,'publish');
    }
    die();
}