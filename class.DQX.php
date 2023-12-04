<?php
// DQX Plugin
// Version 0.1.11

class DQX{

	public $sqlConn = null;
	public $sqlSelect = null;
	public $sqlFrom = null;
	public $sqlQuery = null;
	public $sqlWhere = null;
	public $sqlSort = null;
	public $sqlGroup = null;
	public $sqlHaving = null;
	public $sqlLimit = null;
	public $sqlJoin = null;
	public $sqlDeleteFrom = null;
	public $sqlUpdate = null;
	public $sqlInsertInto = null;
	public $sqlSetKeys = null;
	public $sqlSetValues = null;
	public $sqlSet = null;
	public $sqlSetMulti = null;
	public $sqlUnfiltered = null;
	public $buildQuery = null;
	public $buildQueryCount = null;

	public function __construct( $connInfo = [] ) {
		$this->dbname = isset( $connInfo['dbname'] ) ? $connInfo['dbname'] : 'master';
		$this->dbhost = isset( $connInfo['host'] ) ? $connInfo['host'] : 'localhost';
		$this->dbuser = isset( $connInfo['username'] ) ? $connInfo['username'] : 'root';
		$this->dbpass = isset( $connInfo['password'] ) ? $connInfo['password'] : 'root' ;
		$this->dbsql = isset( $connInfo['sql'] ) ? ( in_array( $connInfo['sql'] , ['pgsql','mssql','mysql','sqlite'] ) ? $connInfo['sql'] : 'mysql' ) : 'mysql' ;
		
		if( $this->dbsql == 'pgsql' ) :
			$this->sqlConn = new PDO( "pgsql:host={$this->dbhost};dbname={$this->dbname}" , $this->dbuser , $this->dbpass ) ;
		elseif( $this->dbsql == 'mssql' ) :
			$this->sqlConn = new PDO("odbc: Driver={SQL Server};Server={$this->dbhost};Database={$this->dbname};Uid={$this->dbuser};Pwd={$this->dbpass};") ;
		elseif( $this->dbsql == 'mysql' ) :
			$this->sqlConn = new PDO( "mysql:host={$this->dbhost};dbname={$this->dbname}" , $this->dbuser , $this->dbpass );
		elseif( $this->dbsql == 'sqlite' ) :
				$this->sqlConn = new PDO( "sqlite:".$this->dbhost );
		endif ;
		return $this;
	}

	public function sqlSelect($qry = "*"){
		$this->sqlSelect = $qry ;
		return $this;
	}
	
	public function sqlFrom($qry){
		$this->sqlFrom = $qry;
		return $this;
	}

	public function sqlJoin($qry){
		$this->sqlJoin .= " {$qry}";
		return $this;
	}

	public function sqlWhere($qry){
		$this->sqlWhere .= " {$qry}";
		return $this;
	}

	public function sqlQuery($qry){
		$this->sqlQuery = $qry;
		return $this;
	}

	public function sqlSort($qry){
		$this->sqlSort .= $this->sqlSort == null ? " {$qry}" : ", {$qry}";
		return $this;
	}

	public function sqlGroup($qry){
		$this->sqlGroup .= $this->sqlGroup == null ? " {$qry}" : ", {$qry}";
		return $this;
	}

	public function sqlHaving($qry){
		$this->sqlHaving .= $this->sqlHaving == null ? " {$qry}" : ", {$qry}";
		return $this;
	}

	public function sqlDeleteFrom($qry){
		$this->sqlDeleteFrom = $qry;
		return $this;
	}

	public function sqlInsertInto($qry){
		$this->sqlInsertInto = $qry;
		return $this;
	}

	public function sqlUpdate($qry){
		$this->sqlUpdate = $qry;
		return $this;
	}

	public function sqlSet($qry){
		foreach($qry as $keyx=>$value) :
			$key = substr( strtolower( $keyx ) , 0 , 3) == '[q]' ? substr( $keyx , 3 ) : $keyx ;
			if( $this->sqlUpdate == null ) :
				$this->sqlSetKeys .= ( $this->sqlSetKeys == null ? '' : ',' ) . $key ;
				if( substr( strtolower( $keyx ) , 0 , 3) == '[q]' ) :
					$this->sqlSetValues .= ( $this->sqlSetValues == null ? '' : ',' ) . "{$value}" ;	
				else :
					$this->sqlSetValues .= ( $this->sqlSetValues == null ? '' : ',' ) . "'{$value}'" ;					
				endif ;
			else :
				$value = substr( strtolower( $keyx ) , 0 , 3) != '[q]' ? "'{$value}'" : "{$value}" ;
				$this->sqlSet .= ( $this->sqlSet == null ? '' : ',' ) . "{$key} = {$value}" ;
			endif ;
		endforeach ;
		return $this;
	}

