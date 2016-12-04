<?php

declare(strict_types = 1);

function xrange(int $a, int $b) {
    for ($i = $a; $i <= $b; $i++) {
        yield $i;
    }
}

function conn() {
    static $conn;

    if (!$conn) {
        $conn = new PDO('mysql:host=localhost;dbname=perftest', 'test', 'test');
    }

    return $conn;
}

function setup(PDO $conn) {
    $conn->exec('DROP TABLE IF EXISTS testdata');

    $conn->exec("CREATE TABLE `testdata` (
      `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
      `ws_master` TINYINT(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0,
      `document` mediumtext COLLATE utf8_unicode_ci NOT NULL COMMENT '(DC2Type:json_array)',
      PRIMARY KEY (`title`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
}

function populate(PDO $conn, int $count) {
    $chunk = 20;
    $query = 'INSERT INTO testdata (title, ws_master, document) VALUES ';
    $query .= implode(', ', array_fill(0, $chunk, '(?, ?, ?)'));
    $stmt = $conn->prepare($query);
    $membership = counter();

    foreach (xrange(1, (int)ceil($count/$chunk)) as $i) {
        $args = [];
        foreach (xrange(1, $chunk) as $j) {
            $args = array_merge($args, ["item-$i-$j", $membership->current(), "data-$i-$j"]);
            $membership->next();
        }
        $stmt->execute($args);
    }
}

function counter() {
    while (1) {
        yield 1;
        yield 0;
    }
}

function modify(PDO $conn) {
    $conn->exec('ALTER TABLE testdata ADD `ws_cubs` TINYINT(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 0');
}


function replicate(PDO $conn) {
    $conn->exec('UPDATE testdata SET ws_cubs = ws_master');
}

function selective_replicate(PDO $conn) {
    $conn->exec('UPDATE testdata SET ws_cubs = ws_master WHERE ws_master = 1');
}

function timer(string $label, callable $func) {
    $start = $end = 0;
    $start = microtime(true);
    $func();
    $end = microtime(true);

    $microseconds = ($end-$start)*1000;

    printf("%s: %f microseconds\n", $label, $microseconds);
}

// Pre-create the connection to remove the connection time from the result.
conn();

timer('Setup', function() {
    setup(conn());
});

function testrun(int $count) {
    timer('Populate', function() use ($count) {
        populate(conn(), $count);
    });

    timer('Modify', function() {
        modify(conn());
    });

    timer('Replicate', function() {
        replicate(conn());
    });

    timer('Selective Replicate', function() {
        selective_replicate(conn());
    });
}

$runs = [5, 10, 50, 100, 1000, 10000, 100000, 1000000];

foreach ($runs as $count) {
    print "-----$count records-----\n";
    testrun($count);
}


