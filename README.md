
# MySQL PDO


#### PHP MySQL-PDO prepared statement helper class.


## Purpose

Provide concise and consistent usage of MySQL PDO CRUD statements.


## Background

Created to reduce the amount of PDO prepared statement boilerplate code needed in a legacy MySQL website conversion.


## Aims

+ Reduce repetitive inline code.
+ Bind prepared statement parameters reasonably easily, with PDO data-type constants handled automatically.
+ Allow SQL queries of varying complexity with varying numbers of bound parameters.
+ Support the MySQL CRUD statements &ndash; INSERT, SELECT, UPDATE, DELETE.
+ Override the requirement for bound parameters in SELECT queries which have no variable inputs.
+ Capture some erroneous calls before MySQL or PHP start complaining.
+ Ability to use different database connections in separate queries.


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
    $q = 'SELECT template FROM placements WHERE placementID = :pid'; // placeholder for bound variable
    $r = Query::select($conn, $q, [ ':pid' => $pid ], false);        // bind variable(s) within array

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

    try
    {
        $conn = new PDO("mysql:host={$host};dbname={$db};charset=utf8", $un, $pw);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch (PDOException $e)
    {
        die($e->getMessage());
    }
```


### SELECT

```php
    $q = 'SELECT name, email FROM users WHERE userID = :uid';
    $aR = Query::select($conn, $q, [ ':uid' => $user_id ], false);
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
    $aI = Query::insert($conn, 'INSERT INTO users (name, email) VALUES (:name, :email)', [ ':name' => $name, ':email' => $email ]);
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
    $aU = Query::update($conn, 'UPDATE users SET email = :e WHERE name = :n', [ ':e' => $email, ':n' => $name ]);
        /* parameter names can be anything providing SQL and array definitions match */

    if ($aU['update'])
    {
        ...
```


### DELETE

```php
    $aD = Query::delete($conn, 'DELETE FROM messages WHERE messageID = :id', [ ':id' => 3 ]);
        /* literal value used instead of a variable */
```

        Array
        (
            [delete] => true
            [numdeletes] => 2
            [error] => null
        )


### LIKE

```php
    $q = 'SELECT * FROM users WHERE name LIKE :name';
    $like = 'jon' . '%';
    $binds = [ ':name' => $like ];

    $aR = Query::select($conn, $q, $binds);
```


### TRANSACTION

```php
    try
    {
        $conn->beginTransaction();
        $aI = Query::insert($conn, 'INSERT INTO users (name, email) VALUES (:name, :email)', [ ':name' => $name, ':email' => $email ]);
        $aI2 = Query::insert($conn, 'INSERT INTO messages (messageID, message) VALUES (:id, :message)', [ ':id' => $aI['insertid'], ':message' => $message ]);
        $conn->commit();
    }
    catch (PDOException $e)
    {
        echo $e->getMessage();
        $conn->rollback();
    }
```


**Close**

```php
    $conn = null;
```


## License

MySQL PDO is released under the [GPL v.3](https://www.gnu.org/licenses/gpl-3.0.html).
