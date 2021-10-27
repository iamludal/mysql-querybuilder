# 🔧 MySQL QueryBuilder

[![PHPUnit](https://github.com/iamludal/mysql-querybuilder/actions/workflows/run-tests.yml/badge.svg)](https://github.com/iamludal/mysql-querybuilder/actions/workflows/run-tests.yml)
![Version](https://img.shields.io/github/v/tag/iamludal/PHP-QueryBuilder?label=version)
![PHP Version](https://img.shields.io/packagist/php-v/ludal/mysql-querybuilder?color=blueviolet)
![License](https://img.shields.io/packagist/l/ludal/mysql-querybuilder?color=orange)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/8ab804e60c38445a8e184c264c06cd45)](https://www.codacy.com/manual/iamludal/PHP-QueryBuilder?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=iamludal/PHP-QueryBuilder&amp;utm_campaign=Badge_Grade)

```
composer require ludal/mysql-querybuilder
```

## ℹ️ Presentation

This is a PHP query builder for simple MySQL queries. It allows you to write
them without using strings nor heredoc, which often breaks the code's
cleanliness.

This package is designed to support MySQL DBMS. However, it may work with other
DBMS (PostgreSQL, SQLite...), **but I cannot guarantee that, so use it at your
own risk**.

> 💡 Made with ❤️ in 🇫🇷


## 📘 Usage

### Getting Started

First, initialize a new instance of `QueryBuilder`.

```php
$builder = new QueryBuilder();
```

> 💡 You can also pass a PDO instance as a parameter to execute and fetch
> queries directly.
> ```php
> $pdo = new PDO($dsn, $login, $password);
> $builder = new QueryBuilder($pdo);
> ```

From this instance, you can now build your query:

```php
$select = $builder
  ->select()
  ->from('users')
  ->where('name = :name');

$update = $builder
  ->update('users')
  ->set(['name' => 'John'])
  ->where('id = 6');
```

Then, you can either:
- Convert your query into a SQL string : `toSQL()`
- Bind parameters: `setParam('name', 'John')`, `setParams(['name' => 'John'])`...
- Execute the query : `execute()`, `execute(['John'])`...
- Fetch the results of your query : `fetch()`, `fetchAll()`, `fetch(PDO::FETCH_COLUMN)`...
- Get the rowCount : `rowCount()`
- Get the PDO statement corresponding to your query : `getStatement()`
- And more: see docs for a full reference

```php
$select->toSQL(); // returns 'SELECT * FROM users'

$select->fetchAll(); // returns the rows fetched from the db

$select->getStatement(); // get the PDO statement, useful for handling errors

$update->execute(); // executes the UPDATE query
```


### Supported Statements

- [x] `SELECT`
- [x] `UPDATE`
- [x] `DELETE FROM`
- [x] `INSERT INTO`


### Supported Clauses

- [x] `WHERE`
- [x] `GROUP BY`
- [x] `ORDER BY`
- [x] `LIMIT`
- [x] `OFFSET`


### Code Samples

> ⚠️ For clarity reasons, these examples will use the same instance of
> `QueryBuilder`. However, it is HIGHLY recommended that you create a new
> one for each of your requests in order to prevent unexpected behaviours.

```php
$pdo = new PDO(...);
$qb = new QueryBuilder($pdo);

QueryBuilder::setDefaultFetchMode(PDO::FETCH_ASSOC);

// SELECT
$res = $qb
  ->select()
  ->from('users')
  ->where('id < 4', 'name = :name')
  ->orWhere('age < 12')
  ->orderBy('id', 'desc')
  ->limit(2)
  ->offset(1)
  ->fetchAll();

// INSERT
$insert = $qb
  ->insertInto('articles')
  ->values(['title' => 'Lorem ipsum', 'content' => 'Some content'])
  ->getStatement(); 

$insert->execute();
$insert->errorCode(); // or any other PDOStatement method

// UPDATE
$updated = $qb
  ->update('connections')
  ->set(['exp' => true, 'date' => date('Y-m-d')])
  ->where(['token' => $token])
  ->orderBy('date')
  ->limit(1)
  ->execute();

// DELETE
$rowCount = $qb
  ->deleteFrom('users')
  ->where('id > 5')
  ->orWhere('name = :name')
  ->orderBy('id', 'desc')
  ->limit(10)
  ->setParam(':name', 'John')
  ->rowCount(); // will execute, and return the rowCount
```


## 📖 Docs

[Wiki](https://github.com/iamludal/PHP-QueryBuilder/wiki) under construction. 🚧


## 🙏 Acknowledgements

- [Vincent Aranega](https://github.com/aranega) for tips and tricks about the
code organization
