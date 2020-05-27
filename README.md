# PHP QueryBuilder 🔧

# Presentation ℹ️

This is a PHP query builder for SQL queries.


# Usage 📝

First, initialize a new instance of the QueryBuilder class.

```php
$builder = new QueryBuilder();
```

> Note that you can pass a PDO instance as a parameter to execute queries directly
> ```php
> $pdo = new PDO($dsn, $login, $password);
> $builder = new QueryBuilder($pdo);
> ```

Then, you can either convert your query into a SQL string, or execute/fetch it directly

```php
$query = $builder
  ->select()
  ->from('users')
  ->toSQL();
// $query = "SELECT * FROM users"

$results = $builder
  ->select()
  ->from('users')
  ->fetchAll();
// Return the rows fetched from the db
```

> You can specify the fetch type as you would specify it to `PDO`
> ```php
> $builder
>   ->select()
>   ->from('users')
>   ->fetchAll(PDO::FETCH_CLASS, 'ClassName')
> ```

## 1. `SELECT` query

Simple queries
```php
$builder
  ->select() // default -> '*'
  ->from('users')

$builder
  ->select('name', 'age')
  ->from('users');
  
$builder
  ->select(['name', 'age'])
  ->from('users');
```

Complex query
```php
$builder
 ->select('name')
 ->from('users', 'u') // specify an alias for the table
 ->where('id < :id', 'age > 18') // where conditions are joined with 'AND'
 ->orWhere('name = "George"')
 ->orderBy('age', 'asc') // 'asc' or 'desc', default: 'asc'
 ->limit(5)
 ->offset(2)
 ->setParam(':id', 5, PDO::PARAM_INT) // or let the class guess the corresponding PDO type by omitting the last parameter
 ->fetchAll(PDO::FETCH_ASSOC);
```

## 2. `INSERT` query

## 3. `UPDATE` query

## 4. `DELETE` query








