
# MySQL PDO


#### Simple PHP MySQL-PDO prepared statement helper class.


## Purpose

Created to reduce the amount of PDO prepared statement boilerplate code needed in a legacy MySQL website conversion.


## Intentions

+ Reduce repetitive inline code.
+ Bind prepared statement parameters reasonably easily, with PDO data-type constants handled automatically.
+ Flexibly support both simple and complex SQL statements.
+ Support the MySQL CRUD statements - INSERT, SELECT, UPDATE, DELETE.
+ Allow the use of multiple database connections.


## Examples

        require('Query.class.php');

### SELECT

        $aR = Query::select($conn, 'SELECT name, email FROM users WHERE uid = :uid', [ ':uid' => $user_id ], false);
            /* 'false' used to return single result row, as in query intention; default is 'true' returning multiple rows from a suitable query */

        if ($aR['results'])
        {
            foreach ($aR as $aRow)
            {
                echo $aRow['name'];
                ...

### UPDATE

        $aU = Query::update($conn, 'UPDATE users SET email = :email WHERE name = :name', [ ':email' => $email, ':name' => $name ]);

        if ($aU['update'])
        {
            ...


### *@TODO* ...


## License

MySQL PDO is released under the [GPL v.3](https://www.gnu.org/licenses/gpl-3.0.html).
