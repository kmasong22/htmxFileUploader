<?php 
  header( 'content-type:application/json' ) ;
  include_once( __DIR__ . "/class.htmxFileUploader.php" ) ;
  include_once( __DIR__ . "/class.DQX.php" ) ;
  
  if( isset( $_POST['file_import'] ) ) :

    $file = HTMXFileUploader::processUpload( $_FILES['file_upload'] ) ;
    $fr = json_decode( $file , true ) ;
    if( $fr['code'] == 200 ) :
      print_r( "<div class='alert alert-info'><strong>Success!</strong> Uploaded successfully {$fr['doc_id']}</div>" ) ;
      $fp = "<a target='_blank' href='./upload_processor.php?do=view&id={$fr['doc_id']}'>{$fr['doc_name']}</a>";
      print_r("<script>document.getElementById('htmxUploadLabel_{$_POST['uiid']}').innerHTML=`View File`;document.getElementById('htmxUploadField_{$_POST['uiid']}').innerHTML=`{$fp}`</script>");
    elseif( $fr['code'] == 413 ) :
      print_r( "<div class='alert alert-danger'><strong>Error!</strong> File is too large</div>" ) ;
    elseif( $fr['code'] == 422 ) :
      print_r( "<div class='alert alert-danger'><strong>Error!</strong> Cannot process this content</div>" ) ;
    else :
      print_r( "<div class='alert alert-danger'><strong>Error!</strong> Internal server error</div>" ) ;
    endif;

  elseif( isset( $_GET['do'] ) ):

    if( $_GET['do'] == 'view' AND isset( $_GET['id'] ) ) :
      $doc_id = $_GET['id'] ;
      $doc = HTMXFileUploader::viewFile( $doc_id ) ;
      if( count( $doc ) > 0 ) :
        header("content-type:{$doc['doc_mime_type']};") ;
        header("Content-Disposition:inline; filename={$doc['doc_filename']}") ;
        print_r( $doc['doc_content'] ) ;
      else :
        header("content-type:application/json;") ;
        print_r( json_encode( [ "code"=>404, "msg"=>"Not Found" ] ) ) ;
      endif ;

    elseif( $_GET['do'] == 'uploadForm' ) :

      print_r( HTMXFileUploader::htmxBuildForm() ) ;

    elseif( $_GET['do'] == 'closeForm' ) :
  
        print_r( null ) ;
    
    elseif( $_GET['do'] == 'test' ) :
  
        print_r( "TEST" ) ;

    endif;

  endif;

