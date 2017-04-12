# DBObject.php

## Introduction
   The intent of this class is to provide an easy way for child classes, which
   are meant to represent the data contained within one row of a table,
   an easy way of interacting with a PDO result set in which the rows have been
   returned as arrays. In particular, this allows the knowledge of what is
   expected / contained in these tables / classes to be defined at particular
   point in time (i.e. git commit ) as opposed to spread throughout the code
   utilizing these objects. It also allows the utilizing code to interact with
   the class and its associated properties / functions as opposed to a simple
   array.
  
   On a more technical note, it provides dynamic 'getter' and 'setter'
   support for calls that follow the form 'getCamelCasePropertyName()' and
   'setCamelCasePropertyName($propertyName)' the property name is assumed to be
   in the form: lcfirst(CamelCase(column_name)) => columnName.
  
   And for those who enjoy working with their classes in an array type manner.
   ArrayAccess has been implemented such that 'offsetGet' corresponds to
   'getCamelCasePropertyName()', 'offsetSet' corresponds to
   'setCamelCasePropertyName($propertyName)' and 'offsetExists($offset)'
   ensures that the '$offset' is defined in the $PROP_MAP and that there
   is a property currently defined with a name that that matches '$offset';
   
## Examples
### User Class
```php
/**
 * 
 * @method string getUserName()
 * @method void   setUserName($userName)
 * @method string getEmailAddress()
 * @method void   setEmailAddress($emailAddress)
 **/
class User extends DBObject
{
    protected $PROP_MAP = array(
        'username' => 'username', 
        'email_address' => 'emailAddress'
    );
}
```
### User Class Usage
```php
/**
 * Attempt to retrieve a listing of all users who currently have a record in the
 * Users table.
 *
 * @return User[]
 **/
public function getUsers()
{
    $rows = $pdo->query("SELECT * FROM Users");
    if (false !== $rows) {
        $results = array();
        foreach($rows as $row) {
            // NOTE: If there are other columns besides username and 'email_address' 
            // they will not be set in the resultant User Object. In this way 
            // the $PROP_MAP acts as a white list.
            $results []= new User($row);
        }
        return $results;
    }
    return array();
}
```
