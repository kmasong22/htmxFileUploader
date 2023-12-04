<?php
/* 
  * HTMX File Uploader v 0.0.1
  * This Library is dependent on the DQX Library
*/
define( 'HTMX_FILE_UPLOAD_PATH' , defined('HTMX_FILE_UPLOAD_PATH') ? HTMX_FILE_UPLOAD_PATH : __DIR__ . '\uploads' ) ;
define( 'HTMX_FILE_UPLOAD_SERVER' , defined('HTMX_FILE_UPLOAD_SERVER') ? HTMX_FILE_UPLOAD_SERVER : 'mysql' ) ;
define( 'HTMX_FILE_UPLOAD_HOST' , defined('HTMX_FILE_UPLOAD_HOST') ? HTMX_FILE_UPLOAD_HOST : 'localhost' ) ;
define( 'HTMX_FILE_UPLOAD_USER' , defined('HTMX_FILE_UPLOAD_USER') ? HTMX_FILE_UPLOAD_USER : 'root' ) ;
define( 'HTMX_FILE_UPLOAD_PASS' , defined('HTMX_FILE_UPLOAD_PASS') ? HTMX_FILE_UPLOAD_PASS : 'root' ) ;
define( 'HTMX_FILE_UPLOAD_DB' , defined('HTMX_FILE_UPLOAD_DB') ? HTMX_FILE_UPLOAD_DB : 'test_db' ) ;
define( 'HTMX_FILE_UPLOAD_TBL' , defined('HTMX_FILE_UPLOAD_TBL') ? HTMX_FILE_UPLOAD_TBL : 'tb_documents' ) ;

define( 'HTMX_FILE_UPLOAD_CONN_STR' ,  
        [ "sql"=>HTMX_FILE_UPLOAD_SERVER ,  
          "host"=>HTMX_FILE_UPLOAD_HOST , 
          "dbname"=>HTMX_FILE_UPLOAD_DB ,
          "username"=>HTMX_FILE_UPLOAD_USER , 
          "password"=>HTMX_FILE_UPLOAD_PASS 
        ] 
      ) ;

class HTMXFileUploader{

  public static function htmxUploadModal( $b = null , $h = null , $f = null , $p = './upload_processor.php' ){
    $h = is_null( $h ) ? null : "<div class='header'>{$h}</div>" ;
    $f = is_null( $f ) ? null : "<div class='footer'>{$f}</div>" ;
    return <<<EOT
    <div id='htmxFileUpload' class='htmxFileUploadModal'>
      <div class='content'>
        <span class='close' hx-get='{$p}?do=hideForm' hx-target='#htmxUploadDiv' hx-swap='innerHTML'>&times;</span>
        {$h}<div class='body'>{$b}</div>{$f}
      </div>
    </div>
    EOT;
  }

  public static function htmxBuildForm( $p = './upload_processor.php' ){
    $header = '<h3>Upload File</h3>';
    $body = "<form class='form modal-content' method='POST' hx-post='{$p}' hx-encoding='multipart/form-data' >
        <div class='form-group'>
          <input type='hidden' name='uiid' value='{$_GET['iid']}'>
          <label for='file_upload'>Select File</label>
          <input type='file' name='file_upload' id='file_upload' class='form-control mt-2' required='true'>
        </div>
        <div class='form-group mt-3'>
          <span class='btn btn-danger' hx-get='{$p}?do=hideForm' hx-target='#htmxUploadDiv' hx-swap='innerHTML'>Close</span>
          <button class='btn btn-primary' type='submit' name='file_import'>Upload</button>
        </div>
      </form>" ;
    return self::htmxUploadModal( $body , $header , $footer = null , $p ) ;
  }

  public static function htmxUploadBtn( $id = null , $r = false , $f = './upload_processor.php' ){
    $iid = bin2hex( random_bytes(5) ) ;
    if( is_null( $id ) ) :
      $lbl = [ 'Choose File' , 'No file uploaded' ] ;
    else:
      $lbl = [ 'View File' , self::goToFile( $id , $f ) ] ;
    endif;
    $fu = $r ? null : "hx-get='{$f}?do=uploadForm&iid={$iid}' hx-target='#htmxUploadDiv' hx-swap='innerHTML'";
    return "<div class='htmxUploadButton'><span id='htmxUploadLabel_{$iid}' {$fu}> {$lbl[0]} </span><span id='htmxUploadField_{$iid}'> {$lbl[1]}</span></div><div id='htmxUploadDiv'></div>";
  }