	public function sqlSetMulti($qry){
		foreach($qry as $sets) :
			$this->sqlSetKeys = null;
			$this->sqlSetValues = null;
				foreach($sets as $keyx=>$value) :
					$key = substr( strtolower( $keyx ) , 0 , 3) == '[q]' ? substr( $keyx , 3 ) : $keyx ;
					$this->sqlSetKeys .= ( $this->sqlSetKeys == null ? '' : ', ' ) . $key ;
					if( substr( strtolower( $keyx ) , 0 , 3) == '[q]' ) :
						$this->sqlSetValues .= ( $this->sqlSetValues == null ? '' : ',' ) . "{$value}" ;	
					else :
						$this->sqlSetValues .= ( $this->sqlSetValues == null ? '' : ',' ) . "'{$value}'" ;					
					endif ;
				endforeach ;
			$this->sqlSetMulti .= "INSERT INTO {$this->sqlInsertInto} ( {$this->sqlSetKeys} ) VALUES( {$this->sqlSetValues} );";
		endforeach ;
		return $this;
	}

	public function sqlLimit( $limit , $offset = null ){

		if( !is_null( $offset ) OR !is_null( $limit ) ) :
			$offset = ( !is_null($offset) AND is_numeric($offset) ) ? $offset : 0 ;
			if( $this->dbsql == 'mssql' ) :
				$this->sqlLimit .= "OFFSET {$offset} ROWS" . ( ( !is_null($limit) AND is_numeric($limit) ) ? " FETCH NEXT {$limit} ROWS ONLY" : null );
			else:
				$this->sqlLimit .= ( ( !is_null($limit) AND is_numeric($limit) ) ? "LIMIT {$limit} " : null ) . "OFFSET {$offset}";					
			endif ;
		endif ;
		return $this;

	}

	public function sqlUnfiltered(){

		$this->sqlUnfiltered = true ;
		return $this;

	}

	public function sqlGetTables(){
			$sqlGetTables = $this->sqlConn ;
			$sqlGetTables = $sqlGetTables->prepare( "SHOW TABLES" );
			$sqlGetTables->execute();
			$sqlGetTablesOut = $sqlGetTables->fetchAll(PDO::FETCH_COLUMN);
			return $sqlGetTablesOut ;
	}

	public function sqlGetColumns( $sqlTbl ){
			$sqlGetColumnsOut = [] ;

			if( $this->dbsql == 'mssql' ) :
				$sqlGetColumnsStmt = "SELECT TOP 1 * FROM {$sqlTbl}" ;
			else :
				$sqlGetColumnsStmt = "SELECT * FROM {$sqlTbl} LIMIT 1" ;
			endif ;

			$sqlGetColumns = $this->sqlConn ;
			$sqlGetColumns = $sqlGetColumns->prepare( $sqlGetColumnsStmt );
			$sqlGetColumns->execute();
			$sqlGetColumns = $sqlGetColumns->fetchAll(PDO::FETCH_ASSOC);

			if( isset( $sqlGetColumns[0] ) ) :
				foreach( $sqlGetColumns[0] as $skk=>$skv ) :
					$sqlGetColumnsOut[] = $skk ;
				endforeach ;
			else :
				$sqlGetColumnsOut = ["Invalid Table"] ;
			endif ;

			return $sqlGetColumnsOut ;

	}

