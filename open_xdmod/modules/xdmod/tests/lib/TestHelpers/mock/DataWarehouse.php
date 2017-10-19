<?php

class DataWarehouse
{
    public static $mockDatabaseImplementation = null;

    public static function connect()
    {
        return DataWarehouse::$mockDatabaseImplementation;
    }
}