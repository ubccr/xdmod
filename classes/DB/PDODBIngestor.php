<?php
/*
 * @author: Amin Ghadersohi 7/1/2010
 * @deprecated in favor of PDODBMultiIngestor
 */

class PDODBIngestor implements Ingestor
{ 
	protected $_destination_db = null;
	protected $_source_db = null;
	
	protected $_source_query = null;
	protected $_dest_insert_statement = null;
	
	protected $_post_ingest_update_statements;
	
	function __construct($dest_db, $source_db, $source_query, $dest_insert_statement, $post_ingest_update_statements = array())
	{
		$this->_destination_db =  $dest_db;
		$this->_source_db = $source_db;
		
		$this->_source_query = $source_query;
		$this->_dest_insert_statement = $dest_insert_statement;	 	 
		
		$this->_post_ingest_update_statements = $post_ingest_update_statements;
	}
	
	function __destruct()
	{
	}
	
	public function ingest()
	{	 
	    $time_start = microtime(true);
		$destStatementPrepared = $this->_destination_db->handle()->prepare($this->_dest_insert_statement);	
		
		
		$sourceRows = 0;
		$countRowsAffected = 0;
		
		$message = get_class($this).': Querying...';
		$message_length = strlen($message);
		print($message);
		
		$srcStatement = $this->_source_db->handle()->prepare($this->_source_query);
		$srcStatement->execute();
		
		print(str_repeat(chr(8),$message_length));	
		
		$rowsTotal =  $srcStatement->rowCount();

		
		while($srcRow = $srcStatement->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT))
		{
			try
			{
				$destStatementPrepared->execute($srcRow);
			}catch(PDOException $e)
			{
				echo 'Caught exception: ', $e->getMessage(), "\n";
				print_r($srcRow);
				echo $this->_dest_insert_statement, "\n";
				
			}
			if($destStatementPrepared->rowCount() > 0 ) 
			{
				$countRowsAffected++;
			}
			$sourceRows++;
			
			if($sourceRows % 1000 == 0)
			{
				$message = get_class($this).': Rows Processed: '.$sourceRows.' of '.$rowsTotal. ', Fields Affected: '.$countRowsAffected ;
				$message_length = strlen($message);
				print($message);
				print(str_repeat(chr(8),$message_length));
			}

		}
		
		foreach ($this->_post_ingest_update_statements as $updateStatement)
		{
			try
			{
				$this->_destination_db->handle()->prepare($updateStatement)->execute();	
			}catch(PDOException $e)
			{
				echo 'Caught exception: ', $e->getMessage(), "\n";
				print_r($updateStatement);
			}
		}
		
		$time_end = microtime(true);
		$time = $time_end - $time_start;

		print(get_class($this).': Rows Processed: '.$sourceRows.' of '.$rowsTotal. ', Rows Affected: '.$countRowsAffected." (Time Taken: ".number_format($time,2)." s)\n");


	}
	
}

?>