	public function sqlPrepare(){
		$this->sqlWhere = $this->sqlWhere == null ? null : "WHERE ( 1=1 " . $this->sqlWhere . " )";
		$this->sqlSort = $this->sqlSort == null ? null : "ORDER BY " . $this->sqlSort ;
		$this->sqlGroup = $this->sqlGroup == null ? null : "GROUP BY " . $this->sqlGroup ;
		$this->sqlHaving = $this->sqlHaving == null ? null : "HAVING " . $this->sqlHaving ;
		$this->sqlLimit = ( !is_null($this->sqlLimit) AND ( $this->sqlSort == null AND $this->dbsql == 'mssql' ) ) ? 'ORDER BY 1 ASC ' . $this->sqlLimit : $this->sqlLimit ;

		if( $this->sqlQuery != null ) :
			$buildQuery = $this->sqlQuery ;
		elseif( $this->sqlDeleteFrom != null ) :
			$buildQuery = "DELETE FROM {$this->sqlDeleteFrom} {$this->sqlWhere}";
		elseif( $this->sqlUpdate != null && $this->sqlWhere != null && $this->sqlSet != null ) :
			$buildQuery = "UPDATE {$this->sqlUpdate} SET {$this->sqlSet} {$this->sqlWhere}";
		elseif( $this->sqlInsertInto != null && $this->sqlSetMulti != null ) :
			$buildQuery = "{$this->sqlSetMulti}";
		elseif( $this->sqlInsertInto != null ) :
			$buildQuery = "INSERT INTO {$this->sqlInsertInto} ({$this->sqlSetKeys}) VALUES ({$this->sqlSetValues})";
		else :
			$buildQuery = "SELECT {$this->sqlSelect} FROM {$this->sqlFrom} {$this->sqlJoin} {$this->sqlWhere} {$this->sqlGroup} {$this->sqlHaving} {$this->sqlSort} {$this->sqlLimit}" ;
			$buildQueryCount = "SELECT COUNT(1) AS COUNT FROM {$this->sqlFrom} {$this->sqlJoin} {$this->sqlWhere} {$this->sqlGroup} {$this->sqlHaving}" ;
		endif ;
		$this->buildQuery = $buildQuery ;
		if( isset( $buildQueryCount ) ) :
			$this->buildQueryCount = $buildQueryCount ;
		endif;
		return $this ;
	}

	public function sqlCommit( $exec = null ){
		$response = [] ;

		try{
			$buildQuery = $this->buildQuery ;
			$buildQueryCount = $this->buildQueryCount ;
			$buildQuery = preg_replace('/\s+/', ' ', $buildQuery);
			if( is_null( $exec ) ) :
				$sqlStmt = $this->sqlConn ;
				$sqlStmt = $sqlStmt->prepare( $buildQuery );
				$sqlStmt->execute();
				$sqlRows = $sqlStmt->fetchAll(PDO::FETCH_ASSOC);

				if( isset( $this->sqlInsertInto ) ) :
					$sqlLastId = $this->sqlConn->lastInsertId();
				else :
					$sqlLastId = null ;
				endif ;

				if( $this->sqlUnfiltered ) :
					$buildQueryCount = preg_replace('/\s+/', ' ', $buildQueryCount);
					$sqlStmtCount = $this->sqlConn ;
					$sqlStmtCount = $sqlStmtCount->prepare( $buildQueryCount );
					$sqlStmtCount->execute();
					$sqlRowsCount = $sqlStmtCount->fetchAll(PDO::FETCH_ASSOC);
				endif ;

				if(	is_array( $sqlRows ) ) :

					if( count( $sqlRows ) > 0) :
						foreach( $sqlRows[0] AS $sklKey=>$sklValue ) :
							$sqlCols[] = $sklKey ;
						endforeach ;
						$response['unfiltered'] = $this->sqlUnfiltered ? (int)$sqlRowsCount[0]['COUNT'] : count( $sqlRows ) ;
						$response['filtered'] = count( $sqlRows ) ;
						$response['data'] = $sqlRows ;
						$response['cols'] = $sqlCols ;
						$response['sql'] = $buildQuery ;
						$response['last_id'] = $sqlLastId ;
					else :
						$response['unfiltered'] = $this->sqlUnfiltered ? (int)$sqlRowsCount[0]['COUNT'] : 0 ;
						$response['filtered'] = 0 ;
						$response['data'] = [] ;
						$response['cols'] = [] ;
						$response['sql'] = $buildQuery ;
						$response['last_id'] = $sqlLastId ;
					endif ;
				else:
					$response['data'] = 'OK' ;
					$response['sql'] = $buildQuery ;
					$response['last_id'] = $sqlLastId ;
				endif ;
			else:
				$response['sql'] = $buildQuery ;
			endif;
		}catch(\Throwable $th){

			$response['error'] = $th->getMessage() ;
			$response['sql'] = $buildQuery ;

		}

		return $response ;
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

}