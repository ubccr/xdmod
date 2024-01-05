<?php
/*
 * @author: Amin Ghadersohi 7/1/2010
 *
 */
class ArraySourceIngestor implements Ingestor
{
	protected $_logger;

	function __construct(protected $_dest_db, protected $_source_data, protected $_dest_insert_statement)
	{
		$this->_logger = Log::singleton('null');
	}

	function __destruct()
	{
	}

	public function ingest(): void
	{
	    $time_start = microtime(true);

		$destStatementPrepared = $this->_dest_db->handle()->prepare($this->_dest_insert_statement);

		$rowsAffected = 0;
		$sourceRows = count($this->_source_data);

		foreach ($this->_source_data as $srcRow)
		{
			try
			{
				$destStatementPrepared->execute($srcRow);
			}
			catch(PDOException $e)
			{
				echo 'Caught exception: ', $e->getMessage(), "<br />\n";
				print_r($srcRow);
			}
			$rowsAffected += $destStatementPrepared->rowCount();
		}
		$time_end = microtime(true);
		$time = $time_end - $time_start;

		print(static::class.": Values Affected: $rowsAffected, Source Rows: $sourceRows  (Time Taken: ".number_format($time,2)." s) <br />\n");

	}

    public function setLogger(\Psr\Log\LoggerInterface $logger): void
    {
        $this->_logger = $logger;
    }

}
