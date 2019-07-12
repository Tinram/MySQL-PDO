
# MySQL PDO (MySQLi)


#### PHP MySQLi prepared statement helper class.


## Purpose

Reduce the amount of MySQLi prepared statement boilerplate code needed in a legacy MySQL website conversion.


## Aims

+ Reduce repetitive inline code.
+ Bind prepared statement parameters reasonably easily.
+ Allow SQL queries of varying complexity with varying numbers of bound parameters.
+ Support the MySQL CRUD statements - INSERT, SELECT, UPDATE, DELETE.
+ Override the requirement for bound parameters in SELECT queries which have no variable inputs.
+ Capture some erroneous calls before MySQL or PHP start complaining.
+ Able to use different database connections in separate queries.


## Conversion Example

### Legacy Code

```php
    $q = "SELECT template FROM placements WHERE placementID = $pid"; // unsanitized $pid

    if (mysql_query($q))
    {
        if (mysql_num_rows($q) > 0)
        {
            $template = mysql_result($q, 0, 'template');
        }
    }
```

### Conversion

```php
    $q = 'SELECT template FROM placements WHERE placementID = ?';    // placeholder for bound variable
    $r = Query::select($conn, $q, [ $pid ], false);                  // bind variable(s) in array

    if ($r['numrows'] > 0)
    {
        $template = $r['results']['template'];
    }
```


## CRUD Examples

**Set-up**

```php
    require('Query.class.php');

    $host = 'localhost'; $db = 'accounts'; $un = 'test'; $pw = 'password';

    $conn = new mysqli($host, $un, $pw, $db);
    $conn->set_charset('utf-8');
    if ($conn->connect_errno) {die('conn failed: ' . $conn->connect_errno . ') ' . $conn->connect_error);}
```


### SELECT

```php
    $q = 'SELECT name, email FROM users WHERE userID = ?';
    $aR = Query::select($conn, $q, [ $user_id ], false);
        /* 'false' used to return single result row, as in query intention; default is 'true' returning multiple rows from a suitable query */
    var_dump($aR);

    /* no parameters */
    $q = 'SELECT name, email FROM users';
    $aR = Query::select($conn, $q, null, true, false);

    if ($aR['results'])
    {
        foreach ($aR['results'] as $aRow)
        {
            echo $aRow['name'] . ' | ' . $aRow['email'];
            ...
```

        Array
        (
            [results] => Array
            (
                [0] => Array
                (
                    [name] => ...
                    [email] => ...
                )

                [1] => Array
                (
                    [name] => ...
                    [email] => ...
                )

                ...
            )

            [numrows] => 7
        )


### INSERT

```php
    $aI = Query::insert($conn, 'INSERT INTO users (name, email) VALUES (?, ?)', [ $name, $email ]);
```

        Array
        (
            [insert] => true          # insert succeeded
            [numinserts] => 1         # 1 insert
            [insertid] => 101         # lastInsertId()
            [error] => null           # no errors
        )


### UPDATE

```php
    $aU = Query::update($conn, 'UPDATE users SET email = ? WHERE name = ?', [ $email, $name ]);
        /* parameter names can be anything providing SQL and array definitions match */

    if ($aU['update'])
    {
        ...
```


### DELETE

```php
    $aD = Query::delete($conn, 'DELETE FROM messages WHERE messageID = ?', [ 3 ]);
        /* literal value bound instead of a variable */
```

        Array
        (
            [delete] => true
            [numdeletes] => 2
            [error] => null
        )


### LIKE

```php
    $q = 'SELECT * FROM users WHERE name LIKE ?';
    $like = 'jon' . '%';
    $binds = [ $like ];

    $aR = Query::select($conn, $q, $binds);
```


### TRANSACTION

```php
    $conn->begin_transaction();
    $aI = Query::insert($conn, 'INSERT INTO users (name, email) VALUES (?, ?)', [ $name, $email ]);
    $aI2 = Query::insert($conn, 'INSERT INTO messages (messageID, message) VALUES (?, ?)', [ $aI['insertid'], $message ]);
    $conn->commit();

    if ($aI['numinserts'] === 0 || $aI2['numinserts'] === 0)
    {
        $conn->rollback();
    }
```


**Close**

```php
    $conn->close();
```


## License

MySQL PDO (MySQLi) is released under the [GPL v.3](https://www.gnu.org/licenses/gpl-3.0.html).