  public static function processUpload( $f , $p = [] ){
  //Variable setting
    $path = isset( $p['path'] ) ? $p['path'] : HTMX_FILE_UPLOAD_PATH ;
    $tbl = isset( $p['tbl'] ) ? $p['tbl'] : HTMX_FILE_UPLOAD_TBL ;
    $mimes = isset( $p['mimes'] ) ? $p['mimes'] : null ;
    $size = isset( $p['size'] ) ? $p['size'] : 2048000 ;
    $owner = isset( $p['owner'] ) ? $p['owner'] : 1 ;
  //Processing the file
    $fileInfo = pathinfo( $f['name'] ) ;
    if( isset( $fileInfo['extension'] ) AND isset( $fileInfo['filename'] ) ) :
      $mimeType = mime_content_type( $f['tmp_name'] ) ;
      $randomStr = bin2hex( random_bytes(5) ) ;
      $fileName = str_ireplace( ' ' , '_' , $fileInfo['filename'] ) . '_' . $randomStr . '.' . $fileInfo['extension'] ;
      $targetPath = $path . "\\" . $fileName ;
      $fileSize = filesize( $f['tmp_name'] ) ;
      if( $fileSize <= $size AND ( is_null( $mimes ) OR ( !is_null( $mimes ) AND in_array( $mimeType , $mimes ) ) ) ) :
        $doc_uid = strtoupper( self::getUID() ) ;
        $payload = [ "doc_id"=>$doc_uid ,
                      "doc_name"=>$fileInfo['filename'] ,
                      "doc_extension"=>$fileInfo['extension'] ,
                      "doc_type"=>$mimeType ,
                      "doc_location"=>$fileName ,
                      "doc_owner"=>$owner ,
                      "[q]doc_uuid"=>'UUID()'
                  ] ;
        move_uploaded_file( $f['tmp_name'] , $targetPath ) ;
        $writeToDB = self::writeToDB( [ 'payload' => $payload] ) ;
        $response = [ "code"=>200 , "msg"=>"OK" , "doc_id"=>$doc_uid , "doc_name"=>$fileInfo['basename'] , "doc_target"=>$fileName , "doc_type"=>$mimeType ] ;
      else :
        if( $fileSize > $size ) : 
          $response = [ "code"=>413 , "msg"=>"Content Too Large" ] ;
        elseif( !is_null( $mimes ) AND !in_array( $mimeType , $mimes ) ) :
          $response = [ "code"=>422 , "msg"=>"Unprocessable Entity" ] ;
        endif ;
      endif ;
    endif ;
    $response = !isset( $response ) ? [ "code"=>500 , "msg"=>"Internal Server Error" ] : $response ;
    return json_encode( $response ) ;
  }

  private static function getFileInfo( $id = '' ){
    $c = new DQX( HTMX_FILE_UPLOAD_CONN_STR ) ;
    $d = $c->sqlSelect()->sqlFrom( HTMX_FILE_UPLOAD_TBL )->sqlWhere("AND doc_id IN ( '{$id}' )")->sqlLimit(1)->sqlPrepare()->sqlCommit() ;
    return isset( $d['data'][0] ) ? $d['data'][0] : [] ;
  }

  private static function writeToDB( $p = [] ){
    $c = new DQX( HTMX_FILE_UPLOAD_CONN_STR ) ;
    if( isset( $p['payload'] ) AND isset( $p['id'] ) AND isset( $p['token'] ) ) :
      $d = $c->sqlUpdate( HTMX_FILE_UPLOAD_TBL )->sqlSet( $p['payload'] )->sqlWhere(" AND doc_id IN ( '{$p['id']}' ) ")->sqlWhere(" AND doc_uuid IN ( '{$p['token']}' ) ")->sqlPrepare()->sqlCommit();
    elseif( isset( $p['payload'] ) ) :
      $d = $c->sqlInsertInto( HTMX_FILE_UPLOAD_TBL )->sqlSet( $p['payload'] )->sqlPrepare()->sqlCommit();
    endif ;
    return isset( $d['data'][0] ) ? $d['data'][0] : [] ;
  }

  public static function goToFile( $id = '' , $b = './upload_processor.php' ){
    $f = self::getFileInfo( $id ) ;
    if( count( $f ) > 0 ) :
      return "<a target='_blank' href='{$b}?do=view&id={$f['doc_id']}'>{$f['doc_name']}.{$f['doc_extension']}</a>" ;
    else:
      return "File does not exist";
    endif;
  }

  public static function deleteFile( $id = '' , $token = '' ){
    $f = self::getFileInfo( $id ) ;
    if( count( $f ) > 0 ) :
      return false ;
    else :
      return [] ;
    endif;
  }

  public static function viewFile( $id = '' , $p = HTMX_FILE_UPLOAD_PATH ){
    $f = self::getFileInfo( $id ) ;
    if( count( $f ) > 0 ) :
      $doc['doc_token'] = $f['doc_uuid'] ;
      $doc['doc_mime_type'] = $f['doc_type'] ;
      $doc['doc_filename'] = $f['doc_name'] . '.' . $f['doc_extension'] ;
      $doc['doc_content'] = file_get_contents( $p . '\\' . $f['doc_location'] ) ;
      return $doc ;
    else :
      return [] ;
    endif;
  }

	public static function getUID( $_Array = [5,5,6,'-'] , $_upper = 1 ){
		$_Separator = is_numeric( $_Array[count($_Array) - 1] ) ? '' : $_Array[count($_Array) - 1] ;
		$_ArrLength = is_numeric( $_Array[count($_Array) - 1] ) ? count($_Array) - 2 : count($_Array) - 1 ;
		$_Output = null ;
		for($i = 0 ; $i < $_ArrLength ; $i++ ) :
				$_Output .= ( $_Output == null ? '' : $_Separator ) . bin2hex( random_bytes( ( $_Array[$i] - ( $_Array[$i] % 2 ) ) / 2 ) ) ;
		endfor ;
		return $_upper == 1 ? strtoupper( $_Output ) : $_Output ;
	}

  public static function createSchema( $tbl = 'tb_documents' ){
    $sql = "CREATE TABLE IF NOT EXISTS {$tbl} (
      id int(11) NOT NULL AUTO_INCREMENT,
      doc_id varchar(32) NOT NULL,
      doc_name varchar(255) NOT NULL,
      doc_extension varchar(32) NOT NULL,
      doc_basename varchar(255) NOT NULL,
      doc_type varchar(255) NOT NULL,
      doc_location varchar(255) NOT NULL,
      doc_upload datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      doc_owner int(11) NOT NULL,
      doc_status varchar(10) NOT NULL DEFAULT 'E',
      doc_updated datetime DEFAULT NULL,
      doc_updater int(4) DEFAULT NULL,
      doc_uuid varchar(100) NOT NULL,
      PRIMARY KEY (id)
    )";
    $c = new DQX( HTMX_FILE_UPLOAD_CONN_STR ) ;
    $d = $c->sqlQuery($sql)->sqlPrepare()->sqlCommit();
  }


}