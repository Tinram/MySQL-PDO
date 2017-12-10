
# MySQL PDO


#### PHP MySQL-PDO prepared statement helper class.


## Purpose

Reduce the amount of PDO prepared statement boilerplate code needed in a legacy MySQL website conversion.


## Aims

+ Reduce repetitive inline code.
+ Bind prepared statement parameters reasonably easily, with PDO data-type constants handled automatically.
+ Allow SQL queries of varying complexity with varying numbers of bound parameters.
+ Support the MySQL CRUD statements - INSERT, SELECT, UPDATE, DELETE.
+ Override the requirement for bound parameters in SELECT queries.
+ Capture some erroneous calls before MySQL or PHP start complaining.
+ Pass different database connections in separate queries.


## Examples

**Set-up**

        require('Query.class.php');

        $host = 'localhost'; $db = 'accounts'; $un = 'test'; $pw = 'password';
        try {
            $conn = new PDO("mysql:host={$host};dbname={$db};charset=utf8", $un, $pw);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch (PDOException $e) {die($e->getMessage());}


### SELECT

        $q = 'SELECT name, email FROM users WHERE uid = :uid';
        $aR = Query::select($conn, $q, [ ':uid' => $user_id ], false);
            /* 'false' used to return single result row, as in query intention; default is 'true' returning multiple rows from a suitable query */

        if ($aR['results'])
        {
            foreach ($aR as $aRow)
            {
                echo $aRow['name'];
                ...


        /* no parameters */
        $q = 'SELECT name, email FROM users';
        $aR = Query::select($conn, $q, null, true, false);

        Array
        (
            [results] => Array
            (
                [0] => Array
                (
                    [id] => 1
                    [name] => ...
                    [email] => ...
                )

                [1] => Array
                (
                    [id] => 2
                    [name] => ...
                    [email] => ...
                )

                ...
            )

            [numrows] => 7
        )


### INSERT

        $aI = Query::insert($conn, 'INSERT INTO users VALUES (name, email) VALUES (:name, :email)', [ ':name' => $name, ':email' => $email ]);

        Array
        (
            [insert] => true          # update succeeded
            [numinserts] => 1         # 1 insert
            [insertid] => 101         # lastInsertId()
            [error] => null           # no errors
        )


### UPDATE

        $aU = Query::update($conn, 'UPDATE users SET email = :e WHERE name = :n', [ ':e' => $email, ':n' => $name ]);
           /* parameter names can be anything providing SQL and array definitions match */

        if ($aU['update'])
        {
            ...


### DELETE

        $aD = Query::delete($conn, 'DELETE FROM messages WHERE source = :s', [ ':s' => 3 ]);
           /* use a literal value to bind instead of a variable */

        Array
        (
            [delete] => true
            [numdeletes] => 2
            [error] => null
        )


### LIKES

        $q = 'SELECT * FROM users WHERE name LIKE :name';
        $like = 'jon' . '%';
        $binds = [':name' => $like];

        $aR = Query::select($conn, $q, $binds);


**Close**

        $conn = null;


## License

MySQL PDO is released under the [GPL v.3](https://www.gnu.org/licenses/gpl-3.0.html).
